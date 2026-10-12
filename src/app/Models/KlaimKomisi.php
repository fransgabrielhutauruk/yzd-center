<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KlaimKomisi extends Model
{
    use HasFactory;

    protected $table = 'klaim_komisi';

    protected $fillable = [
        'kode_klaim',
        'affiliator_id',
        'total_downline',
        'total_akun',
        'total_modal_riil',
        'total_deposit_amount',
        'total_gross_profit',
        'total_master_gross_share',
        'total_master_net_share',
        'total_investor_net_profit',
        'total_affiliate_commission',
        'total_profit_master',
        'total_nominal_transfer',
        'kurs_konversi_idr',
        'total_nominal_transfer_idr',
        'nama_bank',
        'nomor_rekening',
        'atas_nama_rekening',
        'status',
        'bukti_transfer_path',
        'catatan_admin',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_downline'             => 'integer',
            'total_akun'                 => 'integer',
            'total_modal_riil'           => 'decimal:2',
            'total_deposit_amount'       => 'decimal:2',
            'total_gross_profit'         => 'decimal:2',
            'total_master_gross_share'   => 'decimal:2',
            'total_master_net_share'     => 'decimal:2',
            'total_investor_net_profit'  => 'decimal:2',
            'total_affiliate_commission' => 'decimal:2',
            'total_profit_master'        => 'decimal:2',
            'total_nominal_transfer'     => 'decimal:2',
            'kurs_konversi_idr'          => 'decimal:2',
            'total_nominal_transfer_idr' => 'decimal:2',
            'processed_at'               => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relasi
    // -------------------------------------------------------------------------

    public function items(): HasMany
    {
        return $this->hasMany(KlaimKomisiItem::class, 'klaim_komisi_id');
    }

    public function affiliator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affiliator_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // -------------------------------------------------------------------------
    // Status Helper
    // -------------------------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved_transferred';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved_transferred');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    // -------------------------------------------------------------------------
    // Static Helpers & Proxies
    // -------------------------------------------------------------------------

    public static function determineTier(float $modalRiil): array
    {
        return KlaimKomisiItem::determineTier($modalRiil);
    }

    public static function calculateNominalTransfer(float $profitMaster, int $persentaseTier): float
    {
        return KlaimKomisiItem::calculateNominalTransfer($profitMaster, $persentaseTier);
    }

    /**
     * Buat kode unik klaim komisi berurutan per bulan berjalan (e.g. CLM-202609-0001).
     */
    public static function generateKodeKlaim(): string
    {
        $prefix = 'CLM-' . now()->format('Ym') . '-';

        $latest = static::where('kode_klaim', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('kode_klaim');

        if ($latest) {
            $number = (int) substr($latest, strlen($prefix)) + 1;
        } else {
            $number = 1;
        }

        return $prefix . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
