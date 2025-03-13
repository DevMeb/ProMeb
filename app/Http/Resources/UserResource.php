<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transforme les données en format API.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'prenom'      => $this->prenom,
            'email'       => $this->email,
            'adresse'     => $this->adresse,
            'code_postal' => $this->code_postal,
            'ville'       => $this->ville,
            'pays'        => $this->pays,
            'telephone'   => $this->telephone,
            'siren'       => $this->siren,
            'nom_societe' => $this->nom_societe,
            'created_at'  => $this->created_at ? $this->created_at->format('d/m/Y H:i:s') : null,
            'updated_at'  => $this->updated_at ? $this->updated_at->format('d/m/Y H:i:s') : null,
        ];
    }
}
