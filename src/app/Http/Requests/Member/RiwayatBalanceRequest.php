<?php

namespace App\Http\Requests\Member;

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
        $recordId = $this->route('id') ?? $this->route('balance');
        $isUpdate = (bool) $recordId || $this->isMethod('put') || $this->isMethod('patch');

        return [
            'akun_copytrade_id' => [
                'required',
                'integer',
                Rule::exists('akun_copytrade', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('status', 'active'),
            ],
            'tanggal_pencatatan' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($recordId): void {
                    $tanggal = \Carbon\Carbon::parse($value)->toDateString();
                    $akunId = $this->input('akun_copytrade_id');

                    $exists = \App\Models\RiwayatBalanceCopytrade::query()
                        ->where('akun_copytrade_id', $akunId)
                        ->whereDate('tanggal_pencatatan', $tanggal)
                        ->when($recordId, fn ($q) => $q->where('id', '!=', $recordId))
                        ->exists();

                    if ($exists) {
                        $fail('Catatan saldo untuk tanggal tersebut sudah terdaftar pada akun trading ini.');
                    }
                },
            ],
            'balance'          => ['required', 'numeric', 'min:0'],
            'penarikan'        => ['nullable', 'numeric', 'min:0'],
            'setoran_tambahan' => ['nullable', 'numeric', 'min:0'],
            'bukti_saldo'      => [
                $isUpdate ? 'nullable' : 'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:10240',
            ],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'akun_copytrade_id.required'  => 'Akun investasi wajib dipilih.',
            'akun_copytrade_id.exists'    => 'Akun trading tidak ditemukan atau belum disetujui oleh administrator.',
            'tanggal_pencatatan.required' => 'Tanggal pencatatan saldo wajib diisi.',
            'tanggal_pencatatan.date'     => 'Format tanggal pencatatan tidak valid.',
            'tanggal_pencatatan.unique'   => 'Catatan saldo untuk tanggal tersebut sudah terdaftar pada akun trading ini.',
            'balance.required'            => 'Nilai saldo (Balance) akun trading wajib diisi.',
            'balance.numeric'             => 'Nilai saldo harus berupa angka.',
            'balance.min'                 => 'Nilai saldo tidak boleh kurang dari 0.',
            'penarikan.numeric'           => 'Nilai penarikan harus berupa angka.',
            'penarikan.min'               => 'Nilai penarikan tidak boleh kurang dari 0.',
            'setoran_tambahan.numeric'    => 'Nilai setoran tambahan harus berupa angka.',
            'setoran_tambahan.min'        => 'Nilai setoran tambahan tidak boleh kurang dari 0.',
            'bukti_saldo.required'        => 'Bukti saldo trading wajib diunggah.',
            'bukti_saldo.image'           => 'Bukti saldo harus berupa gambar.',
            'bukti_saldo.mimes'           => 'Format gambar bukti saldo harus jpeg, png, jpg, atau webp.',
            'bukti_saldo.max'             => 'Ukuran bukti saldo maksimal 10MB.',
            'catatan.max'                 => 'Catatan maksimal 255 karakter.',
        ];
    }
}
