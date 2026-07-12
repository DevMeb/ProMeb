<?php

use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('interdit a un tiers de modifier le taux horaire d\'un autre utilisateur', function () {
    $proprietaire = User::factory()->create();
    $intrus       = User::factory()->create();

    $taux = TauxHoraire::factory()->create([
        'user_id' => $proprietaire->id,
        'taux'    => 60,
    ]);

    $this->actingAs($intrus)
        ->putJson("/api/taux-horaires/{$taux->id}", ['taux' => 1])
        ->assertForbidden();

    // Le taux ne doit pas avoir bougé.
    expect((float) $taux->fresh()->taux)->toBe(60.0);
});

it('interdit a un tiers de supprimer le taux horaire d\'un autre utilisateur', function () {
    $proprietaire = User::factory()->create();
    $intrus       = User::factory()->create();

    $taux = TauxHoraire::factory()->create(['user_id' => $proprietaire->id, 'taux' => 60]);

    $this->actingAs($intrus)
        ->deleteJson("/api/taux-horaires/{$taux->id}")
        ->assertForbidden();

    expect(TauxHoraire::find($taux->id))->not->toBeNull();
});

it('autorise le proprietaire a modifier son propre taux horaire non facture', function () {
    $user = User::factory()->create();
    $taux = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 60]);

    $this->actingAs($user)
        ->putJson("/api/taux-horaires/{$taux->id}", ['taux' => 65])
        ->assertOk();

    expect((float) $taux->fresh()->taux)->toBe(65.0);
});
