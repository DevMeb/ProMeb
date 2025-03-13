<?php

use App\Http\Resources\PrestationResource;
use App\Models\Prestation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('retourne les champs attendus pour une prestation', function () {
    $user = User::factory()->create();
    $prestation = Prestation::factory()->create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'heures' => 4,
        'adresse' => '123 Rue Test',
        'horaires' => '14:00-18:00',
    ]);

    $resource = (new PrestationResource($prestation))->resolve();

    expect($resource)->toMatchArray([
        'id' => $prestation->id,
        'date' => $prestation->date->toDateString(),
        'heures' => $prestation->heures,
        'adresse' => $prestation->adresse,
        'horaires' => $prestation->horaires,
    ]);
});
