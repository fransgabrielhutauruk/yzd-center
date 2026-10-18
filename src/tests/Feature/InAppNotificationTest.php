<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\KlaimReferral;
use App\Models\User;
use App\Models\UserDokumen;
use App\Notifications\KycDocumentStatusChangedNotification;
use App\Notifications\ReferralClaimStatusChangedNotification;
use App\Services\BalanceService;
use App\Services\CopytradeService;
use App\Services\KycService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $investor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => 'administrator',
            'status' => 'active',
        ]);

        $this->investor = User::factory()->create([
            'role'   => 'investor',
            'status' => 'pending',
        ]);
    }

    public function test_investor_receives_database_notification_when_kyc_document_is_verified(): void
    {
        $dokumen = UserDokumen::create([
            'user_id'      => $this->investor->id,
            'tipe_dokumen' => 'ktp',
            'file_path'    => 'test/path.jpg',
            'file_name'    => 'ktp.jpg',
            'mime_type'    => 'image/jpeg',
            'file_size'    => 1024,
            'status'       => 'pending',
        ]);

        $kycService = app(KycService::class);
        $kycService->verifyDocument($dokumen, 'approved', null, $this->admin);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $this->investor->id,
            'notifiable_type' => User::class,
        ]);

        $this->assertEquals(1, $this->investor->unreadNotifications()->count());
        $notification = $this->investor->unreadNotifications()->first();
        $this->assertEquals('Dokumen KTP Disetujui', $notification->data['title']);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $dokumen = UserDokumen::create([
            'user_id'      => $this->investor->id,
            'tipe_dokumen' => 'ktp',
            'file_path'    => 'test/path.jpg',
            'file_name'    => 'ktp.jpg',
            'mime_type'    => 'image/jpeg',
            'file_size'    => 1024,
            'status'       => 'pending',
        ]);

        $this->investor->notify(new KycDocumentStatusChangedNotification($dokumen, 'approved'));

        $notification = $this->investor->unreadNotifications()->first();
        $this->assertNotNull($notification);

        $response = $this->actingAs($this->investor)
            ->post("/notifications/{$notification->id}/read");

        $response->assertRedirect();
        $this->assertEquals(0, $this->investor->fresh()->unreadNotifications()->count());
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $dokumen = UserDokumen::create([
            'user_id'      => $this->investor->id,
            'tipe_dokumen' => 'ktp',
            'file_path'    => 'test/path.jpg',
            'file_name'    => 'ktp.jpg',
            'mime_type'    => 'image/jpeg',
            'file_size'    => 1024,
            'status'       => 'pending',
        ]);

        $this->investor->notify(new KycDocumentStatusChangedNotification($dokumen, 'approved'));
        $this->investor->notify(new KycDocumentStatusChangedNotification($dokumen, 'revision_needed', 'Foto buram'));

        $this->assertEquals(2, $this->investor->unreadNotifications()->count());

        $response = $this->actingAs($this->investor)
            ->post('/notifications/mark-all-read');

        $response->assertRedirect();
        $this->assertEquals(0, $this->investor->fresh()->unreadNotifications()->count());
    }

    public function test_guest_cannot_access_notifications_page(): void
    {
        $response = $this->get('/notifications');

        $response->assertRedirect('/login');
    }

    public function test_user_can_access_notifications_page(): void
    {
        $dokumen = UserDokumen::create([
            'user_id'      => $this->investor->id,
            'tipe_dokumen' => 'ktp',
            'file_path'    => 'test/path.jpg',
            'file_name'    => 'ktp.jpg',
            'mime_type'    => 'image/jpeg',
            'file_size'    => 1024,
            'status'       => 'pending',
        ]);

        $this->investor->notify(new KycDocumentStatusChangedNotification($dokumen, 'approved'));

        $response = $this->actingAs($this->investor)
            ->get('/notifications');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Notifications/Index')
            ->has('notificationsData.data', 1)
            ->where('currentFilter', 'all')
        );
    }

    public function test_user_can_filter_unread_notifications(): void
    {
        $dokumen = UserDokumen::create([
            'user_id'      => $this->investor->id,
            'tipe_dokumen' => 'ktp',
            'file_path'    => 'test/path.jpg',
            'file_name'    => 'ktp.jpg',
            'mime_type'    => 'image/jpeg',
            'file_size'    => 1024,
            'status'       => 'pending',
        ]);

        $this->investor->notify(new KycDocumentStatusChangedNotification($dokumen, 'approved'));
        $this->investor->notify(new KycDocumentStatusChangedNotification($dokumen, 'revision_needed', 'Foto buram'));

        // Mark 1 as read
        $notification = $this->investor->unreadNotifications()->first();
        $notification->markAsRead();

        $response = $this->actingAs($this->investor)
            ->get('/notifications?filter=unread');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Notifications/Index')
            ->has('notificationsData.data', 1)
            ->where('currentFilter', 'unread')
        );
    }

    public function test_admin_receives_database_notification_when_investor_submits_copytrade(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('perjanjian.pdf', 500, 'application/pdf');

        $copytradeService = app(CopytradeService::class);
        $akun = $copytradeService->storeCopytrade(
            $this->investor,
            [
                'tier_investasi'        => 'tier_1',
                'nomor_akun_trading'    => '99887766',
                'modal_riil'            => 1500,
                'tanggal_mulai_trading' => '2026-03-01',
                'nama_bank'             => 'BCA',
                'nomor_rekening'        => '1234567890',
                'atas_nama_rekening'    => 'Investor Tester',
            ],
            $file
        );

        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $this->admin->id,
            'notifiable_type' => User::class,
        ]);

        $this->assertEquals(1, $this->admin->unreadNotifications()->count());
        $notification = $this->admin->unreadNotifications()->first();
        $this->assertEquals('Pengajuan Akun Copytrade Baru', $notification->data['title']);
        $this->assertEquals('/admin/copytrade', $notification->data['url']);
        $this->assertEquals('file-check', $notification->data['icon']);
        $this->assertEquals($akun->id, $notification->data['copytrade_id']);
    }

    public function test_investor_receives_database_notification_when_copytrade_is_approved(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '99887766',
            'modal_riil'         => 1500,
            'tanggal_mulai'      => '2026-03-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Investor Tester',
            'status'             => 'pending',
        ]);

        $copytradeService = app(CopytradeService::class);
        $copytradeService->verifyAccount($akun, 'active', 'Akun aktif dan terhubung.', $this->admin);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $this->investor->id,
            'notifiable_type' => User::class,
        ]);

        $this->assertEquals(1, $this->investor->unreadNotifications()->count());
        $notification = $this->investor->unreadNotifications()->first();
        $this->assertEquals('Akun Copy Trade Valid & Aktif', $notification->data['title']);
        $this->assertEquals('/investor/investments', $notification->data['url']);
        $this->assertEquals('check-circle', $notification->data['icon']);
        $this->assertEquals('active', $notification->data['status']);
    }

    public function test_investor_receives_database_notification_when_copytrade_is_rejected(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '99887766',
            'modal_riil'         => 1500,
            'tanggal_mulai'      => '2026-03-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Investor Tester',
            'status'             => 'pending',
        ]);

        $copytradeService = app(CopytradeService::class);
        $copytradeService->verifyAccount($akun, 'rejected', 'Nomor akun MT4 salah.', $this->admin);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $this->investor->id,
            'notifiable_type' => User::class,
        ]);

        $this->assertEquals(1, $this->investor->unreadNotifications()->count());
        $notification = $this->investor->unreadNotifications()->first();
        $this->assertEquals('Akun Copy Trade Ditolak', $notification->data['title']);
        $this->assertEquals('/investor/investments', $notification->data['url']);
        $this->assertEquals('x-circle', $notification->data['icon']);
        $this->assertEquals('rejected', $notification->data['status']);
        $this->assertStringContainsString('Nomor akun MT4 salah.', $notification->data['message']);
    }

    public function test_investor_receives_monthly_balance_recap_reminder_notification_during_recap_period(): void
    {
        Carbon::setTestNow('2026-10-02 10:00:00');

        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '11223344',
            'modal_riil'         => 1000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Investor Tester',
            'status'             => 'active',
        ]);

        $balanceService = app(BalanceService::class);
        $result = $balanceService->sendMonthlyBalanceReminderIfNeeded($this->investor);

        $this->assertTrue($result);
        $this->assertEquals(1, $this->investor->unreadNotifications()->count());

        $notification = $this->investor->unreadNotifications()->first();
        $this->assertEquals('balance_recap_reminder', $notification->data['type']);
        $this->assertEquals('Waktunya Rekapitulasi Saldo Tanggal 1!', $notification->data['title']);
        $this->assertEquals('/investor/investments#kinerja-dan-riwayat-akun', $notification->data['url']);
        $this->assertEquals('calendar', $notification->data['icon']);

        // Pastikan tidak dikirim berulang (idempotent / tidak duplikat)
        $secondResult = $balanceService->sendMonthlyBalanceReminderIfNeeded($this->investor);
        $this->assertFalse($secondResult);
        $this->assertEquals(1, $this->investor->unreadNotifications()->count());

        Carbon::setTestNow();
    }

    public function test_send_monthly_balance_recap_reminder_artisan_command(): void
    {
        Carbon::setTestNow('2026-10-01 07:00:00');

        $this->investor->update(['status' => 'active']);

        AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '55667788',
            'modal_riil'         => 2000,
            'tanggal_mulai'      => '2026-09-01',
            'nama_bank'          => 'Mandiri',
            'nomor_rekening'     => '987654321',
            'atas_nama_rekening' => 'Investor Tester',
            'status'             => 'active',
        ]);

        $this->artisan('app:send-balance-recap-reminder')
            ->assertSuccessful();

        $this->assertEquals(1, $this->investor->unreadNotifications()->count());

        Carbon::setTestNow();
    }

    public function test_referral_claim_rejected_notification_truncates_long_catatan_admin(): void
    {
        $affiliator = User::factory()->create([
            'role'   => 'investor',
            'status' => 'active',
        ]);

        $claim = KlaimReferral::create([
            'affiliator_id' => $affiliator->id,
            'downline_id'   => $this->investor->id,
            'alasan'        => 'Klaim referral susulan',
            'status'        => 'pending',
        ]);

        $longCatatan = 'Satu dua tiga empat lima enam tujuh delapan sembilan sepuluh sebelas dua-belas tiga-belas empat-belas lima-belas.';

        $affiliator->notify(new ReferralClaimStatusChangedNotification($claim, 'rejected', $longCatatan));

        $this->assertEquals(1, $affiliator->unreadNotifications()->count());
        $notification = $affiliator->unreadNotifications()->first();

        $this->assertEquals('Klaim Referral Susulan Ditolak', $notification->data['title']);
        $this->assertEquals('/investor/affiliates?tab=claims', $notification->data['url']);
        $this->assertEquals($longCatatan, $notification->data['catatan_admin']);

        // Pastikan pesan hanya memuat 10 kata pertama dan diakhiri ...
        $expectedPreview = 'Satu dua tiga empat lima enam tujuh delapan sembilan sepuluh...';
        $this->assertStringContainsString("Catatan: {$expectedPreview}", $notification->data['message']);
        $this->assertStringNotContainsString('sebelas', $notification->data['message']);
    }

    public function test_referral_claim_rejected_notification_preserves_short_catatan_admin(): void
    {
        $affiliator = User::factory()->create([
            'role'   => 'investor',
            'status' => 'active',
        ]);

        $claim = KlaimReferral::create([
            'affiliator_id' => $affiliator->id,
            'downline_id'   => $this->investor->id,
            'alasan'        => 'Klaim referral susulan',
            'status'        => 'pending',
        ]);

        $shortCatatan = 'Bukti chat tidak valid.';

        $affiliator->notify(new ReferralClaimStatusChangedNotification($claim, 'rejected', $shortCatatan));

        $notification = $affiliator->unreadNotifications()->first();
        $this->assertStringContainsString("Catatan: {$shortCatatan}", $notification->data['message']);
        $this->assertStringNotContainsString('...', $notification->data['message']);
    }
}
