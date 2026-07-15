<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('refuse un mot de passe de moins de 12 caracteres', function () {
    $this->artisan('user:create', [
        'name'     => 'Court',
        'email'    => 'court@promeb.fr',
        'password' => 'court',
    ])->assertFailed();

    expect(User::where('email', 'court@promeb.fr')->exists())->toBeFalse();
});

it('accepte un mot de passe de 12 caracteres ou plus', function () {
    $this->artisan('user:create', [
        'name'     => 'Correct',
        'email'    => 'correct@promeb.fr',
        'password' => 'motdepasse-solide',
    ])->assertExitCode(0);

    expect(User::where('email', 'correct@promeb.fr')->exists())->toBeTrue();
});
