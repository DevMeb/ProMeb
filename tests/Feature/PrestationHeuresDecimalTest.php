<?php

use App\Models\Client;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Models\User;
use App\Services\FactureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('stocke et relit des heures fractionnées sans perte', function () {
    $user = User::factory()->create();

    $prestation = Prestation::factory()->create([
        'user_id' => $user->id,
        'heures'  => 6.25,
    ]);

    // Le cast decimal:2 garantit une représentation exacte et stable (string "6.25").
    expect($prestation->fresh()->heures)->toBe('6.25');
});

it('somme les heures et montants décimaux exactement dans la facture', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 20]);

    $p1 = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'heures'          => 6.25,
    ]);
    $p2 = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'heures'          => 9.50,
    ]);

    Auth::login($user);
    $facture = app(FactureService::class)->create(['prestations' => [$p1->id, $p2->id]]);

    // 6.25 + 9.50 = 15.75 h ; 15.75 × 20 = 315.00 €
    expect($facture->heures_total)->toBe('15.75');
    expect($facture->montant_total)->toBe('315.00');
});
