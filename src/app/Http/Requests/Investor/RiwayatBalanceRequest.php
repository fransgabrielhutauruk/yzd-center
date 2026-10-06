<?php

namespace App\Http\Requests\Investor;

use App\Models\AkunCopytrade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RiwayatBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $akunId = $this->input('akun_copytrade_id');
        if ($akunId) {
            return AkunCopytrade::query()
                ->where('id', $akunId)
                ->where('user_id', $user->id)
                ->exists();
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'akun_copytrade_id' => [
                'required',
                'integer',
                Rule::exists('akun_copytrade', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('status', 'active'),
            ],
            'tanggal_pencatatan' => ['required', 'date'],
            'balance'            => ['required', 'numeric', 'min:0'],
            'catatan'            => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'akun_copytrade_id.required'  => 'Akun investasi wajib dipilih.',
            'akun_copytrade_id.exists'    => 'Akun trading tidak ditemukan atau belum disetujui oleh administrator.',
            'tanggal_pencatatan.required' => 'Tanggal pencatatan saldo wajib diisi.',
            'tanggal_pencatatan.date'     => 'Format tanggal pencatatan tidak valid.',
            'balance.required'            => 'Nilai saldo (Balance) akun trading wajib diisi.',
            'balance.numeric'             => 'Nilai saldo harus berupa angka.',
            'balance.min'                 => 'Nilai saldo tidak boleh kurang dari 0.',
            'catatan.max'                 => 'Catatan maksimal 255 karakter.',
        ];
    }
}
