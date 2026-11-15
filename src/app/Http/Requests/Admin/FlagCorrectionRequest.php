<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FlagCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'administrator';
    }

    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'max:20000'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan.required' => 'Catatan koreksi wajib diisi.',
            'alasan.string'   => 'Catatan koreksi harus berupa teks.',
            'alasan.max'      => 'Catatan koreksi maksimal 20.000 karakter.',
        ];
    }
}
