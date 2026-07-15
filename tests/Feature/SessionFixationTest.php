<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('regenere l\'identifiant de session apres une connexion reussie (protection anti fixation de session)', function () {
    // Sanctum ne démarre une session que pour les requêtes "stateful" (Origin/Referer
    // reconnu dans sanctum.stateful). On force ce domaine pour que StartSession
    // s'exécute réellement et que session()->regenerate() ait un effet à observer.
    config(['sanctum.stateful' => ['testserver']]);

    User::factory()->create([
        'email'    => 'victime@promeb.fr',
        'password' => bcrypt('le-bon-mot-de-passe'),
    ]);

    // Simule un attaquant qui a fixé l'identifiant de session de la victime avant
    // l'authentification (attaque par fixation de session). L'ID doit être valide
    // au sens d'Illuminate\Session\Store::isValidId() (40 caractères alphanumériques),
    // sinon Laravel l'ignore et en génère un nouveau — ce qui rendrait le test inutile.
    $idAttaquant = bin2hex(random_bytes(20));

    $response = $this->withCredentials()
        ->withCookie(config('session.cookie'), $idAttaquant)
        ->withHeaders(['Origin' => 'http://testserver'])
        ->postJson('/api/auth/login', [
            'email'    => 'victime@promeb.fr',
            'password' => 'le-bon-mot-de-passe',
        ]);

    $response->assertNoContent();

    // Le store de session (array driver) est un singleton du conteneur applicatif :
    // à l'issue de la requête, il porte encore l'ID courant de LA session traitée.
    $idApresConnexion = $this->app['session']->getId();

    expect($idApresConnexion)
        ->not->toBe($idAttaquant)
        ->and($idApresConnexion)->toHaveLength(40);
});
