<?php

namespace App\Services;

use App\Models\AkunCopytrade;
use App\Models\RiwayatBalanceCopytrade;
use App\Models\User;
use App\Notifications\MonthlyBalanceRecapReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService
    ) {}

    /**
     * Menyimpan input saldo tanggal pencatatan dan menghitung otomatis keuntungannya.
     */
    public function recordBalance(AkunCopytrade $akun, array $data, User $user): RiwayatBalanceCopytrade
    {
        if ($akun->status !== 'active') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'akun_copytrade_id' => 'Catatan saldo hanya dapat diinput untuk akun trading yang telah diverifikasi dan aktif.',
            ]);
        }

        return DB::transaction(function () use ($akun, $data, $user) {
            $tanggal = Carbon::parse($data['tanggal_pencatatan'])->toDateString();
            $balance = (float) $data['balance'];
            $modalAwal = (float) $akun->modal_riil;

            // Cari record sebelum tanggal ini untuk menghitung keuntungan MoM
            $prevRecord = RiwayatBalanceCopytrade::query()
                ->where('akun_copytrade_id', $akun->id)
                ->where('tanggal_pencatatan', '<', $tanggal)
                ->orderByDesc('tanggal_pencatatan')
                ->first();

            $baseBalance = $prevRecord ? (float) $prevRecord->balance : $modalAwal;

            $keuntunganNominal = $balance - $baseBalance;
            $keuntunganPersen = $baseBalance > 0
                ? round(($keuntunganNominal / $baseBalance) * 100.0, 2)
                : 0.0;

            // Keuntungan Kumulatif All-Time (Saldo terakhir - Modal Awal)
            $keuntunganKumulatif = $balance - $modalAwal;
            $pertumbuhanKumulatifPersen = $modalAwal > 0
                ? round(($keuntunganKumulatif / $modalAwal) * 100.0, 2)
                : 0.0;

            $record = RiwayatBalanceCopytrade::updateOrCreate(
                [
                    'akun_copytrade_id'  => $akun->id,
                    'tanggal_pencatatan' => $tanggal,
                ],
                [
                    'user_id'                      => $user->id,
                    'balance'                      => $balance,
                    'keuntungan_nominal'           => round($keuntunganNominal, 2),
                    'keuntungan_persen'            => round($keuntunganPersen, 2),
                    'keuntungan_kumulatif'         => round($keuntunganKumulatif, 2),
                    'pertumbuhan_kumulatif_persen' => round($pertumbuhanKumulatifPersen, 2),
                    'catatan'                      => !empty($data['catatan']) ? trim($data['catatan']) : null,
                ]
            );

            // Rekalkulasi data record setelah tanggal ini (jika input disisipkan di antara tanggal)
            $this->recalculateAccountBalances($akun);

            return $record->fresh();
        });
    }

    /**
     * Menghapus catatan balance tertentu dan merekalibrasi data berurutan.
     */
    public function deleteBalance(RiwayatBalanceCopytrade $record): void
    {
        DB::transaction(function () use ($record) {
            $akun = $record->akunCopytrade;
            $record->delete();
            $this->recalculateAccountBalances($akun);
        });
    }

    /**
     * Rekalkulasi otomatis seluruh record berurutan agar selisih dan persentase selalu 100% konsisten.
     */
    public function recalculateAccountBalances(AkunCopytrade $akun): void
    {
        $records = RiwayatBalanceCopytrade::query()
            ->where('akun_copytrade_id', $akun->id)
            ->orderBy('tanggal_pencatatan', 'asc')
            ->get();

        $modalAwal = (float) $akun->modal_riil;
        $prevBalance = $modalAwal;

        foreach ($records as $rec) {
            $currentBal = (float) $rec->balance;
            $keuntunganNominal = $currentBal - $prevBalance;
            $keuntunganPersen = $prevBalance > 0
                ? round(($keuntunganNominal / $prevBalance) * 100.0, 2)
                : 0.0;

            $keuntunganKumulatif = $currentBal - $modalAwal;
            $pertumbuhanKumulatifPersen = $modalAwal > 0
                ? round(($keuntunganKumulatif / $modalAwal) * 100.0, 2)
                : 0.0;

            $rec->updateQuietly([
                'keuntungan_nominal'           => round($keuntunganNominal, 2),
                'keuntungan_persen'            => round($keuntunganPersen, 2),
                'keuntungan_kumulatif'         => round($keuntunganKumulatif, 2),
                'pertumbuhan_kumulatif_persen' => round($pertumbuhanKumulatifPersen, 2),
            ]);

            $prevBalance = $currentBal;
        }
    }

    /**
     * Mengambil ringkasan performa dan riwayat saldo berdasarkan filter periode bebas.
     * Periode yang didukung: 1m, 3m, 6m, 1y, 2y, 3y, 4y, all, custom.
     */
    public function getPerformanceSummary(
        AkunCopytrade $akun,
        string $period = 'all',
        ?string $customStart = null,
        ?string $customEnd = null
    ): array {
        $kursData = $this->exchangeRateService->getUsdToIdrRate();
        $kurs = (float) ($kursData['rate'] ?? 16796.0);
        $modalAwal = (float) $akun->modal_riil;

        $allRecords = RiwayatBalanceCopytrade::query()
            ->where('akun_copytrade_id', $akun->id)
            ->orderBy('tanggal_pencatatan', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $latestRecord = $allRecords->last();
        $currentBalance = $latestRecord ? (float) $latestRecord->balance : $modalAwal;

        $latestDateCarbon = $latestRecord ? Carbon::parse($latestRecord->tanggal_pencatatan) : now();
        $anchorDate = $latestDateCarbon->gt(now()) ? $latestDateCarbon->copy() : now();

        $startDate = null;
        $endDate = null;
        $labelPeriode = 'Semua Periode';

        switch ($period) {
            case '1m':
                $startDate = $anchorDate->copy()->subMonth()->toDateString();
                $labelPeriode = '1 Bulan Terakhir';
                break;
            case '3m':
                $startDate = $anchorDate->copy()->subMonths(3)->toDateString();
                $labelPeriode = '3 Bulan Terakhir';
                break;
            case '6m':
                $startDate = $anchorDate->copy()->subMonths(6)->toDateString();
                $labelPeriode = '6 Bulan Terakhir';
                break;
            case '1y':
                $startDate = $anchorDate->copy()->subYear()->toDateString();
                $labelPeriode = '1 Tahun Terakhir';
                break;
            case '2y':
                $startDate = $anchorDate->copy()->subYears(2)->toDateString();
                $labelPeriode = '2 Tahun Terakhir';
                break;
            case '3y':
                $startDate = $anchorDate->copy()->subYears(3)->toDateString();
                $labelPeriode = '3 Tahun Terakhir';
                break;
            case '4y':
                $startDate = $anchorDate->copy()->subYears(4)->toDateString();
                $labelPeriode = '4 Tahun Terakhir';
                break;
            case 'custom':
                if ($customStart) {
                    $startDate = Carbon::parse($customStart)->startOfMonth()->toDateString();
                }
                if ($customEnd) {
                    $endDate = Carbon::parse($customEnd)->endOfMonth()->toDateString();
                }
                $startLabel = $customStart ? Carbon::parse($customStart)->locale('id')->translatedFormat('F Y') : 'Awal';
                $endLabel = $customEnd ? Carbon::parse($customEnd)->locale('id')->translatedFormat('F Y') : 'Sekarang';
                $labelPeriode = ($startLabel === $endLabel) ? $startLabel : "{$startLabel} s/d {$endLabel}";
                break;
            case 'all':
            default:
                $period = 'all';
                $startDate = null;
                $endDate = null;
                $labelPeriode = 'Semua Periode (Sejak Awal Join)';
                break;
        }

        // Query data yang masuk dalam filter periode
        $filteredRecords = $allRecords->filter(function ($rec) use ($startDate, $endDate) {
            $tgl = $rec->tanggal_pencatatan->format('Y-m-d');
            if ($startDate && $tgl < $startDate) {
                return false;
            }
            if ($endDate && $tgl > $endDate) {
                return false;
            }
            return true;
        })->values();

        // Saldo Awal Periode & Saldo Akhir Periode
        if ($filteredRecords->isNotEmpty()) {
            $periodEndBalance = (float) $filteredRecords->last()->balance;

            // Saldo sebelum record pertama dalam periode (atau modal awal jika ini awal mula)
            $firstRecordDate = $filteredRecords->first()->tanggal_pencatatan->format('Y-m-d');
            $recordBeforePeriod = $allRecords->where('tanggal_pencatatan', '<', $firstRecordDate)->last();
            $periodStartBalance = $recordBeforePeriod ? (float) $recordBeforePeriod->balance : $modalAwal;
        } else {
            $periodStartBalance = $modalAwal;
            $periodEndBalance = $currentBalance;
        }

        $periodProfitNominal = $periodEndBalance - $periodStartBalance;
        $periodGrowthPct = $periodStartBalance > 0
            ? round(($periodProfitNominal / $periodStartBalance) * 100.0, 2)
            : 0.0;

        // Total Keuntungan All-Time
        $allTimeProfitNominal = $currentBalance - $modalAwal;
        $allTimeGrowthPct = $modalAwal > 0
            ? round(($allTimeProfitNominal / $modalAwal) * 100.0, 2)
            : 0.0;

        // Rata-rata Keuntungan Bulanan Periode
        $monthsCount = 1;
        if ($startDate) {
            $diff = Carbon::parse($startDate)->diffInMonths(Carbon::parse($endDate ?? $anchorDate));
            $monthsCount = max(1, (int) $diff);
        } elseif ($filteredRecords->count() > 1) {
            $diff = $filteredRecords->first()->tanggal_pencatatan->diffInMonths($filteredRecords->last()->tanggal_pencatatan);
            $monthsCount = max(1, (int) $diff);
        }

        $avgMonthlyProfitPct = round($periodGrowthPct / $monthsCount, 2);

        // Format riwayat untuk tabel
        $formattedHistory = $filteredRecords->map(function ($rec) use ($kurs) {
            return [
                'id'                           => $rec->id,
                'tanggal_pencatatan'           => $rec->tanggal_pencatatan->format('Y-m-d'),
                'tanggal_formatted'            => function_exists('tanggal_indo') ? tanggal_indo($rec->tanggal_pencatatan) : $rec->tanggal_pencatatan->format('d M Y'),
                'balance'                      => (float) $rec->balance,
                'balance_idr'                  => round((float) $rec->balance * $kurs, 0),
                'keuntungan_nominal'           => (float) $rec->keuntungan_nominal,
                'keuntungan_nominal_idr'       => round((float) $rec->keuntungan_nominal * $kurs, 0),
                'keuntungan_persen'            => (float) $rec->keuntungan_persen,
                'keuntungan_kumulatif'         => (float) $rec->keuntungan_kumulatif,
                'keuntungan_kumulatif_idr'     => round((float) $rec->keuntungan_kumulatif * $kurs, 0),
                'pertumbuhan_kumulatif_persen' => (float) $rec->pertumbuhan_kumulatif_persen,
                'catatan'                      => $rec->catatan,
            ];
        })->toArray();

        // Hitung durasi aktif akun dalam satuan bulan
        $earliestRecordDate = $allRecords->first()?->tanggal_pencatatan;
        $effectiveStartDate = $earliestRecordDate ?? $akun->copy_started_at ?? $akun->tanggal_mulai ?? $akun->created_at;
        $diffMonths = $effectiveStartDate ? max(0, (int) Carbon::parse($effectiveStartDate)->diffInMonths(now())) : 0;
        $recordsCount = $allRecords->count();
        $monthsActive = max($diffMonths, $recordsCount > 1 ? $recordsCount - 1 : 0);

        return [
            'account' => [
                'id'                 => $akun->id,
                'nomor_akun_trading' => $akun->nomor_akun_trading,
                'broker'             => $akun->broker,
                'tier'               => $akun->tier,
                'modal_riil'         => $modalAwal,
                'modal_riil_idr'     => round($modalAwal * $kurs, 0),
                'tanggal_mulai'      => $akun->tanggal_mulai?->format('Y-m-d'),
                'tanggal_mulai_indo' => function_exists('tanggal_indo') ? tanggal_indo($akun->tanggal_mulai) : $akun->tanggal_mulai?->format('d M Y'),
                'months_active'      => $monthsActive,
                'status'             => $akun->status,
            ],
            'months_active'               => $monthsActive,
            'period'                      => $period,
            'period_label'                => $labelPeriode,
            'start_date'                  => $startDate,
            'end_date'                    => $endDate,
            'period_start_balance'        => round($periodStartBalance, 2),
            'period_start_balance_idr'    => round($periodStartBalance * $kurs, 0),
            'period_end_balance'          => round($periodEndBalance, 2),
            'period_end_balance_idr'      => round($periodEndBalance * $kurs, 0),
            'period_profit_nominal'       => round($periodProfitNominal, 2),
            'period_profit_nominal_idr'   => round($periodProfitNominal * $kurs, 0),
            'period_growth_pct'           => $periodGrowthPct,
            'avg_monthly_profit_pct'      => $avgMonthlyProfitPct,
            'all_time_profit_nominal'     => round($allTimeProfitNominal, 2),
            'all_time_profit_nominal_idr' => round($allTimeProfitNominal * $kurs, 0),
            'all_time_growth_pct'         => $allTimeGrowthPct,
            'current_balance'             => round($currentBalance, 2),
            'current_balance_idr'         => round($currentBalance * $kurs, 0),
            'history'                     => $formattedHistory,
            'total_records_count'         => $allRecords->count(),
            'exchange_rate'               => $kurs,
        ];
    }

    /**
     * Menghitung ringkasan konsolidasi portofolio seluruh akun investasi milik investor.
     * Mengagregasi modal awal, saldo terkini, keuntungan all-time, dan estimasi BEP kuota riil.
     */
    public function getPortfolioConsolidatedSummary(User $user): array
    {
        $accounts = AkunCopytrade::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->with(['riwayatBalance' => function ($q) {
                $q->orderBy('tanggal_pencatatan', 'desc')->orderBy('id', 'desc');
            }])
            ->orderBy('id', 'asc')
            ->get();

        $kursData = $this->exchangeRateService->getUsdToIdrRate();
        $kurs = (float) ($kursData['rate'] ?? 16796.0);

        $activeAccounts = $accounts->where('status', 'active');
        $pendingAccounts = $accounts->where('status', 'pending');
        $activeCount = $activeAccounts->count();
        $pendingCount = $pendingAccounts->count();

        // Jika tidak ada akun aktif sama sekali (hanya ada pending atau tidak ada akun),
        // seluruh statistik dinonaktifkan (bernilai 0) sampai akun diverifikasi.
        if ($activeAccounts->isEmpty()) {
            return [
                'total_accounts'            => $accounts->count(),
                'active_accounts_count'     => 0,
                'pending_accounts_count'    => $pendingCount,
                'has_active_accounts'       => false,
                'total_initial_capital'     => 0.0,
                'total_initial_capital_idr' => 0,
                'total_current_balance'     => 0.0,
                'total_current_balance_idr' => 0,
                'total_all_time_profit'     => 0.0,
                'total_all_time_profit_idr' => 0,
                'total_all_time_growth_pct' => 0.0,
                'has_balance_history'       => false,
                'bep_summary'               => [
                    'bep_progress_pct'         => 0.0,
                    'initial_capital'          => 0.0,
                    'initial_capital_idr'      => 0,
                    'total_withdrawn'          => 0.0,
                    'total_withdrawn_idr'      => 0,
                    'total_compounded'         => 0.0,
                    'total_compounded_idr'     => 0,
                    'remaining_capital_to_bep' => 0.0,
                    'remaining_capital_idr'    => 0,
                    'remaining_to_bep'         => 0.0,
                    'remaining_to_bep_idr'     => 0,
                    'is_bep_achieved'          => false,
                    'months_to_bep'            => 23,
                    'estimated_months_to_bep'  => 23,
                    'remaining_months_to_bep'  => 23,
                    'avg_monthly_profit_pct'   => 0.0,
                    'months_active'            => 1,
                    'joined_months_count'      => 1,
                    'join_date_formatted'      => null,
                    'total_profit_accumulated' => 0.0,
                    'total_profit_idr'         => 0,
                ],
            ];
        }

        $totalInitialCapital = 0.0;
        $totalCurrentBalance = 0.0;
        $hasAnyBalanceRecord = false;

        foreach ($activeAccounts as $acc) {
            $modalAwal = (float) $acc->modal_riil;
            $totalInitialCapital += $modalAwal;

            $latestRecord = $acc->riwayatBalance->sortByDesc('id')->sortByDesc('tanggal_pencatatan')->first();
            if ($latestRecord) {
                $hasAnyBalanceRecord = true;
                $totalCurrentBalance += (float) $latestRecord->balance;
            } else {
                $totalCurrentBalance += $modalAwal;
            }
        }

        $totalAllTimeProfit = $totalCurrentBalance - $totalInitialCapital;
        $totalAllTimeGrowthPct = $totalInitialCapital > 0
            ? round(($totalAllTimeProfit / $totalInitialCapital) * 100.0, 2)
            : 0.0;

        $bepProgressPct = $totalInitialCapital > 0
            ? min(100.0, max(0.0, round(($totalAllTimeProfit / $totalInitialCapital) * 100.0, 1)))
            : 0.0;

        $remainingToBep = max(0.0, $totalInitialCapital - max(0.0, $totalAllTimeProfit));
        $isBepAchieved = $totalAllTimeProfit >= $totalInitialCapital && $totalInitialCapital > 0;

        $earliestActiveAccount = $activeAccounts->sortBy('created_at')->first();
        $joinDate = $earliestActiveAccount?->copy_started_at ?? $earliestActiveAccount?->tanggal_mulai ?? $earliestActiveAccount?->created_at;
        $startCarbon = $joinDate ? Carbon::parse($joinDate) : now();
        $diffMonths = (int) $startCarbon->diffInMonths(now());
        $monthsActive = max(1, $diffMonths > 0 ? $diffMonths : 1);

        $avgMonthlyProfitPct = $monthsActive > 0 ? round($totalAllTimeGrowthPct / $monthsActive, 2) : 0.0;
        if ($avgMonthlyProfitPct <= 0 && $hasAnyBalanceRecord) {
            $estimatedMonthsToBep = null;
        } else {
            $effectiveMonthlyRate = $avgMonthlyProfitPct > 0 ? ($avgMonthlyProfitPct / 100.0) : 0.035;
            $estimatedMonthsToBep = ($totalInitialCapital > 0 && $effectiveMonthlyRate > 0)
                ? (int) ceil(log(2) / log(1 + $effectiveMonthlyRate))
                : 21;
        }

        $remainingMonthsToBep = $isBepAchieved ? 0 : max(0, ($estimatedMonthsToBep ?: 23) - $monthsActive);

        return [
            'total_accounts'            => $accounts->count(),
            'active_accounts_count'     => $activeCount,
            'pending_accounts_count'    => $pendingCount,
            'has_active_accounts'       => true,
            'total_initial_capital'     => round($totalInitialCapital, 2),
            'total_initial_capital_idr' => round($totalInitialCapital * $kurs, 0),
            'total_current_balance'     => round($totalCurrentBalance, 2),
            'total_current_balance_idr' => round($totalCurrentBalance * $kurs, 0),
            'total_all_time_profit'     => round($totalAllTimeProfit, 2),
            'total_all_time_profit_idr' => round($totalAllTimeProfit * $kurs, 0),
            'total_all_time_growth_pct' => $totalAllTimeGrowthPct,
            'has_balance_history'       => $hasAnyBalanceRecord,
            'bep_summary'               => [
                'bep_progress_pct'         => $bepProgressPct,
                'initial_capital'          => round($totalInitialCapital, 2),
                'initial_capital_idr'      => round($totalInitialCapital * $kurs, 0),
                'total_withdrawn'          => 0.0,
                'total_withdrawn_idr'      => 0,
                'total_compounded'         => max(0.0, round($totalAllTimeProfit, 2)),
                'total_compounded_idr'     => max(0, round($totalAllTimeProfit * $kurs, 0)),
                'remaining_capital_to_bep' => round($remainingToBep, 2),
                'remaining_capital_idr'    => round($remainingToBep * $kurs, 0),
                'remaining_to_bep'         => round($remainingToBep, 2),
                'remaining_to_bep_idr'     => round($remainingToBep * $kurs, 0),
                'is_bep_achieved'          => $isBepAchieved,
                'months_to_bep'            => $estimatedMonthsToBep,
                'estimated_months_to_bep'  => $estimatedMonthsToBep,
                'remaining_months_to_bep'  => $remainingMonthsToBep,
                'avg_monthly_profit_pct'   => $avgMonthlyProfitPct,
                'months_active'            => $monthsActive,
                'joined_months_count'      => $monthsActive,
                'join_date_formatted'      => $earliestActiveAccount ? ($earliestActiveAccount->created_at ? (function_exists('tanggal_indo') ? tanggal_indo($earliestActiveAccount->created_at) : Carbon::parse($earliestActiveAccount->created_at)->format('d M Y')) : null) : null,
                'total_profit_accumulated' => max(0.0, round($totalAllTimeProfit, 2)),
                'total_profit_idr'         => max(0, round($totalAllTimeProfit * $kurs, 0)),
            ],
        ];
    }

    /**
     * Kirim notifikasi pengingat in-app rekapitulasi saldo bulanan jika memenuhi kriteria:
     * 1. Berada dalam rentang waktu rekapitulasi (tanggal 1-5 setiap bulan).
     * 2. Pengguna memiliki minimal 1 akun investasi aktif.
     * 3. Pengguna belum menginput saldo untuk tanggal 1 bulan berjalan pada setidaknya 1 akun aktifnya.
     * 4. Notifikasi untuk bulan & tahun ini belum pernah dikirimkan sebelumnya (mencegah duplikasi).
     */
    public function sendMonthlyBalanceReminderIfNeeded(User $user): bool
    {
        $now = Carbon::now();
        if ($now->day > 5) {
            return false;
        }

        $activeAccounts = AkunCopytrade::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        if ($activeAccounts->isEmpty()) {
            return false;
        }

        $firstDayOfMonth = $now->copy()->startOfMonth()->toDateString();

        // Cek apakah seluruh akun aktif sudah tercatat untuk tanggal 1 bulan ini
        $activeAccountIds = $activeAccounts->pluck('id');
        $recordedCount = RiwayatBalanceCopytrade::query()
            ->whereIn('akun_copytrade_id', $activeAccountIds)
            ->where('tanggal_pencatatan', $firstDayOfMonth)
            ->count();

        if ($recordedCount >= $activeAccounts->count()) {
            return false;
        }

        // Cek apakah notifikasi pengingat untuk bulan & tahun ini sudah pernah dikirim
        $alreadySent = $user->notifications()
            ->where('data->type', 'balance_recap_reminder')
            ->where('data->month', $now->month)
            ->where('data->year', $now->year)
            ->exists();

        if ($alreadySent) {
            return false;
        }

        $user->notify(new MonthlyBalanceRecapReminderNotification($now->month, $now->year));

        return true;
    }
}
