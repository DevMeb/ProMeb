<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Supprime explicitement les prestations de l'utilisateur avant que la
     * cascade native de la base ne s'attaque à ses clients, taux horaires
     * et factures.
     *
     * Depuis la migration 2026_07_12_134733, prestations.client_id et
     * prestations.taux_horaire_id sont en RESTRICT (défense en profondeur
     * contre les suppressions de client/taux horaire encore utilisés).
     * Mais users.id reste référencé en CASCADE direct par clients.user_id,
     * taux_horaires.user_id, factures.user_id ET prestations.user_id.
     *
     * Sans cette étape, `DELETE FROM users WHERE id = ?` déclenche des
     * cascades concurrentes vers clients/taux_horaires (CASCADE) et vers
     * prestations (CASCADE) : si InnoDB traite un client ou un taux
     * horaire avant la prestation qui le référence encore, la contrainte
     * RESTRICT de prestations bloque toute l'opération (SQLSTATE 23000 /
     * erreur 1451) — reproduit sur MySQL, invisible sur SQLite où l'ordre
     * de résolution diffère.
     *
     * En vidant d'abord prestations pour cet utilisateur, plus aucune
     * ligne ne référence ses clients ou ses taux horaires : la cascade
     * native sur clients, taux_horaires, factures puis users peut ensuite
     * s'exécuter sans obstacle. prestations.facture_id est en
     * nullOnDelete et ne bloque de toute façon jamais une suppression.
     *
     * IMPORTANT : cette méthode n'ouvre volontairement AUCUNE transaction.
     * `Model::delete()` ne s'exécute pas lui-même dans une transaction : il
     * déclenche l'event `deleting` (donc cette méthode), PUIS exécute le
     * `DELETE` du user. Une transaction ouverte ici committerait dès la fin
     * de cette méthode, indépendamment du succès de la suppression du user
     * qui suit — les prestations seraient perdues même si la suppression du
     * user échoue ensuite. L'atomicité est garantie par l'appelant : voir
     * `App\Actions\DeleteUser`, le seul point d'entrée à utiliser pour
     * supprimer un compte.
     */
    public function deleting(User $user): void
    {
        $user->prestations()->delete();
    }
}
