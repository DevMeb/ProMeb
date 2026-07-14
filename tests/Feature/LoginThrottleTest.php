<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('bloque la connexion apres 5 tentatives echouees (429 a la 6e)', function () {
    User::factory()->create([
        'email'    => 'victime@promeb.fr',
        'password' => bcrypt('le-vrai-mot-de-passe'),
    ]);

    // 5 tentatives échouées : refusées (422), mais pas encore bloquées.
    for ($i = 1; $i <= 5; $i++) {
        $this->postJson('/api/auth/login', [
            'email'    => 'victime@promeb.fr',
            'password' => 'mauvais',
        ])->assertStatus(422);
    }

    // La 6e est bloquée par le limiteur.
    $this->postJson('/api/auth/login', [
        'email'    => 'victime@promeb.fr',
        'password' => 'mauvais',
    ])->assertStatus(429);
});

it('ne bloque pas une autre adresse IP pour le meme email', function () {
    User::factory()->create([
        'email'    => 'victime@promeb.fr',
        'password' => bcrypt('le-vrai-mot-de-passe'),
    ]);

    // 5 échecs depuis une première IP.
    for ($i = 1; $i <= 5; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->postJson('/api/auth/login', ['email' => 'victime@promeb.fr', 'password' => 'mauvais'])
            ->assertStatus(422);
    }

    // Une IP différente sur le même email n'est pas bloquée : la clé (email+IP) change.
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
        ->postJson('/api/auth/login', ['email' => 'victime@promeb.fr', 'password' => 'mauvais'])
        ->assertStatus(422);
});

it('une connexion reussie remet le compteur a zero', function () {
    User::factory()->create([
        'email'    => 'victime@promeb.fr',
        'password' => bcrypt('le-bon-mot-de-passe'),
    ]);

    // 4 échecs.
    for ($i = 1; $i <= 4; $i++) {
        $this->postJson('/api/auth/login', ['email' => 'victime@promeb.fr', 'password' => 'mauvais'])
            ->assertStatus(422);
    }

    // Une réussite doit effacer l'ardoise.
    $this->postJson('/api/auth/login', ['email' => 'victime@promeb.fr', 'password' => 'le-bon-mot-de-passe'])
        ->assertNoContent();

    // Après quoi on doit pouvoir de nouveau échouer 5 fois sans être bloqué :
    // si la remise à zéro n'a pas eu lieu, la 2e de ces tentatives (6e cumulée) serait un 429.
    for ($i = 1; $i <= 5; $i++) {
        $this->postJson('/api/auth/login', ['email' => 'victime@promeb.fr', 'password' => 'mauvais'])
            ->assertStatus(422);
    }
});
