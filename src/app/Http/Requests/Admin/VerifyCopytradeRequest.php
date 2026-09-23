<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCopytradeRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki otorisasi untuk membuat request ini.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status'        => ['required', 'string', 'in:active,rejected'],
            'catatan_admin' => ['required_if:status,rejected', 'nullable', 'string', 'max:20000'],
        ];
    }

    /**
     * Pesan kesalahan kustom dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required'           => 'Keputusan verifikasi pendaftaran copytrade wajib dipilih.',
            'status.in'                 => 'Pilihan status keputusan tidak valid.',
            'catatan_admin.required_if' => 'Catatan atau instruksi perbaikan wajib diisi ketika menolak permohonan copytrade.',
            'catatan_admin.max'         => 'Catatan admin tidak boleh melebihi 20.000 karakter.',
        ];
    }
}
