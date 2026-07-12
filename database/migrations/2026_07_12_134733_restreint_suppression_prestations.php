<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Empêche la base de détruire des prestations en cascade.
     *
     * `client_id` et `taux_horaire_id` étaient en CASCADE : supprimer un client
     * ou un taux horaire détruisait silencieusement ses prestations — y compris
     * facturées. Les policies refusent désormais ces suppressions ; la base doit
     * refuser aussi, pour tout ce qui ne passe pas par elles (commandes artisan,
     * seeders, futurs endpoints).
     *
     * `user_id` reste en CASCADE : supprimer un compte doit bien tout emporter.
     */
    public function up(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();

            $table->dropForeign(['taux_horaire_id']);
            $table->foreign('taux_horaire_id')->references('id')->on('taux_horaires')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();

            $table->dropForeign(['taux_horaire_id']);
            $table->foreign('taux_horaire_id')->references('id')->on('taux_horaires')->cascadeOnDelete();
        });
    }
};
