<?php

use App\Models\Client;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ecrireReleve(string $contenu): string
{
    $path = tempnam(sys_get_temp_dir(), 'releve_') . '.txt';
    file_put_contents($path, $contenu);

    return $path;
}

it('importe les prestations depuis un fichier pour un client et un taux', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 25]);

    $file = ecrireReleve("02/06: 6h15 18h30-00h45\n13/06: 9h15 11h45-15h 2V 18h45-2h\nTOTAL: 15h30");

    $this->artisan('prestations:import', [
        '--client' => $client->id,
        '--taux'   => $taux->id,
        '--year'   => 2026,
        '--file'   => $file,
        '--force'  => true,
    ])->assertExitCode(0);

    expect(Prestation::where('user_id', $user->id)->count())->toBe(2);

    $p = Prestation::where('date', '2026-06-02')->first();
    expect($p->heures)->toEqual('6.25');
    expect($p->adresse)->toBe('PARIS');
    expect($p->client_id)->toBe($client->id);
    expect($p->taux_horaire_id)->toBe($taux->id);

    // Les marqueurs V sont retirés des horaires.
    expect(Prestation::where('date', '2026-06-13')->first()->horaires)->toBe('11h45-15h 18h45-2h');

    unlink($file);
});

it('refuse l\'import si une ligne est illisible et ne crée rien', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux = TauxHoraire::factory()->create(['user_id' => $user->id]);

    $file = ecrireReleve("02/06: 6h15 18h30-00h45\nligne cassée");

    $this->artisan('prestations:import', [
        '--client' => $client->id,
        '--taux'   => $taux->id,
        '--year'   => 2026,
        '--file'   => $file,
        '--force'  => true,
    ])->assertExitCode(1);

    expect(Prestation::count())->toBe(0);

    unlink($file);
});

it('refuse un taux qui n\'appartient pas au même utilisateur que le client', function () {
    $client = Client::factory()->create();
    $tauxAutre = TauxHoraire::factory()->create(); // autre utilisateur

    $file = ecrireReleve('02/06: 6h15 18h30-00h45');

    $this->artisan('prestations:import', [
        '--client' => $client->id,
        '--taux'   => $tauxAutre->id,
        '--year'   => 2026,
        '--file'   => $file,
        '--force'  => true,
    ])->assertExitCode(1);

    expect(Prestation::count())->toBe(0);

    unlink($file);
});
