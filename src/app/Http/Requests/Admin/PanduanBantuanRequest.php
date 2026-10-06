<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PanduanBantuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $articleId = $this->route('id') ?? $this->route('article')?->id;

        return [
            'kategori_id'  => ['required', 'exists:panduan_kategori,id'],
            'judul'        => ['required', 'string', 'max:255'],
            'slug'         => ['nullable', 'string', 'max:255', Rule::unique('panduan_bantuan', 'slug')->ignore($articleId)],
            'ringkasan'    => ['nullable', 'string', 'max:1000'],
            'konten'       => ['required', 'string', 'max:20000'],
            'video_url'    => ['nullable', 'url', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'kategori_id.required' => 'Kategori topik wajib dipilih.',
            'kategori_id.exists'   => 'Kategori topik yang dipilih tidak valid.',
            'judul.required'       => 'Judul artikel panduan wajib diisi.',
            'judul.max'            => 'Judul maksimal 255 karakter.',
            'konten.required'      => 'Konten artikel panduan wajib diisi.',
            'konten.max'           => 'Konten panduan maksimal 20.000 karakter.',
            'video_url.url'        => 'Format URL video tidak valid (gunakan link URL lengkap).',
        ];
    }
}
