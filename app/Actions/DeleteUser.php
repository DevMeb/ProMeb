<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteUser
{
    /**
     * Point d'entrée UNIQUE pour supprimer un compte utilisateur.
     *
     * Ne jamais supprimer un `User` autrement (ni `$user->delete()` isolé, ni
     * `User::where(...)->delete()`, ni tout autre delete de query builder) :
     *
     * - `UserObserver::deleting()` vide d'abord les prestations de
     *   l'utilisateur, faute de quoi la cascade native de la base peut
     *   tenter de supprimer un client ou un taux horaire avant la prestation
     *   qui le référence encore (RESTRICT) et échouer sur MySQL/InnoDB
     *   (SQLSTATE 23000 / erreur 1451) — invisible sur SQLite. Or les
     *   événements Eloquent (`deleting`, donc l'observer) ne se déclenchent
     *   que sur `Model::delete()` / `Model::destroy()`, jamais sur un
     *   `delete()` de query builder.
     * - `Model::delete()` ne s'exécute lui-même dans aucune transaction :
     *   l'event `deleting` part, PUIS le `DELETE` s'exécute. Sans la
     *   transaction ouverte ici, un échec de la suppression du `User` après
     *   coup (veto d'un autre listener, exception, deadlock…) laisserait les
     *   prestations déjà détruites par l'observer alors que le compte, lui,
     *   existe toujours. Cette action rend l'ensemble atomique : soit tout
     *   est supprimé, soit rien ne l'est.
     */
    public function execute(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->delete();
        });
    }
}
