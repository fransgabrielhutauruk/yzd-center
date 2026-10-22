<?php

namespace App\Http\Requests\Member;

use App\Models\AkunCopytrade;
use App\Models\PengaturanMaster;
use Illuminate\Foundation\Http\FormRequest;

class StoreCopytradeRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki otorisasi untuk membuat request ini.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $hasAnyCopytrade = AkunCopytrade::where('user_id', $this->user()->id)
            ->whereIn('status', ['pending', 'active'])
            ->exists();

        $rules = [
            'tier_investasi'        => ['required', 'string', 'in:tier_1,tier_2,tier_3'],
            'nomor_akun_trading'    => ['required', 'string', 'max:50'],
            'modal_riil'            => ['required', 'numeric', 'gt:0'],
            'tanggal_mulai_trading' => ['required', 'date'],
            'nama_bank'             => [$hasAnyCopytrade ? 'nullable' : 'required', 'string', 'max:50'],
            'nomor_rekening'        => [$hasAnyCopytrade ? 'nullable' : 'required', 'string', 'max:50'],
            'atas_nama_rekening'    => [$hasAnyCopytrade ? 'nullable' : 'required', 'string', 'max:100'],
            'server_broker'         => ['required_if:tier_investasi,tier_3', 'nullable', 'string', 'max:50'],
            'master_password'       => ['required_if:tier_investasi,tier_3', 'nullable', 'string', 'max:100'],
            'investor_password'     => ['required_if:tier_investasi,tier_3', 'nullable', 'string', 'max:100'],
        ];

        if (!$hasAnyCopytrade) {
            $rules['dokumen_perjanjian'] = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'];
        } else {
            $rules['dokumen_perjanjian'] = ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'];
        }

        return $rules;
    }

    /**
     * Konfigurasi validator instance tambahan untuk memeriksa batas range modal setiap tier.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tier = $this->input('tier_investasi');
            $modal = (float) $this->input('modal_riil');

            if (!$tier || !in_array($tier, ['tier_1', 'tier_2', 'tier_3'], true)) {
                return;
            }

            $tier2Min = PengaturanMaster::TIER_2_MIN_USD;
            $tier3Min = PengaturanMaster::TIER_3_MIN_USD;

            if ($tier === 'tier_1') {
                if ($modal <= 0) {
                    $validator->errors()->add('modal_riil', 'Modal riil untuk Tier 1 harus lebih besar dari $0.');
                } elseif ($modal >= $tier2Min) {
                    $validator->errors()->add(
                        'modal_riil',
                        'Modal riil untuk Tier 1 harus di bawah $' . number_format($tier2Min) . '. Untuk modal $' . number_format($tier2Min) . ' ke atas, silakan pilih Tier 2 atau Tier 3.'
                    );
                }
            } elseif ($tier === 'tier_2') {
                if ($modal < $tier2Min) {
                    $validator->errors()->add(
                        'modal_riil',
                        'Modal riil untuk Tier 2 minimal sebesar $' . number_format($tier2Min) . '. Untuk modal di bawah $' . number_format($tier2Min) . ', silakan pilih Tier 1.'
                    );
                } elseif ($modal > $tier3Min) {
                    $validator->errors()->add(
                        'modal_riil',
                        'Modal riil untuk Tier 2 maksimal sebesar $' . number_format($tier3Min) . '. Untuk modal di atas $' . number_format($tier3Min) . ', silakan pilih Tier 3.'
                    );
                }
            } elseif ($tier === 'tier_3') {
                if ($modal < $tier3Min) {
                    $validator->errors()->add(
                        'modal_riil',
                        'Modal riil untuk Tier 3 minimal sebesar $' . number_format($tier3Min) . '.'
                    );
                }
            }
        });
    }

    /**
     * Pesan kesalahan kustom dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tier_investasi.required'        => 'Tier investasi wajib dipilih.',
            'tier_investasi.in'              => 'Pilihan tier investasi tidak valid.',
            'nomor_akun_trading.required'    => 'Nomor akun trading / investor (copier) wajib diisi.',
            'modal_riil.required'            => 'Modal deposit (usd) investasi wajib diisi.',
            'modal_riil.numeric'             => 'Modal riil harus berupa nominal angka.',
            'modal_riil.gt'                  => 'Modal riil harus lebih besar dari $0.',
            'modal_riil.min'                 => 'Modal riil harus lebih besar dari $0.',
            'tanggal_mulai_trading.required' => 'Tanggal mulai investasi/copytrade wajib ditentukan.',
            'nama_bank.required'             => 'Nama bank untuk penarikan dana wajib diisi.',
            'nomor_rekening.required'        => 'Nomor rekening bank penarikan dana wajib diisi.',
            'atas_nama_rekening.required'    => 'Nama pemilik rekening bank wajib diisi.',
            'server_broker.required_if'      => 'Server broker wajib diisi untuk pendaftaran Tier 3.',
            'master_password.required_if'    => 'Master password MT4 wajib diisi untuk pendaftaran Tier 3.',
            'investor_password.required_if'  => 'Investor password MT4 wajib diisi untuk pendaftaran Tier 3.',
            'dokumen_perjanjian.required'    => 'Surat perjanjian kemitraan bermaterai wajib diunggah pada investasi/copytrade pertama.',
            'dokumen_perjanjian.mimes'       => 'Berkas perjanjian harus berupa file gambar (JPG, JPEG, PNG) atau PDF.',
            'dokumen_perjanjian.max'         => 'Ukuran berkas surat perjanjian maksimal 10 MB.',
        ];
    }
}
