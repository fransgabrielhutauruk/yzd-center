<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreCopytradeRequest;
use App\Models\AkunCopytrade;
use App\Models\InvestorOnboardingStep;
use App\Models\PengaturanMaster;
use App\Services\CopytradeService;
use App\Services\ExchangeRateService;
use App\Services\ProfitSummaryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvestmentPortfolioController extends Controller
{
    public function __construct(
        protected ProfitSummaryService $profitSummaryService,
        protected CopytradeService $copytradeService,
        protected ExchangeRateService $exchangeRateService
    ) {}

    /**
     * Tampilkan halaman terpadu Portofolio Investasi (Akun, Kinerja & Saldo BEP).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // 1. Ambil Ringkasan Konsolidasi Seluruh Portofolio
        $consolidated = $this->profitSummaryService->getPortfolioConsolidatedSummary($user);

        // 2. Ambil Daftar Seluruh Akun Investasi Milik User Beserta Laporan Profit Terkini
        $accounts = AkunCopytrade::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->with(['laporanProfit' => fn ($q) => $q->orderBy('periode_ke', 'desc')])
            ->orderBy('id', 'asc')
            ->get();

        $kursData = $this->exchangeRateService->getUsdToIdrRate();
        $kurs = (float) ($kursData['rate'] ?? 16796.0);

        $accountsFormatted = $accounts->map(function (AkunCopytrade $akun) use ($kurs) {
            $modalAwal = (float) $akun->modal_riil;
            $isActive = $akun->status === 'active';
            $reports = $akun->laporanProfit;
            $totalProfit = $isActive ? (float) $reports->sum('profit_usd') : 0.0;
            $currentBalance = $modalAwal + $totalProfit;
            $growthPct = ($isActive && $modalAwal > 0) ? round(($totalProfit / $modalAwal) * 100.0, 2) : 0.0;

            $startDateCarbon = $akun->tanggal_mulai ?? $akun->created_at;
            $diffMonths = $startDateCarbon ? max(0, (int) Carbon::parse($startDateCarbon)->diffInMonths(now())) : 0;
            $reportsCount = $reports->count();
            $monthsActive = max($diffMonths, $reportsCount > 1 ? $reportsCount - 1 : 0);
            $latestReport = $reports->first();

            return [
                'id'             => $akun->id,
                'tier'           => $akun->tier,
                'tier_investasi' => $akun->tier,
                'tier_label'     => match ($akun->tier) {
                    'tier_1' => 'Tier 1 (Cent < $5,000)',
                    'tier_2' => 'Tier 2 (Standard $5,000 - $300,000)',
                    'tier_3' => 'Tier 3 (Direct VPS ≥ $300,000)',
                    default  => $akun->tier,
                },
                'broker'               => $akun->broker,
                'tipe_akun'            => $akun->tipe_akun,
                'nomor_akun_trading'   => $akun->nomor_akun_trading,
                'modal_riil'           => $modalAwal,
                'modal_riil_idr'       => round($modalAwal * $kurs, 0),
                'modal_riil_formatted' => '$' . number_format($modalAwal, 2, '.', ','),
                'current_balance'      => round($currentBalance, 2),
                'current_balance_idr'  => round($currentBalance * $kurs, 0),
                'profit_nominal'       => round($totalProfit, 2),
                'profit_nominal_idr'   => round($totalProfit * $kurs, 0),
                'growth_pct'           => $growthPct,
                'status'               => $akun->status,
                'tanggal_mulai'        => $akun->tanggal_mulai ? (function_exists('tanggal_indo') ? tanggal_indo($akun->tanggal_mulai) : $akun->tanggal_mulai->format('d M Y')) : '-',
                'months_active'        => $monthsActive,
                'nama_bank'            => $akun->nama_bank,
                'nomor_rekening'       => $akun->nomor_rekening,
                'atas_nama_rekening'   => $akun->atas_nama_rekening,
                'server_broker'        => $akun->server_broker,
                'catatan_admin'        => $akun->catatan_admin,
                'myfxbook_url'         => $akun->myfxbook_url,
                'has_myfxbook'         => !empty($akun->myfxbook_url),
                'has_surat_perjanjian' => !empty($akun->surat_perjanjian_path),
                'latest_record_date'   => $latestReport?->periode_akhir?->format('Y-m-d'),
                'records_count'        => $reportsCount,
            ];
        });

        // 3. Tentukan Akun yang Dipilih untuk Detail Kinerja
        $selectedAccountId = $request->integer('account_id');
        $selectedAccount = $accounts->firstWhere('id', $selectedAccountId);

        if (!$selectedAccount) {
            $selectedAccount = $accounts->firstWhere('status', 'active') ?: $accounts->first();
            $selectedAccountId = $selectedAccount?->id;
        }

        // 4. Ambil Detail Kinerja Akun Terpilih Berdasarkan Periode Bebas (Khusus Akun Aktif)
        $period = $request->string('period', 'all')->value();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $summary = null;
        if ($selectedAccount && $selectedAccount->status === 'active') {
            $summary = $this->profitSummaryService->getPerformanceSummary(
                $selectedAccount,
                $period,
                $startDate,
                $endDate
            );
        }

        $onboardingStep = InvestorOnboardingStep::firstOrCreate(
            ['user_id' => $user->id, 'status' => 'in_progress'],
            [
                'modal_usd'       => null,
                'tier'            => null,
                'completed_steps' => [],
                'draft_data'      => [],
            ]
        );

        return Inertia::render('Member/Investments/Index', [
            'consolidated'        => $consolidated,
            'accounts'            => $accountsFormatted,
            'selected_account_id' => $selectedAccountId,
            'summary'             => $summary,
            'has_existing'        => AkunCopytrade::where('user_id', $user->id)->whereIn('status', ['pending', 'active'])->exists(),
            'onboarding'          => [
                'id'              => $onboardingStep->id,
                'modal_usd'       => $onboardingStep->modal_usd !== null ? (float) $onboardingStep->modal_usd : null,
                'tier'            => $onboardingStep->tier,
                'completed_steps' => $onboardingStep->completed_steps ?? [],
                'draft_data'      => $onboardingStep->draft_data ?? [],
                'status'          => $onboardingStep->status,
            ],
            'filters' => [
                'period'     => $period,
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
            'exchange_rate_meta' => $kursData,
            'today_date'         => now()->toDateString(),
        ]);
    }

    /**
     * Mendaftarkan akun investasi baru (Tier 1, Tier 2, atau Tier 3).
     */
    public function storeAccount(StoreCopytradeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $suratPerjanjian = $request->file('dokumen_perjanjian');

        $akun = $this->copytradeService->storeCopytrade($user, $validated, $suratPerjanjian);

        // Tandai progres onboarding aktif sebagai submitted
        InvestorOnboardingStep::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->update(['status' => 'submitted']);

        return redirect()->route('member.investments.index')
            ->with('success', "Pendaftaran akun trading {$akun->nomor_akun_trading} ({$akun->tier_investasi}) berhasil diajukan dan sedang menunggu verifikasi Administrator.");
    }

    /**
     * Simpan pembaruan progres onboarding / checklist langkah investasi investor.
     */
    public function updateOnboarding(Request $request): RedirectResponse
    {
        $user = $request->user();
        $step = InvestorOnboardingStep::firstOrCreate(
            ['user_id' => $user->id, 'status' => 'in_progress']
        );

        $modalUsd = $request->has('modal_usd') && filled($request->input('modal_usd'))
            ? (float) $request->input('modal_usd')
            : null;

        $tier = $request->input('tier');
        if (!$tier && $modalUsd !== null && $modalUsd > 0) {
            $tier = match (true) {
                $modalUsd < PengaturanMaster::TIER_2_MIN_USD => 'tier_1',
                $modalUsd < PengaturanMaster::TIER_3_MIN_USD => 'tier_2',
                default                                      => 'tier_3',
            };
        }

        $step->update([
            'modal_usd'       => $modalUsd,
            'tier'            => $tier,
            'completed_steps' => $request->input('completed_steps', $step->completed_steps ?? []),
            'draft_data'      => $request->input('draft_data', $step->draft_data ?? []),
        ]);

        return redirect()->back();
    }

    /**
     * Reset progres flow investor untuk memulai dari awal.
     */
    public function resetOnboarding(Request $request): RedirectResponse
    {
        $user = $request->user();
        InvestorOnboardingStep::where('user_id', $user->id)->delete();

        return redirect()->back()->with('success', 'Alur panduan investasi berhasil di-reset.');
    }

    /**
     * Memperbarui tautan Myfxbook akun copytrade oleh investor pemilik akun.
     */
    public function updateMyfxbook(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'myfxbook_url' => [
                'required',
                'url',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $host = parse_url((string) $value, PHP_URL_HOST);
                    if (!$host || !str_contains(strtolower($host), 'myfxbook.com')) {
                        $fail('Tautan Myfxbook harus berasal dari domain myfxbook.com.');
                    }
                },
            ],
        ], [
            'myfxbook_url.required' => 'Tautan Myfxbook wajib diisi.',
            'myfxbook_url.url'      => 'Format tautan Myfxbook tidak valid.',
            'myfxbook_url.max'      => 'Tautan Myfxbook maksimal 255 karakter.',
        ]);

        $akun = AkunCopytrade::findOrFail($id);
        if ($akun->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke akun ini.');
        }

        $this->copytradeService->updateMyfxbookUrl($akun, $validated['myfxbook_url'], $request->user());

        return redirect()->back()->with('success', 'Tautan Myfxbook akun copytrade berhasil diperbarui.');
    }

    /**
     * Unduh berkas template surat perjanjian Word (.docx) terisi otomatis.
     */
    public function downloadTemplate(Request $request, string $tier): BinaryFileResponse|RedirectResponse
    {
        $user = $request->user();
        $formData = $request->all();

        $generatedPath = $this->copytradeService->generatePerjanjianDocx($user, $tier, $formData);

        if (!$generatedPath || !file_exists($generatedPath)) {
            return redirect()->back()->with('error', 'Berkas template perjanjian belum tersedia atau gagal diproses.');
        }

        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $user->name);
        $tierLabel = strtoupper(str_replace('_', '', $tier));
        $downloadName = "Surat_Perjanjian_Kemitraan_{$tierLabel}_{$cleanName}.docx";

        return response()->download($generatedPath, $downloadName)->deleteFileAfterSend(true);
    }
}
