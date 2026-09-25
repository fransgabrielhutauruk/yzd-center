<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GlosariumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'istilah'  => ['required', 'string', 'max:100'],
            'definisi' => ['required', 'string', 'max:20000'],
        ];
    }

    public function messages(): array
    {
        return [
            'istilah.required'  => 'Istilah wajib diisi.',
            'istilah.max'       => 'Istilah maksimal 100 karakter.',
            'definisi.required' => 'Definisi istilah wajib diisi.',
            'definisi.max'      => 'Definisi maksimal 20.000 karakter.',
        ];
    }
}
