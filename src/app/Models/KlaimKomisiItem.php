<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KlaimKomisiItem extends Model
{
    use HasFactory;

    protected $table = 'klaim_komisi_items';

    protected $fillable = [
        'klaim_komisi_id',
        'downline_id',
        'akun_copytrade_id',
        'broker',
        'nomor_akun_trading',
        'is_akun_pertama',
        'tanggal_mulai',
        'tanggal_selesai',
        'hari_kehitung',
        'modal_riil',
        'deposit_amount',
        'tier_level',
        'persentase_tier',
        'roi_percentage',
        'gross_profit',
        'master_share_pct',
        'master_gross_share',
        'affiliate_share_pct',
        'affiliate_commission',
        'master_net_share',
        'investor_net_profit',
        'profit_master',
        'nominal_transfer',
    ];

    protected function casts(): array
    {
        return [
            'is_akun_pertama'      => 'boolean',
            'tanggal_mulai'        => 'date',
            'tanggal_selesai'      => 'date',
            'hari_kehitung'        => 'integer',
            'modal_riil'           => 'decimal:2',
            'deposit_amount'       => 'decimal:2',
            'tier_level'           => 'integer',
            'persentase_tier'      => 'integer',
            'roi_percentage'       => 'decimal:2',
            'gross_profit'         => 'decimal:2',
            'master_share_pct'     => 'decimal:2',
            'master_gross_share'   => 'decimal:2',
            'affiliate_share_pct'  => 'decimal:2',
            'affiliate_commission' => 'decimal:2',
            'master_net_share'     => 'decimal:2',
            'investor_net_profit'  => 'decimal:2',
            'profit_master'        => 'decimal:2',
            'nominal_transfer'     => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------------------
    // Relasi
    // -------------------------------------------------------------------------

    public function klaimKomisi(): BelongsTo
    {
        return $this->belongsTo(KlaimKomisi::class, 'klaim_komisi_id');
    }

    public function downline(): BelongsTo
    {
        return $this->belongsTo(User::class, 'downline_id');
    }

    public function akunCopytrade(): BelongsTo
    {
        return $this->belongsTo(AkunCopytrade::class, 'akun_copytrade_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Tentukan level tier dan persentase komisi berdasarkan nominal modal riil.
     *
     * @return array{tier_level: int, persentase_tier: int, label: string}
     */
    public static function determineTier(float $modalRiil): array
    {
        if ($modalRiil > 10_000_000_000) {
            return [
                'tier_level'      => 1,
                'persentase_tier' => 50,
                'label'           => 'Tier 1 (> Rp 10 Milyar) - Komisi 50%',
            ];
        }

        if ($modalRiil >= 1_000_000_000) {
            return [
                'tier_level'      => 2,
                'persentase_tier' => 40,
                'label'           => 'Tier 2 (Rp 1 Milyar - Rp 10 Milyar) - Komisi 40%',
            ];
        }

        if ($modalRiil >= 100_000_000) {
            return [
                'tier_level'      => 3,
                'persentase_tier' => 30,
                'label'           => 'Tier 3 (Rp 100 Juta - Rp 1 Milyar) - Komisi 30%',
            ];
        }

        return [
            'tier_level'      => 4,
            'persentase_tier' => 20,
            'label'           => 'Tier 4 (< Rp 100 Juta) - Komisi 20%',
        ];
    }

    /**
     * Hitung nominal transfer komisi dari profit master (kompatibilitas mundur).
     */
    public static function calculateNominalTransfer(float $profitMaster, int $persentaseTier): float
    {
        return round($profitMaster * ($persentaseTier / 100), 2);
    }

    /**
     * Hitung kalkulasi komisi afiliator sesuai formula bisnis:
     * 1. gross_profit = deposit_amount * (roi_percentage / 100)
     * 2. Kondisi Rugi / Impas (Drawdown Guard):
     *    Jika gross_profit <= 0:
     *      affiliate_commission = 0
     *      master_gross_share = 0
     *      master_net_share = 0
     *      investor_net_profit = gross_profit
     * 3. Bagi Hasil Master:
     *    master_gross_share = gross_profit * (master_share_pct / 100)
     * 4. Komisi Afiliator (Dipotong dari Jatah Master):
     *    affiliate_commission = master_gross_share * (affiliate_share_pct / 100)
     * 5. Hasil Bersih Akhir:
     *    master_net_share = master_gross_share - affiliate_commission
     *    investor_net_profit = gross_profit - master_gross_share
     *
     * @return array{
     *     gross_profit: float,
     *     master_gross_share: float,
     *     affiliate_commission: float,
     *     master_net_share: float,
     *     investor_net_profit: float,
     *     is_drawdown: bool
     * }
     */
    public static function calculateCommission(
        float $depositAmount,
        float $roiPercentage,
        float $masterSharePct = 30.0,
        float $affiliateSharePct = 20.0
    ): array {
        $grossProfit = $depositAmount * ($roiPercentage / 100);

        if ($grossProfit <= 0) {
            return [
                'gross_profit'         => round($grossProfit, 2),
                'master_gross_share'   => 0.0,
                'affiliate_commission' => 0.0,
                'master_net_share'     => 0.0,
                'investor_net_profit'  => round($grossProfit, 2),
                'is_drawdown'          => true,
            ];
        }

        $masterGrossShare = $grossProfit * ($masterSharePct / 100);
        $affiliateCommission = $masterGrossShare * ($affiliateSharePct / 100);
        $masterNetShare = $masterGrossShare - $affiliateCommission;
        $investorNetProfit = $grossProfit - $masterGrossShare;

        return [
            'gross_profit'         => round($grossProfit, 2),
            'master_gross_share'   => round($masterGrossShare, 2),
            'affiliate_commission' => round($affiliateCommission, 2),
            'master_net_share'     => round($masterNetShare, 2),
            'investor_net_profit'  => round($investorNetProfit, 2),
            'is_drawdown'          => false,
        ];
    }
}
