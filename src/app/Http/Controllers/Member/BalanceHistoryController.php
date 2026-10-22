<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\RiwayatBalanceRequest;
use App\Models\AkunCopytrade;
use App\Models\RiwayatBalanceCopytrade;
use App\Services\BalanceService;
use App\Services\ExchangeRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BalanceHistoryController extends Controller
{
    public function __construct(
        protected BalanceService $balanceService,
        protected ExchangeRateService $exchangeRateService
    ) {}

    /**
     * Tampilkan halaman rekapitulasi Kinerja & Riwayat Saldo MT4.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Ambil seluruh akun copytrade milik member
        $accounts = AkunCopytrade::query()
            ->where('user_id', $user->id)
            ->orderBy('id', 'asc')
            ->get();

        $selectedAccountId = $request->integer('account_id') ?: $accounts->first()?->id;
        $selectedAccount = $accounts->firstWhere('id', $selectedAccountId);

        $period = $request->string('period', 'all')->value();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $summary = null;
        if ($selectedAccount) {
            $summary = $this->balanceService->getPerformanceSummary(
                $selectedAccount,
                $period,
                $startDate,
                $endDate
            );
        }

        $kursData = $this->exchangeRateService->getUsdToIdrRate();

        return Inertia::render('Member/Copytrade/Balance', [
            'accounts' => $accounts->map(fn ($acc) => [
                'id'                 => $acc->id,
                'nomor_akun_trading' => $acc->nomor_akun_trading,
                'broker'             => $acc->broker,
                'tier'               => $acc->tier,
                'modal_riil'         => (float) $acc->modal_riil,
                'status'             => $acc->status,
            ]),
            'selected_account_id' => $selectedAccountId,
            'summary'             => $summary,
            'filters'             => [
                'period'     => $period,
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
            'exchange_rate_meta'    => $kursData,
            'today_date'            => now()->toDateString(),
            'is_first_day_of_month' => now()->day === 1,
        ]);
    }

    /**
     * Menyimpan input saldo tanggal pencatatan dan memperbarui keuntungan otomatis.
     */
    public function store(RiwayatBalanceRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $akun = AkunCopytrade::query()
            ->where('id', $validated['akun_copytrade_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $record = $this->balanceService->recordBalance($akun, $validated, $user);

        $tglIndo = function_exists('tanggal_indo')
            ? tanggal_indo($record->tanggal_pencatatan)
            : $record->tanggal_pencatatan->format('d/m/Y');

        return redirect()->route('member.investments.index', [
            'account_id' => $akun->id,
        ])->with('success', "Saldo per {$tglIndo} berhasil dicatat. Keuntungan otomatis dihitung.");
    }

    /**
     * Menghapus catatan saldo tertentu.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();

        $record = RiwayatBalanceCopytrade::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $akunId = $record->akun_copytrade_id;
        $this->balanceService->deleteBalance($record);

        return redirect()->route('member.investments.index', [
            'account_id' => $akunId,
        ])->with('success', 'Catatan saldo berhasil dihapus.');
    }
}
