<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
            'prestations'  => 'required|array',
            'prestations.*'=> 'integer|exists:prestations,id',
        ];
    }

    public function messages(): array
    {
        return [
            'prestations.required'  => 'La sélection de prestations est obligatoire.',
            'prestations.*.exists'    => 'Une ou plusieurs prestations sélectionnées n\'existent pas.',
        ];
    }
}
