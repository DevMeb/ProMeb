<?php

namespace App\Models;

use App\Enums\FactureStatut;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'heures_total',
        'montant_total',
        'statut',
        'paye_le',
        'envoye_le',
        'user_id',
    ];

    protected $casts = [
        'paye_le' => 'date:Y-m-d',
    ];

    protected function casts(): array
    {
        return [
            'statut' => FactureStatut::class,
        ];
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('d/m/Y');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prestations()
    {
        return $this->hasMany(Prestation::class);
    }
}
