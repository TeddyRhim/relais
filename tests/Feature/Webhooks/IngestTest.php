<?php

use App\Models\Source;
use App\Models\WebhookEvent;
use App\Services\SignatureVerifier;

function signed(Source $source, string $body): string
{
    return app(SignatureVerifier::class)->sign($body, $source->signing_secret);
}

function eventsOf(Source $source)
{
    return WebhookEvent::withoutGlobalScopes()->where('source_id', $source->id);
}

test('un webhook est reçu sans authentification et stocké avec son contenu et ses en-têtes', function () {
    $source = Source::factory()->create();
    $body = '{"evenement":"commande.creee"}';

    $this->call('POST', '/h/'.$source->token, [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CUSTOM' => 'abc',
    ], $body)->assertStatus(202)->assertJsonPath('signature', 'missing');

    $event = eventsOf($source)->first();

    expect($event->body)->toBe($body)
        ->and($event->organization_id)->toBe($source->organization_id)
        ->and($event->method)->toBe('POST')
        ->and($event->headers['x-custom'])->toBe(['abc'])
        ->and($event->signature_status)->toBe('missing');
});

test('un jeton inconnu renvoie 404 et ne stocke rien', function () {
    $this->postJson('/h/inconnu', ['a' => 1])->assertNotFound();

    expect(WebhookEvent::withoutGlobalScopes()->count())->toBe(0);
});

test('seul POST est accepté', function () {
    $source = Source::factory()->create();

    $this->get('/h/'.$source->token)->assertStatus(405);
});

test('signature valide, invalide et absente sont qualifiées', function () {
    $source = Source::factory()->create();
    $body = '{"x":1}';
    $url = '/h/'.$source->token;

    $send = fn (?string $signature) => $this->call('POST', $url, [], [], [], array_filter([
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_RELAIS_SIGNATURE' => $signature,
    ]), $body);

    $send(signed($source, $body))->assertJsonPath('signature', 'valid');
    $send('sha256='.signed($source, $body))->assertJsonPath('signature', 'valid');
    $send(strtoupper(signed($source, $body)))->assertJsonPath('signature', 'valid');
    $send(signed($source, 'autre corps'))->assertJsonPath('signature', 'invalid');
    $send('n-importe-quoi')->assertJsonPath('signature', 'invalid');
    $send(null)->assertJsonPath('signature', 'missing');

    expect(eventsOf($source)->count())->toBe(6);
});

test('la signature d\'une autre source n\'est pas acceptée', function () {
    $source = Source::factory()->create();
    $other = Source::factory()->create();
    $body = '{"x":1}';

    $this->call('POST', '/h/'.$source->token, [], [], [], ['HTTP_X_RELAIS_SIGNATURE' => signed($other, $body)], $body)
        ->assertJsonPath('signature', 'invalid');
});

test('avec la signature obligatoire, un webhook non signé ou mal signé est refusé et non stocké', function () {
    $source = Source::factory()->create(['require_signature' => true]);
    $body = '{"x":1}';
    $url = '/h/'.$source->token;

    $this->call('POST', $url, [], [], [], [], $body)->assertStatus(401);
    $this->call('POST', $url, [], [], [], ['HTTP_X_RELAIS_SIGNATURE' => 'faux'], $body)->assertStatus(401);
    expect(eventsOf($source)->count())->toBe(0);

    $this->call('POST', $url, [], [], [], ['HTTP_X_RELAIS_SIGNATURE' => signed($source, $body)], $body)->assertStatus(202);
    expect(eventsOf($source)->count())->toBe(1);
});

test('l\'en-tête de signature est configurable par source', function () {
    $source = Source::factory()->create(['signature_header' => 'X-Hub-Signature-256']);
    $body = '{"x":1}';

    $this->call('POST', '/h/'.$source->token, [], [], [], ['HTTP_X_HUB_SIGNATURE_256' => 'sha256='.signed($source, $body)], $body)
        ->assertJsonPath('signature', 'valid');
});

test('un corps trop volumineux est refusé', function () {
    config(['relais.max_body_bytes' => 10]);
    $source = Source::factory()->create();

    $this->call('POST', '/h/'.$source->token, [], [], [], [], str_repeat('a', 11))->assertStatus(413);

    expect(eventsOf($source)->count())->toBe(0);
});

test('les en-têtes sensibles ne sont pas conservés', function () {
    $source = Source::factory()->create();

    $this->call('POST', '/h/'.$source->token, [], [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer secret-de-lemetteur',
        'HTTP_COOKIE' => 'session=abc',
    ], '{}')->assertStatus(202);

    $stored = json_encode(eventsOf($source)->first()->headers);

    expect($stored)->not->toContain('secret-de-lemetteur')->not->toContain('session=abc');
});

test('les webhooks de deux sources vont chacun dans leur organisation', function () {
    $a = Source::factory()->create();
    $b = Source::factory()->create();

    $this->postJson('/h/'.$a->token, ['n' => 1]);
    $this->postJson('/h/'.$b->token, ['n' => 2]);

    expect(eventsOf($a)->first()->organization_id)->toBe($a->organization_id)
        ->and(eventsOf($b)->first()->organization_id)->toBe($b->organization_id)
        ->and($a->organization_id)->not->toBe($b->organization_id);
});

test('la réception est limitée par source', function () {
    config(['relais.ingest_rate_per_minute' => 2]);
    $source = Source::factory()->create();

    $this->postJson('/h/'.$source->token, [])->assertStatus(202);
    $this->postJson('/h/'.$source->token, [])->assertStatus(202);
    $this->postJson('/h/'.$source->token, [])->assertStatus(429);
});

test('le secret de signature est chiffré en base', function () {
    $source = Source::factory()->create();

    $raw = DB::table('sources')->where('id', $source->id)->value('signing_secret');

    expect($raw)->not->toBe($source->signing_secret)->and($source->signing_secret)->not->toBeEmpty();
});

test('les en-têtes de clés et de jetons sont masqués mais la signature est conservée', function () {
    $source = Source::factory()->create();

    $this->call('POST', '/h/'.$source->token, [], [], [], [
        'HTTP_X_API_KEY' => 'cle-privee-123',
        'HTTP_X_AUTH_TOKEN' => 'jeton-prive-456',
        'HTTP_X_RELAIS_SIGNATURE' => 'abc',
    ], '{}');

    $stored = eventsOf($source)->first()->headers;

    expect(json_encode($stored))->not->toContain('cle-privee-123')->not->toContain('jeton-prive-456')
        ->and($stored['x-relais-signature'])->toBe(['abc']);
});
