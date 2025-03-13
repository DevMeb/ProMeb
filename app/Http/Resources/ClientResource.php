<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transforme la ressource en un tableau.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'nom'         => $this->nom,
            'adresse'     => $this->adresse,
            'code_postal' => $this->code_postal,
            'ville'       => $this->ville,
            'pays'        => $this->pays,
            'siren'       => $this->siren,
            'created_at' => $this->created_at ? $this->created_at->format('d/m/Y H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('d/m/Y H:i:s') : null,
        ];
    }
}
