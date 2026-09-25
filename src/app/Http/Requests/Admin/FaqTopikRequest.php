<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FaqTopikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'nama'      => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:20000'],
            'urutan'    => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama topik FAQ wajib diisi.',
            'nama.max'      => 'Nama topik FAQ maksimal 100 karakter.',
            'deskripsi.max' => 'Deskripsi maksimal 20.000 karakter.',
        ];
    }
}
