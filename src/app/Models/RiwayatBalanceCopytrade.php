<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatBalanceCopytrade extends Model
{
    protected $table = 'riwayat_balance_copytrade';

    protected $fillable = [
        'akun_copytrade_id',
        'user_id',
        'tanggal_pencatatan',
        'balance',
        'keuntungan_nominal',
        'keuntungan_persen',
        'keuntungan_kumulatif',
        'pertumbuhan_kumulatif_persen',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pencatatan'           => 'date',
            'balance'                      => 'decimal:2',
            'keuntungan_nominal'           => 'decimal:2',
            'keuntungan_persen'            => 'decimal:2',
            'keuntungan_kumulatif'         => 'decimal:2',
            'pertumbuhan_kumulatif_persen' => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------------------
    // Relasi
    // -------------------------------------------------------------------------

    public function akunCopytrade(): BelongsTo
    {
        return $this->belongsTo(AkunCopytrade::class, 'akun_copytrade_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
