<?php

use App\Models\Client;
use App\Models\Facture;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Une facture ne peut concerner qu'un seul client. Le garde-fou existait déjà,
 * mais il levait une Exception générique : l'utilisateur recevait un 500 —
 * un plantage serveur — au lieu d'une erreur métier lisible.
 */
it('refuse de facturer des prestations de clients differents, avec un message lisible', function () {
    $user = User::factory()->create();
    $taux = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 50]);

    $clientA = Client::factory()->create(['user_id' => $user->id]);
    $clientB = Client::factory()->create(['user_id' => $user->id]);

    $prestationA = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $clientA->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => null,
        'heures'          => 10,
    ]);

    $prestationB = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $clientB->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => null,
        'heures'          => 10,
    ]);

    $response = $this->actingAs($user)->postJson('/api/factures', [
        'prestations' => [$prestationA->id, $prestationB->id],
    ]);

    // Une erreur métier, pas un plantage serveur.
    $response->assertStatus(422);

    // Le message doit être exploitable par l'utilisateur, et porté par la clé
    // « prestations » pour que le front l'affiche au bon endroit.
    expect($response->json('errors.prestations.0'))->toContain('même client');

    // Le garde-fou fonctionnait déjà : rien ne doit avoir été créé ni rattaché.
    expect(Facture::count())->toBe(0);
    expect($prestationA->fresh()->facture_id)->toBeNull();
    expect($prestationB->fresh()->facture_id)->toBeNull();
});

it('facture normalement plusieurs prestations d\'un meme client', function () {
    $user   = User::factory()->create();
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 50]);
    $client = Client::factory()->create(['user_id' => $user->id]);

    $prestations = Prestation::factory()->count(3)->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => null,
        'heures'          => 10,
    ]);

    $this->actingAs($user)
        ->postJson('/api/factures', ['prestations' => $prestations->pluck('id')->all()])
        ->assertCreated();

    expect((float) Facture::first()->montant_total)->toBe(1500.0);
});
