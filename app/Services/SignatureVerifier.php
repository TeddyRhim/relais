<?php

namespace App\Services;

use App\Models\WebhookEvent;

/**
 * Signature HMAC-SHA256 du corps brut, au format hexadécimal, avec ou sans le préfixe "sha256=".
 */
class SignatureVerifier
{
    public function sign(string $body, string $secret): string
    {
        return hash_hmac('sha256', $body, $secret);
    }

    /**
     * @return string une des constantes WebhookEvent::SIGNATURE_*
     */
    public function check(string $body, string $secret, ?string $header): string
    {
        if ($header === null || trim($header) === '') {
            return WebhookEvent::SIGNATURE_MISSING;
        }

        $provided = strtolower(trim($header));
        $provided = str_starts_with($provided, 'sha256=') ? substr($provided, 7) : $provided;

        return hash_equals($this->sign($body, $secret), $provided)
            ? WebhookEvent::SIGNATURE_VALID
            : WebhookEvent::SIGNATURE_INVALID;
    }
}
