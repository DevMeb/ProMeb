<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('verifie que apres logout la route protegee repond 401', function () {
    config(['sanctum.stateful' => ['testserver']]);

    $user = User::factory()->create([
        'email' => 'test@promeb.fr',
        'password' => bcrypt('secret123'),
    ]);

    // Login réel (pas actingAs) pour obtenir un vrai cookie de session
    $login = $this->withHeaders(['Origin' => 'http://testserver'])
        ->postJson('/api/auth/login', [
            'email' => 'test@promeb.fr',
            'password' => 'secret123',
        ]);
    $login->assertNoContent();

    // Route protégée accessible avec le cookie de session
    $before = $this->withHeaders(['Origin' => 'http://testserver'])->getJson('/api/user');
    $before->assertStatus(200);

    // Logout
    $logout = $this->withHeaders(['Origin' => 'http://testserver'])->postJson('/api/auth/logout');
    $logout->assertNoContent();

    // Le guard sanctum/web mémoïse l'utilisateur résolu au sein du process PHPUnit
    // (RequestGuard::user() / AuthManager). Sans ce reset, l'appel suivant renverrait
    // l'utilisateur mis en cache avant le logout, masquant une éventuelle régression.
    // En production, chaque requête est un process séparé : aucune mémoïsation partagée.
    $this->app['auth']->forgetGuards();

    // Route protégée doit maintenant échouer
    $after = $this->withHeaders(['Origin' => 'http://testserver'])->getJson('/api/user');
    $after->assertStatus(401);
});
