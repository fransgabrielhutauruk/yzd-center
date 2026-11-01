<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class StoreTiketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'member';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subjek'   => ['required', 'string', 'max:255'],
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
            'subjek.required' => 'Subjek tiket wajib diisi.',
            'subjek.max'      => 'Subjek tiket tidak boleh lebih dari 255 karakter.',
            'pesan.required'  => 'Pesan deskripsi kendala wajib diisi.',
            'pesan.max'       => 'Pesan deskripsi kendala tidak boleh lebih dari 20.000 karakter.',
            'images.*.image'  => 'Setiap berkas lampiran harus berupa gambar.',
            'images.*.mimes'  => 'Format gambar yang diperbolehkan adalah JPG, JPEG, PNG, atau WEBP.',
            'images.*.max'    => 'Ukuran masing-masing gambar tidak boleh melebihi 10 MB.',
        ];
    }
}
