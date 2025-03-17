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
        Schema::create('prestations', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('heures');
            $table->string('horaires');
            $table->string('adresse');
            $table->foreignId('facture_id')->nullable()->constrained('factures')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('taux_horaire_id')->constrained('taux_horaires')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestations');
    }
};
