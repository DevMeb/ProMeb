<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FactureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'heures_total'   => $this->heures_total,
            'montant_total'  => $this->montant_total,
            'statut'         => $this->statut,
            'envoye_le'      => $this->envoye_le,
            'paye_le'        => $this->paye_le ? $this->paye_le->format('d/m/Y') : null,
            'prestations'    => PrestationResource::collection($this->whenLoaded('prestations')),
            'created_at'     => $this->created_at ? $this->created_at->format('d/m/Y H:i:s') : null,
            'updated_at'     => $this->updated_at ? $this->updated_at->format('d/m/Y H:i:s') : null,
        ];
    }
}
