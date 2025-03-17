<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nom'         => 'required|string|max:255',
            'adresse'     => 'nullable|string|max:255',
            'code_postal' => 'nullable|string|max:20',
            'ville'       => 'nullable|string|max:100',
            'pays'        => 'nullable|string|max:100',
            'siren'       => 'nullable|regex:/^\d{9}(\d{5})?$/',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'        => 'Le nom du client est obligatoire.',
            'nom.string'          => 'Le nom doit être une chaîne de caractères.',
            'nom.max'             => 'Le nom ne doit pas dépasser 255 caractères.',
            'siren.regex'  => 'Le SIREN doit être un numéro de 9 chiffres ou de 14 chiffres (siret).',
        ];
    }
}
