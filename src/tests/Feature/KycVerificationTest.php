<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\User;
use App\Models\UserAhliWaris;
use App\Models\UserDokumen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingUserWithDocs(): User
    {
        $user = User::factory()->create([
            'role'   => 'investor',
            'status' => 'pending',
        ]);

        UserDokumen::create([
            'user_id'      => $user->id,
            'tipe_dokumen' => 'ktp',
            'file_path'    => "kyc_documents/{$user->id}/ktp.jpg",
            'file_name'    => 'ktp.jpg',
            'mime_type'    => 'image/jpeg',
            'file_size'    => 500000,
            'status'       => 'pending',
        ]);

        UserDokumen::create([
            'user_id'      => $user->id,
            'tipe_dokumen' => 'perjanjian',
            'file_path'    => "kyc_documents/{$user->id}/perjanjian.pdf",
            'file_name'    => 'perjanjian.pdf',
            'mime_type'    => 'application/pdf',
            'file_size'    => 800000,
            'status'       => 'pending',
        ]);

        return $user;
    }

    public function test_only_admin_can_access_kyc_verification_page(): void
    {
        $admin = User::factory()->admin()->create();
        $investor = User::factory()->create(['role' => 'investor']);

        // Tamu ditolak (redirect ke login)
        $this->get('/admin/kyc')->assertRedirect('/login');

        // Investor ditolak (403 Forbidden)
        $this->actingAs($investor)->get('/admin/kyc')->assertForbidden();

        // Admin berhasil (200 OK)
        $this->actingAs($admin)->get('/admin/kyc')->assertOk();
    }

    public function test_admin_can_approve_document(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->createPendingUserWithDocs();
        $ktpDoc = $user->dokumen()->where('tipe_dokumen', 'ktp')->first();

        $response = $this->actingAs($admin)->post("/admin/kyc/documents/{$ktpDoc->id}/verify", [
            'status' => 'approved',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('approved', $ktpDoc->fresh()->status);
        $this->assertEquals($admin->id, $ktpDoc->fresh()->verified_by);

        // Karena dokumen perjanjian belum diapprove, user masih pending
        $this->assertEquals('pending', $user->fresh()->status);
    }

    public function test_user_becomes_active_when_all_documents_are_approved(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->createPendingUserWithDocs();

        $ktpDoc = $user->dokumen()->where('tipe_dokumen', 'ktp')->first();
        $perjanjianDoc = $user->dokumen()->where('tipe_dokumen', 'perjanjian')->first();

        // Approve KTP
        $this->actingAs($admin)->post("/admin/kyc/documents/{$ktpDoc->id}/verify", [
            'status' => 'approved',
        ]);

        // Approve Perjanjian
        $this->actingAs($admin)->post("/admin/kyc/documents/{$perjanjianDoc->id}/verify", [
            'status' => 'approved',
        ]);

        // Kedua dokumen approved -> user otomatis ACTIVE
        $this->assertEquals('approved', $ktpDoc->fresh()->status);
        $this->assertEquals('approved', $perjanjianDoc->fresh()->status);
        $this->assertEquals('active', $user->fresh()->status);
    }

    public function test_admin_must_provide_notes_when_requesting_revision(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->createPendingUserWithDocs();
        $ktpDoc = $user->dokumen()->where('tipe_dokumen', 'ktp')->first();

        // Gagal jika catatan admin kosong saat revision_needed
        $response = $this->actingAs($admin)->post("/admin/kyc/documents/{$ktpDoc->id}/verify", [
            'status'        => 'revision_needed',
            'catatan_admin' => '',
        ]);

        $response->assertSessionHasErrors(['catatan_admin']);
        $this->assertEquals('pending', $ktpDoc->fresh()->status);

        // Berhasil jika catatan admin diisi
        $responseSuccess = $this->actingAs($admin)->post("/admin/kyc/documents/{$ktpDoc->id}/verify", [
            'status'        => 'revision_needed',
            'catatan_admin' => 'Foto KTP buram dan silau pada bagian NIK.',
        ]);

        $responseSuccess->assertSessionHasNoErrors();
        $this->assertEquals('revision_needed', $ktpDoc->fresh()->status);
        $this->assertEquals('Foto KTP buram dan silau pada bagian NIK.', $ktpDoc->fresh()->catatan_admin);
    }

    public function test_admin_cannot_reject_document_directly(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->createPendingUserWithDocs();
        $ktpDoc = $user->dokumen()->where('tipe_dokumen', 'ktp')->first();

        // Status rejected tidak lagi diizinkan untuk verifikasi dokumen (hanya approved atau revision_needed)
        $response = $this->actingAs($admin)->post("/admin/kyc/documents/{$ktpDoc->id}/verify", [
            'status'        => 'rejected',
            'catatan_admin' => 'Dokumen ditolak langsung.',
        ]);

        $response->assertSessionHasErrors(['status']);
        $this->assertEquals('pending', $ktpDoc->fresh()->status);
    }

    public function test_investor_can_reupload_document_that_needs_revision(): void
    {
        Storage::fake('local');
        $user = $this->createPendingUserWithDocs();
        $ktpDoc = $user->dokumen()->where('tipe_dokumen', 'ktp')->first();

        // Set status ke revision_needed
        $ktpDoc->update(['status' => 'revision_needed', 'catatan_admin' => 'Foto buram']);

        $response = $this->actingAs($user)->post('/investor/kyc/reupload/ktp', [
            'file' => UploadedFile::fake()->image('ktp_baru.jpg', 1000, 700),
        ]);

        $response->assertSessionHasNoErrors();

        // Status kembali menjadi pending setelah diunggah ulang
        $this->assertEquals('pending', $ktpDoc->fresh()->status);
        $this->assertNull($ktpDoc->fresh()->catatan_admin);
    }

    public function test_investor_can_reupload_document_when_pending_with_quality_warning(): void
    {
        Storage::fake('local');
        $user = $this->createPendingUserWithDocs();
        $ktpDoc = $user->dokumen()->where('tipe_dokumen', 'ktp')->first();

        // Dokumen berstatus pending dengan is_quality_warning = true
        $ktpDoc->update([
            'status'             => 'pending',
            'is_quality_warning' => true,
        ]);

        $response = $this->actingAs($user)->post('/investor/kyc/reupload/ktp', [
            'file' => UploadedFile::fake()->image('ktp_tajam.jpg', 1200, 800),
        ]);

        $response->assertSessionHasNoErrors();

        $ktpDocFresh = $ktpDoc->fresh();
        $this->assertEquals('pending', $ktpDocFresh->status);
        $this->assertEquals('ktp_tajam.jpg', $ktpDocFresh->file_name);
    }

    public function test_investor_cannot_reupload_approved_document(): void
    {
        Storage::fake('local');
        $user = $this->createPendingUserWithDocs();
        $ktpDoc = $user->dokumen()->where('tipe_dokumen', 'ktp')->first();

        // Set status ke approved
        $ktpDoc->update(['status' => 'approved']);

        $response = $this->actingAs($user)->post('/investor/kyc/reupload/ktp', [
            'file' => UploadedFile::fake()->image('ktp_iseng.jpg', 1000, 700),
        ]);

        $response->assertSessionHasErrors(['ktp']);
        $this->assertEquals('approved', $ktpDoc->fresh()->status);
    }

    public function test_investor_can_view_kyc_page_with_copytrade_account_and_profile_fields(): void
    {
        $user = User::factory()->create([
            'role'          => 'investor',
            'status'        => 'pending',
            'tempat_lahir'  => 'Bandung',
            'tanggal_lahir' => '1995-08-17',
            'pekerjaan'     => 'Wiraswasta',
        ]);

        UserAhliWaris::create([
            'user_id'           => $user->id,
            'nama'              => 'Dewi Lestari',
            'hubungan_keluarga' => 'Istri',
            'no_hp'             => '08123456789',
            'alamat'            => 'Jl. Sukajadi No. 12, Bandung',
        ]);

        AkunCopytrade::create([
            'user_id'            => $user->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '99887766',
            'estimasi_modal'     => 1500,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Dewi & User',
            'status'             => 'pending',
        ]);

        $response = $this->actingAs($user)->get('/investor/kyc');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Kyc/Index')
            ->where('user.tempat_lahir', 'Bandung')
            ->where('user.tanggal_lahir', '17 Agustus 1995')
            ->where('user.pekerjaan', 'Wiraswasta')
            ->where('ahli_waris.nama', 'Dewi Lestari')
            ->where('ahli_waris.hubungan_keluarga', 'Istri')
            ->where('ahli_waris.no_hp', '08123456789')
            ->where('ahli_waris.alamat', 'Jl. Sukajadi No. 12, Bandung')
            ->where('akun_copytrade.tier', 'tier_1')
            ->where('akun_copytrade.broker', 'HFM')
            ->where('akun_copytrade.nomor_akun_trading', '99887766')
            ->where('akun_copytrade.estimasi_modal', '1500.00')
            ->where('akun_copytrade.nama_bank', 'BCA')
        );
    }

    public function test_admin_can_view_kyc_list_with_copytrade_account_and_profile_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $user = User::factory()->create([
            'role'          => 'investor',
            'status'        => 'pending',
            'tempat_lahir'  => 'Surabaya',
            'tanggal_lahir' => '1992-12-05',
            'pekerjaan'     => 'Dokter',
        ]);

        UserAhliWaris::create([
            'user_id'           => $user->id,
            'nama'              => 'Ahmad Santoso',
            'hubungan_keluarga' => 'Saudara Kandung',
            'no_hp'             => '08198765432',
            'alamat'            => 'Jl. Pemuda No. 45, Surabaya',
        ]);

        AkunCopytrade::create([
            'user_id'            => $user->id,
            'tier'               => 'tier_2',
            'broker'             => 'XM',
            'tipe_akun'          => 'standard',
            'nomor_akun_trading' => '55443322',
            'estimasi_modal'     => 10000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'Mandiri',
            'nomor_rekening'     => '9876543210123',
            'atas_nama_rekening' => 'Ahmad Santoso',
            'status'             => 'pending',
        ]);

        $response = $this->actingAs($admin)->get('/admin/kyc');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Kyc/Index')
            ->has('investors.data', fn ($data) => $data
                ->where('0.tempat_lahir', 'Surabaya')
                ->where('0.tanggal_lahir', '05 Desember 1992')
                ->where('0.pekerjaan', 'Dokter')
                ->where('0.ahli_waris.nama', 'Ahmad Santoso')
                ->where('0.ahli_waris.alamat', 'Jl. Pemuda No. 45, Surabaya')
                ->where('0.akun_copytrade.tier', 'tier_2')
                ->where('0.akun_copytrade.broker', 'XM')
                ->where('0.akun_copytrade.nomor_akun_trading', '55443322')
                ->etc()
            )
        );
    }

    public function test_admin_can_approve_copytrade_account(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => 'investor', 'status' => 'pending']);

        $akun = AkunCopytrade::create([
            'user_id'            => $user->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '12345678',
            'estimasi_modal'     => 1000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => 'Test Investor',
            'status'             => 'pending',
        ]);

        $response = $this->actingAs($admin)->post("/admin/kyc/accounts/{$akun->id}/verify", [
            'status'        => 'active',
            'catatan_admin' => 'Akun telah terverifikasi dan follow master.',
        ]);

        $response->assertSessionHasNoErrors();
        $akunFresh = $akun->fresh();
        $this->assertEquals('active', $akunFresh->status);
        $this->assertEquals('Akun telah terverifikasi dan follow master.', $akunFresh->catatan_admin);
    }

    public function test_admin_must_provide_notes_when_rejecting_copytrade_account(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => 'investor', 'status' => 'pending']);

        $akun = AkunCopytrade::create([
            'user_id'            => $user->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '12345678',
            'estimasi_modal'     => 1000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => 'Test Investor',
            'status'             => 'pending',
        ]);

        // Gagal jika catatan admin kosong saat rejected
        $responseFail = $this->actingAs($admin)->post("/admin/kyc/accounts/{$akun->id}/verify", [
            'status'        => 'rejected',
            'catatan_admin' => '',
        ]);

        $responseFail->assertSessionHasErrors(['catatan_admin']);
        $this->assertEquals('pending', $akun->fresh()->status);

        // Berhasil saat catatan diisi untuk rejected
        $responseReject = $this->actingAs($admin)->post("/admin/kyc/accounts/{$akun->id}/verify", [
            'status'        => 'rejected',
            'catatan_admin' => 'Nomor akun trading tidak ditemukan di broker HFM.',
        ]);

        $responseReject->assertSessionHasNoErrors();
        $this->assertEquals('rejected', $akun->fresh()->status);
        $this->assertEquals('Nomor akun trading tidak ditemukan di broker HFM.', $akun->fresh()->catatan_admin);
    }

    public function test_admin_cannot_verify_copytrade_account_with_invalid_status(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => 'investor', 'status' => 'pending']);

        $akun = AkunCopytrade::create([
            'user_id'            => $user->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '12345678',
            'estimasi_modal'     => 1000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => 'Test Investor',
            'status'             => 'pending',
        ]);

        // Status suspended tidak lagi diizinkan dalam verifikasi admin (hanya active atau rejected)
        $responseSuspend = $this->actingAs($admin)->post("/admin/kyc/accounts/{$akun->id}/verify", [
            'status'        => 'suspended',
            'catatan_admin' => 'Akun dihentikan sementara karena margin level rendah.',
        ]);

        $responseSuspend->assertSessionHasErrors(['status']);
        $this->assertEquals('pending', $akun->fresh()->status);
    }

    public function test_investor_cannot_verify_copytrade_account(): void
    {
        $investor = User::factory()->create(['role' => 'investor', 'status' => 'pending']);

        $akun = AkunCopytrade::create([
            'user_id'            => $investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '12345678',
            'estimasi_modal'     => 1000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => 'Test Investor',
            'status'             => 'pending',
        ]);

        $response = $this->actingAs($investor)->post("/admin/kyc/accounts/{$akun->id}/verify", [
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertEquals('pending', $akun->fresh()->status);
    }

    public function test_investor_can_update_copytrade_account_and_banking_details(): void
    {
        $investor = User::factory()->create(['role' => 'investor', 'status' => 'pending']);

        $akun = AkunCopytrade::create([
            'user_id'            => $investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '12345678',
            'estimasi_modal'     => 1000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => 'Investor Lama',
            'status'             => 'rejected',
            'catatan_admin'      => 'Nomor MT4 salah, mohon perbaiki.',
        ]);

        $response = $this->actingAs($investor)->post('/investor/kyc/account/update', [
            'nomor_akun_trading' => '87654321',
            'estimasi_modal'     => 1500,
            'nama_bank'          => 'Mandiri',
            'nomor_rekening'     => '998877665544',
            'atas_nama_rekening' => 'Investor Baru',
        ]);

        $response->assertSessionHasNoErrors();
        $akunFresh = $akun->fresh();
        $this->assertEquals('87654321', $akunFresh->nomor_akun_trading);
        $this->assertEquals('1500.00', $akunFresh->estimasi_modal);
        $this->assertEquals('Mandiri', $akunFresh->nama_bank);
        $this->assertEquals('998877665544', $akunFresh->nomor_rekening);
        $this->assertEquals('Investor Baru', $akunFresh->atas_nama_rekening);
        // Status otomatis kembali menjadi pending dan catatan admin direset
        $this->assertEquals('pending', $akunFresh->status);
        $this->assertNull($akunFresh->catatan_admin);
    }

    public function test_investor_cannot_update_active_copytrade_account(): void
    {
        $investor = User::factory()->create(['role' => 'investor', 'status' => 'active']);

        $akun = AkunCopytrade::create([
            'user_id'            => $investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '12345678',
            'estimasi_modal'     => 1000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => 'Investor',
            'status'             => 'active',
        ]);

        $response = $this->actingAs($investor)->post('/investor/kyc/account/update', [
            'nomor_akun_trading' => '99999999',
            'estimasi_modal'     => 1000,
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => 'Investor',
        ]);

        $response->assertSessionHasErrors(['nomor_akun_trading']);
        $this->assertEquals('12345678', $akun->fresh()->nomor_akun_trading);
        $this->assertEquals('active', $akun->fresh()->status);
    }

    public function test_investor_can_confirm_follow_master(): void
    {
        $investor = User::factory()->create(['role' => 'investor', 'status' => 'pending']);

        $akun = AkunCopytrade::create([
            'user_id'            => $investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '12345678',
            'estimasi_modal'     => 1000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => 'Investor',
            'status'             => 'rejected',
            'catatan_admin'      => 'Belum follow master trader.',
        ]);

        $response = $this->actingAs($investor)->post('/investor/kyc/account/confirm-follow');

        $response->assertSessionHasNoErrors();
        $akunFresh = $akun->fresh();
        $this->assertEquals('pending', $akunFresh->status);
        $this->assertNull($akunFresh->catatan_admin);
    }

    public function test_user_status_syncs_with_copytrade_account_and_documents(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->createPendingUserWithDocs();

        $akun = AkunCopytrade::create([
            'user_id'            => $user->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '12345678',
            'estimasi_modal'     => 1000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => 'Test Investor',
            'status'             => 'pending',
        ]);

        $ktpDoc = $user->dokumen()->where('tipe_dokumen', 'ktp')->first();
        $perjanjianDoc = $user->dokumen()->where('tipe_dokumen', 'perjanjian')->first();

        // 1. Approve KTP & Perjanjian, but copytrade account is still pending -> user remains pending
        $this->actingAs($admin)->post("/admin/kyc/documents/{$ktpDoc->id}/verify", ['status' => 'approved']);
        $this->actingAs($admin)->post("/admin/kyc/documents/{$perjanjianDoc->id}/verify", ['status' => 'approved']);

        $this->assertEquals('pending', $user->fresh()->status);

        // 2. Approve copytrade account -> user status becomes active
        $this->actingAs($admin)->post("/admin/kyc/accounts/{$akun->id}/verify", [
            'status'        => 'active',
            'catatan_admin' => 'Valid and verified',
        ]);

        $this->assertEquals('active', $user->fresh()->status);

        // 3. Admin rejects copytrade account -> user status reverts to pending
        $this->actingAs($admin)->post("/admin/kyc/accounts/{$akun->id}/verify", [
            'status'        => 'rejected',
            'catatan_admin' => 'Unfollowed master trader',
        ]);

        $this->assertEquals('rejected', $akun->fresh()->status);
        $this->assertEquals('pending', $user->fresh()->status);

        // 4. Admin re-approves copytrade account -> user status becomes active again
        $this->actingAs($admin)->post("/admin/kyc/accounts/{$akun->id}/verify", [
            'status'        => 'active',
            'catatan_admin' => 'Re-verified',
        ]);

        $this->assertEquals('active', $user->fresh()->status);

        // 5. Admin requests revision on KTP -> user status reverts to pending
        $this->actingAs($admin)->post("/admin/kyc/documents/{$ktpDoc->id}/verify", [
            'status'        => 'revision_needed',
            'catatan_admin' => 'Revisi KTP',
        ]);

        $this->assertEquals('pending', $user->fresh()->status);
    }

    public function test_admin_can_filter_kyc_list_by_status(): void
    {
        $admin = User::factory()->admin()->create();

        // 1. Investor Pending (dokumen pending)
        $userPending = User::factory()->create([
            'role'   => 'investor',
            'status' => 'pending',
            'name'   => 'Pending User',
        ]);
        UserDokumen::create([
            'user_id'      => $userPending->id,
            'tipe_dokumen' => 'ktp',
            'file_path'    => 'kyc_documents/ktp_pending.jpg',
            'file_name'    => 'ktp.jpg',
            'mime_type'    => 'image/jpeg',
            'file_size'    => 500000,
            'status'       => 'pending',
        ]);

        // 2. Investor Active (dokumen approved & copytrade active)
        $userActive = User::factory()->create([
            'role'   => 'investor',
            'status' => 'active',
            'name'   => 'Active User',
        ]);
        UserDokumen::create([
            'user_id'      => $userActive->id,
            'tipe_dokumen' => 'ktp',
            'file_path'    => 'kyc_documents/ktp_active.jpg',
            'file_name'    => 'ktp.jpg',
            'mime_type'    => 'image/jpeg',
            'file_size'    => 500000,
            'status'       => 'approved',
        ]);
        AkunCopytrade::create([
            'user_id'            => $userActive->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '11111111',
            'estimasi_modal'     => 1000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '11112222',
            'atas_nama_rekening' => 'Active User',
            'status'             => 'active',
        ]);

        // 3. Investor dengan Dokumen Revision Needed
        $userRevision = User::factory()->create([
            'role'   => 'investor',
            'status' => 'pending',
            'name'   => 'Revision User',
        ]);
        UserDokumen::create([
            'user_id'      => $userRevision->id,
            'tipe_dokumen' => 'ktp',
            'file_path'    => 'kyc_documents/ktp_rev.jpg',
            'file_name'    => 'ktp.jpg',
            'mime_type'    => 'image/jpeg',
            'file_size'    => 500000,
            'status'       => 'revision_needed',
        ]);

        // 4. Investor dengan Akun Copy Trade Rejected
        $userRejected = User::factory()->create([
            'role'   => 'investor',
            'status' => 'pending',
            'name'   => 'Rejected User',
        ]);
        AkunCopytrade::create([
            'user_id'            => $userRejected->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '22222222',
            'estimasi_modal'     => 1000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '22223333',
            'atas_nama_rekening' => 'Rejected User',
            'status'             => 'rejected',
        ]);

        // Uji filter 'active'
        $responseActive = $this->actingAs($admin)->get('/admin/kyc?status=active');
        $responseActive->assertOk();
        $responseActive->assertInertia(fn ($page) => $page
            ->has('investors.data', 1)
            ->where('investors.data.0.name', 'Active User')
        );

        // Uji filter 'revision_needed'
        $responseRev = $this->actingAs($admin)->get('/admin/kyc?status=revision_needed');
        $responseRev->assertOk();
        $responseRev->assertInertia(fn ($page) => $page
            ->has('investors.data', 1)
            ->where('investors.data.0.name', 'Revision User')
        );

        // Uji filter 'rejected'
        $responseRej = $this->actingAs($admin)->get('/admin/kyc?status=rejected');
        $responseRej->assertOk();
        $responseRej->assertInertia(fn ($page) => $page
            ->has('investors.data', 1)
            ->where('investors.data.0.name', 'Rejected User')
        );

        // Uji filter 'pending'
        $responsePending = $this->actingAs($admin)->get('/admin/kyc?status=pending');
        $responsePending->assertOk();
        $responsePending->assertInertia(fn ($page) => $page
            ->has('investors.data', 3) // userPending, userRevision, userRejected
        );
    }

    public function test_investor_kyc_page_hides_admin_notes_when_items_are_approved(): void
    {
        $user = User::factory()->create([
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => true,
        ]);

        UserDokumen::create([
            'user_id'       => $user->id,
            'tipe_dokumen'  => 'ktp',
            'file_path'     => 'kyc_documents/test_ktp.jpg',
            'file_name'     => 'test_ktp.jpg',
            'mime_type'     => 'image/jpeg',
            'file_size'     => 102400,
            'status'        => 'approved',
            'catatan_admin' => 'Catatan KTP lama yang sudah tidak relevan',
        ]);

        UserDokumen::create([
            'user_id'       => $user->id,
            'tipe_dokumen'  => 'perjanjian',
            'file_path'     => 'kyc_documents/test_perjanjian.pdf',
            'file_name'     => 'test_perjanjian.pdf',
            'mime_type'     => 'application/pdf',
            'file_size'     => 204800,
            'status'        => 'approved',
            'catatan_admin' => 'Catatan Perjanjian lama yang sudah tidak relevan',
        ]);

        AkunCopytrade::create([
            'user_id'            => $user->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '99887766',
            'estimasi_modal'     => 1000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Test Investor',
            'status'             => 'active',
            'catatan_admin'      => 'Catatan Akun Copytrade lama yang sudah tidak relevan',
        ]);

        $response = $this->actingAs($user)->get('/investor/kyc');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Kyc/Index')
            ->where('dokumen.ktp.catatan_admin', null)
            ->where('dokumen.perjanjian.catatan_admin', null)
            ->where('akun_copytrade.catatan_admin', null)
        );
    }
}
