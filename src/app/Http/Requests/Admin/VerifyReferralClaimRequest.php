<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VerifyReferralClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status'        => ['required', 'string', 'in:approved,rejected'],
            'catatan_admin' => ['nullable', 'string', 'max:20000', 'required_if:status,rejected'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required'           => 'Keputusan verifikasi klaim wajib dipilih.',
            'status.in'                 => 'Pilihan keputusan harus disetujui atau ditolak.',
            'catatan_admin.required_if' => 'Catatan alasan penolakan wajib diisi saat menolak permohonan klaim.',
            'catatan_admin.max'         => 'Catatan penolakan melebihi batas maksimal kapasitas teks (20.000 karakter).',
        ];
    }
}
