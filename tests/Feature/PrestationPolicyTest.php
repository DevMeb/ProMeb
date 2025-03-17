<?php

use App\Models\Prestation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('autorise un utilisateur à voir ses propres prestations même si la liste est vide', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('prestations.index'))
        ->assertStatus(200)
        ->assertJson(['prestations' => [], 'message' => 'Liste des prestations récupérée avec succès.']);
});

it('interdit un utilisateur de voir les prestations d\'un autre', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Prestation::factory()->create(['user_id' => $user1->id]);

    $this->actingAs($user2)
        ->getJson(route('prestations.index'))
        ->assertStatus(200)
        ->assertJson(['prestations' => [], 'message' => 'Liste des prestations récupérée avec succès.']);
});


it('autorise un utilisateur à créer une prestation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('prestations.store'), [
            'date'      => now()->toDateString(),
            'heures'    => 5,
            'adresse'   => '123 rue du Test',
            'horaires'  => '10:00-12:00',
        ])
        ->assertCreated()
        ->assertJson(['message' => 'Prestation créée avec succès.']);
});


it('autorise un utilisateur de modifier une prestation qui lui appartient ', function () {
    $user1 = User::factory()->create();
    $prestation = Prestation::factory()->create(['user_id' => $user1->id]);

    $this->actingAs($user1)
        ->putJson(route('prestations.update', $prestation), [
            'date'      => now()->toDateString(),
            'heures'    => 3,
            'adresse'   => '456 rue Modifiée',
            'horaires'  => '14:00-16:00',
        ])
        ->assertStatus(200)
        ->assertJson(['message' => 'Prestation mise à jour avec succès.']);
});

it('interdit un utilisateur de modifier une prestation qui ne lui appartient pas', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $prestation = Prestation::factory()->create(['user_id' => $user1->id]);

    $this->actingAs($user2)
        ->putJson(route('prestations.update', $prestation), [
            'date'      => now()->toDateString(),
            'heures'    => 3,
            'adresse'   => '456 rue Modifiée',
            'horaires'  => '14:00-16:00',
        ])
        ->assertForbidden()  // ✅ Plus lisible
        ->assertJson(['message' => 'This action is unauthorized.']);
});

it('autorise un utilisateur à supprimer sa propre prestation', function () {
    $user = User::factory()->create();
    $prestation = Prestation::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->deleteJson(route('prestations.destroy', $prestation))
        ->assertStatus(200)
        ->assertJson(['message' => 'Prestation supprimée avec succès.']);
});

it('interdit un utilisateur de supprimer la prestation d\'un autre', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $prestation = Prestation::factory()->create(['user_id' => $user1->id]);

    $this->actingAs($user2)
        ->deleteJson(route('prestations.destroy', $prestation))
        ->assertForbidden()
        ->assertJson(['message' => 'This action is unauthorized.']);
});

it('interdit un utilisateur non authentifié d\'accéder aux prestations', function () {
    $this->getJson(route('prestations.index'))
        ->assertUnauthorized() // ✅ Vérifie qu'il reçoit un 401 Unauthorized
        ->assertJson(['message' => 'Unauthenticated.']); // Vérifie le message Laravel
});
