<?php

namespace App\Policies;

use App\Models\Prestation;
use App\Models\User;

class PrestationPolicy
{
    /**
     * L'utilisateur peut mettre à jour une prestation **s'il en est le propriétaire**.
     */
    public function update(User $user, Prestation $prestation): bool
    {
        return $user->id === $prestation->user_id;
    }

    /**
     * L'utilisateur peut supprimer une prestation **s'il en est le propriétaire**.
     */
    public function delete(User $user, Prestation $prestation): bool
    {
        return $user->id === $prestation->user_id;
    }
}

