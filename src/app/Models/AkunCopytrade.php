<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class AkunCopytrade extends Model
{
    protected $table = 'akun_copytrade';

    protected $fillable = [
        'user_id',
        'tier',
        'broker',
        'tipe_akun',
        'nomor_akun_trading',
        'server_broker',
        'master_password',
        'investor_password',
        'estimasi_modal',
        'tanggal_mulai',
        'tanggal_selesai',
        'nama_bank',
        'nomor_rekening',
        'atas_nama_rekening',
        'status',
        'catatan_admin',
    ];

    protected $hidden = [
        'master_password',
        'investor_password',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai'   => 'date',
            'tanggal_selesai' => 'date',
            'estimasi_modal'  => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------------------
    // Relasi
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // Mutators & Accessors – enkripsi password MT4
    // -------------------------------------------------------------------------

    /** Simpan master password dalam bentuk terenkripsi. */
    public function setMasterPasswordAttribute(?string $value): void
    {
        $this->attributes['master_password'] = $value ? Crypt::encryptString($value) : null;
    }

    /** Dekripsi master password saat dibaca. */
    public function getMasterPasswordAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value;
        }
    }

    /** Simpan investor password dalam bentuk terenkripsi. */
    public function setInvestorPasswordAttribute(?string $value): void
    {
        $this->attributes['investor_password'] = $value ? Crypt::encryptString($value) : null;
    }

    /** Dekripsi investor password saat dibaca. */
    public function getInvestorPasswordAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value;
        }
    }
}
