<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommissionClaimRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isMember();
    }

    /**
     * Normalisasi input akun_copytrade_ids dan downline_ids dari berbagai variasi payload.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('akun_copytrade_id') && !$this->has('akun_copytrade_ids')) {
            $this->merge([
                'akun_copytrade_ids' => [(int) $this->input('akun_copytrade_id')],
            ]);
        } elseif ($this->has('account_ids') && !$this->has('akun_copytrade_ids')) {
            $this->merge([
                'akun_copytrade_ids' => array_map('intval', (array) $this->input('account_ids')),
            ]);
        }

        if ($this->has('downline_id') && !$this->has('downline_ids')) {
            $this->merge([
                'downline_ids' => [(int) $this->input('downline_id')],
            ]);
        } elseif ($this->has('claims') && is_array($this->input('claims')) && !$this->has('downline_ids')) {
            $ids = array_map(function ($c) {
                return is_array($c) ? ($c['downline_id'] ?? 0) : (int) $c;
            }, $this->input('claims'));

            $this->merge([
                'downline_ids' => array_values(array_unique(array_filter($ids))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'akun_copytrade_ids'   => ['nullable', 'array'],
            'akun_copytrade_ids.*' => ['integer', 'exists:akun_copytrade,id'],
            'downline_ids'         => ['nullable', 'array'],
            'downline_ids.*'       => ['integer', 'exists:users,id'],
            'nama_bank'            => ['required', 'string', 'max:100'],
            'nomor_rekening'       => ['required', 'string', 'max:50'],
            'atas_nama_rekening'   => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * Pastikan minimal salah satu dari akun_copytrade_ids atau downline_ids terisi.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasAccounts = filled($this->input('akun_copytrade_ids'));
            $hasDownlines = filled($this->input('downline_ids'));

            if (!$hasAccounts && !$hasDownlines) {
                $validator->errors()->add('akun_copytrade_ids', 'Pilih minimal satu akun copytrade investor downline yang telah aktif minimal 3 bulan.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'downline_ids.required'       => 'Pilih minimal satu investor downline yang telah aktif minimal 90 hari.',
            'downline_ids.min'            => 'Pilih minimal satu investor downline yang telah aktif minimal 90 hari.',
            'downline_ids.*.required'     => 'ID investor downline wajib valid.',
            'downline_ids.*.exists'       => 'Investor downline yang dipilih tidak terdaftar di sistem.',
            'nama_bank.required'          => 'Nama bank tujuan transfer pencairan wajib diisi.',
            'nomor_rekening.required'     => 'Nomor rekening bank tujuan transfer wajib diisi.',
            'atas_nama_rekening.required' => 'Nama pemilik rekening bank tujuan wajib diisi.',
        ];
    }
}
