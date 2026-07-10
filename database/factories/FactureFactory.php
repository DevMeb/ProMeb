<?php

namespace Database\Factories;

use App\Enums\FactureStatut;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facture>
 */
class FactureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'heures_total'  => $this->faker->numberBetween(1, 40),
            'montant_total' => $this->faker->numberBetween(100, 5000),
            'statut'        => FactureStatut::EnAttentePaiement,
            'user_id'       => User::factory(),
        ];
    }
}
