<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagihanMasterFee extends Model
{
    protected $table = 'tagihan_master_fee';

    public const STATUS_BELUM_DIBAYAR = 'belum_dibayar';

    public const STATUS_MENUNGGU_VERIFIKASI = 'menunggu_verifikasi';

    public const STATUS_LUNAS = 'lunas';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_LABELS = [
        self::STATUS_BELUM_DIBAYAR       => 'Belum Dibayar',
        self::STATUS_MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
        self::STATUS_LUNAS               => 'Lunas',
        self::STATUS_DITOLAK             => 'Ditolak',
    ];

    protected $fillable = [
        'nomor',
        'riwayat_balance_id',
        'akun_copytrade_id',
        'user_id',
        'periode',
        'profit_bersih',
        'fee_persen',
        'nominal_fee',
        'jatuh_tempo',
        'status',
        'bukti_transfer_path',
        'dibayar_at',
        'catatan_admin',
        'verified_by',
        'verified_at',
    ];

    protected $appends = [
        'is_terlambat',
        'status_label',
    ];

    protected function casts(): array
    {
        return [
            'periode'       => 'date:Y-m-d',
            'jatuh_tempo'   => 'date:Y-m-d',
            'profit_bersih' => 'decimal:2',
            'fee_persen'    => 'decimal:2',
            'nominal_fee'   => 'decimal:2',
            'dibayar_at'    => 'datetime',
            'verified_at'   => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relasi
    // -------------------------------------------------------------------------

    public function riwayatBalance(): BelongsTo
    {
        return $this->belongsTo(RiwayatBalanceCopytrade::class, 'riwayat_balance_id');
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(AkunCopytrade::class, 'akun_copytrade_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // -------------------------------------------------------------------------
    // Accessors & Scopes
    // -------------------------------------------------------------------------

    public function getIsTerlambatAttribute(): bool
    {
        if (!in_array($this->status, [self::STATUS_BELUM_DIBAYAR, self::STATUS_DITOLAK], true)) {
            return false;
        }

        if (!$this->jatuh_tempo) {
            return false;
        }

        return Carbon::now()->startOfDay()->gt(Carbon::parse($this->jatuh_tempo)->startOfDay());
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function scopeTerkunci(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_MENUNGGU_VERIFIKASI, self::STATUS_LUNAS]);
    }
}
