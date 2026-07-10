<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TauxHoraire>
 */
class TauxHoraireFactory extends Factory
{
    public function definition(): array
    {
        return [
            'taux'    => $this->faker->numberBetween(20, 120),
            'user_id' => User::factory(),
        ];
    }
}
