<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class UploadBuktiMasterFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'bukti_transfer' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'bukti_transfer.required' => 'Bukti transfer wajib diunggah.',
            'bukti_transfer.image'    => 'Berkas bukti transfer harus berupa gambar.',
            'bukti_transfer.mimes'    => 'Format gambar harus berupa jpeg, png, jpg, atau webp.',
            'bukti_transfer.max'      => 'Ukuran bukti transfer maksimal 10MB.',
        ];
    }
}
