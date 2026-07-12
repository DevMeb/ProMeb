<?php

use App\Models\Client;
use App\Models\Facture;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Rend la vue du PDF pour un client dont afficher_horaires vaut $afficherHoraires,
 * et renvoie le HTML produit.
 */
function rendreVuePdf(bool $afficherHoraires): string
{
    $user   = User::factory()->create([
        'iban'        => 'FR7630006000011234567890189',
        'name'        => 'Dupont',
        'prenom'      => 'Jean',
        'adresse'     => '1 rue de Paris',
        'ville'       => 'Paris',
        'code_postal' => '75001',
        'siren'       => '123456789',
        'nom_societe' => 'Jean Dupont Freelance',
    ]);
    $client = Client::factory()->create([
        'user_id'           => $user->id,
        'afficher_horaires' => $afficherHoraires,
    ]);
    $taux    = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 25]);
    $facture = Facture::factory()->create(['user_id' => $user->id]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => $facture->id,
        'horaires'        => '11h45-15h 18h45-2h',
        'heures'          => 9.25,
    ]);

    return view('invoices.pdf', [
        'facture'          => $facture,
        'prestations'      => collect([$prestation->load('tauxHoraire')]),
        'client'           => $client,
        'user'             => $user,
        'afficherHoraires' => $afficherHoraires,
    ])->render();
}

it('affiche la colonne horaires quand le client l\'accepte', function () {
    $html = rendreVuePdf(true);

    expect($html)->toContain('Horaires');
    expect($html)->toContain('11h45-15h 18h45-2h');
});

it('masque la colonne horaires quand le client la refuse', function () {
    $html = rendreVuePdf(false);

    expect($html)->not->toContain('Horaires');
    expect($html)->not->toContain('11h45-15h 18h45-2h');
});

it('conserve les autres colonnes quand les horaires sont masqués', function () {
    $html = rendreVuePdf(false);

    // Les 5 colonnes restantes sont toujours là.
    expect($html)->toContain('Réf.');
    expect($html)->toContain('Date');
    expect($html)->toContain('Qté');
    expect($html)->toContain('PU HT');
    expect($html)->toContain('Total HT');
});
