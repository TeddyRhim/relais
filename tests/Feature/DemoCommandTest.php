<?php

use App\Enums\OrganizationRole;
use App\Models\Replay;
use App\Models\Source;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Hash;

test('la commande de démo demande les identifiants et crée des données fictives isolées', function () {
    $this->artisan('relais:demo')
        ->expectsQuestion('Adresse e-mail du compte de démonstration', 'demo@example.test')
        ->expectsQuestion('Mot de passe (12 caractères minimum)', 'un-mot-de-passe-de-test')
        ->assertSuccessful();

    $user = User::where('email', 'demo@example.test')->first();

    expect($user->role)->toBe(OrganizationRole::Owner)
        ->and(Hash::check('un-mot-de-passe-de-test', $user->password))->toBeTrue()
        ->and(Source::withoutGlobalScopes()->count())->toBe(2)
        ->and(WebhookEvent::withoutGlobalScopes()->count())->toBe(30)
        ->and(Replay::withoutGlobalScopes()->count())->toBeGreaterThan(0)
        ->and(WebhookEvent::withoutGlobalScopes()->distinct()->pluck('organization_id')->all())->toBe([$user->organization_id]);

    $this->actingAs($user)->get('/events')->assertOk();
});

test('la commande lit les identifiants dans l\'environnement', function () {
    putenv('DEMO_EMAIL=env@example.test');
    putenv('DEMO_PASSWORD=mot-de-passe-via-env-1');

    try {
        $this->artisan('relais:demo')->assertSuccessful();
    } finally {
        putenv('DEMO_EMAIL');
        putenv('DEMO_PASSWORD');
    }

    expect(User::where('email', 'env@example.test')->exists())->toBeTrue();
});

test('sans identifiants et sans saisie, la commande échoue sans rien créer ni attendre', function () {
    $this->artisan('relais:demo')
        ->expectsQuestion('Adresse e-mail du compte de démonstration', '')
        ->expectsQuestion('Mot de passe (12 caractères minimum)', '')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

test('un mot de passe trop court est refusé', function () {
    $this->artisan('relais:demo --email=court@example.test')
        ->expectsQuestion('Mot de passe (12 caractères minimum)', 'court')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

test('le seeder par défaut ne crée aucun compte', function () {
    $this->seed();

    expect(User::count())->toBe(0);
});
