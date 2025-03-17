<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prestation>
 */
class PrestationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date'      => $this->faker->date(),
            'heures'    => $this->faker->numberBetween(1, 10),
            'adresse'   => $this->faker->address(),
            'horaires'  => $this->faker->time(),
            'user_id'   => User::factory(),
        ];
    }
}
