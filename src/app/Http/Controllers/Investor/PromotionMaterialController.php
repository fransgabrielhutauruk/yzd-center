<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Models\PengaturanMaster;
use App\Services\ProfitReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromotionMaterialController extends Controller
{
    public function __construct(
        private readonly ProfitReportService $profitReportService,
    ) {}

    /**
     * Tampilkan galeri materi promosi dan laporan keuntungan khusus untuk Affiliator.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isAffiliator(), 403, 'Akses materi promosi dan laporan keuntungan hanya tersedia untuk mitra affiliator.');

        $filters = $request->only(['tipe', 'is_kalender', 'tahun', 'bulan', 'search', 'sort_by']);

        $reports = $this->profitReportService->getPaginatedReports(
            $filters,
            perPage: (int) $request->input('per_page', 9),
            onlyPublished: true
        );

        $referralCode = $user->referral_code ?? null;
        $referralUrl = $referralCode ? route('register', ['ref' => $referralCode]) : route('register');
        $masterSetting = PengaturanMaster::first();

        return Inertia::render('Investor/Promotions/Index', [
            'reports'      => $reports,
            'filters'      => $filters,
            'statistics'   => $this->profitReportService->getStatistics(),
            'referralCode' => $referralCode,
            'referralUrl'  => $referralUrl,
            'brokerUrl'    => $masterSetting->link_referral_broker ?? '',
            'isAffiliator' => $user->isAffiliator(),
        ]);
    }
}
