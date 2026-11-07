<?php

namespace Database\Seeders;

use App\Models\AkunCopytrade;
use App\Models\PengaturanMasterFee;
use App\Models\RiwayatBalanceCopytrade;
use App\Models\TagihanMasterFee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MasterFeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan pengaturan master fee ada
        PengaturanMasterFee::current();

        $admin = User::where('role', 'administrator')->first();
        $investor = User::where('username', 'investor')->first();

        if (!$investor) {
            return;
        }

        $akun = AkunCopytrade::where('user_id', $investor->id)->first();
        if (!$akun) {
            return;
        }

        // Buat 4 histori rekap saldo bulanan dengan variasi status tagihan master fee
        // 1. Rekap 3 bulan lalu -> Lunas
        $tgl1 = Carbon::now()->subMonths(3)->startOfMonth()->toDateString();
        $rekap1 = RiwayatBalanceCopytrade::updateOrCreate(
            [
                'akun_copytrade_id'  => $akun->id,
                'tanggal_pencatatan' => $tgl1,
            ],
            [
                'user_id'                      => $investor->id,
                'balance'                      => 1200.00,
                'penarikan'                    => 0.00,
                'setoran_tambahan'             => 0.00,
                'keuntungan_nominal'           => 200.00,
                'keuntungan_persen'            => 20.00,
                'keuntungan_kumulatif'         => 200.00,
                'pertumbuhan_kumulatif_persen' => 20.00,
                'catatan'                      => 'Rekap bulan ke-1',
                'bukti_saldo_path'             => 'balance_proofs/demo_proof_1.jpg',
            ]
        );

        $periode1 = Carbon::parse($tgl1)->subMonth()->startOfMonth()->toDateString();
        TagihanMasterFee::updateOrCreate(
            ['riwayat_balance_id' => $rekap1->id],
            [
                'nomor'               => 'MFE-' . Carbon::parse($tgl1)->format('Ym') . '-0001',
                'akun_copytrade_id'   => $akun->id,
                'user_id'             => $investor->id,
                'periode'             => $periode1,
                'profit_bersih'       => 200.00,
                'fee_persen'          => 30.00,
                'nominal_fee'         => 60.00,
                'jatuh_tempo'         => Carbon::parse($tgl1)->setDay(10)->toDateString(),
                'status'              => TagihanMasterFee::STATUS_LUNAS,
                'bukti_transfer_path' => 'master_fee_proofs/demo_transfer_1.jpg',
                'dibayar_at'          => Carbon::parse($tgl1)->setDay(5)->setTime(10, 0),
                'verified_by'         => $admin?->id,
                'verified_at'         => Carbon::parse($tgl1)->setDay(6)->setTime(14, 0),
            ]
        );

        // 2. Rekap 2 bulan lalu -> Ditolak (karena bukti buram)
        $tgl2 = Carbon::now()->subMonths(2)->startOfMonth()->toDateString();
        $rekap2 = RiwayatBalanceCopytrade::updateOrCreate(
            [
                'akun_copytrade_id'  => $akun->id,
                'tanggal_pencatatan' => $tgl2,
            ],
            [
                'user_id'                      => $investor->id,
                'balance'                      => 1500.00,
                'penarikan'                    => 0.00,
                'setoran_tambahan'             => 0.00,
                'keuntungan_nominal'           => 300.00,
                'keuntungan_persen'            => 25.00,
                'keuntungan_kumulatif'         => 500.00,
                'pertumbuhan_kumulatif_persen' => 50.00,
                'catatan'                      => 'Rekap bulan ke-2',
                'bukti_saldo_path'             => 'balance_proofs/demo_proof_2.jpg',
            ]
        );

        $periode2 = Carbon::parse($tgl2)->subMonth()->startOfMonth()->toDateString();
        TagihanMasterFee::updateOrCreate(
            ['riwayat_balance_id' => $rekap2->id],
            [
                'nomor'               => 'MFE-' . Carbon::parse($tgl2)->format('Ym') . '-0001',
                'akun_copytrade_id'   => $akun->id,
                'user_id'             => $investor->id,
                'periode'             => $periode2,
                'profit_bersih'       => 300.00,
                'fee_persen'          => 30.00,
                'nominal_fee'         => 90.00,
                'jatuh_tempo'         => Carbon::parse($tgl2)->setDay(10)->toDateString(),
                'status'              => TagihanMasterFee::STATUS_DITOLAK,
                'bukti_transfer_path' => 'master_fee_proofs/demo_transfer_2.jpg',
                'dibayar_at'          => Carbon::parse($tgl2)->setDay(4)->setTime(11, 0),
                'catatan_admin'       => 'Nominal pada bukti transfer tidak sesuai atau gambar buram. Silakan unggah bukti yang jelas.',
                'verified_by'         => $admin?->id,
                'verified_at'         => Carbon::parse($tgl2)->setDay(5)->setTime(16, 0),
            ]
        );

        // 3. Rekap 1 bulan lalu -> Menunggu Verifikasi
        $tgl3 = Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $rekap3 = RiwayatBalanceCopytrade::updateOrCreate(
            [
                'akun_copytrade_id'  => $akun->id,
                'tanggal_pencatatan' => $tgl3,
            ],
            [
                'user_id'                      => $investor->id,
                'balance'                      => 1750.00,
                'penarikan'                    => 50.00,
                'setoran_tambahan'             => 0.00,
                'keuntungan_nominal'           => 300.00, // 1750 - 1500 + 50 = 300
                'keuntungan_persen'            => 20.00,
                'keuntungan_kumulatif'         => 800.00,
                'pertumbuhan_kumulatif_persen' => 80.00,
                'catatan'                      => 'Rekap bulan ke-3 ada penarikan $50',
                'bukti_saldo_path'             => 'balance_proofs/demo_proof_3.jpg',
            ]
        );

        $periode3 = Carbon::parse($tgl3)->subMonth()->startOfMonth()->toDateString();
        TagihanMasterFee::updateOrCreate(
            ['riwayat_balance_id' => $rekap3->id],
            [
                'nomor'               => 'MFE-' . Carbon::parse($tgl3)->format('Ym') . '-0001',
                'akun_copytrade_id'   => $akun->id,
                'user_id'             => $investor->id,
                'periode'             => $periode3,
                'profit_bersih'       => 300.00,
                'fee_persen'          => 30.00,
                'nominal_fee'         => 90.00,
                'jatuh_tempo'         => Carbon::parse($tgl3)->setDay(10)->toDateString(),
                'status'              => TagihanMasterFee::STATUS_MENUNGGU_VERIFIKASI,
                'bukti_transfer_path' => 'master_fee_proofs/demo_transfer_3.jpg',
                'dibayar_at'          => Carbon::now()->subDays(2),
            ]
        );

        // 4. Rekap Bulan Ini -> Belum Dibayar
        $tgl4 = Carbon::now()->startOfMonth()->toDateString();
        $rekap4 = RiwayatBalanceCopytrade::updateOrCreate(
            [
                'akun_copytrade_id'  => $akun->id,
                'tanggal_pencatatan' => $tgl4,
            ],
            [
                'user_id'                      => $investor->id,
                'balance'                      => 2100.00,
                'penarikan'                    => 0.00,
                'setoran_tambahan'             => 0.00,
                'keuntungan_nominal'           => 350.00, // 2100 - 1750 = 350
                'keuntungan_persen'            => 20.00,
                'keuntungan_kumulatif'         => 1150.00,
                'pertumbuhan_kumulatif_persen' => 115.00,
                'catatan'                      => 'Rekap bulan berjalan',
                'bukti_saldo_path'             => 'balance_proofs/demo_proof_4.jpg',
            ]
        );

        $periode4 = Carbon::parse($tgl4)->subMonth()->startOfMonth()->toDateString();
        TagihanMasterFee::updateOrCreate(
            ['riwayat_balance_id' => $rekap4->id],
            [
                'nomor'             => 'MFE-' . Carbon::parse($tgl4)->format('Ym') . '-0001',
                'akun_copytrade_id' => $akun->id,
                'user_id'           => $investor->id,
                'periode'           => $periode4,
                'profit_bersih'     => 350.00,
                'fee_persen'        => 30.00,
                'nominal_fee'       => 105.00,
                'jatuh_tempo'       => Carbon::parse($tgl4)->setDay(10)->toDateString(),
                'status'            => TagihanMasterFee::STATUS_BELUM_DIBAYAR,
            ]
        );
    }
}
