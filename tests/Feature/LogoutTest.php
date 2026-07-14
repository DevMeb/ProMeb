<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deconnecte un utilisateur authentifie', function () {
    // Sanctum ne démarre une session que pour les requêtes "stateful" (Origin/Referer
    // reconnu dans sanctum.stateful) — même technique que tests/Feature/SessionFixationTest.php.
    // Sans cet en-tête, StartSession ne s'exécute jamais et $request->session() lève une
    // exception avant l'assertion : non représentatif d'une vraie requête SPA (qui envoie
    // toujours ce header), donc on le simule ici pour exercer le vrai chemin de code.
    config(['sanctum.stateful' => ['testserver']]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withHeaders(['Origin' => 'http://testserver'])
        ->postJson('/api/auth/logout')
        ->assertNoContent();
});

it('repond 401 (et non 500) sur un logout sans session', function () {
    $this->postJson('/api/auth/logout')
        ->assertStatus(401);
});
