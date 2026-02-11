<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchPlansRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'profile' => ['required', 'string', 'in:pme,adesao,cpf'],
            'lives' => ['required', 'array'],
            'hospital' => ['nullable', 'string'],
            'hospitalId' => ['nullable', 'integer'],
            'regiao' => ['nullable', 'integer'],
        ];
    }
}
