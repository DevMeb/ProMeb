<?php

namespace App\Policies;

use App\Models\TauxHoraire;
use App\Models\User;

class TauxHorairePolicy
{
    /**
     * L'utilisateur peut modifier un taux horaire **s'il en est le propriétaire**
     * et **tant qu'aucune facture ne s'appuie dessus** — sinon les lignes d'une
     * facture émise changeraient rétroactivement.
     */
    public function update(User $user, TauxHoraire $tauxHoraire): bool
    {
        if ($user->id !== $tauxHoraire->user_id) {
            return false;
        }

        return !$tauxHoraire->prestations()->whereNotNull('facture_id')->exists();
    }

    /**
     * L'utilisateur peut supprimer un taux horaire **s'il en est le propriétaire**
     * et **tant qu'aucune facture ne s'appuie dessus**.
     */
    public function delete(User $user, TauxHoraire $tauxHoraire): bool
    {
        if ($user->id !== $tauxHoraire->user_id) {
            return false;
        }

        return !$tauxHoraire->prestations()->whereNotNull('facture_id')->exists();
    }
}

