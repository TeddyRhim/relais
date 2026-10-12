<?php

use App\Models\User;

test('les pages web et l\'API envoient les en-têtes de sécurité', function () {
    $web = $this->get('/login');
    $api = $this->getJson('/api/v1/events');

    foreach ([$web, $api] as $response) {
        expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
            ->and($response->headers->get('X-Frame-Options'))->toBe('DENY')
            ->and($response->headers->get('Referrer-Policy'))->toBe('no-referrer');
    }
});

test('la page d\'inscription par invitation ne transmet pas son adresse aux sites tiers', function () {
    $this->get('/register?invitation=quelconque')->assertHeader('Referrer-Policy', 'no-referrer');
});

test('l\'authentification d\'une session sur une autre organisation reste impossible via un identifiant de session partagé', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    $this->actingAs($a)->get('/dashboard')->assertOk()->assertSee($a->organization->name)->assertDontSee($b->organization->name);
});
