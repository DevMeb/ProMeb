<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrestationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'date'      => $this->date->format('Y-m-d'),
            'heures'    => $this->heures,
            'horaires'  => $this->horaires,
            'adresse'   => $this->adresse,
            'client'    => new ClientResource($this->whenLoaded('client')),
            'client_id' => $this->client_id,
            'taux_horaire' => new TauxHoraireResource($this->whenLoaded('tauxHoraire')),
            'taux_horaire_id' => $this->taux_horaire_id,
            'facture_id'      => $this->facture_id,
            'created_at' => $this->created_at ? $this->created_at->format('d/m/Y H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('d/m/Y H:i:s') : null,
        ];
    }
}
