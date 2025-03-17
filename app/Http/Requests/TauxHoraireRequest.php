<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class TauxHoraireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'taux' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'taux.required' => 'Le taux est obligatoire',
            'taux.numeric' => 'Le taux doit être un nombre',
            'taux.min' => 'Le taux doit être supérieur à 0',
        ];
    }
}
