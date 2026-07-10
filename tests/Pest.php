<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Construit un payload de prestation valide dont le client et le taux horaire
 * appartiennent à l'utilisateur donné (requis par PrestationRequest).
 */
function prestationPayload(\App\Models\User $user, array $overrides = []): array
{
    return array_merge([
        'date'            => now()->toDateString(),
        'heures'          => 5,
        'adresse'         => '123 rue du Test',
        'horaires'        => '10:00-12:00',
        'client_id'       => \App\Models\Client::factory()->create(['user_id' => $user->id])->id,
        'taux_horaire_id' => \App\Models\TauxHoraire::factory()->create(['user_id' => $user->id])->id,
    ], $overrides);
}
