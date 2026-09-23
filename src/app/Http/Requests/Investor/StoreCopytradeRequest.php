<?php

namespace App\Http\Requests\Investor;

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
        $hasAnyCopytrade = AkunCopytrade::where('user_id', $this->user()->id)->exists();

        $rules = [
            'tier_investasi'        => ['required', 'string', 'in:tier_1,tier_2,tier_3'],
            'nomor_akun_trading'    => ['required', 'string', 'max:50'],
            'modal_riil'            => ['required', 'numeric', 'min:0'],
            'tanggal_mulai_trading' => ['required', 'date'],
            'nama_bank'             => ['required', 'string', 'max:50'],
            'nomor_rekening'        => ['required', 'string', 'max:50'],
            'atas_nama_rekening'    => ['required', 'string', 'max:100'],
            'server_broker'         => ['required_if:tier_investasi,tier_3', 'nullable', 'string', 'max:50'],
            'master_password'       => ['required_if:tier_investasi,tier_3', 'nullable', 'string', 'max:100'],
            'investor_password'     => ['required_if:tier_investasi,tier_3', 'nullable', 'string', 'max:100'],
            'url_mql5'              => ['nullable', 'url', 'max:255'],
        ];

        if (!$hasAnyCopytrade) {
            $rules['dokumen_perjanjian'] = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'];
        } else {
            $rules['dokumen_perjanjian'] = ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'];
        }

        return $rules;
    }

    /**
     * Konfigurasi validator instance tambahan untuk memeriksa batas minimal modal.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tier = $this->input('tier_investasi');
            $modal = (float) $this->input('modal_riil');

            if (!$tier || !in_array($tier, ['tier_1', 'tier_2', 'tier_3'], true)) {
                return;
            }

            $settings = PengaturanMaster::getSettings();
            $minModal = match ($tier) {
                'tier_1' => (float) $settings->tier_1_min,
                'tier_2' => (float) $settings->tier_2_min,
                'tier_3' => (float) $settings->tier_3_min,
                default  => 0,
            };

            if ($modal < $minModal) {
                $tierNama = match ($tier) {
                    'tier_1' => 'Tier 1',
                    'tier_2' => 'Tier 2',
                    'tier_3' => 'Tier 3',
                    default  => $tier,
                };
                $validator->errors()->add(
                    'modal_riil',
                    "Modal riil untuk {$tierNama} minimal sebesar \${$minModal}."
                );
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
            'nomor_akun_trading.required'    => 'Nomor akun trading MT4 wajib diisi.',
            'modal_riil.required'            => 'Modal riil investasi wajib diisi.',
            'modal_riil.numeric'             => 'Modal riil harus berupa nominal angka.',
            'modal_riil.min'                 => 'Modal riil tidak boleh bernilai negatif.',
            'tanggal_mulai_trading.required' => 'Tanggal mulai trading wajib ditentukan.',
            'nama_bank.required'             => 'Nama bank untuk penarikan dana wajib diisi.',
            'nomor_rekening.required'        => 'Nomor rekening bank penarikan dana wajib diisi.',
            'atas_nama_rekening.required'    => 'Nama pemilik rekening bank wajib diisi.',
            'server_broker.required_if'      => 'Server broker wajib diisi untuk pendaftaran Tier 3.',
            'master_password.required_if'    => 'Master password MT4 wajib diisi untuk pendaftaran Tier 3.',
            'investor_password.required_if'  => 'Investor password MT4 wajib diisi untuk pendaftaran Tier 3.',
            'url_mql5.url'                   => 'Format URL sinyal MQL5 tidak valid.',
            'dokumen_perjanjian.required'    => 'Surat perjanjian kemitraan bermaterai wajib diunggah pada pendaftaran copytrade pertama.',
            'dokumen_perjanjian.mimes'       => 'Berkas perjanjian harus berupa file gambar (JPG, JPEG, PNG) atau PDF.',
            'dokumen_perjanjian.max'         => 'Ukuran berkas surat perjanjian maksimal 10 MB.',
        ];
    }
}
