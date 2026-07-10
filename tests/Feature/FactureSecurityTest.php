<?php

use App\Models\Client;
use App\Models\Facture;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Crée une prestation cohérente (même propriétaire pour user/client/taux) rattachée à une facture.
 */
function prestationPour(User $user, ?Facture $facture = null): Prestation
{
    return Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => Client::factory()->create(['user_id' => $user->id])->id,
        'taux_horaire_id' => TauxHoraire::factory()->create(['user_id' => $user->id])->id,
        'facture_id'      => $facture?->id,
    ]);
}

// ─── Faille #1 : téléchargement du PDF d'autrui (IDOR) ───────────────────────

it('interdit de télécharger le PDF de la facture d\'un autre utilisateur', function () {
    $proprietaire = User::factory()->create();
    $facture = Facture::factory()->create(['user_id' => $proprietaire->id]);
    prestationPour($proprietaire, $facture);

    $intrus = User::factory()->create();

    $this->actingAs($intrus)
        ->getJson(route('factures.pdf', $facture))
        ->assertForbidden();
});

// ─── Faille #2 : marquer payée la facture d'autrui (IDOR) ────────────────────

it('interdit de marquer payée la facture d\'un autre utilisateur', function () {
    $proprietaire = User::factory()->create();
    $facture = Facture::factory()->create(['user_id' => $proprietaire->id]);

    $intrus = User::factory()->create();

    $this->actingAs($intrus)
        ->patchJson(route('factures.paid', $facture))
        ->assertForbidden();

    expect($facture->fresh()->paye_le)->toBeNull();
});

// ─── Faille #3 : créer une facture depuis les prestations d'autrui (IDOR) ─────

it('interdit de créer une facture depuis les prestations d\'un autre utilisateur', function () {
    $victime = User::factory()->create();
    $prestationVictime = prestationPour($victime);

    $intrus = User::factory()->create();

    $this->actingAs($intrus)
        ->postJson(route('factures.store'), [
            'prestations' => [$prestationVictime->id],
        ])
        ->assertStatus(422);

    // La prestation de la victime ne doit pas avoir été réassignée.
    expect($prestationVictime->fresh()->facture_id)->toBeNull();
    expect(Facture::where('user_id', $intrus->id)->count())->toBe(0);
});

// ─── Non-régression : le propriétaire garde ses accès légitimes ──────────────

it('autorise le propriétaire à marquer sa facture payée', function () {
    $user = User::factory()->create();
    $facture = Facture::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->patchJson(route('factures.paid', $facture))
        ->assertOk()
        ->assertJson(['message' => 'La facture a été payée avec succès']);

    expect($facture->fresh()->paye_le)->not->toBeNull();
});

it('autorise le propriétaire à créer une facture depuis ses propres prestations', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 50]);

    $p1 = Prestation::factory()->create([
        'user_id' => $user->id, 'client_id' => $client->id, 'taux_horaire_id' => $taux->id, 'heures' => 2,
    ]);
    $p2 = Prestation::factory()->create([
        'user_id' => $user->id, 'client_id' => $client->id, 'taux_horaire_id' => $taux->id, 'heures' => 3,
    ]);

    $this->actingAs($user)
        ->postJson(route('factures.store'), ['prestations' => [$p1->id, $p2->id]])
        ->assertCreated()
        ->assertJson(['message' => 'Facture créée avec succès.']);

    expect($p1->fresh()->facture_id)->not->toBeNull();
    expect($p2->fresh()->facture_id)->toBe($p1->fresh()->facture_id);
});
