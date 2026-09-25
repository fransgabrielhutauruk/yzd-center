<?php

namespace App\Services;

use App\Models\AkunCopytrade;
use App\Models\RiwayatBalanceCopytrade;
use App\Models\User;
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

        $startDate = null;
        $endDate = now()->toDateString();
        $labelPeriode = 'Semua Periode';

        switch ($period) {
            case '1m':
                $startDate = now()->subMonth()->toDateString();
                $labelPeriode = '1 Bulan Terakhir';
                break;
            case '3m':
                $startDate = now()->subMonths(3)->toDateString();
                $labelPeriode = '3 Bulan Terakhir';
                break;
            case '6m':
                $startDate = now()->subMonths(6)->toDateString();
                $labelPeriode = '6 Bulan Terakhir';
                break;
            case '1y':
                $startDate = now()->subYear()->toDateString();
                $labelPeriode = '1 Tahun Terakhir';
                break;
            case '2y':
                $startDate = now()->subYears(2)->toDateString();
                $labelPeriode = '2 Tahun Terakhir';
                break;
            case '3y':
                $startDate = now()->subYears(3)->toDateString();
                $labelPeriode = '3 Tahun Terakhir';
                break;
            case '4y':
                $startDate = now()->subYears(4)->toDateString();
                $labelPeriode = '4 Tahun Terakhir';
                break;
            case 'custom':
                if ($customStart) {
                    $startDate = Carbon::parse($customStart)->toDateString();
                }
                if ($customEnd) {
                    $endDate = Carbon::parse($customEnd)->toDateString();
                }
                $labelPeriode = ($customStart ? Carbon::parse($customStart)->format('d M Y') : 'Awal') . ' s/d ' . ($customEnd ? Carbon::parse($customEnd)->format('d M Y') : 'Hari ini');
                break;
            case 'all':
            default:
                $period = 'all';
                $startDate = null;
                $labelPeriode = 'Semua Periode (Sejak Awal Join)';
                break;
        }

        // Query seluruh data riwayat akun
        $allRecords = RiwayatBalanceCopytrade::query()
            ->where('akun_copytrade_id', $akun->id)
            ->orderBy('tanggal_pencatatan', 'asc')
            ->get();

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

        // Saldo Terakhir Akun (Latest)
        $latestRecord = $allRecords->last();
        $currentBalance = $latestRecord ? (float) $latestRecord->balance : $modalAwal;

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
            $diff = Carbon::parse($startDate)->diffInMonths(Carbon::parse($endDate));
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
                'status'             => $akun->status,
            ],
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
}
