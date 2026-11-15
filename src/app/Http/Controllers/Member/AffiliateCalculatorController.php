<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\KlaimKomisiItem;
use App\Models\PengaturanMasterFee;
use App\Services\ExchangeRateService;
use Inertia\Inertia;
use Inertia\Response;

class AffiliateCalculatorController extends Controller
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService
    ) {}

    /**
     * Tampilkan halaman kalkulator estimasi komisi afiliator.
     */
    public function index(): Response
    {
        $masterFeeSettings = PengaturanMasterFee::current();
        $rateData = $this->exchangeRateService->getUsdToIdrRate();
        $rate = (float) ($rateData['rate'] ?? 16796.0);

        return Inertia::render('Member/Affiliates/Calculator', [
            'tiers'   => KlaimKomisiItem::tierDefinitions(),
            'presets' => [
                'default_profit_rate'   => 5.0,
                'default_modal_idr'     => 100_000_000.0,
                'wd_pct'                => (float) ($masterFeeSettings->wd_maks_persen ?? 60.0),
                'master_fee_pct'        => (float) ($masterFeeSettings->fee_persen ?? 30.0),
                'months'                => 3,
                'max_investors'         => null,
                'default_exchange_rate' => $rate,
                'exchange_rate_meta'    => [
                    'rate'       => $rate,
                    'updated_at' => $rateData['updated_at'] ?? null,
                    'date'       => tanggal_singkat($rateData['date']) ?? null,
                    'is_live'    => (bool) ($rateData['is_live'] ?? false),
                    'source'     => $rateData['source'] ?? 'default_fallback',
                ],
            ],
        ]);
    }
}
