<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TauxHoraire extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'taux', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prestations()
    {
        return $this->hasMany(Prestation::class);
    }
}
