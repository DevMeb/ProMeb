<?php

namespace App\Policies;

use App\Models\Prestation;
use App\Models\User;

class PrestationPolicy
{
    /**
     * L'utilisateur peut mettre à jour une prestation **s'il en est le propriétaire**
     * et **tant qu'elle n'est pas déjà rattachée à une facture**.
     */
    public function update(User $user, Prestation $prestation): bool
    {
        return $user->id === $prestation->user_id && !$prestation->facture_id;
    }

    /**
     * L'utilisateur peut supprimer une prestation **s'il en est le propriétaire**
     * et **tant qu'elle n'est pas déjà rattachée à une facture**.
     */
    public function delete(User $user, Prestation $prestation): bool
    {
        return $user->id === $prestation->user_id && !$prestation->facture_id;
    }
}

