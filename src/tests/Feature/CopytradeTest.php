<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\User;
use App\Models\UserDokumen;
use App\Notifications\CopytradeAccountStatusChangedNotification;
use App\Notifications\NewCopytradeSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CopytradeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $investor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::factory()->create([
            'role'   => 'administrator',
            'status' => 'active',
        ]);

        $this->investor = User::factory()->create([
            'role'   => 'member',
            'status' => 'active',
        ]);
    }

    public function test_investor_can_view_copytrade_page(): void
    {
        $redirectResponse = $this->actingAs($this->investor)->get('/member/copytrade');
        $redirectResponse->assertRedirect(route('member.investments.index'));

        $response = $this->actingAs($this->investor)->get(route('member.investments.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Member/Investments/Index')
            ->has('consolidated')
            ->has('accounts')
        );
    }

    public function test_first_copytrade_requires_agreement_document(): void
    {
        $payload = [
            'tier_investasi'        => 'tier_1',
            'nomor_akun_trading'    => '11223344',
            'modal_riil'            => 1000,
            'tanggal_mulai_trading' => '2026-03-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Investor Tester',
            // dokumen_perjanjian sengaja dikosongkan
        ];

        $response = $this->actingAs($this->investor)->post('/member/copytrade', $payload);

        $response->assertSessionHasErrors('dokumen_perjanjian');
    }

    public function test_investor_can_create_first_copytrade_with_agreement(): void
    {
        Notification::fake();

        $file = UploadedFile::fake()->create('perjanjian.pdf', 500, 'application/pdf');

        $payload = [
            'tier_investasi'        => 'tier_1',
            'nomor_akun_trading'    => '11223344',
            'modal_riil'            => 1000,
            'tanggal_mulai_trading' => '2026-03-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Investor Tester',
            'dokumen_perjanjian'    => $file,
        ];

        $response = $this->actingAs($this->investor)->post('/member/copytrade', $payload);

        $response->assertRedirect(route('member.investments.index'));
        $this->assertDatabaseHas('akun_copytrade', [
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'nomor_akun_trading' => '11223344',
            'status'             => 'pending',
        ]);

        $akun = AkunCopytrade::where('nomor_akun_trading', '11223344')->first();
        $this->assertNotNull($akun->surat_perjanjian_path);
        Storage::disk('local')->assertExists($akun->surat_perjanjian_path);

        Notification::assertSentTo(
            $this->admin,
            NewCopytradeSubmittedNotification::class,
            fn ($n) => $n->akun->id === $akun->id && $n->investor->id === $this->investor->id
        );
    }

    public function test_subsequent_copytrade_does_not_require_agreement(): void
    {
        // Buat akun copytrade awal
        AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '11223344',
            'modal_riil'         => 1000,
            'tanggal_mulai'      => '2026-03-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Investor Tester',
            'status'             => 'active',
        ]);

        // Pengajuan akun copytrade kedua tanpa dokumen
        $payload = [
            'tier_investasi'        => 'tier_2',
            'nomor_akun_trading'    => '55667788',
            'modal_riil'            => 10000,
            'tanggal_mulai_trading' => '2026-04-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Investor Tester',
        ];

        $response = $this->actingAs($this->investor)->post('/member/copytrade', $payload);

        $response->assertRedirect(route('member.investments.index'));
        $this->assertDatabaseHas('akun_copytrade', [
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_2',
            'nomor_akun_trading' => '55667788',
            'status'             => 'pending',
        ]);
    }

    public function test_copytrade_validates_minimum_deposit_against_settings(): void
    {
        $payload = [
            'tier_investasi'        => 'tier_2', // Tier 2 min is 5000
            'nomor_akun_trading'    => '88776655',
            'modal_riil'            => 500, // Di bawah batas minimal 5000
            'tanggal_mulai_trading' => '2026-03-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Investor Tester',
        ];

        $response = $this->actingAs($this->investor)->post('/member/copytrade', $payload);

        $response->assertSessionHasErrors('modal_riil');
    }

    public function test_tier_3_requires_server_and_passwords(): void
    {
        $payload = [
            'tier_investasi'        => 'tier_3',
            'nomor_akun_trading'    => '99887766',
            'modal_riil'            => 350000,
            'tanggal_mulai_trading' => '2026-03-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Investor Tester',
            // server_broker, master_password, investor_password sengaja kosong
        ];

        $response = $this->actingAs($this->investor)->post('/member/copytrade', $payload);

        $response->assertSessionHasErrors(['server_broker', 'master_password', 'investor_password']);
    }

    public function test_tier_3_stores_encrypted_credentials(): void
    {
        $file = UploadedFile::fake()->create('perjanjian.pdf', 500, 'application/pdf');

        $payload = [
            'tier_investasi'        => 'tier_3',
            'nomor_akun_trading'    => '99887766',
            'modal_riil'            => 350000,
            'tanggal_mulai_trading' => '2026-03-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Investor Tester',
            'server_broker'         => 'XMGlobal-Real 50',
            'master_password'       => 'MasterSecret123',
            'investor_password'     => 'InvestorSecret456',
            'dokumen_perjanjian'    => $file,
        ];

        $response = $this->actingAs($this->investor)->post('/member/copytrade', $payload);

        $response->assertRedirect(route('member.investments.index'));

        $akun = AkunCopytrade::where('nomor_akun_trading', '99887766')->first();
        $this->assertNotNull($akun);

        // Nilai terdekripsi transparan saat diakses via Eloquent
        $this->assertEquals('MasterSecret123', $akun->master_password);
        $this->assertEquals('InvestorSecret456', $akun->investor_password);

        // Di database mentah tidak disimpan sebagai plain text
        $raw = \Illuminate\Support\Facades\DB::table('akun_copytrade')->where('id', $akun->id)->first();
        $this->assertNotEquals('MasterSecret123', $raw->master_password);
        $this->assertNotEquals('InvestorSecret456', $raw->investor_password);
    }

    public function test_admin_can_view_copytrade_list_with_search_and_filter(): void
    {
        AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '11223344',
            'modal_riil'         => 1000,
            'tanggal_mulai'      => '2026-03-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Investor Tester',
            'status'             => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/copytrade?ver_status=pending&ver_search=11223344');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Copytrade/Index')
            ->has('verifikasi')
            ->has('aktif')
            ->has('counts')
            ->has('verFilters')
            ->has('aktFilters')
        );
    }

    public function test_admin_can_approve_copytrade(): void
    {
        Notification::fake();

        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '11223344',
            'modal_riil'         => 1000,
            'tanggal_mulai'      => '2026-03-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Investor Tester',
            'status'             => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/copytrade/{$akun->id}/verify", [
            'status'        => 'active',
            'catatan_admin' => 'Akun valid dan tersambung.',
        ]);

        $response->assertSessionHas('success');

        $akun->refresh();
        $this->assertEquals('active', $akun->status);
        $this->assertNotNull($akun->verified_at);
        $this->assertEquals($this->admin->id, $akun->verified_by);

        Notification::assertSentTo(
            $this->investor,
            CopytradeAccountStatusChangedNotification::class,
            fn ($n) => $n->status === 'active'
        );
    }

    public function test_admin_rejection_requires_notes(): void
    {
        Notification::fake();

        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '11223344',
            'modal_riil'         => 1000,
            'tanggal_mulai'      => '2026-03-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Investor Tester',
            'status'             => 'pending',
        ]);

        // Tolak tanpa catatan -> gagal
        $response = $this->actingAs($this->admin)->post("/admin/copytrade/{$akun->id}/verify", [
            'status'        => 'rejected',
            'catatan_admin' => '',
        ]);
        $response->assertSessionHasErrors('catatan_admin');

        // Tolak dengan catatan -> berhasil
        $responseSuccess = $this->actingAs($this->admin)->post("/admin/copytrade/{$akun->id}/verify", [
            'status'        => 'rejected',
            'catatan_admin' => 'Nomor akun trading MT4 tidak ditemukan di server broker.',
        ]);
        $responseSuccess->assertSessionHas('success');

        $akun->refresh();
        $this->assertEquals('rejected', $akun->status);
        $this->assertEquals('Nomor akun trading MT4 tidak ditemukan di server broker.', $akun->catatan_admin);

        Notification::assertSentTo(
            $this->investor,
            CopytradeAccountStatusChangedNotification::class,
            fn ($n) => $n->status === 'rejected' && $n->notes === 'Nomor akun trading MT4 tidak ditemukan di server broker.'
        );
    }

    public function test_admin_rejects_first_copytrade_updates_agreement_document_status_to_rejected(): void
    {
        Notification::fake();

        $file = UploadedFile::fake()->create('surat_perjanjian.pdf', 500, 'application/pdf');
        $storedPath = $file->store("copytrade_agreements/{$this->investor->id}", 'local');

        $dokumen = UserDokumen::create([
            'user_id'      => $this->investor->id,
            'tipe_dokumen' => 'perjanjian',
            'file_path'    => $storedPath,
            'file_name'    => 'surat_perjanjian.pdf',
            'mime_type'    => 'application/pdf',
            'file_size'    => 500000,
            'status'       => 'pending',
        ]);

        $akun = AkunCopytrade::create([
            'user_id'               => $this->investor->id,
            'tier'                  => 'tier_1',
            'broker'                => 'HFM',
            'tipe_akun'             => 'cent',
            'nomor_akun_trading'    => '11223344',
            'modal_riil'            => 1000,
            'tanggal_mulai'         => '2026-03-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Investor Tester',
            'status'                => 'pending',
            'surat_perjanjian_path' => $storedPath,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/copytrade/{$akun->id}/verify", [
            'status'        => 'rejected',
            'catatan_admin' => 'Tanda tangan pada surat perjanjian tidak sesuai dengan KTP.',
        ]);

        $response->assertSessionHas('success');

        $akun->refresh();
        $this->assertEquals('rejected', $akun->status);

        $dokumen->refresh();
        $this->assertEquals('rejected', $dokumen->status);
        $this->assertEquals('Tanda tangan pada surat perjanjian tidak sesuai dengan KTP.', $dokumen->catatan_admin);
        $this->assertEquals($this->admin->id, $dokumen->verified_by);
        $this->assertNotNull($dokumen->verified_at);
    }

    public function test_admin_rejects_subsequent_copytrade_does_not_affect_approved_agreement(): void
    {
        Notification::fake();

        $file = UploadedFile::fake()->create('surat_perjanjian.pdf', 500, 'application/pdf');
        $storedPath = $file->store("copytrade_agreements/{$this->investor->id}", 'local');

        // Perjanjian awal sudah disetujui bersama akun pertama
        $dokumen = UserDokumen::create([
            'user_id'      => $this->investor->id,
            'tipe_dokumen' => 'perjanjian',
            'file_path'    => $storedPath,
            'file_name'    => 'surat_perjanjian.pdf',
            'mime_type'    => 'application/pdf',
            'file_size'    => 500000,
            'status'       => 'approved',
            'verified_at'  => now()->subDay(),
            'verified_by'  => $this->admin->id,
        ]);

        // Akun pertama sudah aktif
        AkunCopytrade::create([
            'user_id'               => $this->investor->id,
            'tier'                  => 'tier_1',
            'broker'                => 'HFM',
            'tipe_akun'             => 'cent',
            'nomor_akun_trading'    => '11223344',
            'modal_riil'            => 1000,
            'tanggal_mulai'         => '2026-03-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Investor Tester',
            'status'                => 'active',
            'surat_perjanjian_path' => $storedPath,
        ]);

        // Pengajuan akun kedua (pending)
        $akun2 = AkunCopytrade::create([
            'user_id'               => $this->investor->id,
            'tier'                  => 'tier_2',
            'broker'                => 'XM',
            'tipe_akun'             => 'standard',
            'nomor_akun_trading'    => '55667788',
            'modal_riil'            => 10000,
            'tanggal_mulai'         => '2026-04-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Investor Tester',
            'status'                => 'pending',
            'surat_perjanjian_path' => $storedPath,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/copytrade/{$akun2->id}/verify", [
            'status'        => 'rejected',
            'catatan_admin' => 'Nomor akun kedua salah.',
        ]);

        $response->assertSessionHas('success');

        $akun2->refresh();
        $this->assertEquals('rejected', $akun2->status);

        // Dokumen perjanjian awal tetap berstatus approved
        $dokumen->refresh();
        $this->assertEquals('approved', $dokumen->status);
    }

    public function test_account_page_only_displays_approved_agreement_and_disallows_modification(): void
    {
        $file = UploadedFile::fake()->create('surat_perjanjian.pdf', 500, 'application/pdf');
        $storedPath = $file->store("copytrade_agreements/{$this->investor->id}", 'local');

        $dokumen = UserDokumen::create([
            'user_id'       => $this->investor->id,
            'tipe_dokumen'  => 'perjanjian',
            'file_path'     => $storedPath,
            'file_name'     => 'surat_perjanjian.pdf',
            'mime_type'     => 'application/pdf',
            'file_size'     => 500000,
            'status'        => 'rejected',
            'catatan_admin' => 'Tanda tangan buram.',
        ]);

        // 1. Dokumen yang ditolak / belum disetujui TIDAK ditampilkan pada halaman account
        $response = $this->actingAs($this->investor)->get('/member/account');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Member/Account/Index')
            ->where('dokumen.perjanjian', null)
        );

        // 2. Fungsional pengubahan / unggah ulang surat perjanjian di halaman account ditiadakan (404)
        $fileBaru = UploadedFile::fake()->create('surat_perjanjian_revisi.pdf', 600, 'application/pdf');
        $reuploadResponse = $this->actingAs($this->investor)->post('/member/account/reupload/perjanjian', [
            'file' => $fileBaru,
        ]);
        $reuploadResponse->assertStatus(404);

        // 3. Hanya dokumen perjanjian yang telah berstatus approved yang ditampilkan
        $dokumen->update(['status' => 'approved', 'verified_at' => now(), 'verified_by' => $this->admin->id]);
        $responseApproved = $this->actingAs($this->investor)->get('/member/account');
        $responseApproved->assertStatus(200);
        $responseApproved->assertInertia(fn ($page) => $page
            ->component('Member/Account/Index')
            ->where('dokumen.perjanjian.status', 'approved')
            ->where('dokumen.perjanjian.file_name', 'surat_perjanjian.pdf')
        );
    }

    public function test_document_security_streaming_agreement(): void
    {
        $file = UploadedFile::fake()->create('perjanjian.pdf', 500, 'application/pdf');
        $storedPath = $file->store("copytrade_agreements/{$this->investor->id}", 'local');

        $akun = AkunCopytrade::create([
            'user_id'               => $this->investor->id,
            'tier'                  => 'tier_1',
            'broker'                => 'HFM',
            'tipe_akun'             => 'cent',
            'nomor_akun_trading'    => '11223344',
            'modal_riil'            => 1000,
            'tanggal_mulai'         => '2026-03-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Investor Tester',
            'status'                => 'pending',
            'surat_perjanjian_path' => $storedPath,
        ]);

        // Pemilik dapat mengakses berkas
        $ownerResponse = $this->actingAs($this->investor)->get("/documents/copytrade/{$akun->id}");
        $ownerResponse->assertStatus(200);

        // Admin dapat mengakses berkas
        $adminResponse = $this->actingAs($this->admin)->get("/documents/copytrade/{$akun->id}");
        $adminResponse->assertStatus(200);

        // Member lain dilarang mengakses berkas (403 Forbidden)
        $otherMember = User::factory()->create([
            'role'   => 'member',
            'status' => 'active',
        ]);
        $otherResponse = $this->actingAs($otherMember)->get("/documents/copytrade/{$akun->id}");
        $otherResponse->assertStatus(403);
    }

    public function test_investor_can_download_prefilled_agreement_docx(): void
    {
        $this->investor->update([
            'nik'          => '3201123456780001',
            'tempat_lahir' => 'Bandung',
            'pekerjaan'    => 'Pengusaha',
        ]);

        $this->investor->ahliWaris()->create([
            'nama'              => 'Dewi Lestari',
            'hubungan_keluarga' => 'Istri',
            'no_hp'             => '08123456789',
            'alamat'            => 'Jl. Merdeka No. 10',
        ]);

        $params = [
            'nomor_akun_trading'    => '99887711',
            'modal_riil'            => '15000',
            'tanggal_mulai_trading' => '2026-05-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '8899001122',
            'atas_nama_rekening'    => $this->investor->name,
        ];

        $response = $this->actingAs($this->investor)->get('/member/copytrade/template-perjanjian/tier_1?' . http_build_query($params));

        $response->assertStatus(200);
        $this->assertStringContainsString('Surat_Perjanjian_Kemitraan_TIER1_', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.docx', $response->headers->get('content-disposition'));

        // Pastikan konten file binary memuat nama investor
        $binaryContent = $response->streamedContent();
        $tempPath = tempnam(sys_get_temp_dir(), 'test_dl_') . '.docx';
        file_put_contents($tempPath, $binaryContent);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($tempPath));
        $xml = $zip->getFromName('word/document.xml');
        $this->assertStringContainsString($this->investor->name, $xml);
        $this->assertStringContainsString('3201123456780001', $xml);
        $this->assertStringContainsString('99887711', $xml);
        $this->assertStringContainsString('Dewi Lestari', $xml);
        $zip->close();
        unlink($tempPath);
    }
}
