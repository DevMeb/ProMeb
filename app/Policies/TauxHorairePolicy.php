<?php

namespace App\Policies;

use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TauxHorairePolicy
{
    /**
     * L'utilisateur peut modifier un taux horaire **s'il en est le propriétaire**
     * et **tant qu'aucune facture ne s'appuie dessus** — sinon les lignes d'une
     * facture émise changeraient rétroactivement.
     */
    public function update(User $user, TauxHoraire $tauxHoraire): Response|bool
    {
        // La propriété d'abord, et en `false` muet : le message de refus
        // ci-dessous ne doit jamais renseigner un intrus sur l'usage que le
        // propriétaire fait de ce taux horaire.
        if ($user->id !== $tauxHoraire->user_id) {
            return false;
        }

        if ($tauxHoraire->prestations()->whereNotNull('facture_id')->exists()) {
            return Response::deny(
                'Ce taux horaire est utilisé par au moins une prestation facturée : ses valeurs ne '
                . 'peuvent plus être modifiées, cela changerait rétroactivement une facture déjà émise. '
                . 'Supprimez d\'abord la ou les factures correspondantes si vous devez le modifier.'
            );
        }

        return true;
    }

    /**
     * L'utilisateur peut supprimer un taux horaire **s'il en est le propriétaire**
     * et **si aucune prestation ne l'utilise** — facturée ou non.
     *
     * Sans ce garde-fou, la cascade de la base détruirait silencieusement les
     * prestations non facturées qui s'appuient sur ce taux.
     */
    public function delete(User $user, TauxHoraire $tauxHoraire): Response|bool
    {
        // La propriété d'abord : le message de refus ci-dessous ne doit jamais
        // renseigner un intrus sur le volume d'activité d'autrui.
        if ($user->id !== $tauxHoraire->user_id) {
            return false;
        }

        $nonFacturees = $tauxHoraire->prestations()->whereNull('facture_id')->count();
        $facturees = $tauxHoraire->prestations()->whereNotNull('facture_id')->count();

        if ($nonFacturees === 0 && $facturees === 0) {
            return true;
        }

        if ($facturees === 0) {
            $leurTaux = $nonFacturees > 1 ? 'leur taux' : 'son taux';

            return Response::deny(
                "Ce taux horaire est utilisé par {$nonFacturees} prestation" . ($nonFacturees > 1 ? 's' : '')
                . ' non facturée' . ($nonFacturees > 1 ? 's' : '') . '. '
                . "Modifiez {$leurTaux} ou supprimez-l" . ($nonFacturees > 1 ? 'es' : 'a')
                . ' avant de supprimer ce taux horaire.'
            );
        }

        if ($nonFacturees === 0) {
            return Response::deny(
                "Ce taux horaire est utilisé par {$facturees} prestation" . ($facturees > 1 ? 's' : '')
                . ' facturée' . ($facturees > 1 ? 's' : '') . '. '
                . 'Supprimez d\'abord la ou les factures correspondantes avant de supprimer ce taux horaire.'
            );
        }

        return Response::deny(
            "Ce taux horaire est utilisé par {$nonFacturees} prestation" . ($nonFacturees > 1 ? 's' : '')
            . ' non facturée' . ($nonFacturees > 1 ? 's' : '') . " et {$facturees} prestation"
            . ($facturees > 1 ? 's' : '') . ' facturée' . ($facturees > 1 ? 's' : '') . '. '
            . 'Modifiez le taux des prestations non facturées ou supprimez-les, et supprimez d\'abord '
            . 'la ou les factures correspondantes aux prestations facturées, avant de supprimer ce taux horaire.'
        );
    }
}

