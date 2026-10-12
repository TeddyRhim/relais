<?php

use App\Models\Source;
use App\Models\User;
use App\Models\WebhookEvent;

test('l\'accueil présente le service et propose de créer une organisation ou de se connecter', function () {
    $this->get('/')->assertOk()->assertSee('Reçois.', false)->assertSee('Créer une organisation')->assertSee('Se connecter');
});

test('un utilisateur connecté voit le tableau de bord à la place des boutons d\'inscription', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertSee('Tableau de bord')->assertDontSee('Créer une organisation');
});

test('les pages d\'authentification sont en français', function () {
    $this->get('/login')->assertOk()->assertSee('Mot de passe')->assertSee('Se connecter')->assertSee('Rester connecté');
    $this->get('/register')->assertOk()->assertSee('Créer le compte');
});

test('le tableau de bord montre l\'activité par jour, les derniers événements et les signatures', function () {
    $user = User::factory()->create();
    $source = Source::factory()->for($user->organization)->create(['name' => 'Paiements']);
    WebhookEvent::factory()->count(2)->for($source)->create(['signature_status' => 'valid']);
    WebhookEvent::factory()->for($source)->create(['signature_status' => 'invalid', 'created_at' => now()->subDays(3)]);
    WebhookEvent::factory()->for($source)->create(['created_at' => now()->subDays(40)]);

    $response = $this->actingAs($user)->get('/dashboard')->assertOk()
        ->assertSee('Paiements')->assertSee('Signature valide')->assertSee('Signature invalide')
        ->assertSee('14 derniers jours');

    // 14 barres : une par jour, jours vides compris ; l'événement vieux de 40 jours n'y figure pas.
    expect(substr_count($response->getContent(), 'rounded-t '))->toBe(14);
    $response->assertSee('aria-label="Événements reçus par jour', false);
});

test('le tableau de bord d\'une organisation sans événement montre un état vide', function () {
    $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertSee('Rien reçu pour l\'instant');
});

test('les listes vides expliquent quoi faire', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/sources')->assertSee('Aucune source pour l\'instant');
    $this->actingAs($user)->get('/events')->assertSee('Aucun événement reçu');
});

test('le tableau de bord ne montre jamais les événements d\'une autre organisation', function () {
    $user = User::factory()->create();
    WebhookEvent::factory()->for(Source::factory()->create(['name' => 'Source étrangère']))->create();

    $this->actingAs($user)->get('/dashboard')->assertDontSee('Source étrangère');
});
