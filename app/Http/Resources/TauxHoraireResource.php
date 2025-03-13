<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TauxHoraireResource extends JsonResource
{
    /**
     * Transforme les données en format API.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'taux'        => $this->taux,
            'created_at'  => $this->created_at ? $this->created_at->format('d/m/Y H:i:s') : null,
            'updated_at'  => $this->updated_at ? $this->updated_at->format('d/m/Y H:i:s') : null,
        ];
    }
}
