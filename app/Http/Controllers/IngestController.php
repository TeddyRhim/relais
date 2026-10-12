<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Models\WebhookEvent;
use App\Services\SignatureVerifier;
use App\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngestController extends Controller
{
    /** En-têtes dont la valeur n'est jamais conservée. */
    private const SENSITIVE_HEADERS = ['authorization', 'cookie', 'proxy-authorization'];

    public function __invoke(Request $request, string $token, CurrentOrganization $current, SignatureVerifier $verifier): JsonResponse
    {
        $source = Source::findByToken($token);

        abort_if($source === null, 404);

        $body = $request->getContent();

        abort_if(strlen($body) > config('relais.max_body_bytes'), 413);

        return $current->run($source->organization_id, function () use ($request, $source, $body, $verifier) {
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

        foreach (self::SENSITIVE_HEADERS as $name) {
            if (isset($headers[$name])) {
                $headers[$name] = ['[masqué]'];
            }
        }

        return $headers;
    }
}
