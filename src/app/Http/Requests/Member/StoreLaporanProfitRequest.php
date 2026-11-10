<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaporanProfitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'akun_copytrade_id' => ['required', 'integer', 'exists:akun_copytrade,id'],
            'periode_ke'        => ['nullable', 'integer', 'min:1'],
            'profit_usd'        => ['required', 'numeric'],
            'bukti_myfxbook'    => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'wd_usd'            => ['nullable', 'numeric'],
            'wd_idr'            => ['nullable', 'numeric'],
            'bukti_wd'          => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $profit = (float) $this->input('profit_usd');
            if ($profit > 0) {
                if ($this->input('wd_usd') === null || $this->input('wd_usd') === '') {
                    $validator->errors()->add('wd_usd', 'Nominal penarikan broker (USD) wajib diisi ketika profit di atas $0.');
                }
                if ($this->input('wd_idr') === null || $this->input('wd_idr') === '') {
                    $validator->errors()->add('wd_idr', 'Nominal Rupiah diterima (IDR) wajib diisi ketika profit di atas $0.');
                }
                if (!$this->hasFile('bukti_wd')) {
                    $validator->errors()->add('bukti_wd', 'Screenshot bukti penarikan broker (WD) wajib diunggah ketika profit di atas $0.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'akun_copytrade_id.required' => 'Pilih akun copytrade terlebih dahulu.',
            'akun_copytrade_id.exists'   => 'Akun copytrade tidak ditemukan.',
            'profit_usd.required'        => 'Profit USD wajib diisi.',
            'profit_usd.numeric'         => 'Profit USD harus berupa angka.',
            'bukti_myfxbook.required'    => 'Screenshot Myfxbook wajib diunggah.',
            'bukti_myfxbook.image'       => 'Screenshot Myfxbook harus berupa gambar.',
            'bukti_myfxbook.mimes'       => 'Format screenshot Myfxbook harus jpeg, png, jpg, atau webp.',
            'bukti_myfxbook.max'         => 'Ukuran screenshot Myfxbook maksimal 10MB.',
            'bukti_wd.image'             => 'Screenshot bukti penarikan harus berupa gambar.',
            'bukti_wd.mimes'             => 'Format screenshot bukti penarikan harus jpeg, png, jpg, atau webp.',
            'bukti_wd.max'               => 'Ukuran screenshot bukti penarikan maksimal 10MB.',
        ];
    }
}
