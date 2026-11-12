<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\Invitation;
use App\Models\KlaimKomisi;
use App\Models\KlaimKomisiItem;
use App\Models\KlaimReferral;
use App\Models\LaporanProfitBulanan;
use App\Models\LogAktivitas;
use App\Models\RelasiReferral;
use App\Models\TiketBantuan;
use App\Models\User;
use App\Models\UserDokumen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class AdminXlsxExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name'      => 'Admin Platform',
            'username'  => 'superadmin',
            'email'     => 'admin@yzdcenter.com',
            'role'      => 'administrator',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->member = User::factory()->create([
            'name'      => 'Regular Member',
            'username'  => 'regmember',
            'email'     => 'member@yzdcenter.com',
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);
    }

    /**
     * Helper untuk mengekstrak isi sheet1.xml dari respons XLSX.
     */
    private function extractSheetXmlContent($response): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_xlsx_');
        file_put_contents($tempFile, $response->streamedContent());

        $zip = new ZipArchive;
        $opened = $zip->open($tempFile);
        $this->assertTrue($opened === true, 'Berkas XLSX gagal dibuka sebagai ZipArchive.');

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotEmpty($sheetXml, 'Worksheet sheet1.xml tidak ditemukan di dalam paket OpenXML.');

        $zip->close();
        @unlink($tempFile);

        return $sheetXml;
    }

    public function test_guests_and_members_cannot_access_export_endpoints(): void
    {
        $endpoints = [
            '/admin/users/export',
            '/admin/invitations/export',
            '/admin/registrations/export',
            '/admin/copytrade/export?tab=verifikasi',
            '/admin/copytrade/export?tab=aktif',
            '/admin/referral-claims/export',
            '/admin/affiliates/export?tab=affiliates',
            '/admin/affiliates/export?tab=claims',
            '/admin/commissions/export',
            '/admin/master-fee/export?tab=laporan',
            '/admin/master-fee/export?tab=belum_lapor',
            '/admin/tickets/export',
            '/admin/activity-logs/export',
        ];

        // 1. Tamu / Guest dialihkan ke login
        foreach ($endpoints as $url) {
            $guestRes = $this->get($url);
            $guestRes->assertRedirect(route('login'));
        }

        // 2. Member ditolak dengan 403 Forbidden
        foreach ($endpoints as $url) {
            $memberRes = $this->actingAs($this->member)->get($url);
            $memberRes->assertForbidden();
        }
    }

    public function test_admin_can_export_users_xlsx(): void
    {
        User::factory()->create([
            'name'          => 'Budi Santoso',
            'username'      => 'budisantoso',
            'email'         => 'budi@example.com',
            'role'          => 'member',
            'status'        => 'active',
            'is_active'     => true,
            'referral_code' => 'BUD123',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/users/export?search=budisantoso');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $xml = $this->extractSheetXmlContent($response);
        $this->assertStringContainsString('budisantoso', $xml);
        $this->assertStringContainsString('Budi Santoso', $xml);

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi'     => 'ekspor_data',
            'kategori' => 'pengguna',
            'user_id'  => $this->admin->id,
        ]);
    }

    public function test_admin_can_export_invitations_xlsx(): void
    {
        Invitation::create([
            'label'          => 'Undangan Seminar Surabaya',
            'token'          => 'INV-SEMINAR-SUB-2026',
            'kuota_maksimal' => 50,
            'total_terpakai' => 12,
            'is_active'      => true,
            'expired_at'     => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/invitations/export?search=Surabaya');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $xml = $this->extractSheetXmlContent($response);
        $this->assertStringContainsString('Undangan Seminar Surabaya', $xml);

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi'     => 'ekspor_data',
            'kategori' => 'undangan',
            'user_id'  => $this->admin->id,
        ]);
    }

    public function test_admin_can_export_registrations_kyc_xlsx(): void
    {
        $kycUser = User::factory()->create([
            'name'      => 'Calon Investor',
            'username'  => 'caloninvestor',
            'email'     => 'calon@example.com',
            'role'      => 'member',
            'status'    => 'pending',
            'is_active' => true,
        ]);

        UserDokumen::create([
            'user_id'       => $kycUser->id,
            'tipe_dokumen'  => 'ktp',
            'file_path'     => 'kyc_documents/ktp_test.jpg',
            'file_name'     => 'ktp_test.jpg',
            'mime_type'     => 'image/jpeg',
            'file_size'     => 1024,
            'status'        => 'pending',
            'catatan_admin' => 'Periksa kesesuaian NIK',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/registrations/export?search=caloninvestor');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $xml = $this->extractSheetXmlContent($response);
        $this->assertStringContainsString('caloninvestor', $xml);
        $this->assertStringContainsString('Calon Investor', $xml);

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi'     => 'ekspor_data',
            'kategori' => 'kyc',
            'user_id'  => $this->admin->id,
        ]);
    }

    public function test_admin_can_export_copytrade_without_leaking_credentials(): void
    {
        $investor = User::factory()->create([
            'name'      => 'Akun Investor Copytrade',
            'username'  => 'investorcopy',
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $akunPending = AkunCopytrade::create([
            'user_id'            => $investor->id,
            'tier'               => 'tier_3',
            'broker'             => 'Exness Global',
            'tipe_akun'          => 'pro',
            'nomor_akun_trading' => '99887711',
            'modal_riil'         => 5000.00,
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Akun Investor Copytrade',
            'server_broker'      => 'Exness-Real10',
            'master_password'    => 'SECRET_MASTER_LEAK_TEST_123',
            'investor_password'  => 'SECRET_INVESTOR_LEAK_TEST_456',
            'status'             => 'pending',
            'tanggal_mulai'      => now()->toDateString(),
        ]);

        $akunAktif = AkunCopytrade::create([
            'user_id'            => $investor->id,
            'tier'               => 'tier_2',
            'broker'             => 'XM Global',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '88776622',
            'modal_riil'         => 2500.00,
            'nama_bank'          => 'Mandiri',
            'nomor_rekening'     => '9876543210',
            'atas_nama_rekening' => 'Akun Investor Copytrade',
            'server_broker'      => 'XM-Real5',
            'master_password'    => 'SECRET_MASTER_LEAK_TEST_789',
            'investor_password'  => 'SECRET_INVESTOR_LEAK_TEST_999',
            'status'             => 'active',
            'tanggal_mulai'      => now()->subMonths(2)->toDateString(),
        ]);

        // 1. Ekspor Tab Verifikasi
        $resVer = $this->actingAs($this->admin)->get('/admin/copytrade/export?tab=verifikasi&search=99887711');
        $resVer->assertOk();
        $xmlVer = $this->extractSheetXmlContent($resVer);

        $this->assertStringContainsString('99887711', $xmlVer);
        $this->assertStringNotContainsString('SECRET_MASTER_LEAK_TEST_123', $xmlVer);
        $this->assertStringNotContainsString('SECRET_INVESTOR_LEAK_TEST_456', $xmlVer);

        // 2. Ekspor Tab Akun Aktif
        $resAkt = $this->actingAs($this->admin)->get('/admin/copytrade/export?tab=aktif&search=88776622');
        $resAkt->assertOk();
        $xmlAkt = $this->extractSheetXmlContent($resAkt);

        $this->assertStringContainsString('88776622', $xmlAkt);
        $this->assertStringNotContainsString('SECRET_MASTER_LEAK_TEST_789', $xmlAkt);
        $this->assertStringNotContainsString('SECRET_INVESTOR_LEAK_TEST_999', $xmlAkt);

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi'     => 'ekspor_data',
            'kategori' => 'copytrade',
            'user_id'  => $this->admin->id,
        ]);
    }

    public function test_admin_can_export_referral_claims_xlsx(): void
    {
        $affiliator = User::factory()->create([
            'name'          => 'Affiliator Beken',
            'username'      => 'affiliatorbeken',
            'referral_code' => 'BEK999',
            'role'          => 'member',
            'status'        => 'active',
            'is_active'     => true,
        ]);

        $downline = User::factory()->create([
            'name'      => 'Downline Terklaim',
            'username'  => 'downlineklaim',
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        KlaimReferral::create([
            'affiliator_id' => $affiliator->id,
            'downline_id'   => $downline->id,
            'alasan'        => 'Pendaftaran lewat webinar belum terikat referral.',
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/referral-claims/export?search=affiliatorbeken');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $xml = $this->extractSheetXmlContent($response);
        $this->assertStringContainsString('affiliatorbeken', $xml);
        $this->assertStringContainsString('downlineklaim', $xml);

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi'     => 'ekspor_data',
            'kategori' => 'afiliasi',
            'user_id'  => $this->admin->id,
        ]);
    }

    public function test_admin_can_export_affiliates_and_claims_tab(): void
    {
        $affiliator = User::factory()->create([
            'name'          => 'Mitra Sukses',
            'username'      => 'mitrasukses',
            'referral_code' => 'SUK777',
            'role'          => 'member',
            'status'        => 'active',
            'is_active'     => true,
        ]);

        $downline = User::factory()->create([
            'name'      => 'Investor Downline',
            'username'  => 'invdownline',
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        RelasiReferral::create([
            'affiliator_id' => $affiliator->id,
            'downline_id'   => $downline->id,
            'sumber'        => 'link_otomatis',
            'joined_at'     => now(),
        ]);

        // Tab Affiliates
        $resAff = $this->actingAs($this->admin)->get('/admin/affiliates/export?tab=affiliates&search=mitrasukses');
        $resAff->assertOk();
        $xmlAff = $this->extractSheetXmlContent($resAff);
        $this->assertStringContainsString('mitrasukses', $xmlAff);

        // Tab Claims
        $resClaims = $this->actingAs($this->admin)->get('/admin/affiliates/export?tab=claims');
        $resClaims->assertOk();
        $xmlClaims = $this->extractSheetXmlContent($resClaims);
        $this->assertNotEmpty($xmlClaims);

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi'     => 'ekspor_data',
            'kategori' => 'afiliasi',
            'user_id'  => $this->admin->id,
        ]);
    }

    public function test_admin_can_export_commissions_xlsx(): void
    {
        $affiliator = User::factory()->create([
            'name'      => 'Affiliator Komisi',
            'username'  => 'affkomisi',
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $downline = User::factory()->create([
            'name'      => 'Investor Komisi',
            'username'  => 'invkomisi',
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $claim = KlaimKomisi::create([
            'affiliator_id'          => $affiliator->id,
            'kode_klaim'             => 'CLM-202611-0099',
            'total_downline'         => 1,
            'total_akun'             => 1,
            'total_modal_riil'       => 1000.00,
            'total_nominal_transfer' => 1500000,
            'nama_bank'              => 'BCA',
            'nomor_rekening'         => '12345678',
            'atas_nama_rekening'     => 'Affiliator Komisi',
            'status'                 => 'pending',
        ]);

        KlaimKomisiItem::create([
            'klaim_komisi_id'    => $claim->id,
            'downline_id'        => $downline->id,
            'nomor_akun_trading' => '99881122',
            'broker'             => 'Exness',
            'modal_riil'         => 1000.00,
            'tier_level'         => 1,
            'persentase_tier'    => 20,
            'tanggal_mulai'      => now()->subMonth()->toDateString(),
            'tanggal_selesai'    => now()->toDateString(),
            'hari_kehitung'      => 30,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/commissions/export?search=CLM-202611-0099');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $xml = $this->extractSheetXmlContent($response);
        $this->assertStringContainsString('CLM-202611-0099', $xml);
        $this->assertStringContainsString('affkomisi', $xml);

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi'     => 'ekspor_data',
            'kategori' => 'komisi_afiliasi',
            'user_id'  => $this->admin->id,
        ]);
    }

    public function test_admin_can_export_master_fee_reports_and_missing_reports(): void
    {
        $investor = User::factory()->create([
            'name'      => 'Investor Master Fee',
            'username'  => 'investorfee',
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $akun = AkunCopytrade::create([
            'user_id'            => $investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'Exness',
            'tipe_akun'          => 'pro',
            'nomor_akun_trading' => '77665544',
            'modal_riil'         => 1000.00,
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '12345678',
            'atas_nama_rekening' => 'Investor Master Fee',
            'status'             => 'active',
            'tanggal_mulai'      => now()->subMonths(3)->toDateString(),
        ]);

        LaporanProfitBulanan::create([
            'nomor'               => 'MFE-202611-0001',
            'user_id'             => $investor->id,
            'akun_copytrade_id'   => $akun->id,
            'periode_ke'          => 1,
            'periode_awal'        => now()->subMonths(2)->toDateString(),
            'periode_akhir'       => now()->subMonth()->toDateString(),
            'jatuh_tempo'         => now()->toDateString(),
            'profit_usd'          => 250.00,
            'fee_persen'          => 30.00,
            'fee_usd'             => 75.00,
            'fee_idr'             => 1150000,
            'status'              => LaporanProfitBulanan::STATUS_LUNAS,
            'bukti_myfxbook_path' => 'master_fee/myfxbook_test.png',
        ]);

        // 1. Ekspor Tab Laporan Profit
        $resLaporan = $this->actingAs($this->admin)->get('/admin/master-fee/export?tab=laporan&search=MFE-202611-0001');
        $resLaporan->assertOk();
        $xmlLaporan = $this->extractSheetXmlContent($resLaporan);
        $this->assertStringContainsString('MFE-202611-0001', $xmlLaporan);
        $this->assertStringContainsString('investorfee', $xmlLaporan);

        // 2. Ekspor Tab Akun Belum Lapor
        $resMissing = $this->actingAs($this->admin)->get('/admin/master-fee/export?tab=belum_lapor&search=77665544');
        $resMissing->assertOk();
        $xmlMissing = $this->extractSheetXmlContent($resMissing);
        $this->assertStringContainsString('77665544', $xmlMissing);

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi'     => 'ekspor_data',
            'kategori' => 'master_fee',
            'user_id'  => $this->admin->id,
        ]);
    }

    public function test_admin_can_export_tickets_xlsx(): void
    {
        $member = User::factory()->create([
            'name'      => 'Member Pelapor',
            'username'  => 'memberlapor',
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        TiketBantuan::create([
            'nomor'         => 'TKT-202611-0001',
            'user_id'       => $member->id,
            'subjek'        => 'Kendala Deposit Akun Trading',
            'status'        => 'open',
            'pesan_awal'    => 'Deposit via bank BCA belum masuk.',
            'last_reply_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/tickets/export?search=TKT-202611-0001');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $xml = $this->extractSheetXmlContent($response);
        $this->assertStringContainsString('TKT-202611-0001', $xml);
        $this->assertStringContainsString('Kendala Deposit Akun Trading', $xml);

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi'     => 'ekspor_data',
            'kategori' => 'bantuan',
            'user_id'  => $this->admin->id,
        ]);
    }

    public function test_admin_can_export_activity_logs_xlsx(): void
    {
        LogAktivitas::create([
            'user_id'       => $this->admin->id,
            'user_name'     => $this->admin->name,
            'user_username' => $this->admin->username,
            'user_role'     => $this->admin->role,
            'kategori'      => 'keamanan',
            'aksi'          => 'audit_sistem',
            'deskripsi'     => 'Pengecekan integritas audit log platform.',
            'tingkat'       => 'info',
            'ip_address'    => '127.0.0.1',
            'user_agent'    => 'Mozilla/5.0 PHPUnit',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/activity-logs/export?search=integritas');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $xml = $this->extractSheetXmlContent($response);
        $this->assertStringContainsString('integritas', $xml);

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi'     => 'ekspor_audit_log',
            'kategori' => 'sistem',
            'user_id'  => $this->admin->id,
        ]);
    }
}
