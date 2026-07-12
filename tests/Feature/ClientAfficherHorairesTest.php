<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('affiche les horaires par défaut (contrainte de la base) pour un client créé sans le champ', function () {
    // Insertion directe en base, sans passer par le modèle ni la factory (qui
    // fixent désormais explicitement afficher_horaires) : on prouve ainsi que
    // c'est bien le défaut de la colonne en base qui vaut true, pas une valeur
    // fournie par le code applicatif.
    $userId = User::factory()->create()->id;

    DB::table('clients')->insert([
        'nom'        => 'Client sans horaires précisés',
        'user_id'    => $userId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $client = Client::where('nom', 'Client sans horaires précisés')->first();

    expect($client->afficher_horaires)->toBeTrue();
});

it('permet de masquer les horaires sur un client', function () {
    $client = Client::factory()->create([
        'user_id'           => User::factory(),
        'afficher_horaires' => false,
    ]);

    expect($client->fresh()->afficher_horaires)->toBeFalse();
});

it('renvoie afficher_horaires à true dans la réponse quand le champ n\'est pas envoyé à la création', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/clients', [
        'nom' => 'Client par défaut',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('client.afficher_horaires', true);
    expect(Client::where('nom', 'Client par défaut')->first()->afficher_horaires)->toBeTrue();
});

it('persiste afficher_horaires à la création via l\'API', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/clients', [
        'nom'               => 'Client discret',
        'afficher_horaires' => false,
    ]);

    $response->assertCreated();
    $response->assertJsonPath('client.afficher_horaires', false);
    expect(Client::where('nom', 'Client discret')->first()->afficher_horaires)->toBeFalse();
});

it('persiste afficher_horaires à la mise à jour via l\'API', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson("/api/clients/{$client->id}", [
        'nom'               => $client->nom,
        'afficher_horaires' => false,
    ]);

    $response->assertOk();
    expect($client->fresh()->afficher_horaires)->toBeFalse();
});

it('conserve afficher_horaires à false quand la mise à jour ne l\'envoie pas', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create([
        'user_id'           => $user->id,
        'afficher_horaires' => false,
    ]);

    $response = $this->actingAs($user)->putJson("/api/clients/{$client->id}", [
        'nom' => 'Nouveau nom du client',
    ]);

    $response->assertOk();
    $clientActualise = $client->fresh();
    expect($clientActualise->nom)->toBe('Nouveau nom du client');
    expect($clientActualise->afficher_horaires)->toBeFalse();
});
