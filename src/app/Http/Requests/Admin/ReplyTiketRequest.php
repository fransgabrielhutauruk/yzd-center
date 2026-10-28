<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReplyTiketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pesan'    => ['required', 'string', 'max:20000'],
            'images'   => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pesan.required' => 'Pesan tanggapan admin tidak boleh kosong.',
            'pesan.max'      => 'Pesan tanggapan admin tidak boleh melebihi 20.000 karakter.',
            'images.*.image' => 'Setiap berkas lampiran harus berupa gambar.',
            'images.*.mimes' => 'Format gambar yang diperbolehkan adalah JPG, JPEG, PNG, atau WEBP.',
            'images.*.max'   => 'Ukuran masing-masing gambar tidak boleh melebihi 10 MB.',
        ];
    }
}
