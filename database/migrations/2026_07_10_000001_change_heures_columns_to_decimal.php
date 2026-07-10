<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Passe les colonnes d'heures et de montants d'entier à décimal pour
     * permettre les fractions d'heure (ex. 6,25 h). Conversion sans perte :
     * les valeurs entières existantes deviennent x.00.
     */
    public function up(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->decimal('heures', 6, 2)->change();
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->decimal('heures_total', 8, 2)->default(0)->change();
            $table->decimal('montant_total', 10, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->integer('heures')->change();
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->integer('heures_total')->default(0)->change();
            $table->integer('montant_total')->default(0)->change();
        });
    }
};
