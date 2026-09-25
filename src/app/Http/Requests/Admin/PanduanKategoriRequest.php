<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PanduanKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $categoryId = $this->route('id') ?? $this->route('category')?->id;

        return [
            'nama'      => ['required', 'string', 'max:100'],
            'slug'      => ['nullable', 'string', 'max:100', Rule::unique('panduan_kategori', 'slug')->ignore($categoryId)],
            'deskripsi' => ['nullable', 'string', 'max:20000'],
            'icon'      => ['nullable', 'string', 'max:50'],
            'urutan'    => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kategori bantuan wajib diisi.',
            'nama.max'      => 'Nama kategori maksimal 100 karakter.',
            'slug.unique'   => 'Slug kategori ini sudah digunakan.',
            'deskripsi.max' => 'Deskripsi maksimal 20.000 karakter.',
        ];
    }
}
