<?php

use App\Models\Prestation;
use App\Models\User;
use App\Services\PrestationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(PrestationService::class);
});

it('récupère uniquement les prestations de l\'utilisateur connecté', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Prestation::factory()->create(['user_id' => $user1->id]);
    Prestation::factory()->create(['user_id' => $user2->id]);

    Auth::login($user1);
    $prestations = $this->service->getAll();

    expect($prestations)->toHaveCount(1);
    expect($prestations->first()->user_id)->toBe($user1->id);
});

it('crée une prestation avec des données valides', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $data = [
        'date'      => now()->toDateString(),
        'heures'    => 5,
        'adresse'   => '123 rue du Test',
        'horaires'  => '10:00-12:00',
    ];

    $prestation = $this->service->create($data);

    expect($prestation)->toBeInstanceOf(Prestation::class);
    expect($prestation->user_id)->toBe($user->id);
    expect($prestation->date->toDateString())->toBe($data['date']); // ✅ Fix ici
    expect($prestation->heures)->toBe($data['heures']);
    expect($prestation->adresse)->toBe($data['adresse']);
    expect($prestation->horaires)->toBe($data['horaires']);
});

it('met à jour une prestation existante', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $prestation = Prestation::factory()->create(['user_id' => $user->id]);

    $updatedData = [
        'date'      => now()->addDays(5)->toDateString(),
        'heures'    => 8,
        'adresse'   => 'Nouvelle adresse',
        'horaires'  => '12:00-14:00',
    ];

    $updatedPrestation = $this->service->update($prestation, $updatedData);

    expect($updatedPrestation->date->toDateString())->toBe($updatedData['date']); // ✅ Fix ici
    expect($updatedPrestation->heures)->toBe($updatedData['heures']);
    expect($updatedPrestation->adresse)->toBe($updatedData['adresse']);
    expect($updatedPrestation->horaires)->toBe($updatedData['horaires']);
});

it('supprime une prestation existante', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $prestation = Prestation::factory()->create(['user_id' => $user->id]);

    $this->service->delete($prestation);

    $this->assertDatabaseMissing('prestations', ['id' => $prestation->id]);
});

it('ne supprime pas une prestation inexistante et lève une exception', function () {
    $this->expectException(Illuminate\Database\Eloquent\ModelNotFoundException::class);

    $user = User::factory()->create();
    Auth::login($user);

    $this->service->delete(Prestation::findOrFail(999)); // ✅ Correction ici avec une instanciation explicite
});
