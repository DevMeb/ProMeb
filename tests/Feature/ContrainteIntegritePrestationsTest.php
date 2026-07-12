<?php

use App\Models\Client;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('la base refuse de supprimer un taux horaire encore utilise, meme hors policy', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
    ]);

    // Suppression directe, en contournant complètement les policies.
    expect(fn () => $taux->delete())->toThrow(QueryException::class);

    expect(Prestation::find($prestation->id))->not->toBeNull();
});

it('la base refuse de supprimer un client encore utilise, meme hors policy', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
    ]);

    expect(fn () => $client->delete())->toThrow(QueryException::class);

    expect(Prestation::find($prestation->id))->not->toBeNull();
});

it('la suppression d\'un utilisateur emporte toujours ses donnees', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
    ]);

    // user_id reste en CASCADE : supprimer un compte doit tout emporter.
    // Ce test existe parce que RESTRICT sur client_id / taux_horaire_id
    // pourrait faire échouer cette cascade selon l'ordre choisi par la base.
    $user->delete();

    expect(Prestation::find($prestation->id))->toBeNull();
    expect(Client::find($client->id))->toBeNull();
    expect(TauxHoraire::find($taux->id))->toBeNull();
});
