<?php

use App\Models\Source;
use App\Models\User;

test('le propriétaire crée une source avec jeton et secret générés', function () {
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)->post('/sources', ['name' => 'Paiements']);

    $source = Source::withoutGlobalScopes()->first();
    $response->assertRedirect(route('sources.show', $source));

    $this->actingAs($owner)->get('/sources')->assertSee('Paiements');

    expect($source->token)->toHaveLength(40)
        ->and($source->signing_secret)->toHaveLength(48)
        ->and($source->organization_id)->toBe($owner->organization_id);
});

test('un membre voit les sources mais ne peut pas les gérer', function () {
    $owner = User::factory()->create();
    $member = User::factory()->memberOf($owner->organization)->create();
    $source = Source::factory()->for($owner->organization)->create(['name' => 'Facturation']);

    $this->actingAs($member)->get('/sources')->assertOk()->assertSee('Facturation');
    $this->actingAs($member)->get('/sources/'.$source->id)->assertOk();
    $this->actingAs($member)->post('/sources', ['name' => 'X'])->assertForbidden();
    $this->actingAs($member)->patch('/sources/'.$source->id, ['name' => 'X'])->assertForbidden();
    $this->actingAs($member)->delete('/sources/'.$source->id)->assertForbidden();
});

test('les pages exigent une connexion', function () {
    $this->get('/sources')->assertRedirect('/login');
    $this->get('/events')->assertRedirect('/login');
    $this->post('/sources', ['name' => 'X'])->assertRedirect('/login');
});

test('la mise à jour valide l\'adresse cible', function () {
    $owner = User::factory()->create();
    $source = Source::factory()->for($owner->organization)->create();

    $this->actingAs($owner)->patch('/sources/'.$source->id, ['name' => 'S', 'target_url' => 'http://127.0.0.1/hook'])
        ->assertSessionHasErrors('target_url');
    $this->actingAs($owner)->patch('/sources/'.$source->id, ['name' => 'S', 'target_url' => 'ftp://93.184.216.34/x'])
        ->assertSessionHasErrors('target_url');
    $this->actingAs($owner)->patch('/sources/'.$source->id, ['name' => 'S', 'signature_header' => 'Mauvais En-tête'])
        ->assertSessionHasErrors('signature_header');

    $this->actingAs($owner)->patch('/sources/'.$source->id, [
        'name' => 'Renommée', 'target_url' => 'https://93.184.216.34/hook', 'require_signature' => '1',
    ])->assertSessionHasNoErrors();

    $source->refresh();
    expect($source->name)->toBe('Renommée')->and($source->require_signature)->toBeTrue()
        ->and($source->target_url)->toBe('https://93.184.216.34/hook');
});

test('une autre organisation ne voit, ne modifie et ne supprime pas la source', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $source = Source::factory()->for($owner->organization)->create(['name' => 'Secrète']);

    $this->actingAs($attacker)->get('/sources')->assertDontSee('Secrète');
    $this->actingAs($attacker)->get('/sources/'.$source->id)->assertNotFound();
    $this->actingAs($attacker)->patch('/sources/'.$source->id, ['name' => 'Piratée'])->assertNotFound();
    $this->actingAs($attacker)->delete('/sources/'.$source->id)->assertNotFound();

    expect(Source::withoutGlobalScopes()->find($source->id)->name)->toBe('Secrète');
});

test('le propriétaire supprime sa source', function () {
    $owner = User::factory()->create();
    $source = Source::factory()->for($owner->organization)->create();

    $this->actingAs($owner)->delete('/sources/'.$source->id)->assertRedirect(route('sources.index'));

    expect(Source::withoutGlobalScopes()->count())->toBe(0);
});
