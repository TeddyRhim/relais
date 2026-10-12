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
use Illuminate\Support\Str;
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
                    ->withBody($event->body, $event->content_type ?: 'application/octet-stream')
                    ->post($replay->target_url);
            } catch (ConnectionException $e) {
                $replay->update(['status' => Replay::RETRYING, 'error' => Str::limit($e->getMessage(), 500)]);

                throw new RuntimeException('Erreur réseau lors du rejeu.', previous: $e);
            }

            $excerpt = Str::limit($response->body(), config('relais.replay.response_excerpt_bytes'), '');

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
        app(CurrentOrganization::class)->run($this->organizationId, function () use ($exception) {
            Replay::find($this->replayId)?->update([
                'status' => Replay::FAILED,
                'error' => Str::limit($exception?->getMessage() ?? 'Échec du rejeu.', 500),
            ]);
        });
    }
}
