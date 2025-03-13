<?php 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrestationRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation pour la requête.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date'      => ['required', 'date'],
            'heures'    => ['required', 'numeric', 'min:0'],
            'adresse'   => ['required', 'string', 'max:255'],
            'horaires'  => ['required', 'string', 'max:255'],
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()->id);
                }),
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés pour la validation.
     */
    public function messages(): array
    {
        return [
            'date.required'    => 'La date de la prestation est obligatoire.',
            'date.date'        => 'La date de la prestation doit être une date valide.',
            'heures.required'  => "Le nombre d'heures est obligatoire.",
            'heures.numeric'   => "Le nombre d'heures doit être un nombre.",
            'heures.min'       => "Le nombre d'heures doit être supérieur ou égal à 0.",
            'adresse.required' => "L'adresse est obligatoire.",
            'adresse.string'   => "L'adresse doit être une chaîne de caractères.",
            'adresse.max'      => "L'adresse ne doit pas dépasser 255 caractères.",
            'horaires.required'=> "Les horaires sont obligatoires.",
            'horaires.string'  => "Les horaires doivent être une chaîne de caractères.",
            'horaires.max'     => "Les horaires ne doivent pas dépasser 255 caractères.",
            'client_id.required' => 'Un client doit être sélectionné pour cette prestation.',
            'client_id.integer'  => 'Le client sélectionné est invalide.',
            'client_id.exists'   => 'Le client sélectionné n’existe pas ou ne vous appartient pas.',
        ];
    }
}
