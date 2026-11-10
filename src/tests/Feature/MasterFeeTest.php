<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\LaporanProfitBulanan;
use App\Models\PengaturanMasterFee;
use App\Models\RelasiReferral;
use App\Models\User;
use App\Services\MasterFeeService;
use App\Support\ProfitPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MasterFeeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected User $affiliator;

    protected User $otherMember;

    protected AkunCopytrade $akun;

    protected MasterFeeService $masterFeeService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->admin = User::factory()->create([
            'role'      => 'administrator',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->affiliator = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->member = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        RelasiReferral::create([
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $this->member->id,
            'status'        => 'active',
            'joined_at'     => now(),
        ]);

        $this->otherMember = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        // Akun aktif mulai 3 Agustus 2026 (hari 1-15: periode 1 s/d 31 Agu 2026, buka input 1 Sep 2026)
        $this->akun = AkunCopytrade::create([
            'user_id'            => $this->member->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-TEST-FEE-01',
            'modal_riil'         => 1000.00,
            'tanggal_mulai'      => '2026-08-03',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => 'Test Member',
            'status'             => 'active',
            'myfxbook_url'       => 'https://www.myfxbook.com/members/tester/test-sys/12345',
            'verified_at'        => '2026-08-03 10:00:00',
        ]);

        PengaturanMasterFee::current();
        $this->masterFeeService = app(MasterFeeService::class);
    }

    public function test_profit_period_rules(): void
    {
        // 1. Akun mulai tgl 1-15: akhir periode adalah akhir bulan yang sama
        $mulaiAwal = Carbon::parse('2026-08-03');
        $this->assertEquals('2026-08-31', ProfitPeriod::firstPeriodEnd($mulaiAwal)->toDateString());
        $this->assertEquals('2026-09-01', ProfitPeriod::openReportDate(Carbon::parse('2026-08-31'))->toDateString());

        // 2. Akun mulai tgl 16-31: akhir periode adalah akhir bulan berikutnya
        $mulaiAkhir = Carbon::parse('2026-06-17');
        $this->assertEquals('2026-07-31', ProfitPeriod::firstPeriodEnd($mulaiAkhir)->toDateString());
        $this->assertEquals('2026-08-01', ProfitPeriod::openReportDate(Carbon::parse('2026-07-31'))->toDateString());

        // 3. Tanggal input belum dibuka sebelum tanggal 1 bulan berikutnya
        Carbon::setTestNow('2026-08-25');
        $this->assertNull(ProfitPeriod::nextReportablePeriod($this->akun, Carbon::now()));

        // 4. Tanggal input terbuka tepat tanggal 1 bulan berikutnya
        Carbon::setTestNow('2026-09-01');
        $next = ProfitPeriod::nextReportablePeriod($this->akun, Carbon::now());
        $this->assertNotNull($next);
        $this->assertEquals(1, $next['ke']);
        $this->assertEquals('2026-08-03', $next['awal']->toDateString());
        $this->assertEquals('2026-08-31', $next['akhir']->toDateString());
    }

    public function test_submit_report_with_positive_profit_calculates_fee_and_kurs_riil_correctly(): void
    {
        Carbon::setTestNow('2026-09-02');

        $myfxbook = UploadedFile::fake()->image('myfxbook.png');
        $wd = UploadedFile::fake()->image('wd_broker.png');

        // Contoh skenario: profit $100, WD $60, diterima Rp1.000.000
        // fee 30% -> $30. kurs_riil = 1.000.000 / 60 = 16666.67. fee_idr = round(30 * 16666.67) = Rp500.000
        $payload = [
            'akun_copytrade_id' => $this->akun->id,
            'periode_ke'        => 1,
            'profit_usd'        => 100.00,
            'wd_usd'            => 60.00,
            'wd_idr'            => 1000000,
            'bukti_myfxbook'    => $myfxbook,
            'bukti_wd'          => $wd,
        ];

        $response = $this->actingAs($this->member)->post('/member/master-fee', $payload);
        $response->assertRedirect(route('member.master_fee.index'));
        $response->assertSessionHas('success');

        $laporan = LaporanProfitBulanan::where('akun_copytrade_id', $this->akun->id)->first();
        $this->assertNotNull($laporan);
        $this->assertEquals(1, $laporan->periode_ke);
        $this->assertEquals(100.00, (float) $laporan->profit_usd);
        $this->assertEquals(60.00, (float) $laporan->wd_usd);
        $this->assertEquals(1000000, $laporan->wd_idr);
        $this->assertEquals(30.00, (float) $laporan->fee_usd);
        $this->assertEquals(500000, $laporan->fee_idr);
        $this->assertEquals('belum_dibayar', $laporan->status);
        $this->assertEquals('Menunggu Pembayaran', $laporan->status_tampil);

        Storage::disk('local')->assertExists($laporan->bukti_myfxbook_path);
        Storage::disk('local')->assertExists($laporan->bukti_wd_path);
    }

    public function test_submit_report_validates_wd_range_between_fee_and_max_percent(): void
    {
        Carbon::setTestNow('2026-09-02');
        $myfxbook = UploadedFile::fake()->image('myfxbook.png');
        $wd = UploadedFile::fake()->image('wd.png');

        // Profit $100 -> WD min 30% ($30), max 60% ($60)

        // 1. WD terlalu kecil ($25 < $30) -> ditolak
        $responseLow = $this->actingAs($this->member)->post('/member/master-fee', [
            'akun_copytrade_id' => $this->akun->id,
            'periode_ke'        => 1,
            'profit_usd'        => 100.00,
            'wd_usd'            => 25.00,
            'wd_idr'            => 400000,
            'bukti_myfxbook'    => $myfxbook,
            'bukti_wd'          => $wd,
        ]);
        $responseLow->assertSessionHasErrors('wd_usd');

        // 2. WD terlalu besar ($70 > $60) -> ditolak
        $responseHigh = $this->actingAs($this->member)->post('/member/master-fee', [
            'akun_copytrade_id' => $this->akun->id,
            'periode_ke'        => 1,
            'profit_usd'        => 100.00,
            'wd_usd'            => 70.00,
            'wd_idr'            => 1100000,
            'bukti_myfxbook'    => $myfxbook,
            'bukti_wd'          => $wd,
        ]);
        $responseHigh->assertSessionHasErrors('wd_usd');
    }

    public function test_submit_report_with_zero_or_negative_profit_sets_tanpa_fee_status(): void
    {
        Carbon::setTestNow('2026-09-02');
        $myfxbook = UploadedFile::fake()->image('myfxbook.png');

        // Profit impas ($0) atau minus (-$50)
        $payload = [
            'akun_copytrade_id' => $this->akun->id,
            'periode_ke'        => 1,
            'profit_usd'        => -50.00,
            'bukti_myfxbook'    => $myfxbook,
            // tanpa wd_usd, wd_idr, bukti_wd
        ];

        $response = $this->actingAs($this->member)->post('/member/master-fee', $payload);
        $response->assertRedirect(route('member.master_fee.index'));

        $laporan = LaporanProfitBulanan::where('akun_copytrade_id', $this->akun->id)->first();
        $this->assertNotNull($laporan);
        $this->assertEquals(-50.00, (float) $laporan->profit_usd);
        $this->assertEquals(0, (float) $laporan->fee_usd);
        $this->assertEquals(0, $laporan->fee_idr);
        $this->assertNull($laporan->wd_usd);
        $this->assertNull($laporan->bukti_wd_path);
        $this->assertEquals('tanpa_fee', $laporan->status);
        $this->assertEquals('Tanpa Fee', $laporan->status_tampil);
        $this->assertNull($laporan->status_catatan);
    }

    public function test_submit_report_requires_myfxbook_url_on_account(): void
    {
        Carbon::setTestNow('2026-09-02');
        $this->akun->update(['myfxbook_url' => null]);

        $myfxbook = UploadedFile::fake()->image('myfxbook.png');
        $payload = [
            'akun_copytrade_id' => $this->akun->id,
            'periode_ke'        => 1,
            'profit_usd'        => 50.00,
            'wd_usd'            => 20.00,
            'wd_idr'            => 320000,
            'bukti_myfxbook'    => $myfxbook,
            'bukti_wd'          => UploadedFile::fake()->image('wd.png'),
        ];

        $response = $this->actingAs($this->member)->post('/member/master-fee', $payload);
        $response->assertSessionHasErrors('akun_copytrade_id');
    }

    public function test_submit_proof_and_admin_verification_workflow(): void
    {
        Carbon::setTestNow('2026-09-02');

        $laporan = LaporanProfitBulanan::create([
            'nomor'               => 'MFE-202609-0001',
            'akun_copytrade_id'   => $this->akun->id,
            'user_id'             => $this->member->id,
            'periode_ke'          => 1,
            'periode_awal'        => '2026-08-03',
            'periode_akhir'       => '2026-08-31',
            'jatuh_tempo'         => '2026-09-01',
            'profit_usd'          => 100.00,
            'bukti_myfxbook_path' => 'master_fee/myfxbook/test.png',
            'wd_usd'              => 50.00,
            'wd_idr'              => 800000,
            'bukti_wd_path'       => 'master_fee/wd/test.png',
            'kurs_riil'           => 16000.00,
            'fee_persen'          => 30.00,
            'fee_usd'             => 30.00,
            'fee_idr'             => 480000,
            'status'              => 'belum_dibayar',
        ]);

        // 1. Member unggah bukti transfer
        $tf = UploadedFile::fake()->image('tf.png');
        $responseProof = $this->actingAs($this->member)->post("/member/master-fee/{$laporan->id}/proof", [
            'bukti_transfer' => $tf,
        ]);
        $responseProof->assertRedirect();

        $laporan->refresh();
        $this->assertEquals('menunggu_verifikasi', $laporan->status);
        $this->assertEquals('Menunggu Verifikasi', $laporan->status_tampil);
        $this->assertNull($laporan->status_catatan);
        $this->assertNotNull($laporan->bukti_transfer_path);
        Storage::disk('local')->assertExists($laporan->bukti_transfer_path);

        // 2. Admin verifikasi menjadi LUNAS
        $responseVerify = $this->actingAs($this->admin)->post("/admin/master-fee/{$laporan->id}/verify");
        $responseVerify->assertRedirect();

        $laporan->refresh();
        $this->assertEquals('lunas', $laporan->status);
        $this->assertEquals('Lunas', $laporan->status_tampil);
        $this->assertEquals($this->admin->id, $laporan->verified_by);
        $this->assertNotNull($laporan->verified_at);
    }

    public function test_admin_can_reject_payment_proof_and_member_can_reupload(): void
    {
        $laporan = LaporanProfitBulanan::create([
            'nomor'               => 'MFE-202609-0001',
            'akun_copytrade_id'   => $this->akun->id,
            'user_id'             => $this->member->id,
            'periode_ke'          => 1,
            'periode_awal'        => '2026-08-03',
            'periode_akhir'       => '2026-08-31',
            'jatuh_tempo'         => '2026-09-01',
            'profit_usd'          => 100.00,
            'bukti_myfxbook_path' => 'master_fee/myfxbook/test.png',
            'wd_usd'              => 50.00,
            'wd_idr'              => 800000,
            'bukti_wd_path'       => 'master_fee/wd/test.png',
            'kurs_riil'           => 16000.00,
            'fee_persen'          => 30.00,
            'fee_usd'             => 30.00,
            'fee_idr'             => 480000,
            'status'              => 'menunggu_verifikasi',
            'bukti_transfer_path' => 'master_fee/transfers/old_tf.png',
        ]);

        // Admin tolak dengan alasan
        $responseReject = $this->actingAs($this->admin)->post("/admin/master-fee/{$laporan->id}/reject", [
            'alasan' => 'Bukti mutasi transfer terpotong nama penerima.',
        ]);
        $responseReject->assertRedirect();

        $laporan->refresh();
        $this->assertEquals('ditolak', $laporan->status);
        $this->assertEquals('Bukti Ditolak', $laporan->status_tampil);
        $this->assertEquals('Bukti mutasi transfer terpotong nama penerima.', $laporan->catatan_admin);
        $this->assertEquals('Bukti mutasi transfer terpotong nama penerima.', $laporan->status_catatan);
        $this->assertTrue($laporan->is_editable);

        // Member unggah bukti transfer baru
        $newTf = UploadedFile::fake()->image('new_tf.png');
        $responseReupload = $this->actingAs($this->member)->post("/member/master-fee/{$laporan->id}/proof", [
            'bukti_transfer' => $newTf,
        ]);
        $responseReupload->assertRedirect();

        $laporan->refresh();
        $this->assertEquals('menunggu_verifikasi', $laporan->status);
    }

    public function test_admin_flag_correction_workflow(): void
    {
        $laporan = LaporanProfitBulanan::create([
            'nomor'               => 'MFE-202609-0001',
            'akun_copytrade_id'   => $this->akun->id,
            'user_id'             => $this->member->id,
            'periode_ke'          => 1,
            'periode_awal'        => '2026-08-03',
            'periode_akhir'       => '2026-08-31',
            'jatuh_tempo'         => '2026-09-01',
            'profit_usd'          => 100.00,
            'bukti_myfxbook_path' => 'master_fee/myfxbook/test.png',
            'wd_usd'              => 50.00,
            'wd_idr'              => 800000,
            'bukti_wd_path'       => 'master_fee/wd/test.png',
            'kurs_riil'           => 16000.00,
            'fee_persen'          => 30.00,
            'fee_usd'             => 30.00,
            'fee_idr'             => 480000,
            'status'              => 'belum_dibayar',
        ]);

        // 1. Admin tandai Perlu Koreksi
        $responseFlag = $this->actingAs($this->admin)->post("/admin/master-fee/{$laporan->id}/flag-correction", [
            'alasan' => 'Angka profit pada form berbeda dengan screenshot Myfxbook.',
        ]);
        $responseFlag->assertRedirect();

        $laporan->refresh();
        $this->assertTrue($laporan->perlu_koreksi);
        $this->assertEquals('Perlu Koreksi', $laporan->status_tampil);
        $this->assertEquals('Angka profit pada form berbeda dengan screenshot Myfxbook.', $laporan->catatan_koreksi);
        $this->assertEquals('Angka profit pada form berbeda dengan screenshot Myfxbook.', $laporan->status_catatan);
        $this->assertTrue($laporan->is_editable);

        // 2. Member merevisi laporan
        $responseUpdate = $this->actingAs($this->member)->put("/member/master-fee/{$laporan->id}", [
            'profit_usd' => 120.00,
            'wd_usd'     => 60.00,
            'wd_idr'     => 960000,
        ]);
        $responseUpdate->assertRedirect();

        $laporan->refresh();
        $this->assertEquals(120.00, (float) $laporan->profit_usd);
        $this->assertEquals(36.00, (float) $laporan->fee_usd); // 30% dari 120
        $this->assertFalse($laporan->perlu_koreksi);
        $this->assertNull($laporan->catatan_koreksi);
    }

    public function test_document_security_and_streaming_authorization(): void
    {
        Storage::disk('local')->put('master_fee/myfxbook/test.png', 'myfxbook-binary');
        Storage::disk('local')->put('master_fee/wd/test.png', 'wd-binary');
        Storage::disk('local')->put('master_fee/transfers/test.png', 'tf-binary');

        $laporan = LaporanProfitBulanan::create([
            'nomor'               => 'MFE-202609-0001',
            'akun_copytrade_id'   => $this->akun->id,
            'user_id'             => $this->member->id,
            'periode_ke'          => 1,
            'periode_awal'        => '2026-08-03',
            'periode_akhir'       => '2026-08-31',
            'jatuh_tempo'         => '2026-09-01',
            'profit_usd'          => 100.00,
            'bukti_myfxbook_path' => 'master_fee/myfxbook/test.png',
            'wd_usd'              => 50.00,
            'wd_idr'              => 800000,
            'bukti_wd_path'       => 'master_fee/wd/test.png',
            'kurs_riil'           => 16000.00,
            'fee_persen'          => 30.00,
            'fee_usd'             => 30.00,
            'fee_idr'             => 480000,
            'status'              => 'belum_dibayar',
            'bukti_transfer_path' => 'master_fee/transfers/test.png',
        ]);

        // Pemilik dapat mengakses seluruh berkas bukti
        $this->actingAs($this->member)->get("/documents/master-fee/{$laporan->id}/myfxbook")->assertOk();
        $this->actingAs($this->member)->get("/documents/master-fee/{$laporan->id}/wd")->assertOk();
        $this->actingAs($this->member)->get("/documents/master-fee/{$laporan->id}")->assertOk();

        // Admin dapat mengakses seluruh berkas bukti
        $this->actingAs($this->admin)->get("/documents/master-fee/{$laporan->id}/myfxbook")->assertOk();
        $this->actingAs($this->admin)->get("/documents/master-fee/{$laporan->id}/wd")->assertOk();
        $this->actingAs($this->admin)->get("/documents/master-fee/{$laporan->id}")->assertOk();

        // Member lain dilarang mengakses berkas (403 Forbidden)
        $this->actingAs($this->otherMember)->get("/documents/master-fee/{$laporan->id}/myfxbook")->assertForbidden();
        $this->actingAs($this->otherMember)->get("/documents/master-fee/{$laporan->id}/wd")->assertForbidden();
        $this->actingAs($this->otherMember)->get("/documents/master-fee/{$laporan->id}")->assertForbidden();

        // Afiliator dilarang mengakses screenshot / berkas transfer downline (403 Forbidden)
        $this->actingAs($this->affiliator)->get("/documents/master-fee/{$laporan->id}/myfxbook")->assertForbidden();
        $this->actingAs($this->affiliator)->get("/documents/master-fee/{$laporan->id}/wd")->assertForbidden();
        $this->actingAs($this->affiliator)->get("/documents/master-fee/{$laporan->id}")->assertForbidden();
    }

    public function test_affiliator_downline_report_strictly_hides_financial_details_and_proofs(): void
    {
        $laporan = LaporanProfitBulanan::create([
            'nomor'               => 'MFE-202609-0001',
            'akun_copytrade_id'   => $this->akun->id,
            'user_id'             => $this->member->id,
            'periode_ke'          => 1,
            'periode_awal'        => '2026-08-03',
            'periode_akhir'       => '2026-08-31',
            'jatuh_tempo'         => '2026-09-01',
            'profit_usd'          => 250.00,
            'bukti_myfxbook_path' => 'master_fee/myfxbook/secret.png',
            'wd_usd'              => 100.00,
            'wd_idr'              => 1600000,
            'bukti_wd_path'       => 'master_fee/wd/secret.png',
            'kurs_riil'           => 16000.00,
            'fee_persen'          => 30.00,
            'fee_usd'             => 75.00,
            'fee_idr'             => 1200000,
            'status'              => 'lunas',
        ]);

        $paginated = $this->masterFeeService->downlineReport($this->affiliator);
        $data = $paginated->items();

        $this->assertCount(1, $data);
        $item = $data[0];

        $this->assertEquals($this->akun->nomor_akun_trading, $item['nomor_akun_trading']);
        $this->assertEquals($this->akun->myfxbook_url, $item['myfxbook_url']);
        $this->assertArrayNotHasKey('fee_usd', $item);
        $this->assertArrayNotHasKey('fee_idr', $item);
        $this->assertArrayNotHasKey('wd_usd', $item);
        $this->assertArrayNotHasKey('wd_idr', $item);

        $laporanItem = $item['laporan'][0];
        $this->assertEquals(250.00, $laporanItem['profit_usd']);
        $this->assertEquals('Lunas', $laporanItem['status_tampil']);
        $this->assertArrayNotHasKey('fee_usd', $laporanItem);
        $this->assertArrayNotHasKey('fee_idr', $laporanItem);
        $this->assertArrayNotHasKey('bukti_myfxbook_path', $laporanItem);
        $this->assertArrayNotHasKey('bukti_wd_path', $laporanItem);
    }
}
