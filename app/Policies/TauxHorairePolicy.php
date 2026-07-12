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
    public function update(User $user, TauxHoraire $tauxHoraire): bool
    {
        if ($user->id !== $tauxHoraire->user_id) {
            return false;
        }

        return !$tauxHoraire->prestations()->whereNotNull('facture_id')->exists();
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

        $nombre = $tauxHoraire->prestations()->count();

        if ($nombre > 0) {
            return Response::deny(
                "Ce taux horaire est utilisé par {$nombre} prestation" . ($nombre > 1 ? 's' : '') . '. '
                . 'Modifiez leur taux ou supprimez-les avant de le supprimer.'
            );
        }

        return true;
    }
}

