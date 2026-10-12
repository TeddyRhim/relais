<?php

namespace App\Billing;

use App\Models\Organization;

/**
 * Frontière avec le fournisseur de paiement. Les tests et le développement utilisent FakeBillingProvider.
 */
interface BillingProvider
{
    /**
     * Démarre un abonnement. Renvoie l'adresse où envoyer l'utilisateur (paiement hébergé),
     * ou null si le plan est appliqué immédiatement. Dans le premier cas, le plan change quand
     * le fournisseur le confirme par webhook (voir BillingWebhookController).
     */
    public function subscribe(Organization $organization, Plan $plan): ?string;

    /**
     * Résilie l'abonnement. Même convention de retour que subscribe().
     */
    public function cancel(Organization $organization): ?string;
}
