<?php

namespace App\Policies;

use App\Models\Facture;
use App\Models\User;

class FacturePolicy
{
    /**
     * L'utilisateur peut supprimer une facture **s'il en est le propriétaire**.
     */
    public function delete(User $user, Facture $facture): bool
    {
        return $user->id === $facture->user_id;
    }
}

