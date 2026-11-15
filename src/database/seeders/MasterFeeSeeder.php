<?php

namespace Database\Seeders;

use App\Models\AkunCopytrade;
use App\Models\LaporanProfitBulanan;
use App\Models\PengaturanMasterFee;
use App\Models\User;
use App\Support\ProfitPeriod;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MasterFeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = PengaturanMasterFee::current();

        $admin = User::where('role', 'administrator')->first();
        $investor = User::where('username', 'investor')->first();

        if (!$investor) {
            return;
        }

        $akun = AkunCopytrade::where('user_id', $investor->id)->first();
        if (!$akun) {
            return;
        }

        if (!$akun->myfxbook_url) {
            $akun->update([
                'myfxbook_url' => 'https://www.myfxbook.com/members/demo/yzd-investor/1234567',
            ]);
        }

        // Atur tanggal mulai 3 bulan yang lalu tanggal 5 (periode 1-15)
        $mulai = Carbon::now()->subMonths(3)->setDay(5)->startOfDay();
        $akun->update(['tanggal_mulai' => $mulai->toDateString()]);

        $periods = ProfitPeriod::periods($mulai, Carbon::now());

        // Buat laporan untuk periode yang ada
        // Periode 1 -> Lunas
        if (isset($periods[0])) {
            $p = $periods[0];
            $profit = 200.00;
            $feePersen = (float) $settings->fee_persen;
            $feeUsd = round($profit * ($feePersen / 100), 2);
            $wdUsd = 60.00; // 30% dari profit
            $wdIdr = 960000; // Kurs 16.000
            $kurs = $wdIdr / $wdUsd;
            $feeIdr = (int) round($feeUsd * $kurs);

            LaporanProfitBulanan::updateOrCreate(
                [
                    'akun_copytrade_id' => $akun->id,
                    'periode_ke'        => $p['ke'],
                ],
                [
                    'nomor'               => 'MFE-' . $p['akhir']->format('Ym') . '-0001',
                    'user_id'             => $investor->id,
                    'periode_awal'        => $p['awal']->toDateString(),
                    'periode_akhir'       => $p['akhir']->toDateString(),
                    'jatuh_tempo'         => $p['jatuh_tempo']->toDateString(),
                    'profit_usd'          => $profit,
                    'bukti_myfxbook_path' => 'master_fee/myfxbook/demo_p1.jpg',
                    'wd_usd'              => $wdUsd,
                    'wd_idr'              => $wdIdr,
                    'bukti_wd_path'       => 'master_fee/wd/demo_wd1.jpg',
                    'kurs_riil'           => $kurs,
                    'fee_persen'          => $feePersen,
                    'fee_usd'             => $feeUsd,
                    'fee_idr'             => $feeIdr,
                    'status'              => LaporanProfitBulanan::STATUS_LUNAS,
                    'bukti_transfer_path' => 'master_fee/proofs/demo_tf1.jpg',
                    'dibayar_at'          => $p['open_date']->copy()->addDays(2),
                    'verified_by'         => $admin?->id,
                    'verified_at'         => $p['open_date']->copy()->addDays(3),
                ]
            );
        }

        // Periode 2 -> Ditolak
        if (isset($periods[1])) {
            $p = $periods[1];
            $profit = 300.00;
            $feePersen = (float) $settings->fee_persen;
            $feeUsd = round($profit * ($feePersen / 100), 2);
            $wdUsd = 100.00;
            $wdIdr = 1600000;
            $kurs = $wdIdr / $wdUsd;
            $feeIdr = (int) round($feeUsd * $kurs);

            LaporanProfitBulanan::updateOrCreate(
                [
                    'akun_copytrade_id' => $akun->id,
                    'periode_ke'        => $p['ke'],
                ],
                [
                    'nomor'               => 'MFE-' . $p['akhir']->format('Ym') . '-0001',
                    'user_id'             => $investor->id,
                    'periode_awal'        => $p['awal']->toDateString(),
                    'periode_akhir'       => $p['akhir']->toDateString(),
                    'jatuh_tempo'         => $p['jatuh_tempo']->toDateString(),
                    'profit_usd'          => $profit,
                    'bukti_myfxbook_path' => 'master_fee/myfxbook/demo_p2.jpg',
                    'wd_usd'              => $wdUsd,
                    'wd_idr'              => $wdIdr,
                    'bukti_wd_path'       => 'master_fee/wd/demo_wd2.jpg',
                    'kurs_riil'           => $kurs,
                    'fee_persen'          => $feePersen,
                    'fee_usd'             => $feeUsd,
                    'fee_idr'             => $feeIdr,
                    'status'              => LaporanProfitBulanan::STATUS_DITOLAK,
                    'bukti_transfer_path' => 'master_fee/proofs/demo_tf2.jpg',
                    'dibayar_at'          => $p['open_date']->copy()->addDays(3),
                    'catatan_admin'       => 'Nominal pada bukti transfer tidak cocok. Silakan lakukan pembayaran ulang.',
                    'verified_by'         => $admin?->id,
                    'verified_at'         => $p['open_date']->copy()->addDays(4),
                ]
            );
        }

        // Periode 3 -> Menunggu Verifikasi
        if (isset($periods[2])) {
            $p = $periods[2];
            $profit = 250.00;
            $feePersen = (float) $settings->fee_persen;
            $feeUsd = round($profit * ($feePersen / 100), 2);
            $wdUsd = 80.00;
            $wdIdr = 1280000;
            $kurs = $wdIdr / $wdUsd;
            $feeIdr = (int) round($feeUsd * $kurs);

            LaporanProfitBulanan::updateOrCreate(
                [
                    'akun_copytrade_id' => $akun->id,
                    'periode_ke'        => $p['ke'],
                ],
                [
                    'nomor'               => 'MFE-' . $p['akhir']->format('Ym') . '-0001',
                    'user_id'             => $investor->id,
                    'periode_awal'        => $p['awal']->toDateString(),
                    'periode_akhir'       => $p['akhir']->toDateString(),
                    'jatuh_tempo'         => $p['jatuh_tempo']->toDateString(),
                    'profit_usd'          => $profit,
                    'bukti_myfxbook_path' => 'master_fee/myfxbook/demo_p3.jpg',
                    'wd_usd'              => $wdUsd,
                    'wd_idr'              => $wdIdr,
                    'bukti_wd_path'       => 'master_fee/wd/demo_wd3.jpg',
                    'kurs_riil'           => $kurs,
                    'fee_persen'          => $feePersen,
                    'fee_usd'             => $feeUsd,
                    'fee_idr'             => $feeIdr,
                    'status'              => LaporanProfitBulanan::STATUS_MENUNGGU_VERIFIKASI,
                    'bukti_transfer_path' => 'master_fee/proofs/demo_tf3.jpg',
                    'dibayar_at'          => Carbon::now()->subDay(),
                ]
            );
        }
    }
}
