<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom'         => $this->faker->company(),
            'adresse'     => $this->faker->streetAddress(),
            'code_postal' => $this->faker->postcode(),
            'ville'       => $this->faker->city(),
            'pays'        => 'France',
            'siren'       => (string) $this->faker->numberBetween(100000000, 999999999),
            'afficher_horaires' => true,
            'user_id'     => User::factory(),
        ];
    }
}
