<?php

namespace App\Http\Controllers;

use App\Billing\Usage;
use App\Models\Source;
use App\Models\WebhookEvent;
use App\Services\SignatureVerifier;
use App\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngestController extends Controller
{
    /** En-têtes dont la valeur n'est jamais conservée (les signatures restent : elles servent au rejeu). */
    private const SENSITIVE_HEADERS = '/auth|cookie|token|secret|password|api-?key/i';

    public function __invoke(Request $request, string $token, CurrentOrganization $current, SignatureVerifier $verifier, Usage $usage): JsonResponse
    {
        $source = Source::findByToken($token);

        abort_if($source === null, 404);

        $body = $request->getContent();

        abort_if(strlen($body) > config('relais.max_body_bytes'), 413);

        return $current->run($source->organization_id, function () use ($request, $source, $body, $verifier, $usage) {
            if ($usage->isExceeded($source->organization)) {
                return response()->json(['error' => 'quota_exceeded'], 429);
            }

            $status = $verifier->check($body, $source->signing_secret, $request->header($source->signature_header));

            if ($source->require_signature && $status !== WebhookEvent::SIGNATURE_VALID) {
                return response()->json(['error' => 'invalid_signature'], 401);
            }

            $event = WebhookEvent::create([
                'source_id' => $source->id,
                'method' => $request->method(),
                'headers' => $this->storableHeaders($request),
                'body' => $body,
                'content_type' => $request->header('Content-Type'),
                'ip' => $request->ip(),
                'signature_status' => $status,
            ]);

            return response()->json(['id' => $event->id, 'signature' => $status], 202);
        });
    }

    /**
     * @return array<string, list<string|null>>
     */
    private function storableHeaders(Request $request): array
    {
        $headers = $request->headers->all();

        foreach ($headers as $name => $values) {
            if (preg_match(self::SENSITIVE_HEADERS, $name)) {
                $headers[$name] = ['[masqué]'];
            }
        }

        return $headers;
    }
}
