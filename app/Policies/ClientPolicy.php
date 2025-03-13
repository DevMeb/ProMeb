<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * L'utilisateur peut voir tous ses clients.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * L'utilisateur peut créer un client.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * L'utilisateur peut mettre à jour un client **s'il en est le propriétaire**.
     */
    public function update(User $user, Client $client): bool
    {
        return $user->id === $client->user_id;
    }

    /**
     * L'utilisateur peut supprimer un client **s'il en est le propriétaire**.
     */
    public function delete(User $user, Client $client): bool
    {
        return $user->id === $client->user_id;
    }
}
