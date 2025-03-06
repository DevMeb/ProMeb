<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            // Quantité totale d'heures (calculée dynamiquement lors de la création de la facture)
            $table->decimal('quantite_heures', 6, 2)->default(0);
            // Total HT calculé
            $table->decimal('total_ht', 10, 2)->default(0);
            // Optionnel : statut de paiement, utile pour des rapports ultérieurs (ex : chiffre d'affaires)
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
