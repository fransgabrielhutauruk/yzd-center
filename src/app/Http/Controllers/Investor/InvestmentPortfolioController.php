<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investor\RiwayatBalanceRequest;
use App\Http\Requests\Investor\StoreCopytradeRequest;
use App\Models\AkunCopytrade;
use App\Models\PengaturanMaster;
use App\Models\RiwayatBalanceCopytrade;
use App\Services\BalanceService;
use App\Services\CopytradeService;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvestmentPortfolioController extends Controller
{
    public function __construct(
        protected BalanceService $balanceService,
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
        $consolidated = $this->balanceService->getPortfolioConsolidatedSummary($user);

        // Pengingat In-App Rekapitulasi Saldo Tanggal 1 (jika periode hari ke-1 s/d 5)
        $this->balanceService->sendMonthlyBalanceReminderIfNeeded($user);

        // 2. Ambil Daftar Seluruh Akun Investasi Milik User Beserta Saldo Terkini (Kecuali yang ditolak)
        $accounts = AkunCopytrade::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->with(['riwayatBalance' => fn ($q) => $q->orderBy('tanggal_pencatatan', 'desc')->orderBy('id', 'desc')])
            ->orderBy('id', 'asc')
            ->get();

        $kursData = $this->exchangeRateService->getUsdToIdrRate();
        $kurs = (float) ($kursData['rate'] ?? 16796.0);

        $accountsFormatted = $accounts->map(function (AkunCopytrade $akun) use ($kurs) {
            $modalAwal = (float) $akun->modal_riil;
            $isActive = $akun->status === 'active';
            $latestRecord = $isActive ? $akun->riwayatBalance->sortByDesc('id')->sortByDesc('tanggal_pencatatan')->first() : null;
            $currentBalance = $latestRecord ? (float) $latestRecord->balance : $modalAwal;
            $profitNominal = $isActive ? ($currentBalance - $modalAwal) : 0.0;
            $growthPct = ($isActive && $modalAwal > 0) ? round(($profitNominal / $modalAwal) * 100.0, 2) : 0.0;

            $startDateCarbon = $akun->tanggal_mulai ?? $akun->created_at;
            $diffMonths = $startDateCarbon ? max(0, (int) Carbon::parse($startDateCarbon)->diffInMonths(now())) : 0;
            $recordsCount = $isActive ? $akun->riwayatBalance->count() : 0;
            $monthsActive = max($diffMonths, $recordsCount > 1 ? $recordsCount - 1 : 0);

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
                'profit_nominal'       => round($profitNominal, 2),
                'profit_nominal_idr'   => round($profitNominal * $kurs, 0),
                'growth_pct'           => $growthPct,
                'status'               => $akun->status,
                'tanggal_mulai'        => $akun->tanggal_mulai ? (function_exists('tanggal_indo') ? tanggal_indo($akun->tanggal_mulai) : $akun->tanggal_mulai->format('d M Y')) : '-',
                'months_active'        => $monthsActive,
                'nama_bank'            => $akun->nama_bank,
                'nomor_rekening'       => $akun->nomor_rekening,
                'atas_nama_rekening'   => $akun->atas_nama_rekening,
                'server_broker'        => $akun->server_broker,
                'catatan_admin'        => $akun->catatan_admin,
                'has_surat_perjanjian' => !empty($akun->surat_perjanjian_path),
                'latest_record_date'   => $latestRecord?->tanggal_pencatatan?->format('Y-m-d'),
                'records_count'        => $recordsCount,
            ];
        });

        // 3. Tentukan Akun yang Dipilih untuk Detail Kinerja & Riwayat Saldo
        $selectedAccountId = $request->integer('account_id');
        $selectedAccount = $accounts->firstWhere('id', $selectedAccountId);

        if (!$selectedAccount) {
            // Utamakan akun aktif pertama, jika tidak ada baru akun pending pertama
            $selectedAccount = $accounts->firstWhere('status', 'active') ?: $accounts->first();
            $selectedAccountId = $selectedAccount?->id;
        }

        // 4. Ambil Detail Kinerja Akun Terpilih Berdasarkan Periode Bebas (Khusus Akun Aktif)
        $period = $request->string('period', 'all')->value();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $summary = null;
        if ($selectedAccount && $selectedAccount->status === 'active') {
            $summary = $this->balanceService->getPerformanceSummary(
                $selectedAccount,
                $period,
                $startDate,
                $endDate
            );
        }

        $pengaturan = PengaturanMaster::getSettings();

        return Inertia::render('Investor/Investments/Index', [
            'consolidated'        => $consolidated,
            'accounts'            => $accountsFormatted,
            'selected_account_id' => $selectedAccountId,
            'summary'             => $summary,
            'has_existing'        => AkunCopytrade::where('user_id', $user->id)->whereIn('status', ['pending', 'active'])->exists(),
            'filters'             => [
                'period'     => $period,
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
            'pengaturan'            => $pengaturan,
            'exchange_rate_meta'    => $kursData,
            'today_date'            => now()->toDateString(),
            'is_first_day_of_month' => now()->day === 1,
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

        return redirect()->route('investor.investments.index')
            ->with('success', "Pendaftaran akun trading {$akun->nomor_akun_trading} ({$akun->tier_investasi}) berhasil diajukan dan sedang menunggu verifikasi Administrator.");
    }

    /**
     * Menyimpan pencatatan saldo tanggal 1 (atau tanggal tertentu) dan menghitung keuntungan otomatis.
     */
    public function storeBalance(RiwayatBalanceRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $akun = AkunCopytrade::query()
            ->where('id', $validated['akun_copytrade_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($akun->status !== 'active') {
            return redirect()->back()->with('error', 'Catatan saldo hanya dapat diinput untuk akun trading yang telah diverifikasi dan aktif.');
        }

        $record = $this->balanceService->recordBalance($akun, $validated, $user);

        $tglIndo = function_exists('tanggal_indo')
            ? tanggal_indo($record->tanggal_pencatatan)
            : $record->tanggal_pencatatan->format('d/m/Y');

        return redirect()->route('investor.investments.index', [
            'account_id' => $akun->id,
        ])->with('success', "Saldo per {$tglIndo} pada akun {$akun->nomor_akun_trading} berhasil dicatat.");
    }

    /**
     * Memperbarui catatan saldo tertentu dan merekalibrasi seluruh riwayat.
     */
    public function updateBalance(RiwayatBalanceRequest $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $record = RiwayatBalanceCopytrade::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $akun = $record->akunCopytrade;

        if ($akun->status !== 'active') {
            return redirect()->back()->with('error', 'Catatan saldo hanya dapat diperbarui untuk akun trading yang aktif.');
        }

        $record = $this->balanceService->updateBalance($record, $validated);

        $tglIndo = function_exists('tanggal_indo')
            ? tanggal_indo($record->tanggal_pencatatan)
            : $record->tanggal_pencatatan->format('d/m/Y');

        return redirect()->route('investor.investments.index', [
            'account_id' => $akun->id,
        ])->with('success', "Catatan saldo per {$tglIndo} pada akun {$akun->nomor_akun_trading} berhasil diperbarui.");
    }

    /**
     * Menghapus catatan saldo tertentu.
     */
    public function destroyBalance(int $id, Request $request): RedirectResponse
    {
        $user = $request->user();

        $record = RiwayatBalanceCopytrade::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $accountId = $record->akun_copytrade_id;
        $this->balanceService->deleteBalance($record);

        return redirect()->route('investor.investments.index', [
            'account_id' => $accountId,
        ])->with('success', 'Catatan saldo berhasil dihapus.');
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
