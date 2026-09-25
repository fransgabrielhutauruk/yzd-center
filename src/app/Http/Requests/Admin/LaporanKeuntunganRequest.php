<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LaporanKeuntunganRequest extends FormRequest
{
    /**
     * Tentukan otorisasi pengguna (hanya administrator).
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Aturan validasi input.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipe'                => ['required', 'in:harian,bulanan'],
            'judul'               => ['required', 'string', 'max:255'],
            'tanggal'             => ['required_if:tipe,harian', 'nullable', 'date'],
            'bulan'               => ['required_if:tipe,bulanan', 'nullable', 'integer', 'between:1,12'],
            'tahun'               => ['required_if:tipe,bulanan', 'nullable', 'integer', 'between:2020,2050'],
            'profit_persen'       => ['nullable', 'numeric', 'between:-100,1000'],
            'profit_nominal_usd'  => ['nullable', 'numeric', 'min:0'],
            'is_kalender'         => ['nullable', 'boolean'],
            'deskripsi'           => ['nullable', 'string', 'max:10000'],
            'status'              => ['required', 'in:published,draft'],
            'images'              => ['nullable', 'array'],
            'images.*'            => ['file', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'captions'            => ['nullable', 'array'],
            'captions.*'          => ['nullable', 'string', 'max:255'],
            'deleted_image_ids'   => ['nullable', 'array'],
            'deleted_image_ids.*' => ['integer'],
        ];
    }

    /**
     * Pesan validasi kustom.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipe.required'          => 'Tipe keuntungan wajib dipilih (Harian atau Bulanan).',
            'tipe.in'                => 'Tipe keuntungan harus berupa Harian atau Bulanan.',
            'judul.required'         => 'Judul laporan keuntungan wajib diisi.',
            'tanggal.required_if'    => 'Tanggal transaksi wajib diisi untuk laporan keuntungan harian.',
            'bulan.required_if'      => 'Bulan wajib dipilih untuk laporan keuntungan bulanan.',
            'tahun.required_if'      => 'Tahun wajib diisi untuk laporan keuntungan bulanan.',
            'profit_persen.numeric'  => 'Persentase profit harus berupa angka.',
            'profit_nominal_usd.min' => 'Nominal profit USD tidak boleh bernilai negatif.',
            'images.*.mimes'         => 'Format berkas foto harus berupa JPG, PNG, atau WEBP.',
            'images.*.max'           => 'Ukuran masing-masing berkas foto maksimal 10 MB.',
            'status.required'        => 'Status publikasi wajib ditentukan.',
        ];
    }
}
