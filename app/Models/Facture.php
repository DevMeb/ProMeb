<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'quantite_heures',
        'total_ht',
        'is_paid',
    ];

    protected $casts = [
        'quantite_heures' => 'decimal:2',
        'total_ht'        => 'decimal:2',
        'is_paid'         => 'boolean',
    ];

    /**
     * Une facture a plusieurs prestations.
     */
    public function prestations()
    {
        return $this->hasMany(Prestation::class);
    }

    /**
     * Exemple de méthode pour recalculer la quantité totale d'heures et le total HT.
     * Vous pouvez l'appeler lors de la création ou mise à jour d'une facture.
     */
    public function recalculerTotaux($tauxHoraire)
    {
        $totalHeures = $this->prestations()->sum('nombre_heures');
        $totalHT = $totalHeures * $tauxHoraire;
        $this->quantite_heures = $totalHeures;
        $this->total_ht = $totalHT;
        $this->save();
    }
}
