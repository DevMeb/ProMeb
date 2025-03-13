<?php

use App\Models\Prestation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('empêche de créer une prestation avec des données invalides', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->postJson(route('prestations.store'), [
        'date'      => '', // Champ vide
        'heures'    => -3, // Valeur négative
        'adresse'   => '',
        'horaires'  => '',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['date', 'heures', 'adresse', 'horaires']);
});

it('empêche de mettre à jour une prestation avec des données invalides', function () {
    $user = User::factory()->create();
    $prestation = Prestation::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('prestations.update', $prestation), [
            'date'      => null,
            'heures'    => 'texte invalide', // Doit être un entier
            'adresse'   => '',
            'horaires'  => '',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['date', 'heures', 'adresse', 'horaires']);
});

it('empêche de créer une prestation sans être authentifié', function () {
    $this->postJson(route('prestations.store'), [
        'date'      => now()->toDateString(),
        'heures'    => 5,
        'adresse'   => '123 rue du Test',
        'horaires'  => '10:00-12:00',
    ])->assertUnauthorized(); // Renvoie 401 si non connecté
});

it('autorise la création d\'une prestation avec des données valides', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->postJson(route('prestations.store'), [
        'date'      => now()->toDateString(),
        'heures'    => 5,
        'adresse'   => '123 rue du Test',
        'horaires'  => '10:00-12:00',
    ])->assertCreated()
      ->assertJsonStructure(['message', 'prestation']);
});

it('autorise la mise à jour d\'une prestation avec des données valides', function () {
    $user = User::factory()->create();
    $prestation = Prestation::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('prestations.update', $prestation), [
            'date'      => now()->addDays(5)->toDateString(),
            'heures'    => 8,
            'adresse'   => 'Nouvelle adresse',
            'horaires'  => '12:00-14:00',
        ])
        ->assertOk()
        ->assertJsonStructure(['message', 'prestation']);
});
