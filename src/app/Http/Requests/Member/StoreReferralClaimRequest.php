<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class StoreReferralClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'member';
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'downline_id' => ['required', 'integer', 'exists:users,id'],
            'bukti_chat'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'], // Max 5MB, Opsional, PDF/Gambar
            'alasan'      => ['required', 'string', 'min:10', 'max:20000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'downline_id.required' => 'Silakan ketik username dan periksa calon investor downline yang ingin Anda klaim.',
            'downline_id.exists'   => 'Data calon investor tidak valid atau tidak ditemukan dalam sistem.',
            'bukti_chat.file'      => 'Berkas bukti pendukung harus berupa file yang valid.',
            'bukti_chat.mimes'     => 'Format bukti pendukung harus berupa gambar (JPG, JPEG, PNG, WEBP) atau berkas PDF.',
            'bukti_chat.max'       => 'Ukuran berkas bukti pendukung maksimal 5 MB.',
            'alasan.required'      => 'Alasan atau kronologi klaim susulan wajib diisi.',
            'alasan.min'           => 'Alasan klaim minimal 10 karakter untuk kejelasan tinjauan admin.',
            'alasan.max'           => 'Alasan klaim melebihi batas maksimal kapasitas teks (20.000 karakter).',
        ];
    }
}
