<?php

use App\Actions\DeleteUser;
use App\Models\Client;
use App\Models\Facture;
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
    // ATTENTION : ce test tourne sur SQLite (RefreshDatabase / phpunit.xml),
    // dont l'ordre de résolution des cascades diffère de MySQL/InnoDB. Il
    // passait déjà AVANT le correctif (UserObserver) et ne prouve donc pas,
    // à lui seul, que la suppression fonctionne réellement en production.
    // La preuve du correctif est une vérification manuelle sur MySQL réel
    // (container Docker) — voir le rapport de correctif.
    $user    = User::factory()->create();
    $client  = Client::factory()->create(['user_id' => $user->id]);
    $taux    = TauxHoraire::factory()->create(['user_id' => $user->id]);
    $facture = Facture::factory()->create(['user_id' => $user->id]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => $facture->id,
    ]);

    // user_id reste en CASCADE : supprimer un compte doit tout emporter.
    // Ce test existe parce que RESTRICT sur client_id / taux_horaire_id
    // pourrait faire échouer cette cascade selon l'ordre choisi par la base.
    // Depuis le correctif, UserObserver::deleting() supprime explicitement
    // les prestations de l'utilisateur avant que la cascade native ne
    // s'attaque à ses clients, taux horaires et factures.
    //
    // Passe par App\Actions\DeleteUser, seul point d'entrée valide pour
    // supprimer un compte (voir son docblock).
    (new DeleteUser())->execute($user);

    expect(Prestation::find($prestation->id))->toBeNull();
    expect(Client::find($client->id))->toBeNull();
    expect(TauxHoraire::find($taux->id))->toBeNull();
    expect(Facture::find($facture->id))->toBeNull();
});
