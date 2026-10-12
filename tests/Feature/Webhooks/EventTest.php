<?php

use App\Jobs\SendReplay;
use App\Models\Replay;
use App\Models\Source;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Queue;

test('la liste et le détail montrent le contenu des événements de l\'organisation', function () {
    $user = User::factory()->create();
    $source = Source::factory()->for($user->organization)->create(['name' => 'Paiements']);
    $event = WebhookEvent::factory()->for($source)->create(['body' => '{"montant":4200}']);

    $this->actingAs($user)->get('/events')->assertOk()->assertSee('Paiements');
    $this->actingAs($user)->get('/events/'.$event->id)->assertOk()->assertSee('montant');
});

test('le contenu d\'un événement est échappé à l\'affichage', function () {
    $user = User::factory()->create();
    $source = Source::factory()->for($user->organization)->create();
    $event = WebhookEvent::factory()->for($source)->create(['body' => '<script>alert(1)</script>']);

    $this->actingAs($user)->get('/events/'.$event->id)->assertDontSee('<script>alert(1)</script>', false);
});

test('une autre organisation ne voit pas les événements', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $source = Source::factory()->for($owner->organization)->create(['name' => 'Confidentielle']);
    $event = WebhookEvent::factory()->for($source)->create(['body' => 'donnees-privees']);

    $this->actingAs($attacker)->get('/events')->assertDontSee('Confidentielle');
    $this->actingAs($attacker)->get('/events?source='.$source->id)->assertDontSee('Confidentielle');
    $this->actingAs($attacker)->get('/events/'.$event->id)->assertNotFound();
    $this->actingAs($attacker)->get('/sources/'.$source->id)->assertNotFound();
});

test('le rejeu crée un suivi et met la tâche en file', function () {
    Queue::fake();
    $user = User::factory()->create();
    $source = Source::factory()->for($user->organization)->create(['target_url' => 'https://93.184.216.34/hook']);
    $event = WebhookEvent::factory()->for($source)->create();

    $this->actingAs($user)->post('/events/'.$event->id.'/replay')->assertRedirect(route('events.show', $event));

    $replay = Replay::withoutGlobalScopes()->first();

    expect($replay->target_url)->toBe('https://93.184.216.34/hook')
        ->and($replay->status)->toBe('pending')
        ->and($replay->organization_id)->toBe($user->organization_id);

    Queue::assertPushed(SendReplay::class, fn ($job) => $job->replayId === $replay->id && $job->organizationId === $user->organization_id);
});

test('le rejeu accepte une cible saisie et refuse les cibles internes', function () {
    Queue::fake();
    $user = User::factory()->create();
    $source = Source::factory()->for($user->organization)->create();
    $event = WebhookEvent::factory()->for($source)->create();

    $this->actingAs($user)->post('/events/'.$event->id.'/replay')->assertSessionHasErrors('target_url');
    $this->actingAs($user)->post('/events/'.$event->id.'/replay', ['target_url' => 'http://169.254.169.254/latest'])->assertSessionHasErrors('target_url');
    $this->actingAs($user)->post('/events/'.$event->id.'/replay', ['target_url' => 'http://localhost:8000/x'])->assertSessionHasErrors('target_url');
    $this->actingAs($user)->post('/events/'.$event->id.'/replay', ['target_url' => 'https://93.184.216.34/autre'])->assertSessionHasNoErrors();

    Queue::assertPushed(SendReplay::class, 1);
});

test('une autre organisation ne peut pas rejouer un événement', function () {
    Queue::fake();
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $source = Source::factory()->for($owner->organization)->create();
    $event = WebhookEvent::factory()->for($source)->create();

    $this->actingAs($attacker)->post('/events/'.$event->id.'/replay', ['target_url' => 'https://93.184.216.34/x'])->assertNotFound();

    Queue::assertNothingPushed();
    expect(Replay::withoutGlobalScopes()->count())->toBe(0);
});

test('un membre rejoue seulement vers la cible de la source, jamais vers une cible libre', function () {
    Queue::fake();
    $owner = User::factory()->create();
    $member = User::factory()->memberOf($owner->organization)->create();
    $source = Source::factory()->for($owner->organization)->create(['target_url' => 'https://93.184.216.34/officielle']);
    $event = WebhookEvent::factory()->for($source)->create();

    $this->actingAs($member)->post('/events/'.$event->id.'/replay', ['target_url' => 'https://93.184.216.35/libre'])->assertSessionHasNoErrors();

    expect(Replay::withoutGlobalScopes()->first()->target_url)->toBe('https://93.184.216.34/officielle');
});

test('le secret de signature n\'est montré qu\'au propriétaire', function () {
    $owner = User::factory()->create();
    $member = User::factory()->memberOf($owner->organization)->create();
    $source = Source::factory()->for($owner->organization)->create();

    $this->actingAs($owner)->get('/sources/'.$source->id)->assertSee($source->signing_secret);
    $this->actingAs($member)->get('/sources/'.$source->id)->assertDontSee($source->signing_secret);
});
