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
