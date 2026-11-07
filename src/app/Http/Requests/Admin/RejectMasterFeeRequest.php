<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectMasterFeeRequest extends FormRequest
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
            'alasan.required' => 'Alasan penolakan bukti pembayaran wajib diisi.',
            'alasan.string'   => 'Alasan penolakan harus berupa teks.',
            'alasan.max'      => 'Alasan penolakan maksimal 20.000 karakter.',
        ];
    }
}
