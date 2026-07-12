<?php

use App\Models\Client;
use App\Models\Facture;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Crée un utilisateur avec un client, un taux et une prestation libre.
 * Retourne [$user, $prestation].
 */
function contexteFacturation(float $heures = 10, float $taux = 50): array
{
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $th     = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => $taux]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $th->id,
        'facture_id'      => null,
        'heures'          => $heures,
    ]);

    return [$user, $prestation];
}

it('refuse de facturer une prestation deja facturee', function () {
    [$user, $prestation] = contexteFacturation();

    // 1re facture : elle rattache la prestation.
    $this->actingAs($user)
        ->postJson('/api/factures', ['prestations' => [$prestation->id]])
        ->assertCreated();

    $factureOrigine = Facture::first();

    // 2e tentative avec la MÊME prestation.
    $this->actingAs($user)
        ->postJson('/api/factures', ['prestations' => [$prestation->id]])
        ->assertStatus(422);

    // La facture d'origine est intacte : ses lignes ET son montant.
    expect($factureOrigine->fresh()->prestations)->toHaveCount(1);
    expect((float) $factureOrigine->fresh()->montant_total)->toBe(500.0);

    // Aucune seconde facture n'a été créée.
    expect(Facture::count())->toBe(1);
});

it('laisse le PDF de la facture d\'origine intact apres une tentative de refacturation', function () {
    $user   = User::factory()->create([
        'iban'        => 'FR7630001007941234567890185',
        'prenom'      => 'Jean',
        'adresse'     => '1 rue de la Paix',
        'ville'       => 'Paris',
        'code_postal' => '75001',
        'siren'       => '123456789',
        'nom_societe' => 'JD Conseil',
    ]);
    $client = Client::factory()->create(['user_id' => $user->id]);
    $th     = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 50]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $th->id,
        'facture_id'      => null,
        'heures'          => 10,
    ]);

    $this->actingAs($user)->postJson('/api/factures', ['prestations' => [$prestation->id]])->assertCreated();
    $facture = Facture::first();

    $this->actingAs($user)->postJson('/api/factures', ['prestations' => [$prestation->id]])->assertStatus(422);

    // Le PDF doit toujours se générer : c'est lui qui tombait en 500.
    $this->actingAs($user)
        ->get("/api/factures/{$facture->id}/pdf")
        ->assertOk();
});

it('refuse de facturer la prestation d\'un autre utilisateur', function () {
    [$proprietaire, $prestation] = contexteFacturation();
    $intrus = User::factory()->create();

    $this->actingAs($intrus)
        ->postJson('/api/factures', ['prestations' => [$prestation->id]])
        ->assertStatus(422);

    expect($prestation->fresh()->facture_id)->toBeNull();
    expect(Facture::count())->toBe(0);
});

it('facture normalement des prestations libres', function () {
    [$user, $prestation] = contexteFacturation();

    $response = $this->actingAs($user)
        ->postJson('/api/factures', ['prestations' => [$prestation->id]]);

    $response->assertCreated();

    expect($prestation->fresh()->facture_id)->not->toBeNull();
    expect((float) Facture::first()->montant_total)->toBe(500.0);
});
