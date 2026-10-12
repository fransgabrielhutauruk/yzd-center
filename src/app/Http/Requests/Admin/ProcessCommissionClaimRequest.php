<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProcessCommissionClaimRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status'                      => ['required', 'string', 'in:approved_transferred,rejected'],
            'profit_master'               => ['nullable', 'numeric'],
            'kurs_konversi_idr'           => ['nullable', 'numeric', 'min:1'],
            'items'                       => ['nullable', 'array'],
            'items.*.id'                  => ['required_with:items', 'integer'],
            'items.*.roi_percentage'      => ['nullable', 'numeric'],
            'items.*.profit_master'       => ['nullable', 'numeric'],
            'items.*.master_share_pct'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.affiliate_share_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bukti_transfer'              => ['nullable', 'required_if:status,approved_transferred', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'catatan_admin'               => ['nullable', 'required_if:status,rejected', 'string', 'max:20000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required'            => 'Pilih status keputusan pencairan komisi.',
            'status.in'                  => 'Pilihan status keputusan tidak valid.',
            'profit_master.numeric'      => 'Nominal profit Master Trader harus berupa angka.',
            'profit_master.min'          => 'Nominal profit Master Trader minimal Rp 0.',
            'kurs_konversi_idr.numeric'  => 'Nilai kurs konversi Rupiah (MT4) harus berupa angka.',
            'kurs_konversi_idr.min'      => 'Nilai kurs konversi Rupiah (MT4) harus lebih besar dari 0.',
            'items.array'                => 'Format data rincian item komisi tidak valid.',
            'items.*.profit_master.min'  => 'Nominal profit Master Trader untuk setiap item minimal Rp 0.',
            'bukti_transfer.required_if' => 'Unggah berkas bukti/resi transfer bank sebelum menyelesaikan pencairan.',
            'bukti_transfer.mimes'       => 'Berkas bukti transfer harus berformat JPG, PNG, atau PDF.',
            'bukti_transfer.max'         => 'Ukuran berkas bukti transfer maksimal 5 MB.',
            'catatan_admin.required_if'  => 'Alasan penolakan wajib diisi untuk memberikan kejelasan kepada Affiliator.',
            'catatan_admin.max'          => 'Catatan admin maksimal 20.000 karakter.',
        ];
    }
}
