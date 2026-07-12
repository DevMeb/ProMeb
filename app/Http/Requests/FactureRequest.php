<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FactureRequest extends FormRequest
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
            'prestations'   => 'required|array',
            'prestations.*' => [
                'integer',
                // La prestation doit appartenir à l'utilisateur ET être libre :
                // refacturer une prestation déjà rattachée viderait sa facture d'origine.
                Rule::exists('prestations', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()->id)
                          ->whereNull('facture_id');
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'prestations.required' => 'La sélection de prestations est obligatoire.',
            'prestations.*.exists' => 'Une ou plusieurs prestations sélectionnées n\'existent pas ou sont déjà rattachées à une facture.',
        ];
    }
}
