<?php

use App\Models\Client;
use App\Models\Facture;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('refuse de supprimer un taux horaire utilise par des prestations non facturees, sans rien detruire', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 60]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => null,   // NON facturée
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/taux-horaires/{$taux->id}");

    $response->assertForbidden();

    // Le cœur du test : la prestation ne doit PAS avoir été détruite en cascade.
    expect(Prestation::find($prestation->id))->not->toBeNull();
    expect(TauxHoraire::find($taux->id))->not->toBeNull();
});

it('refuse de supprimer un taux horaire deja facture', function () {
    $user    = User::factory()->create();
    $client  = Client::factory()->create(['user_id' => $user->id]);
    $taux    = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 60]);
    $facture = Facture::factory()->create(['user_id' => $user->id]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => $facture->id,
    ]);

    $this->actingAs($user)
        ->deleteJson("/api/taux-horaires/{$taux->id}")
        ->assertForbidden();

    expect(Prestation::find($prestation->id))->not->toBeNull();
});

it('autorise la suppression d\'un taux horaire sans aucune prestation', function () {
    $user = User::factory()->create();
    $taux = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 60]);

    $this->actingAs($user)
        ->deleteJson("/api/taux-horaires/{$taux->id}")
        ->assertOk();

    expect(TauxHoraire::find($taux->id))->toBeNull();
});

it('refuse de supprimer un client qui a des prestations, sans rien detruire', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 60]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => null,
    ]);

    $this->actingAs($user)
        ->deleteJson("/api/clients/{$client->id}")
        ->assertForbidden();

    expect(Prestation::find($prestation->id))->not->toBeNull();
    expect(Client::find($client->id))->not->toBeNull();
});

it('autorise la suppression d\'un client sans prestation', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->deleteJson("/api/clients/{$client->id}")
        ->assertOk();

    expect(Client::find($client->id))->toBeNull();
});

it('explique pourquoi la suppression est refusee', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 60]);

    Prestation::factory()->count(3)->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => null,
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/taux-horaires/{$taux->id}");

    // Le message doit être exploitable : il dit combien de prestations bloquent.
    expect($response->json('message'))->toContain('3 prestations');
});

it('ne revele rien a un intrus dans le message de refus', function () {
    $proprietaire = User::factory()->create();
    $intrus       = User::factory()->create();

    $client = Client::factory()->create(['user_id' => $proprietaire->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $proprietaire->id, 'taux' => 60]);

    Prestation::factory()->count(25)->create([
        'user_id'         => $proprietaire->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => null,
    ]);

    $response = $this->actingAs($intrus)->deleteJson("/api/taux-horaires/{$taux->id}");

    $response->assertForbidden();

    // Le refus ne doit PAS trahir le volume d'activité du propriétaire.
    expect($response->json('message'))->not->toContain('25');
});
