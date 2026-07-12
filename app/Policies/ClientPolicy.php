<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClientPolicy
{
    /**
     * L'utilisateur peut mettre à jour un client **s'il en est le propriétaire**.
     */
    public function update(User $user, Client $client): bool
    {
        return $user->id === $client->user_id;
    }

    /**
     * L'utilisateur peut supprimer un client **s'il en est le propriétaire**
     * et **si aucune prestation ne lui est rattachée**.
     *
     * Sans ce garde-fou, la cascade de la base détruirait ses prestations —
     * y compris facturées, laissant des factures sans lignes dont le PDF échoue.
     */
    public function delete(User $user, Client $client): Response|bool
    {
        if ($user->id !== $client->user_id) {
            return false;
        }

        $nombre = $client->prestations()->count();

        if ($nombre > 0) {
            return Response::deny(
                "Ce client a {$nombre} prestation" . ($nombre > 1 ? 's' : '') . '. '
                . 'Supprimez-les avant de supprimer le client.'
            );
        }

        return true;
    }
}
