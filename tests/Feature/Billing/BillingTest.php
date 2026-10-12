<?php

use App\Billing\BillingProvider;
use App\Billing\FakeBillingProvider;
use App\Billing\Plan;
use App\Models\Organization;
use App\Models\Source;
use App\Models\User;
use App\Models\WebhookEvent;

function billingSignature(string $body, string $secret = 'secret-de-test'): string
{
    return hash_hmac('sha256', $body, $secret);
}

function postBillingWebhook($test, array $payload, ?string $signature = null)
{
    $body = json_encode($payload);

    return $test->call('POST', '/billing/webhook', [], [], [], array_filter([
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_BILLING_SIGNATURE' => $signature ?? billingSignature($body),
    ]), $body);
}

test('le faux fournisseur est celui par défaut', function () {
    expect(app(BillingProvider::class))->toBeInstanceOf(FakeBillingProvider::class);
});

test('la page de facturation affiche plan et consommation', function () {
    $owner = User::factory()->create();
    $source = Source::factory()->for($owner->organization)->create();
    WebhookEvent::factory()->count(2)->for($source)->create();

    $this->actingAs($owner)->get('/billing')->assertOk()->assertSee('Gratuit')->assertSee('2 sur 1 000');
    $this->actingAs($owner)->get('/dashboard')->assertOk()->assertSee('2 sur 1 000');
});

test('le propriétaire passe en Pro puis revient au gratuit avec le faux fournisseur', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)->put('/billing', ['plan' => 'pro'])->assertSessionHasNoErrors();
    expect($owner->organization->fresh()->plan)->toBe(Plan::Pro)
        ->and($owner->organization->fresh()->billing_reference)->toBe('fake_sub_'.$owner->organization_id);

    $this->actingAs($owner)->put('/billing', ['plan' => 'free'])->assertSessionHasNoErrors();
    expect($owner->organization->fresh()->plan)->toBe(Plan::Free);
});

test('un plan inconnu est refusé', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)->put('/billing', ['plan' => 'enterprise'])->assertSessionHasErrors('plan');
});

test('un membre voit la facturation mais ne change pas le plan', function () {
    $owner = User::factory()->create();
    $member = User::factory()->memberOf($owner->organization)->create();

    $this->actingAs($member)->get('/billing')->assertOk();
    $this->actingAs($member)->put('/billing', ['plan' => 'pro'])->assertForbidden();

    expect($owner->organization->fresh()->plan)->toBe(Plan::Free);
});

test('on ne change que le plan de sa propre organisation', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    $this->actingAs($a)->put('/billing', ['plan' => 'pro']);

    expect($a->organization->fresh()->plan)->toBe(Plan::Pro)->and($b->organization->fresh()->plan)->toBe(Plan::Free);
    $this->actingAs($b)->get('/billing')->assertOk()->assertSee('Gratuit');
});

test('le webhook de facturation applique le plan confirmé par le fournisseur', function () {
    config(['relais.billing.webhook_secret' => 'secret-de-test']);
    $org = Organization::factory()->create(['billing_reference' => 'sub_123']);

    postBillingWebhook($this, ['type' => 'subscription.updated', 'reference' => 'sub_123', 'plan' => 'pro'])->assertOk();
    expect($org->fresh()->plan)->toBe(Plan::Pro);

    postBillingWebhook($this, ['type' => 'subscription.canceled', 'reference' => 'sub_123'])->assertOk();
    expect($org->fresh()->plan)->toBe(Plan::Free);
});

test('le webhook de facturation refuse une signature fausse ou absente', function () {
    config(['relais.billing.webhook_secret' => 'secret-de-test']);
    $org = Organization::factory()->create(['billing_reference' => 'sub_123']);
    $payload = ['type' => 'subscription.updated', 'reference' => 'sub_123', 'plan' => 'pro'];

    postBillingWebhook($this, $payload, 'faux')->assertStatus(401);
    $this->postJson('/billing/webhook', $payload)->assertStatus(401);

    expect($org->fresh()->plan)->toBe(Plan::Free);
});

test('sans secret configuré, le webhook de facturation est fermé', function () {
    config(['relais.billing.webhook_secret' => null]);

    postBillingWebhook($this, ['type' => 'subscription.canceled', 'reference' => 'x'], hash_hmac('sha256', '', ''))->assertStatus(503);
});

test('le webhook ignore une référence inconnue sans erreur', function () {
    config(['relais.billing.webhook_secret' => 'secret-de-test']);

    postBillingWebhook($this, ['type' => 'subscription.updated', 'reference' => 'inconnue', 'plan' => 'pro'])->assertOk();
});

test('le faux fournisseur est refusé en production sauf choix explicite', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['relais.billing.driver' => 'fake', 'relais.billing.allow_fake_in_production' => false]);

    expect(fn () => app(BillingProvider::class))->toThrow(InvalidArgumentException::class);

    config(['relais.billing.allow_fake_in_production' => true]);

    expect(app(BillingProvider::class))->toBeInstanceOf(FakeBillingProvider::class);
});

test('la page de facturation signale le mode démonstration', function () {
    $this->actingAs(User::factory()->create())->get('/billing')->assertSee('Mode démonstration');
});

test('un webhook de facturation invalide répond en JSON sans redirection', function () {
    config(['relais.billing.webhook_secret' => 'secret-de-test']);
    $body = json_encode(['type' => 'inconnu', 'reference' => 'x']);

    $this->call('POST', '/billing/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_BILLING_SIGNATURE' => billingSignature($body),
    ], $body)->assertStatus(422);
});

test('le webhook de facturation a son propre limiteur, distinct de la réception', function () {
    $resolved = fn (string $name) => app('router')->resolveMiddleware(
        Route::getRoutes()->getByName($name)->gatherMiddleware(),
        Route::getRoutes()->getByName($name)->excludedMiddleware(),
    );

    expect(implode(' ', $resolved('billing.webhook')))->toContain('ThrottleRequests:billing-webhook')->not->toContain('ThrottleRequests:ingest');
    expect(implode(' ', $resolved('ingest')))->toContain('ThrottleRequests:ingest');
});
