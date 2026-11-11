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
        'master_gross_share_idr',
        'affiliate_share_pct',
        'affiliate_commission',
        'affiliate_commission_idr',
        'master_net_share',
        'investor_net_profit',
        'profit_master',
        'nominal_transfer',
    ];

    protected function casts(): array
    {
        return [
            'is_akun_pertama'          => 'boolean',
            'tanggal_mulai'            => 'date',
            'tanggal_selesai'          => 'date',
            'hari_kehitung'            => 'integer',
            'modal_riil'               => 'decimal:2',
            'deposit_amount'           => 'decimal:2',
            'tier_level'               => 'integer',
            'persentase_tier'          => 'integer',
            'roi_percentage'           => 'decimal:2',
            'gross_profit'             => 'decimal:2',
            'master_share_pct'         => 'decimal:2',
            'master_gross_share'       => 'decimal:2',
            'master_gross_share_idr'   => 'integer',
            'affiliate_share_pct'      => 'decimal:2',
            'affiliate_commission'     => 'decimal:2',
            'affiliate_commission_idr' => 'integer',
            'master_net_share'         => 'decimal:2',
            'investor_net_profit'      => 'decimal:2',
            'profit_master'            => 'decimal:2',
            'nominal_transfer'         => 'decimal:2',
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
     * Definisi resmi 4-Tier Skema Komisi Afiliator YZD Center.
     * Urut dari Tier 1 (tertinggi) ke Tier 4.
     *
     * @return array<int, array{
     *     tier_level: int,
     *     persentase_tier: int,
     *     label: string,
     *     name: string,
     *     min_idr: float|null,
     *     max_idr: float|null,
     *     range_label: string,
     *     is_strict_greater: bool
     * }>
     */
    public static function tierDefinitions(): array
    {
        return [
            [
                'tier_level'        => 1,
                'persentase_tier'   => 50,
                'label'             => 'Tier 1 (> Rp 10 Milyar) - Komisi 50%',
                'name'              => 'Tier 1',
                'min_idr'           => 10_000_000_000.0,
                'max_idr'           => null,
                'range_label'       => '> Rp 10 Milyar',
                'is_strict_greater' => true,
            ],
            [
                'tier_level'        => 2,
                'persentase_tier'   => 40,
                'label'             => 'Tier 2 (Rp 1 Milyar - Rp 10 Milyar) - Komisi 40%',
                'name'              => 'Tier 2',
                'min_idr'           => 1_000_000_000.0,
                'max_idr'           => 10_000_000_000.0,
                'range_label'       => 'Rp 1 Milyar – Rp 10 Milyar',
                'is_strict_greater' => false,
            ],
            [
                'tier_level'        => 3,
                'persentase_tier'   => 30,
                'label'             => 'Tier 3 (Rp 100 Juta - Rp 1 Milyar) - Komisi 30%',
                'name'              => 'Tier 3',
                'min_idr'           => 100_000_000.0,
                'max_idr'           => 1_000_000_000.0,
                'range_label'       => 'Rp 100 Juta – Rp 1 Milyar',
                'is_strict_greater' => false,
            ],
            [
                'tier_level'        => 4,
                'persentase_tier'   => 20,
                'label'             => 'Tier 4 (< Rp 100 Juta) - Komisi 20%',
                'name'              => 'Tier 4',
                'min_idr'           => 0.0,
                'max_idr'           => 100_000_000.0,
                'range_label'       => '< Rp 100 Juta',
                'is_strict_greater' => false,
            ],
        ];
    }

    /**
     * Tentukan level tier dan persentase komisi berdasarkan nominal modal riil.
     *
     * @return array{tier_level: int, persentase_tier: int, label: string}
     */
    public static function determineTier(float $modalRiil): array
    {
        foreach (static::tierDefinitions() as $tier) {
            if ($tier['is_strict_greater']) {
                if ($modalRiil > $tier['min_idr']) {
                    return [
                        'tier_level'      => $tier['tier_level'],
                        'persentase_tier' => $tier['persentase_tier'],
                        'label'           => $tier['label'],
                    ];
                }
            } elseif ($tier['min_idr'] !== null && $modalRiil >= $tier['min_idr']) {
                return [
                    'tier_level'      => $tier['tier_level'],
                    'persentase_tier' => $tier['persentase_tier'],
                    'label'           => $tier['label'],
                ];
            }
        }

        $fallback = static::tierDefinitions()[3];

        return [
            'tier_level'      => $fallback['tier_level'],
            'persentase_tier' => $fallback['persentase_tier'],
            'label'           => $fallback['label'],
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
     * Hitung kalkulasi komisi afiliator dari bagi hasil Master Fee yang lunas.
     * master_gross_share = Σ nominal_fee tagihan lunas
     * gross_profit = Σ profit_bersih tagihan lunas
     * affiliate_commission = (persentase_tier / 100) * master_gross_share
     * master_net_share = master_gross_share - affiliate_commission
     * investor_net_profit = gross_profit - master_gross_share
     *
     * @return array{
     *     gross_profit: float,
     *     master_gross_share: float,
     *     affiliate_commission: float,
     *     master_net_share: float,
     *     investor_net_profit: float,
     *     roi_percentage: float,
     *     is_drawdown: bool
     * }
     */
    public static function calculateCommissionFromPaidFee(
        float $masterGrossShare,
        float $grossProfit,
        float $affiliateSharePct = 20.0,
        float $modalRiil = 0.0
    ): array {
        if ($masterGrossShare <= 0) {
            return [
                'gross_profit'         => round($grossProfit, 2),
                'master_gross_share'   => 0.0,
                'affiliate_commission' => 0.0,
                'master_net_share'     => 0.0,
                'investor_net_profit'  => round($grossProfit, 2),
                'roi_percentage'       => $modalRiil > 0 ? round(($grossProfit / $modalRiil) * 100, 2) : 0.0,
                'is_drawdown'          => true,
            ];
        }

        $affiliateCommission = round($masterGrossShare * ($affiliateSharePct / 100), 2);
        $masterNetShare = round($masterGrossShare - $affiliateCommission, 2);
        $investorNetProfit = round($grossProfit - $masterGrossShare, 2);
        $roiPct = $modalRiil > 0 ? round(($grossProfit / $modalRiil) * 100, 2) : 0.0;

        return [
            'gross_profit'         => round($grossProfit, 2),
            'master_gross_share'   => round($masterGrossShare, 2),
            'affiliate_commission' => round($affiliateCommission, 2),
            'master_net_share'     => round($masterNetShare, 2),
            'investor_net_profit'  => round($investorNetProfit, 2),
            'roi_percentage'       => $roiPct,
            'is_drawdown'          => false,
        ];
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
