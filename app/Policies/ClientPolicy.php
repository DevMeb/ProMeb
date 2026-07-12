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

        $nonFacturees = $client->prestations()->whereNull('facture_id')->count();
        $facturees = $client->prestations()->whereNotNull('facture_id')->count();

        if ($nonFacturees === 0 && $facturees === 0) {
            return true;
        }

        if ($facturees === 0) {
            return Response::deny(
                "Ce client a {$nonFacturees} prestation" . ($nonFacturees > 1 ? 's' : '') . ' non facturée'
                . ($nonFacturees > 1 ? 's' : '') . '. '
                . 'Supprimez-l' . ($nonFacturees > 1 ? 'es' : 'a') . ' avant de supprimer le client.'
            );
        }

        if ($nonFacturees === 0) {
            return Response::deny(
                "Ce client a {$facturees} prestation" . ($facturees > 1 ? 's' : '') . ' facturée'
                . ($facturees > 1 ? 's' : '') . '. '
                . 'Supprimez d\'abord la ou les factures correspondantes avant de supprimer le client.'
            );
        }

        return Response::deny(
            "Ce client a {$nonFacturees} prestation" . ($nonFacturees > 1 ? 's' : '') . ' non facturée'
            . ($nonFacturees > 1 ? 's' : '') . " et {$facturees} prestation" . ($facturees > 1 ? 's' : '')
            . ' facturée' . ($facturees > 1 ? 's' : '') . '. '
            . 'Supprimez d\'abord les prestations non facturées, ainsi que la ou les factures '
            . 'correspondantes aux prestations facturées, avant de supprimer le client.'
        );
    }
}
