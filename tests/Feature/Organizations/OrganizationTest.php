<?php

use App\Enums\OrganizationRole;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Support\Facades\Mail;

test('l\'inscription crée une organisation dont l\'utilisateur est propriétaire', function () {
    $this->post('/register', [
        'name' => 'Alice',
        'organization_name' => 'Atelier Alpha',
        'email' => 'alice@example.com',
        'password' => 'motdepasse-solide-1',
        'password_confirmation' => 'motdepasse-solide-1',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'alice@example.com')->first();

    expect($user->organization->name)->toBe('Atelier Alpha')
        ->and($user->organization->slug)->toBe('atelier-alpha')
        ->and($user->role)->toBe(OrganizationRole::Owner);
});

test('le nom d\'organisation est obligatoire sans invitation', function () {
    $this->post('/register', [
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'password' => 'motdepasse-solide-1',
        'password_confirmation' => 'motdepasse-solide-1',
    ])->assertSessionHasErrors('organization_name');
});

test('le propriétaire renomme son organisation, pas un membre', function () {
    $owner = User::factory()->create();
    $member = User::factory()->memberOf($owner->organization)->create();

    $this->actingAs($member)->patch('/organization', ['name' => 'Piraté'])->assertForbidden();
    $this->actingAs($owner)->patch('/organization', ['name' => 'Nouveau nom'])->assertRedirect();

    expect($owner->organization->fresh()->name)->toBe('Nouveau nom');
});

test('un membre ne peut ni inviter ni retirer', function () {
    $owner = User::factory()->create();
    $member = User::factory()->memberOf($owner->organization)->create();

    $this->actingAs($member)->post('/invitations', ['email' => 'new@example.com'])->assertForbidden();
    $this->actingAs($member)->delete('/members/'.$owner->id)->assertForbidden();
});

test('le propriétaire invite : mail envoyé, jeton hashé, lien affiché une fois', function () {
    Mail::fake();
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)->post('/invitations', ['email' => 'Nouveau@Example.com']);

    $response->assertSessionHas('invitation_link');
    $invitation = Invitation::withoutGlobalScopes()->first();
    $token = str($response->getSession()->get('invitation_link'))->after('invitation=')->toString();

    expect($invitation->email)->toBe('nouveau@example.com')
        ->and($invitation->organization_id)->toBe($owner->organization_id)
        ->and($invitation->token_hash)->toBe(Invitation::hashToken($token))
        ->and($invitation->token_hash)->not->toBe($token);

    Mail::assertSent(InvitationMail::class, fn ($mail) => $mail->hasTo('nouveau@example.com'));
});

test('on ne peut pas inviter deux fois la même adresse ni une adresse déjà inscrite', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $existing = User::factory()->create();

    $this->actingAs($owner)->post('/invitations', ['email' => 'a@example.com'])->assertSessionHasNoErrors();
    $this->actingAs($owner)->post('/invitations', ['email' => 'a@example.com'])->assertSessionHasErrors('email');
    $this->actingAs($owner)->post('/invitations', ['email' => $existing->email])->assertSessionHasErrors('email');
});

test('l\'invité s\'inscrit avec le lien et rejoint l\'organisation comme membre', function () {
    $org = Organization::factory()->create();
    $invitation = Invitation::factory()->for($org)->create(['email' => 'invite@example.com', 'token_hash' => Invitation::hashToken('jeton-secret')]);

    $this->get('/register?invitation=jeton-secret')->assertOk()->assertSee($org->name);

    $this->post('/register', [
        'invitation' => 'jeton-secret',
        'name' => 'Invité',
        'email' => 'invite@example.com',
        'password' => 'motdepasse-solide-1',
        'password_confirmation' => 'motdepasse-solide-1',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'invite@example.com')->first();

    expect($user->organization_id)->toBe($org->id)
        ->and($user->role)->toBe(OrganizationRole::Member)
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();
    expect(Organization::count())->toBe(1);
});

test('une invitation expirée, utilisée ou pour une autre adresse est refusée', function () {
    Invitation::factory()->create(['email' => 'a@example.com', 'token_hash' => Invitation::hashToken('expiree'), 'expires_at' => now()->subDay()]);
    Invitation::factory()->create(['email' => 'b@example.com', 'token_hash' => Invitation::hashToken('utilisee'), 'accepted_at' => now()]);
    Invitation::factory()->create(['email' => 'c@example.com', 'token_hash' => Invitation::hashToken('bonne')]);

    $payload = fn (array $extra) => $extra + [
        'name' => 'X', 'password' => 'motdepasse-solide-1', 'password_confirmation' => 'motdepasse-solide-1',
    ];

    $this->post('/register', $payload(['invitation' => 'expiree', 'email' => 'a@example.com']))->assertSessionHasErrors('invitation');
    $this->post('/register', $payload(['invitation' => 'utilisee', 'email' => 'b@example.com']))->assertSessionHasErrors('invitation');
    $this->post('/register', $payload(['invitation' => 'bonne', 'email' => 'autre@example.com']))->assertSessionHasErrors('email');

    expect(User::count())->toBe(0);
});

test('le propriétaire retire un membre qui repart avec sa propre organisation', function () {
    $owner = User::factory()->create();
    $member = User::factory()->memberOf($owner->organization)->create(['name' => 'Marc']);

    $this->actingAs($owner)->delete('/members/'.$member->id)->assertRedirect();

    $member->refresh();
    expect($member->organization_id)->not->toBe($owner->organization_id)
        ->and($member->role)->toBe(OrganizationRole::Owner);
});

test('le propriétaire ne peut pas se retirer lui-même', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)->delete('/members/'.$owner->id)->assertStatus(422);
});

test('le propriétaire annule une invitation de son organisation', function () {
    $owner = User::factory()->create();
    $invitation = Invitation::factory()->for($owner->organization)->create();

    $this->actingAs($owner)->delete('/invitations/'.$invitation->id)->assertRedirect();

    expect(Invitation::withoutGlobalScopes()->count())->toBe(0);
});

test('un propriétaire ne peut pas supprimer son compte tant que d\'autres membres restent', function () {
    $owner = User::factory()->create();
    User::factory()->memberOf($owner->organization)->create();

    $this->actingAs($owner)->delete('/profile', ['password' => 'password'])->assertSessionHasErrorsIn('userDeletion', 'password');

    expect(User::find($owner->id))->not->toBeNull();
});

test('un propriétaire seul supprime son compte et son organisation', function () {
    $owner = User::factory()->create();
    $orgId = $owner->organization_id;

    $this->actingAs($owner)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

    expect(User::find($owner->id))->toBeNull()->and(Organization::find($orgId))->toBeNull();
});

test('la vérification de l\'hôte est active dans la pile globale', function () {
    // Laravel la désactive pendant les tests unitaires : on vérifie sa présence, le comportement est contrôlé à la main.
    expect(app(Kernel::class)->hasMiddleware(TrustHosts::class))->toBeTrue();
});
