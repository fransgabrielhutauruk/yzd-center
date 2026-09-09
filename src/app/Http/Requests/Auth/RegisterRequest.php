<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invite_token' => ['required', 'string'],
            'name'         => ['required', 'string', 'max:100'],
            'username'     => ['required', 'string', 'min:3', 'max:30', 'unique:users,username', 'regex:/^[a-zA-Z0-9_]+$/'],
            'email'        => ['required', 'string', 'email:rfc', 'max:100', 'unique:users,email'],
            'phone'        => ['required', 'string', 'min:9', 'max:20'],
            'nik'          => ['required', 'string', 'size:16', 'regex:/^[0-9]+$/'],
            'alamat'       => ['required', 'string', 'min:5', 'max:500'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'terms'        => ['accepted'],

            // Data Ahli Waris
            'ahli_waris_nama'     => ['required', 'string', 'max:100'],
            'ahli_waris_hubungan' => ['required', 'string', 'in:Anak,Istri,Suami,Orang Tua,Saudara Kandung'],
            'ahli_waris_phone'    => ['required', 'string', 'min:9', 'max:25', 'different:phone'],
            'ahli_waris_alamat'   => ['required', 'string', 'min:5', 'max:500'],

            // Berkas Legalitas / KYC (maksimal 10 MB mentah, auto-compressed di client)
            'dokumen_ktp'        => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'dokumen_perjanjian' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invite_token.required'        => 'Token tautan pendaftaran wajib disertakan.',
            'name.required'                => 'Nama lengkap wajib diisi.',
            'username.required'            => 'Username wajib diisi.',
            'username.min'                 => 'Username minimal 3 karakter.',
            'username.max'                 => 'Username maksimal 30 karakter.',
            'username.unique'              => 'Username ini sudah digunakan. Silakan pilih username lain.',
            'username.regex'               => 'Username hanya boleh memuat huruf, angka, dan garis bawah (_).',
            'email.required'               => 'Alamat email wajib diisi.',
            'email.email'                  => 'Format alamat email tidak valid.',
            'email.unique'                 => 'Alamat email ini sudah terdaftar.',
            'phone.required'               => 'Nomor WhatsApp/HP wajib diisi.',
            'phone.min'                    => 'Nomor telepon tidak valid.',
            'nik.required'                 => 'Nomor NIK KTP wajib diisi.',
            'nik.size'                     => 'Nomor NIK harus tepat 16 digit angka.',
            'nik.regex'                    => 'Nomor NIK hanya boleh berupa angka.',
            'alamat.required'              => 'Alamat domisili lengkap wajib diisi.',
            'password.required'            => 'Kata sandi wajib diisi.',
            'password.min'                 => 'Kata sandi minimal 8 karakter.',
            'password.confirmed'           => 'Konfirmasi kata sandi tidak cocok.',
            'terms.accepted'               => 'Anda wajib menyetujui Syarat & Ketentuan dan Kebijakan Privasi.',
            'ahli_waris_nama.required'     => 'Nama ahli waris wajib diisi.',
            'ahli_waris_hubungan.required' => 'Pilih hubungan keluarga ahli waris.',
            'ahli_waris_hubungan.in'       => 'Pilihan hubungan keluarga tidak valid.',
            'ahli_waris_phone.required'    => 'Nomor kontak ahli waris wajib diisi.',
            'ahli_waris_phone.different'   => 'Nomor kontak ahli waris tidak boleh sama dengan nomor kontak Anda.',
            'ahli_waris_alamat.required'   => 'Alamat domisili ahli waris wajib diisi.',
            'dokumen_ktp.required'         => 'Foto KTP wajib diunggah.',
            'dokumen_ktp.mimes'            => 'Format foto KTP harus berupa file gambar (JPG, JPEG, atau PNG).',
            'dokumen_ktp.max'              => 'Ukuran foto KTP maksimal 10 MB.',
            'dokumen_perjanjian.required'  => 'Foto atau dokumen scan surat perjanjian wajib diunggah.',
            'dokumen_perjanjian.mimes'     => 'Format surat perjanjian harus berupa JPG, PNG, atau PDF.',
            'dokumen_perjanjian.max'       => 'Ukuran surat perjanjian maksimal 10 MB.',
        ];
    }
}
