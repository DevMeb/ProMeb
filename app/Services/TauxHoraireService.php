<?php

namespace App\Services;

use App\Models\TauxHoraire;
use Illuminate\Support\Facades\Auth;

class TauxHoraireService extends BaseService
{
    public function getAll()
    {
        return $this->handleExceptions(function () {
            return TauxHoraire::where('user_id', Auth::id())->get();
        }, "Erreur lors de la récupération des taux horaires", "taux_horaire");
    }

    public function create(array $data)
    {
        return $this->handleExceptions(function () use ($data) {
            $data['user_id'] = Auth::id();
            $tauxHoraire = TauxHoraire::create($data);
            return $tauxHoraire->refresh();
        }, "Erreur lors de la création du taux horaire", "taux_horaire");
    }

    public function update(tauxHoraire $tauxHoraire, array $data)
    {
        return $this->handleExceptions(function () use ($tauxHoraire, $data) {
            $tauxHoraire->update($data);
            return $tauxHoraire->refresh();
        }, "Erreur lors de la mise à jour du taux horaire (ID: $tauxHoraire->id)", "taux_horaire");
    }

    public function delete(tauxHoraire $tauxHoraire)
    {
        return $this->handleExceptions(function () use ($tauxHoraire) {
            $tauxHoraire->delete();
            return $tauxHoraire;
        }, "Erreur lors de la suppression du taux horaire (ID: $tauxHoraire->id)", "taux_horaire");
    }
}
