<?php

use App\Jobs\SendReplay;
use App\Models\Replay;
use App\Models\Source;
use App\Models\WebhookEvent;
use App\Services\UrlGuard;
use App\Tenancy\CurrentOrganization;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function pendingReplay(array $eventAttributes = [], string $target = 'https://93.184.216.34/hook'): Replay
{
    $source = Source::factory()->create();
    $event = WebhookEvent::factory()->for($source)->create($eventAttributes + ['body' => '{"a":1}']);

    return Replay::factory()->for($event, 'event')->create(['target_url' => $target]);
}

function runReplay(Replay $replay): void
{
    (new SendReplay($replay->id, $replay->organization_id))->handle(app(CurrentOrganization::class), app(UrlGuard::class));
}

function replayRow(Replay $replay): Replay
{
    return Replay::withoutGlobalScopes()->find($replay->id);
}

test('un rejeu réussi envoie le corps d\'origine et passe en réussi', function () {
    Http::fake(['93.184.216.34/*' => Http::response('reçu', 200)]);
    $replay = pendingReplay(['content_type' => 'application/json']);

    runReplay($replay);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://93.184.216.34/hook'
        && $request->method() === 'POST'
        && $request->body() === '{"a":1}'
        && $request->hasHeader('Content-Type', 'application/json')
        && $request->hasHeader('X-Relais-Replay-Id', (string) $replay->id));

    $row = replayRow($replay);
    expect($row->status)->toBe('success')
        ->and($row->attempts)->toBe(1)
        ->and($row->response_status)->toBe(200)
        ->and($row->response_excerpt)->toBe('reçu');
});

test('la signature d\'origine est transmise à la cible', function () {
    Http::fake(['*' => Http::response('', 204)]);
    $replay = pendingReplay(['headers' => ['x-relais-signature' => ['abc123']]]);

    runReplay($replay);

    Http::assertSent(fn (Request $request) => $request->hasHeader('X-Relais-Signature', 'abc123'));
});

test('une réponse en erreur passe en nouvelle tentative et relance l\'exception pour la file', function () {
    Http::fake(['*' => Http::response('panne', 503)]);
    $replay = pendingReplay();

    expect(fn () => runReplay($replay))->toThrow(RuntimeException::class);

    $row = replayRow($replay);
    expect($row->status)->toBe('retrying')
        ->and($row->attempts)->toBe(1)
        ->and($row->response_status)->toBe(503);
});

test('une erreur réseau passe en nouvelle tentative', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));
    $replay = pendingReplay();

    expect(fn () => runReplay($replay))->toThrow(RuntimeException::class);

    expect(replayRow($replay)->status)->toBe('retrying');
});

test('les tentatives s\'additionnent puis l\'échec définitif est enregistré', function () {
    Http::fake(['*' => Http::response('', 500)]);
    $replay = pendingReplay();

    foreach (range(1, 3) as $ignored) {
        try {
            runReplay($replay);
        } catch (RuntimeException) {
        }
    }

    expect(replayRow($replay)->attempts)->toBe(3);

    (new SendReplay($replay->id, $replay->organization_id))->failed(new RuntimeException('La cible a répondu 500.'));

    $row = replayRow($replay);
    expect($row->status)->toBe('failed')->and($row->error)->toContain('500');
});

test('les tentatives de la file suivent les délais configurés', function () {
    $job = new SendReplay(1, 1);

    expect($job->backoff())->toBe([10, 60, 300, 1800])->and($job->tries())->toBe(5);
});

test('une cible interne est refusée à l\'envoi, sans requête, sans nouvelle tentative', function () {
    Http::fake();
    $replay = pendingReplay(target: 'http://127.0.0.1:8080/admin');

    runReplay($replay);

    Http::assertNothingSent();
    expect(replayRow($replay)->status)->toBe('failed');
});

test('les redirections ne sont pas suivies', function () {
    Http::fake(['93.184.216.34/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/secret'])]);
    $replay = pendingReplay();

    expect(fn () => runReplay($replay))->toThrow(RuntimeException::class);

    Http::assertSentCount(1);
});

test('un rejeu terminé n\'est pas renvoyé', function () {
    Http::fake();
    $replay = pendingReplay();
    $replay->update(['status' => 'success']);

    runReplay($replay);

    Http::assertNothingSent();
});

test('la tâche travaille dans l\'organisation du rejeu et n\'atteint pas celle des autres', function () {
    Http::fake();
    $replay = pendingReplay();
    $foreign = Replay::factory()->create();

    (new SendReplay($foreign->id, $replay->organization_id))->handle(app(CurrentOrganization::class), app(UrlGuard::class));

    Http::assertNothingSent();
    expect(replayRow($foreign)->status)->toBe('pending')->and(replayRow($foreign)->attempts)->toBe(0);
});
