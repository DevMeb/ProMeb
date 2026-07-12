<?php

use App\Actions\DeleteUser;
use App\Models\Client;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('n\'efface aucune prestation si la suppression du user echoue apres coup (atomicite)', function () {
    // ATTENTION : ce test tourne sur SQLite et ne reproduit PAS l'erreur
    // MySQL/InnoDB 1451 (cascade concurrente + RESTRICT) qui a motivé le
    // correctif — il ne prouve donc pas, à lui seul, que la suppression
    // fonctionne sur MySQL (voir le rapport de correctif pour la preuve
    // MySQL réelle). Il prouve en revanche que la transaction ouverte par
    // DeleteUser::execute() est réelle : si un autre listener échoue APRÈS
    // le passage de UserObserver, tout est annulé — y compris les
    // prestations que l'observer avait déjà supprimées.
    //
    // Avant le correctif, UserObserver ouvrait lui-même une transaction qui
    // committait seule, indépendamment du sort de la suppression du user :
    // dans ce même scénario, les prestations auraient été détruites pour
    // rien, alors que le user, lui, existerait toujours.
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => null,
    ]);

    // Un listener enregistré après UserObserver (donc déclenché après lui)
    // simule un échec en aval : veto d'un autre observer, exception,
    // deadlock... Reproduit le scénario décrit dans la revue.
    User::deleting(function () {
        throw new RuntimeException('Echec simulé après le passage de UserObserver.');
    });

    expect(fn () => (new DeleteUser())->execute($user))
        ->toThrow(RuntimeException::class);

    expect(User::find($user->id))->not->toBeNull();
    expect(Client::find($client->id))->not->toBeNull();
    expect(TauxHoraire::find($taux->id))->not->toBeNull();
    expect(Prestation::find($prestation->id))->not->toBeNull();
});

it('supprime bien l\'utilisateur et ses prestations quand rien n\'echoue', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => null,
    ]);

    (new DeleteUser())->execute($user);

    expect(User::find($user->id))->toBeNull();
    expect(Prestation::find($prestation->id))->toBeNull();
});
