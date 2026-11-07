<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePengaturanMasterFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'administrator';
    }

    public function rules(): array
    {
        return [
            'fee_persen'     => ['required', 'integer', 'between:1,100'],
            'nama_bank'      => ['required', 'string', 'max:100'],
            'nomor_rekening' => ['required', 'string', 'max:100'],
            'atas_nama'      => ['required', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'fee_persen.required'     => 'Persentase Master Fee wajib diisi.',
            'fee_persen.integer'      => 'Persentase Master Fee harus berupa bilangan bulat.',
            'fee_persen.between'      => 'Persentase Master Fee harus bernilai antara 1% sampai 100%.',
            'nama_bank.required'      => 'Nama bank rekening penerima wajib diisi.',
            'nama_bank.max'           => 'Nama bank maksimal 100 karakter.',
            'nomor_rekening.required' => 'Nomor rekening penerima wajib diisi.',
            'nomor_rekening.max'      => 'Nomor rekening maksimal 100 karakter.',
            'atas_nama.required'      => 'Nama pemilik rekening penerima wajib diisi.',
            'atas_nama.max'           => 'Nama pemilik rekening maksimal 150 karakter.',
        ];
    }
}
