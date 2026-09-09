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
            'invite_token.required' => 'Token tautan pendaftaran wajib disertakan.',
            'name.required'         => 'Nama lengkap wajib diisi.',
            'username.required'     => 'Username wajib diisi.',
            'username.min'          => 'Username minimal 3 karakter.',
            'username.max'          => 'Username maksimal 30 karakter.',
            'username.unique'       => 'Username ini sudah digunakan. Silakan pilih username lain.',
            'username.regex'        => 'Username hanya boleh memuat huruf, angka, dan garis bawah (_).',
            'email.required'        => 'Alamat email wajib diisi.',
            'email.email'           => 'Format alamat email tidak valid.',
            'email.unique'          => 'Alamat email ini sudah terdaftar.',
            'phone.required'        => 'Nomor WhatsApp/HP wajib diisi.',
            'phone.min'             => 'Nomor telepon tidak valid.',
            'nik.required'          => 'Nomor NIK KTP wajib diisi.',
            'nik.size'              => 'Nomor NIK harus tepat 16 digit angka.',
            'nik.regex'             => 'Nomor NIK hanya boleh berupa angka.',
            'alamat.required'       => 'Alamat domisili lengkap wajib diisi.',
            'password.required'     => 'Kata sandi wajib diisi.',
            'password.min'          => 'Kata sandi minimal 8 karakter.',
            'password.confirmed'    => 'Konfirmasi kata sandi tidak cocok.',
            'terms.accepted'        => 'Anda wajib menyetujui Syarat & Ketentuan dan Kebijakan Privasi.',
        ];
    }
}
