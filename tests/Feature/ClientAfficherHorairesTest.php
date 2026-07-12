<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('affiche les horaires par défaut pour un client créé sans le champ', function () {
    $client = Client::factory()->create(['user_id' => User::factory()]);

    expect($client->fresh()->afficher_horaires)->toBeTrue();
});

it('permet de masquer les horaires sur un client', function () {
    $client = Client::factory()->create([
        'user_id'           => User::factory(),
        'afficher_horaires' => false,
    ]);

    expect($client->fresh()->afficher_horaires)->toBeFalse();
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
