<?php

namespace App\Models;

use App\Traits\TracksDeletedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaporanProfitBulanan extends Model
{
    use HasFactory, SoftDeletes, TracksDeletedBy;

    protected $table = 'laporan_profit_bulanan';

    public const STATUS_BELUM_DIBAYAR = 'belum_dibayar';

    public const STATUS_MENUNGGU_VERIFIKASI = 'menunggu_verifikasi';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_LUNAS = 'lunas';

    public const STATUS_TANPA_FEE = 'tanpa_fee';

    protected $fillable = [
        'nomor',
        'akun_copytrade_id',
        'user_id',
        'periode_ke',
        'periode_awal',
        'periode_akhir',
        'jatuh_tempo',
        'profit_usd',
        'bukti_myfxbook_path',
        'wd_usd',
        'wd_idr',
        'bukti_wd_path',
        'kurs_riil',
        'fee_persen',
        'fee_usd',
        'fee_idr',
        'status',
        'bukti_transfer_path',
        'dibayar_at',
        'catatan_admin',
        'perlu_koreksi',
        'catatan_koreksi',
        'verified_by',
        'verified_at',
    ];

    protected $appends = [
        'status_tampil',
        'status_catatan',
        'is_terkunci',
        'is_editable',
    ];

    protected function casts(): array
    {
        return [
            'periode_awal'  => 'date',
            'periode_akhir' => 'date',
            'jatuh_tempo'   => 'date',
            'profit_usd'    => 'decimal:2',
            'wd_usd'        => 'decimal:2',
            'wd_idr'        => 'integer',
            'kurs_riil'     => 'decimal:2',
            'fee_persen'    => 'decimal:2',
            'fee_usd'       => 'decimal:2',
            'fee_idr'       => 'integer',
            'perlu_koreksi' => 'boolean',
            'dibayar_at'    => 'datetime',
            'verified_at'   => 'datetime',
        ];
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(AkunCopytrade::class, 'akun_copytrade_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Tampilan status ke user/admin/afiliator:
     * - "Perlu Koreksi" (jika admin meminta revisi laporan/screenshot)
     * - "Lunas" (admin verifikasi & setujui)
     * - "Tanpa Fee" (profit <= 0)
     * - "Menunggu Verifikasi" (member sudah unggah bukti transfer)
     * - "Bukti Ditolak" (admin menolak bukti transfer)
     * - "Menunggu Pembayaran" (profit > 0 belum dibayar)
     */
    public function getStatusTampilAttribute(): string
    {
        if ($this->perlu_koreksi && $this->status !== self::STATUS_LUNAS) {
            return 'Perlu Koreksi';
        }

        return match ($this->status) {
            self::STATUS_LUNAS               => 'Lunas',
            self::STATUS_TANPA_FEE           => 'Tanpa Fee',
            self::STATUS_MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
            self::STATUS_DITOLAK             => 'Bukti Ditolak',
            default                          => 'Menunggu Pembayaran',
        };
    }

    /**
     * Catatan kecil penjelas status tampilan.
     * Mengembalikan catatan koreksi jika perlu koreksi, atau catatan admin jika bukti ditolak.
     */
    public function getStatusCatatanAttribute(): ?string
    {
        if ($this->perlu_koreksi && $this->status !== self::STATUS_LUNAS) {
            return $this->catatan_koreksi ?: 'Perlu koreksi';
        }

        if ($this->status === self::STATUS_DITOLAK) {
            return $this->catatan_admin ?: 'Bukti ditolak';
        }

        return null;
    }

    /**
     * Laporan terkunci bila sedang menunggu verifikasi atau sudah lunas/tanpa fee,
     * kecuali admin menandai perlu koreksi.
     */
    public function getIsTerkunciAttribute(): bool
    {
        if ($this->perlu_koreksi && $this->status !== self::STATUS_LUNAS) {
            return false;
        }

        return in_array($this->status, [
            self::STATUS_MENUNGGU_VERIFIKASI,
            self::STATUS_LUNAS,
            self::STATUS_TANPA_FEE,
        ], true);
    }

    /**
     * Laporan boleh diedit/dihapus jika belum terkunci dan belum lunas.
     */
    public function getIsEditableAttribute(): bool
    {
        if ($this->status === self::STATUS_LUNAS) {
            return false;
        }

        return !$this->is_terkunci;
    }

    /**
     * Scope untuk laporan yang belum dibayar / menunggu aksi pembayaran.
     */
    public function scopeBelumLunas(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_LUNAS, self::STATUS_TANPA_FEE]);
    }

    /**
     * Scope untuk laporan yang sudah lunas atau tanpa fee.
     */
    public function scopeLunas(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_LUNAS, self::STATUS_TANPA_FEE]);
    }
}
