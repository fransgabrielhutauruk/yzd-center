<?php

namespace App\Support;

use App\Models\AkunCopytrade;
use Carbon\Carbon;

class ProfitPeriod
{
    /**
     * Menghitung tanggal akhir periode pertama berdasarkan tanggal mulai trading.
     * Aturan:
     * - Tanggal 1–15: periode pertama s/d akhir bulan tersebut.
     * - Tanggal 16–31: periode pertama s/d akhir bulan berikutnya.
     */
    public static function firstPeriodEnd(Carbon $mulai): Carbon
    {
        $m = $mulai->copy()->startOfDay();

        if ($m->day <= 15) {
            return $m->copy()->endOfMonth()->startOfDay();
        }

        return $m->copy()->addMonthNoOverflow()->endOfMonth()->startOfDay();
    }

    /**
     * Tanggal dibukanya pelaporan profit untuk suatu periode (tanggal 1 bulan setelah periode akhir).
     */
    public static function openReportDate(Carbon $periodeAkhir): Carbon
    {
        return $periodeAkhir->copy()->addDay()->startOfMonth()->startOfDay();
    }

    /**
     * Menghasilkan daftar seluruh periode yang sudah selesai sebelum $today.
     *
     * @return array<int, array{ke: int, awal: Carbon, akhir: Carbon, jatuh_tempo: Carbon, open_date: Carbon}>
     */
    public static function periods(Carbon $mulai, Carbon $today): array
    {
        $todayStart = $today->copy()->startOfDay();
        $p1Awal = $mulai->copy()->startOfDay();
        $p1Akhir = self::firstPeriodEnd($p1Awal);

        $results = [];

        // Periode pertama hanya masuk jika periode_akhir < $today
        if ($p1Akhir->gte($todayStart)) {
            return $results;
        }

        $results[] = [
            'ke'          => 1,
            'awal'        => $p1Awal,
            'akhir'       => $p1Akhir,
            'jatuh_tempo' => self::openReportDate($p1Akhir),
            'open_date'   => self::openReportDate($p1Akhir),
        ];

        $currentKe = 2;
        $nextAwal = $p1Akhir->copy()->addDay()->startOfMonth()->startOfDay();

        while (true) {
            $nextAkhir = $nextAwal->copy()->endOfMonth()->startOfDay();

            if ($nextAkhir->gte($todayStart)) {
                break;
            }

            $results[] = [
                'ke'          => $currentKe,
                'awal'        => $nextAwal->copy(),
                'akhir'       => $nextAkhir->copy(),
                'jatuh_tempo' => self::openReportDate($nextAkhir),
                'open_date'   => self::openReportDate($nextAkhir),
            ];

            $currentKe++;
            $nextAwal = $nextAkhir->copy()->addDay()->startOfMonth()->startOfDay();
        }

        return $results;
    }

    /**
     * Mengambil periode tertua berikutnya yang berhak dilaporkan oleh akun
     * (belum ada laporan, dan tanggal saat ini sudah mencapai openReportDate).
     *
     * @return array{ke: int, awal: Carbon, akhir: Carbon, jatuh_tempo: Carbon, open_date: Carbon}|null
     */
    public static function nextReportablePeriod(AkunCopytrade $akun, ?Carbon $today = null): ?array
    {
        $today = ($today ?? Carbon::now())->startOfDay();

        if (!$akun->tanggal_mulai) {
            return null;
        }

        $mulai = Carbon::parse($akun->tanggal_mulai)->startOfDay();
        $allPeriods = self::periods($mulai, $today);

        // Ambil periode_ke yang sudah ada laporannya
        $reportedPeriodKes = $akun->laporanProfit()
            ->pluck('periode_ke')
            ->map(fn ($k) => (int) $k)
            ->all();

        foreach ($allPeriods as $p) {
            if (!in_array($p['ke'], $reportedPeriodKes, true)) {
                // Wajib berurutan (periode lama dulu)
                return $p;
            }
        }

        return null;
    }

    /**
     * Memeriksa apakah akun masih berada di periode pertamanya (today <= firstPeriodEnd).
     */
    public static function isInFirstPeriod(AkunCopytrade $akun, ?Carbon $today = null): bool
    {
        if ($akun->status !== 'active' || !$akun->tanggal_mulai) {
            return false;
        }

        $today = ($today ?? Carbon::now())->startOfDay();
        $mulai = Carbon::parse($akun->tanggal_mulai)->startOfDay();
        $p1Akhir = self::firstPeriodEnd($mulai);

        return $today->lte($p1Akhir);
    }
}
