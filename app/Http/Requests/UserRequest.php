<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UserRequest extends FormRequest
{
    /**
     * Autoriser uniquement l'utilisateur connecté.
     */
    public function authorize(): bool
    {
        return Auth::check(); // Vérifie que l'utilisateur est bien connecté
    }

    /**
     * Règles de validation.
     */
    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'prenom'       => 'nullable|string|max:255',
            'adresse'      => 'nullable|string|max:255',
            'code_postal'  => 'nullable|string|max:10',
            'ville'        => 'nullable|string|max:100',
            'pays'         => 'nullable|string|max:100',
            'telephone'    => 'nullable|string|max:20',
            'siren'        => 'nullable|digits:9',
            'nom_societe'  => 'nullable|string|max:255',
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'siren.digits'  => 'Le SIREN doit être un numéro de 9 chiffres.',
        ];
    }
}
