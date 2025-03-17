<?php

use App\Models\Prestation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('empêche les utilisateurs non connectés d\'accéder aux routes protégées', function () {
    $prestation = Prestation::factory()->create();

    $this->getJson(route('prestations.index'))->assertUnauthorized();
    $this->postJson(route('prestations.store'), [])->assertUnauthorized();
    $this->putJson(route('prestations.update', $prestation))->assertUnauthorized();
    $this->deleteJson(route('prestations.destroy', $prestation))->assertUnauthorized();
});

it('retourne une erreur 404 si la route n\'existe pas', function () {
    $this->getJson('/api/prestations-inexistantes')->assertNotFound();
});

it('autorise un utilisateur connecté à accéder aux routes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->getJson(route('prestations.index'))->assertStatus(200);
    $this->postJson(route('prestations.store'), [
        'date'      => now()->toDateString(),
        'heures'    => 5,
        'adresse'   => '123 rue du Test',
        'horaires'  => '10:00-12:00',
    ])->assertStatus(201);
});

it('vérifie qu\'une erreur 405 est retournée pour une mauvaise méthode', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->patchJson(route('prestations.index'))->assertStatus(405);
    $response = $this->call('GET', route('prestations.store'));

    $response->assertStatus(200) // Doit être valide
             ->assertJsonMissing(['message' => 'Prestation créée avec succès']); // store() ne doit pas être exécutée
});

it('récupère uniquement les prestations de l\'utilisateur connecté', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Création de prestations pour 2 utilisateurs différents
    Prestation::factory()->create(['user_id' => $user1->id]);
    Prestation::factory()->create(['user_id' => $user2->id]);

    $this->actingAs($user1)
        ->getJson(route('prestations.index'))
        ->assertStatus(200)
        ->assertJsonCount(1, 'prestations'); // Vérifie que seul user1 voit ses prestations
});

it('retourne une erreur 422 si les données sont invalides lors de la création', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->postJson(route('prestations.store'), [
        'date'      => '', // Date vide
        'heures'    => -3, // Heures négatives
        'adresse'   => '',
        'horaires'  => '',
    ])->assertStatus(422) // Vérifie que Laravel lève bien une erreur de validation
      ->assertJsonValidationErrors(['date', 'heures', 'adresse', 'horaires']);
});

it('retourne une erreur 422 si les données sont invalides lors de la mise à jour', function () {
    $user = User::factory()->create();
    $prestation = Prestation::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('prestations.update', $prestation), [
            'date'      => null,
            'heures'    => 'texte invalide',
            'adresse'   => '',
            'horaires'  => '',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['date', 'heures', 'adresse', 'horaires']);
});

it('retourne une erreur 404 si la prestation à supprimer n\'existe pas', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->deleteJson(route('prestations.destroy', 999)) // ID inexistant
        ->assertNotFound();
});

it('empêche un utilisateur de supprimer la prestation d\'un autre', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $prestation = Prestation::factory()->create(['user_id' => $user1->id]);

    $this->actingAs($user2)
        ->deleteJson(route('prestations.destroy', $prestation))
        ->assertForbidden(); // Devrait renvoyer 403
});

it('empêche un utilisateur de modifier la prestation d\'un autre', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $prestation = Prestation::factory()->create(['user_id' => $user1->id]);

    $this->actingAs($user2)
        ->putJson(route('prestations.update', $prestation), [
            'date'      => now()->toDateString(),
            'heures'    => 3,
            'adresse'   => 'Adresse modifiée',
            'horaires'  => '14:00-16:00',
        ])
        ->assertForbidden(); // Devrait renvoyer 403
});

it('renvoie les prestations au bon format', function () {
    $user = User::factory()->create();
    Prestation::factory()->count(2)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->getJson(route('prestations.index'))
        ->assertStatus(200)
        ->assertJsonStructure([
            'prestations' => [
                '*' => ['id', 'date', 'heures', 'adresse', 'horaires', 'created_at', 'updated_at']
            ]
        ]);
});

it('permet la mise à jour correcte d\'une prestation', function () {
    $user = User::factory()->create();
    $prestation = Prestation::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('prestations.update', $prestation), [
            'date'      => now()->addDay()->toDateString(),
            'heures'    => 4,
            'adresse'   => 'Nouvelle adresse',
            'horaires'  => '12:00-14:00',
        ])
        ->assertStatus(200)
        ->assertJson([
            'message' => 'Prestation mise à jour avec succès.',
            'prestation' => [
                'date' => now()->addDay()->toDateString(),
                'heures' => 4,
                'adresse' => 'Nouvelle adresse',
                'horaires' => '12:00-14:00',
            ]
        ]);
});
