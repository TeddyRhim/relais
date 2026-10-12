<?php

use App\Models\Source;
use App\Models\User;
use App\Models\WebhookEvent;
use Laravel\Sanctum\PersonalAccessToken;

function apiKeyFor(User $user, array $abilities = ['events:read']): string
{
    return $user->createToken('test', $abilities, now()->addDays(90))->plainTextToken;
}

test('sans clé, l\'API répond 401 en JSON', function () {
    $this->getJson('/api/v1/events')->assertStatus(401);
    $this->get('/api/v1/events')->assertStatus(401)->assertHeader('Content-Type', 'application/json');
    $this->withToken('rl_clé-inventée')->getJson('/api/v1/events')->assertStatus(401);
});

test('la clé liste les événements de son organisation uniquement', function () {
    $user = User::factory()->create();
    $mine = Source::factory()->for($user->organization)->create();
    $foreign = Source::factory()->create();
    WebhookEvent::factory()->count(3)->for($mine)->create();
    WebhookEvent::factory()->count(4)->for($foreign)->create();

    $response = $this->withToken(apiKeyFor($user))->getJson('/api/v1/events')->assertOk();

    expect($response->json('meta.total'))->toBe(3)
        ->and(collect($response->json('data'))->pluck('source_id')->unique()->all())->toBe([$mine->id]);
});

test('le détail d\'un événement d\'une autre organisation est introuvable', function () {
    $user = User::factory()->create();
    $foreignEvent = WebhookEvent::factory()->for(Source::factory()->create())->create(['body' => 'prive']);
    $ownEvent = WebhookEvent::factory()->for(Source::factory()->for($user->organization)->create())->create(['body' => '{"ok":true}']);

    $key = apiKeyFor($user);

    $this->withToken($key)->getJson('/api/v1/events/'.$foreignEvent->id)->assertNotFound();
    $this->withToken($key)->getJson('/api/v1/events/'.$ownEvent->id)->assertOk()
        ->assertJsonPath('data.body', '{"ok":true}')
        ->assertJsonStructure(['data' => ['id', 'headers', 'signature_status']]);
});

test('le filtre par source et la pagination fonctionnent, avec une borne', function () {
    $user = User::factory()->create();
    $a = Source::factory()->for($user->organization)->create();
    $b = Source::factory()->for($user->organization)->create();
    WebhookEvent::factory()->count(3)->for($a)->create();
    WebhookEvent::factory()->count(2)->for($b)->create();
    $key = apiKeyFor($user);

    $this->withToken($key)->getJson('/api/v1/events?source='.$b->id)->assertJsonPath('meta.total', 2);
    $this->withToken($key)->getJson('/api/v1/events?per_page=2')->assertJsonCount(2, 'data')->assertJsonPath('meta.last_page', 3);
    $this->withToken($key)->getJson('/api/v1/events?per_page=1000')->assertStatus(422);
});

test('la liste des sources n\'expose ni jeton ni secret', function () {
    $user = User::factory()->create();
    $source = Source::factory()->for($user->organization)->create(['name' => 'Paiements']);
    Source::factory()->create(['name' => 'Autre organisation']);

    $response = $this->withToken(apiKeyFor($user))->getJson('/api/v1/sources')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->getContent())->toContain('Paiements')
        ->not->toContain($source->token)->not->toContain($source->signing_secret)->not->toContain('Autre organisation');
});

test('une clé sans la capacité de lecture est refusée', function () {
    $user = User::factory()->create();

    $this->withToken(apiKeyFor($user, ['autre:chose']))->getJson('/api/v1/events')->assertStatus(403);
});

test('une clé expirée ou révoquée est refusée', function () {
    $user = User::factory()->create();
    $expired = $user->createToken('vieille', ['events:read'], now()->subMinute())->plainTextToken;
    $revoked = apiKeyFor($user);

    $this->withToken($expired)->getJson('/api/v1/events')->assertStatus(401);

    PersonalAccessToken::findToken($revoked)->delete();
    app('auth')->forgetGuards();
    $this->withToken($revoked)->getJson('/api/v1/events')->assertStatus(401);
});

test('la limitation de débit s\'applique par clé', function () {
    config(['relais.api_rate_per_minute' => 2]);
    $user = User::factory()->create();
    $key = apiKeyFor($user);
    $other = apiKeyFor(User::factory()->create());

    $this->withToken($key)->getJson('/api/v1/events')->assertOk();
    $this->withToken($key)->getJson('/api/v1/events')->assertOk();
    $this->withToken($key)->getJson('/api/v1/events')->assertStatus(429);

    // Chaque requête réelle repart d'une application neuve : on oublie l'utilisateur mis en cache par le test.
    app('auth')->forgetGuards();
    $this->withToken($other)->getJson('/api/v1/events')->assertOk();
});

test('la clé d\'un utilisateur d\'une organisation ne donne jamais accès à une autre', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    WebhookEvent::factory()->count(2)->for(Source::factory()->for($b->organization)->create())->create();

    $this->withToken(apiKeyFor($a))->getJson('/api/v1/events')->assertJsonPath('meta.total', 0);
});

test('les essais de clés invalides sont limités par adresse IP avant l\'authentification', function () {
    config(['relais.api_rate_per_minute' => 1]);

    foreach (range(1, 5) as $ignored) {
        $this->withToken('rl_invalide')->getJson('/api/v1/events')->assertStatus(401);
    }

    $this->withToken('rl_invalide')->getJson('/api/v1/events')->assertStatus(429);
});
