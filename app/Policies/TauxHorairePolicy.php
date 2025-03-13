<?php

namespace App\Policies;

use App\Models\TauxHoraire;
use App\Models\User;

class TauxHorairePolicy
{
    /**
     * L'utilisateur peut mettre à jour un taux horaire **s'il en est le propriétaire**.
     */
    public function update(User $user, TauxHoraire $tauxHoraire): bool
    {
        return $user->id === $tauxHoraire->user_id;
    }

    /**
     * L'utilisateur peut supprimer un taux horaire **s'il en est le propriétaire**.
     */
    public function delete(User $user, TauxHoraire $tauxHoraire): bool
    {
        return $user->id === $tauxHoraire->user_id;
    }
}

