<?php

namespace App\Services;

use App\Models\Prestation;
use Illuminate\Support\Facades\Auth;

class PrestationService extends BaseService
{
    public function getAll()
    {
        return $this->handleExceptions(function () {
            return Prestation::where('user_id', Auth::id())
                    ->with('client', 'tauxHoraire')
                    ->get();
        }, "Erreur lors de la récupération des prestations", "prestation");
    }

    public function create(array $data)
    {
        return $this->handleExceptions(function () use ($data) {
            $data['user_id'] = Auth::id();
            $prestation = Prestation::create($data);
            return $prestation->refresh()->load('client', 'tauxHoraire');
        }, "Erreur lors de la création de la prestation", "prestation");
    }

    public function update(Prestation $prestation, array $data)
    {
        return $this->handleExceptions(function () use ($prestation, $data) {
            $prestation->update($data);
            return $prestation->refresh()->load('client', 'tauxHoraire');
        }, "Erreur lors de la mise à jour de la prestation (ID: $prestation->id)", "prestation");
    }

    public function delete(Prestation $prestation)
    {
        return $this->handleExceptions(function () use ($prestation) {
            $prestation->delete();
            return $prestation;
        }, "Erreur lors de la suppression de la prestation (ID: $prestation->id)", "prestation");
    }
}
