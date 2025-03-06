<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prestation extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_prestation',
        'nombre_heures',
        'adresse',
        'facture_id',
    ];

    protected $casts = [
        'date_prestation' => 'date',
        'nombre_heures'  => 'decimal:2',
    ];

    /**
     * Une prestation peut être rattachée à une facture.
     */
    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }
}
