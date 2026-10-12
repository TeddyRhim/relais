<?php

use App\Billing\Plan;
use App\Billing\Usage;
use App\Models\Organization;
use App\Models\Replay;
use App\Models\Source;
use App\Models\User;
use App\Models\WebhookEvent;

test('une nouvelle organisation est sur le plan gratuit avec 1000 événements par mois', function () {
    $org = Organization::create(['name' => 'Neuve']);

    expect($org->plan)->toBe(Plan::Free)
        ->and(app(Usage::class)->limit($org))->toBe(1000)
        ->and(Plan::Pro->monthlyEventLimit())->toBe(50000);
});

test('l\'usage ne compte que les événements du mois civil en cours de l\'organisation', function () {
    $source = Source::factory()->create();
    $other = Source::factory()->create();
    WebhookEvent::factory()->count(3)->for($source)->create();
    WebhookEvent::factory()->for($source)->create(['created_at' => now()->subMonthNoOverflow()->startOfMonth()]);
    WebhookEvent::factory()->count(5)->for($other)->create();

    $usage = app(Usage::class);

    expect($usage->used($source->organization))->toBe(3)
        ->and($usage->used($other->organization))->toBe(5)
        ->and($usage->remaining($source->organization))->toBe(997);
});

test('au quota, la réception refuse avec 429 sans rien stocker', function () {
    config(['relais.plans.free.events_per_month' => 2]);
    $source = Source::factory()->create();

    $this->postJson('/h/'.$source->token, ['n' => 1])->assertStatus(202);
    $this->postJson('/h/'.$source->token, ['n' => 2])->assertStatus(202);
    $this->postJson('/h/'.$source->token, ['n' => 3])->assertStatus(429)->assertJsonPath('error', 'quota_exceeded');

    expect(WebhookEvent::withoutGlobalScopes()->count())->toBe(2);
});

test('le quota d\'une organisation ne bloque pas les autres', function () {
    config(['relais.plans.free.events_per_month' => 1]);
    $a = Source::factory()->create();
    $b = Source::factory()->create();

    $this->postJson('/h/'.$a->token, [])->assertStatus(202);
    $this->postJson('/h/'.$a->token, [])->assertStatus(429);
    $this->postJson('/h/'.$b->token, [])->assertStatus(202);
});

test('passer au plan Pro relève le quota', function () {
    config(['relais.plans.free.events_per_month' => 1, 'relais.plans.pro.events_per_month' => 5]);
    $owner = User::factory()->create();
    $source = Source::factory()->for($owner->organization)->create();

    $this->postJson('/h/'.$source->token, [])->assertStatus(202);
    $this->postJson('/h/'.$source->token, [])->assertStatus(429);

    $this->actingAs($owner)->put('/billing', ['plan' => 'pro'])->assertRedirect(route('billing.show'));

    $this->postJson('/h/'.$source->token, [])->assertStatus(202);
});

test('les rejeux ne consomment pas le quota', function () {
    $source = Source::factory()->create();
    $event = WebhookEvent::factory()->for($source)->create();
    Replay::factory()->count(4)->for($event, 'event')->create();

    expect(app(Usage::class)->used($source->organization))->toBe(1);
});

test('supprimer une source ne remet pas le quota à zéro', function () {
    config(['relais.plans.free.events_per_month' => 2]);
    $owner = User::factory()->create();
    $source = Source::factory()->for($owner->organization)->create();

    $this->postJson('/h/'.$source->token, [])->assertStatus(202);
    $this->postJson('/h/'.$source->token, [])->assertStatus(202);

    $this->actingAs($owner)->delete('/sources/'.$source->id)->assertRedirect();
    $fresh = Source::factory()->for($owner->organization)->create();

    expect(WebhookEvent::withoutGlobalScopes()->count())->toBe(0)
        ->and(app(Usage::class)->used($owner->organization))->toBe(2);
    $this->postJson('/h/'.$fresh->token, [])->assertStatus(429);
});

test('le compteur est propre à chaque mois', function () {
    $source = Source::factory()->create();
    WebhookEvent::factory()->count(2)->for($source)->create(['created_at' => now()->subMonthNoOverflow()]);
    WebhookEvent::factory()->for($source)->create();

    expect(app(Usage::class)->used($source->organization))->toBe(1);
});
