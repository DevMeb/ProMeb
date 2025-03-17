<?php

namespace App\Policies;

use App\Models\TauxHoraire;
use App\Models\User;

class TauxHorairePolicy
{
    public function update(User $user, TauxHoraire $tauxHoraire): bool
    {
        return !$tauxHoraire->prestations()->whereNotNull('facture_id')->exists();
    }

    public function delete(User $user, TauxHoraire $tauxHoraire): bool
    {
        return !$tauxHoraire->prestations()->whereNotNull('facture_id')->exists();
    }
}

