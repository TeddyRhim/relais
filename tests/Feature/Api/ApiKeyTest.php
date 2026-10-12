<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

test('créer une clé l\'affiche une seule fois et ne conserve que son hash', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/api-keys', ['name' => 'Tableau de bord'])->assertRedirect();
    $plain = session('api_key');

    expect($plain)->toContain('|rl_');

    $token = $user->tokens()->first();
    expect($token->name)->toBe('Tableau de bord')
        ->and($token->abilities)->toBe(['events:read'])
        ->and($token->expires_at->isFuture())->toBeTrue()
        ->and($token->token)->not->toContain($plain);

    // Première page après la création : la clé est montrée. Ensuite : plus jamais.
    $this->actingAs($user)->get('/api-keys')->assertOk()->assertSee($plain);
    $this->actingAs($user)->get('/api-keys')->assertOk()->assertSee('Tableau de bord')->assertDontSee($plain);
});

test('la clé créée depuis la page fonctionne sur l\'API', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/api-keys', ['name' => 'k']);
    $plain = session('api_key');
    app('auth')->forgetGuards();

    $this->withToken($plain)->getJson('/api/v1/sources')->assertOk();
});

test('on révoque ses clés mais pas celles d\'un autre utilisateur', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $own = $owner->createToken('a', ['events:read']);
    $foreign = $other->createToken('b', ['events:read']);

    $this->actingAs($owner)->delete('/api-keys/'.$foreign->accessToken->id)->assertNotFound();
    expect(PersonalAccessToken::count())->toBe(2);

    $this->actingAs($owner)->delete('/api-keys/'.$own->accessToken->id)->assertRedirect();
    expect(PersonalAccessToken::count())->toBe(1);
});

test('la page des clés exige une connexion et un nom', function () {
    $this->get('/api-keys')->assertRedirect('/login');
    $this->actingAs(User::factory()->create())->post('/api-keys', [])->assertSessionHasErrors('name');
});
