<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'adresse',
        'code_postal',
        'ville',
        'pays',
        'siren',
        'afficher_horaires',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'afficher_horaires' => 'boolean',
        ];
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
