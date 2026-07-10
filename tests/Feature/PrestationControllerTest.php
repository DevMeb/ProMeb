<?php

use App\Models\Prestation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user); // Connexion d'un utilisateur
});

/**
 * 🔹 TEST: Récupérer les prestations de l'utilisateur connecté
 */
it('récupère les prestations de l\'utilisateur connecté', function () {
    // Création de prestations pour cet utilisateur et un autre utilisateur
    Prestation::factory()->create(['user_id' => $this->user->id]);
    Prestation::factory()->create(); // Autre utilisateur

    $response = $this->getJson(route('prestations.index'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'prestations' => [
                '*' => ['id', 'date', 'heures', 'adresse', 'horaires']
            ]
        ])
        ->assertJsonCount(1, 'prestations'); // Vérifie qu'il n'y a que les prestations du user connecté
});

/**
 * 🔹 TEST: Création d'une prestation avec des données valides
 */
it('crée une prestation avec des données valides', function () {
    $data = prestationPayload($this->user);

    $response = $this->postJson(route('prestations.store'), $data);

    $response->assertStatus(201)
        ->assertJson(['message' => 'Prestation créée avec succès.']);
    $this->assertDatabaseHas('prestations', array_merge($data, ['user_id' => $this->user->id]));
});

/**
 * 🔹 TEST: Échec de la création d'une prestation avec des données invalides
 */
it('retourne une erreur 422 si les données sont invalides lors de la création', function () {
    $response = $this->postJson(route('prestations.store'), [
        'date'      => '', // Vide
        'heures'    => -3, // Heures négatives
        'adresse'   => '',
        'horaires'  => '',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['date', 'heures', 'adresse', 'horaires']);
});

/**
 * 🔹 TEST: Mise à jour d'une prestation existante
 */
it('met à jour une prestation existante', function () {
    $prestation = Prestation::factory()->create(['user_id' => $this->user->id]);

    $updatedData = prestationPayload($this->user, [
        'date'      => now()->addDays(5)->toDateString(),
        'heures'    => 3,
        'adresse'   => '456 rue Modifiée',
        'horaires'  => '14:00-16:00',
    ]);

    $response = $this->putJson(route('prestations.update', $prestation), $updatedData);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Prestation mise à jour avec succès.']);
    $this->assertDatabaseHas('prestations', array_merge($updatedData, ['id' => $prestation->id]));
});

/**
 * 🔹 TEST: Échec de mise à jour d'une prestation qui n'appartient pas à l'utilisateur
 */
it('retourne une erreur 403 si l\'utilisateur tente de modifier une prestation qui ne lui appartient pas', function () {
    $autreUser = User::factory()->create();
    $prestation = Prestation::factory()->create(['user_id' => $autreUser->id]);

    $response = $this->putJson(route('prestations.update', $prestation), prestationPayload($this->user));

    $response->assertForbidden();
});

/**
 * 🔹 TEST: Suppression d'une prestation existante
 */
it('supprime une prestation existante', function () {
    $prestation = Prestation::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson(route('prestations.destroy', $prestation));

    $response->assertStatus(200)
        ->assertJson(['message' => 'Prestation supprimée avec succès.']);
    $this->assertDatabaseMissing('prestations', ['id' => $prestation->id]);
});

/**
 * 🔹 TEST: Échec de suppression d'une prestation qui n'appartient pas à l'utilisateur
 */
it('retourne une erreur 403 si l\'utilisateur tente de supprimer une prestation qui ne lui appartient pas', function () {
    $autreUser = User::factory()->create();
    $prestation = Prestation::factory()->create(['user_id' => $autreUser->id]);

    $response = $this->deleteJson(route('prestations.destroy', $prestation));

    $response->assertForbidden();
});

/**
 * 🔹 TEST: Échec de suppression d'une prestation inexistante
 */
it('retourne une erreur 404 si la prestation à supprimer n\'existe pas', function () {
    $response = $this->deleteJson(route('prestations.destroy', 999));

    $response->assertNotFound();
});
