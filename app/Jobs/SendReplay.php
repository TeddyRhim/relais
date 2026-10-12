<?php

namespace App\Jobs;

use App\Models\Replay;
use App\Services\UrlGuard;
use App\Tenancy\CurrentOrganization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Rejoue un webhook vers sa cible. Une réponse non 2xx ou une erreur réseau fait échouer
 * la tentative : la file la relance après les délais de config('relais.replay.backoff').
 */
class SendReplay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $replayId, public int $organizationId) {}

    public function tries(): int
    {
        return count(config('relais.replay.backoff')) + 1;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return config('relais.replay.backoff');
    }

    public function handle(CurrentOrganization $current, UrlGuard $guard): void
    {
        $current->run($this->organizationId, function () use ($guard) {
            $replay = Replay::with('event.source')->find($this->replayId);

            if (! $replay || $replay->isFinished()) {
                return;
            }

            // Revérifié à l'envoi : la cible a pu changer de résolution DNS depuis la création.
            if (! $guard->isSafe($replay->target_url)) {
                $replay->update(['status' => Replay::FAILED, 'error' => 'Cible non autorisée.', 'last_attempt_at' => now()]);

                return;
            }

            $replay->update(['attempts' => $replay->attempts + 1, 'last_attempt_at' => now()]);

            $event = $replay->event;
            $signatureHeader = strtolower($event->source->signature_header);
            $headers = [
                'User-Agent' => 'Relais-Replay/1.0',
                'X-Relais-Event-Id' => (string) $event->id,
                'X-Relais-Replay-Id' => (string) $replay->id,
            ];

            // La signature d'origine est transmise telle quelle : la cible peut la vérifier avec le même secret.
            if (isset($event->headers[$signatureHeader][0])) {
                $headers[$event->source->signature_header] = $event->headers[$signatureHeader][0];
            }

            try {
                $response = Http::timeout(config('relais.replay.timeout'))
                    ->withoutRedirecting()
                    ->withHeaders($headers)
                    ->withOptions(['stream' => true])
                    ->withBody($event->body, $event->content_type ?: 'application/octet-stream')
                    ->post($replay->target_url);
            } catch (ConnectionException $e) {
                // Le détail réseau reste dans les journaux : l'afficher révélerait la topologie atteinte.
                Log::warning('Rejeu : erreur de connexion', ['replay' => $replay->id, 'detail' => $e->getMessage()]);
                $replay->update(['status' => Replay::RETRYING, 'error' => 'Connexion impossible ou délai dépassé.']);

                throw new RuntimeException('Erreur réseau lors du rejeu.', previous: $e);
            }

            // Lecture bornée : on ne charge jamais tout le corps de la réponse en mémoire.
            $excerpt = mb_convert_encoding(
                $response->toPsrResponse()->getBody()->read(config('relais.replay.response_excerpt_bytes')),
                'UTF-8',
                'UTF-8',
            );
            $response->toPsrResponse()->getBody()->close();

            if ($response->successful()) {
                $replay->update([
                    'status' => Replay::SUCCESS,
                    'response_status' => $response->status(),
                    'response_excerpt' => $excerpt,
                    'error' => null,
                ]);

                return;
            }

            $replay->update([
                'status' => Replay::RETRYING,
                'response_status' => $response->status(),
                'response_excerpt' => $excerpt,
                'error' => 'Réponse HTTP '.$response->status(),
            ]);

            throw new RuntimeException('La cible a répondu '.$response->status().'.');
        });
    }

    public function failed(?Throwable $exception): void
    {
        app(CurrentOrganization::class)->run($this->organizationId, function () {
            $replay = Replay::find($this->replayId);

            // Le motif détaillé de la dernière tentative est déjà dans `error` ; on ne recopie pas le message brut.
            $replay?->update(['status' => Replay::FAILED, 'error' => $replay->error ?? 'Échec du rejeu.']);
        });
    }
}
