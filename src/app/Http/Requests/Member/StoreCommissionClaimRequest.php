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
     * Normalisasi input downline_ids dari berbagai variasi payload (array downline_ids, downline_id tunggal, atau claims array).
     */
    protected function prepareForValidation(): void
    {
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
            'downline_ids'       => ['required', 'array', 'min:1'],
            'downline_ids.*'     => ['required', 'integer', 'exists:users,id'],
            'nama_bank'          => ['required', 'string', 'max:100'],
            'nomor_rekening'     => ['required', 'string', 'max:50'],
            'atas_nama_rekening' => ['required', 'string', 'max:100'],
        ];
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
