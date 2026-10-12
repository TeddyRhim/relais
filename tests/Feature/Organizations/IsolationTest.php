<?php

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Tenancy\CurrentOrganization;

function tenancy(): CurrentOrganization
{
    return app(CurrentOrganization::class);
}

test('le scope global ne renvoie que les lignes de l\'organisation courante', function () {
    $a = Organization::factory()->create();
    $b = Organization::factory()->create();
    Invitation::factory()->for($a)->create(['email' => 'a@example.com']);
    Invitation::factory()->for($b)->create(['email' => 'b@example.com']);

    $seenByA = tenancy()->run($a, fn () => Invitation::pluck('email')->all());
    $seenByB = tenancy()->run($b, fn () => Invitation::pluck('email')->all());

    expect($seenByA)->toBe(['a@example.com'])
        ->and($seenByB)->toBe(['b@example.com']);
});

test('sans contexte d\'organisation, rien n\'est visible', function () {
    Invitation::factory()->create();

    expect(Invitation::count())->toBe(0);
});

test('la création renseigne organization_id depuis le contexte et échoue sans contexte', function () {
    $org = Organization::factory()->create();

    $invitation = tenancy()->run($org, fn () => Invitation::create([
        'email' => 'x@example.com',
        'token_hash' => 'h',
        'expires_at' => now()->addDay(),
    ]));

    expect($invitation->organization_id)->toBe($org->id);

    expect(fn () => Invitation::create([
        'email' => 'y@example.com',
        'token_hash' => 'h2',
        'expires_at' => now()->addDay(),
    ]))->toThrow(LogicException::class);
});

test('run restaure le contexte précédent', function () {
    $a = Organization::factory()->create();
    $b = Organization::factory()->create();

    tenancy()->set($a);
    tenancy()->run($b, fn () => expect(tenancy()->id())->toBe($b->id));

    expect(tenancy()->id())->toBe($a->id);
});

test('la page organisation ne montre que les membres et invitations de sa propre organisation', function () {
    $owner = User::factory()->create(['name' => 'Alice Alpha']);
    $other = User::factory()->create(['name' => 'Bob Beta']);
    Invitation::factory()->for($owner->organization)->create(['email' => 'mine@example.com']);
    Invitation::factory()->for($other->organization)->create(['email' => 'theirs@example.com']);

    $this->actingAs($owner)->get('/organization')
        ->assertOk()
        ->assertSee('Alice Alpha')
        ->assertSee('mine@example.com')
        ->assertDontSee('Bob Beta')
        ->assertDontSee('theirs@example.com');
});

test('une autre organisation ne peut pas annuler une invitation', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $invitation = Invitation::factory()->for($owner->organization)->create();

    $this->actingAs($attacker)->delete('/invitations/'.$invitation->id)->assertNotFound();

    expect(Invitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeTrue();
});

test('une autre organisation ne peut pas retirer un membre', function () {
    $owner = User::factory()->create();
    $member = User::factory()->memberOf($owner->organization)->create();
    $attacker = User::factory()->create();

    $this->actingAs($attacker)->delete('/members/'.$member->id)->assertNotFound();

    expect($member->fresh()->organization_id)->toBe($owner->organization_id);
});
