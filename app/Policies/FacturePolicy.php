<?php

namespace App\Policies;

use App\Models\Facture;
use App\Models\User;

class FacturePolicy
{
    /**
     * L'utilisateur peut consulter une facture (ex: PDF) **s'il en est le propriétaire**.
     */
    public function view(User $user, Facture $facture): bool
    {
        return $user->id === $facture->user_id;
    }

    /**
     * L'utilisateur peut modifier une facture (ex: marquer payée) **s'il en est le propriétaire**.
     */
    public function update(User $user, Facture $facture): bool
    {
        return $user->id === $facture->user_id;
    }

    /**
     * L'utilisateur peut supprimer une facture **s'il en est le propriétaire**.
     */
    public function delete(User $user, Facture $facture): bool
    {
        return $user->id === $facture->user_id;
    }
}

