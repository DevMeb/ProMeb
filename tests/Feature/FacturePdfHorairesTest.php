<?php

use App\Models\Client;
use App\Models\Facture;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

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

    // Et les lignes du tableau des prestations comptent bien 5 cellules, pas 6.
    // On isole le <tbody> : c'est le seul de tout le document (le tableau
    // d'en-tête N° facture / date / lieu n'utilise que des <tr> bruts).
    preg_match('/<tbody>(.*?)<\/tbody>/s', $html, $tbodyMatch);
    expect($tbodyMatch)->toHaveCount(2);

    preg_match('/<tr>(.*?)<\/tr>/s', $tbodyMatch[1], $rowMatch);
    expect($rowMatch)->toHaveCount(2);

    $nbCellules = substr_count($rowMatch[1], '<td>');
    expect($nbCellules)->toBe(5);
});

/**
 * Crée un jeu de données complet (utilisateur au profil complet, client,
 * taux horaire, facture et prestation) prêt pour appeler le vrai endpoint
 * GET /api/factures/{id}/pdf.
 */
function creerFactureAvecHoraires(bool $afficherHoraires): array
{
    $user = User::factory()->create([
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

    Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => $facture->id,
        'horaires'        => '11h45-15h 18h45-2h',
        'heures'          => 9.25,
    ]);

    return ['user' => $user, 'facture' => $facture];
}

it('télécharge un PDF valide pour une facture dont le client affiche les horaires', function () {
    ['user' => $user, 'facture' => $facture] = creerFactureAvecHoraires(true);

    $donneesVue = [];
    Event::listen('composing: invoices.pdf', function ($view) use (&$donneesVue) {
        $donneesVue = $view->getData();
    });

    $response = $this->actingAs($user)->getJson(route('factures.pdf', $facture));

    $response->assertOk();
    expect($response->streamedContent())->toStartWith('%PDF-');
    expect($donneesVue['afficherHoraires'])->toBe(true);
});

it('télécharge un PDF valide pour une facture dont le client masque les horaires', function () {
    ['user' => $user, 'facture' => $facture] = creerFactureAvecHoraires(false);

    $donneesVue = [];
    Event::listen('composing: invoices.pdf', function ($view) use (&$donneesVue) {
        $donneesVue = $view->getData();
    });

    $response = $this->actingAs($user)->getJson(route('factures.pdf', $facture));

    $response->assertOk();
    expect($response->streamedContent())->toStartWith('%PDF-');
    expect($donneesVue['afficherHoraires'])->toBe(false);
});
