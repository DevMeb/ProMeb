<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prestation extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'heures',
        'adresse',
        'horaires',
        'facture_id',
        'user_id',
        'client_id',
        'taux_horaire_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function tauxHoraire()
    {
        return $this->belongsTo(TauxHoraire::class);
    }

}
