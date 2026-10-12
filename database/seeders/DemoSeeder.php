<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Replay;
use App\Models\Source;
use App\Models\WebhookEvent;
use App\Services\SignatureVerifier;
use Illuminate\Database\Seeder;

/**
 * Données d'exemple entièrement fictives : une organisation, deux sources, des événements
 * étalés sur deux semaines et quelques rejeux. Aucune donnée réelle, adresses en example.org.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFor(Organization::factory()->create(['name' => 'Atelier Exemple']));
    }

    public function seedFor(Organization $organization): void
    {
        $verifier = new SignatureVerifier;

        $sources = [
            Source::factory()->for($organization)->create([
                'name' => 'Paiements (exemple)',
                'target_url' => 'https://example.org/webhooks/paiements',
            ]),
            Source::factory()->for($organization)->create([
                'name' => 'Livraisons (exemple)',
                'target_url' => 'https://example.org/webhooks/livraisons',
                'signature_header' => 'X-Livraison-Signature',
            ]),
        ];

        $types = [
            0 => ['paiement.reussi', 'paiement.echoue', 'remboursement.cree'],
            1 => ['colis.expedie', 'colis.livre', 'colis.retarde'],
        ];

        foreach (range(1, 30) as $i) {
            $index = $i % 2;
            $source = $sources[$index];
            $body = json_encode([
                'evenement' => $types[$index][$i % 3],
                'reference' => sprintf('EX-%05d', 1000 + $i),
                'montant' => $index === 0 ? ($i * 1350) % 40000 + 500 : null,
            ], JSON_UNESCAPED_UNICODE);

            $headers = ['content-type' => ['application/json'], 'user-agent' => ['exemple-emetteur/1.0']];
            $status = match ($i % 5) {
                0 => WebhookEvent::SIGNATURE_MISSING,
                4 => WebhookEvent::SIGNATURE_INVALID,
                default => WebhookEvent::SIGNATURE_VALID,
            };

            if ($status !== WebhookEvent::SIGNATURE_MISSING) {
                $signature = $status === WebhookEvent::SIGNATURE_VALID
                    ? $verifier->sign($body, $source->signing_secret)
                    : str_repeat('0', 64);
                $headers[strtolower($source->signature_header)] = [$signature];
            }

            $event = WebhookEvent::factory()->for($source)->create([
                'body' => $body,
                'headers' => $headers,
                'signature_status' => $status,
                'created_at' => now()->subDays(14)->addHours($i * 11),
                'updated_at' => now()->subDays(14)->addHours($i * 11),
            ]);

            if ($i % 6 === 0) {
                Replay::factory()->for($event, 'event')->create([
                    'target_url' => $source->target_url,
                    'status' => $i % 12 === 0 ? Replay::FAILED : Replay::SUCCESS,
                    'attempts' => $i % 12 === 0 ? 5 : 1,
                    'response_status' => $i % 12 === 0 ? 503 : 200,
                    'error' => $i % 12 === 0 ? 'Réponse HTTP 503' : null,
                    'last_attempt_at' => $event->created_at->addMinutes(5),
                ]);
            }
        }
    }
}
