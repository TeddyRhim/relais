<?php

namespace App\Http\Controllers;

use App\Billing\BillingService;
use App\Billing\Plan;
use App\Models\Organization;
use App\Models\WebhookEvent;
use App\Services\SignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Réception des événements du fournisseur de facturation (changement ou résiliation d'abonnement).
 * Authentifié par signature HMAC-SHA256 du corps dans l'en-tête X-Billing-Signature.
 */
class BillingWebhookController extends Controller
{
    private const TOLERANCE_SECONDS = 300;

    public function __invoke(Request $request, SignatureVerifier $verifier, BillingService $billing): JsonResponse
    {
        // Route sans session : une validation qui échoue doit répondre 422 en JSON, pas rediriger.
        $request->headers->set('Accept', 'application/json');

        $secret = config('relais.billing.webhook_secret');

        // Sans secret configuré, aucun webhook n'est accepté : jamais de valeur par défaut.
        abort_if(blank($secret), 503, 'Webhooks de facturation non configurés.');

        $status = $verifier->check($request->getContent(), $secret, $request->header('X-Billing-Signature'));

        abort_unless($status === WebhookEvent::SIGNATURE_VALID, 401);

        $payload = $request->validate([
            'type' => ['required', 'in:subscription.updated,subscription.canceled'],
            'reference' => ['required', 'string', 'max:255'],
            'timestamp' => ['required', 'integer'],
            'plan' => ['required_if:type,subscription.updated', 'in:'.implode(',', array_column(Plan::cases(), 'value'))],
        ]);

        // L'horodatage fait partie du corps signé : un message capturé ne peut être rejoué que 5 minutes.
        abort_if(abs(time() - (int) $payload['timestamp']) > self::TOLERANCE_SECONDS, 401, 'Message trop ancien.');

        $organization = Organization::where('billing_reference', $payload['reference'])->first();

        // Réponse 200 même si la référence est inconnue : le fournisseur ne doit pas réessayer indéfiniment.
        if ($organization) {
            $billing->applyPlan(
                $organization,
                $payload['type'] === 'subscription.canceled' ? Plan::Free : Plan::from($payload['plan']),
            );
        }

        return response()->json(['received' => true]);
    }
}
