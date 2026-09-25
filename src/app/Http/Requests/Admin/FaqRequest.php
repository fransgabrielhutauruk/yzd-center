<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'topik_id'   => ['required', 'integer', 'exists:edukasi_faq_topik,id'],
            'pertanyaan' => ['required', 'string', 'max:255'],
            'jawaban'    => ['required', 'string', 'max:20000'],
            'urutan'     => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'topik_id.required'   => 'Topik FAQ wajib dipilih.',
            'topik_id.exists'     => 'Topik FAQ yang dipilih tidak valid.',
            'pertanyaan.required' => 'Pertanyaan FAQ wajib diisi.',
            'pertanyaan.max'      => 'Pertanyaan FAQ maksimal 255 karakter.',
            'jawaban.required'    => 'Jawaban FAQ wajib diisi.',
            'jawaban.max'         => 'Jawaban FAQ maksimal 20.000 karakter.',
        ];
    }
}
