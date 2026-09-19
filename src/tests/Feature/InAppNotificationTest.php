<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\User;
use App\Models\UserDokumen;
use App\Notifications\KycDocumentStatusChangedNotification;
use App\Services\KycService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_investor_receives_database_notification_when_copytrade_account_is_verified(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'Broker A',
            'tipe_akun'          => 'fix_balance',
            'nomor_akun_trading' => '12345678',
            'estimasi_modal'     => 50000000,
            'tanggal_mulai'      => now()->toDateString(),
            'tanggal_selesai'    => now()->addYear()->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Investor Test',
            'status'             => 'pending',
        ]);

        $kycService = app(KycService::class);
        $kycService->verifyAccount($akun, 'active', null, $this->admin);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $this->investor->id,
            'notifiable_type' => User::class,
        ]);

        $this->assertEquals(1, $this->investor->unreadNotifications()->count());
        $notification = $this->investor->unreadNotifications()->first();
        $this->assertEquals('Akun Copy Trade Valid & Aktif', $notification->data['title']);
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

    public function test_admin_receives_notification_when_investor_updates_copytrade_account(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '12345678',
            'estimasi_modal'     => 1000,
            'tanggal_mulai'      => now()->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => 'Investor Lama',
            'status'             => 'rejected',
            'catatan_admin'      => 'Nomor MT4 salah',
        ]);

        $response = $this->actingAs($this->investor)->post('/investor/kyc/account/update', [
            'nomor_akun_trading' => '87654321',
            'estimasi_modal'     => 1500,
            'nama_bank'          => 'Mandiri',
            'nomor_rekening'     => '998877665544',
            'atas_nama_rekening' => 'Investor Baru',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $this->admin->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $this->admin->unreadNotifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('Pembaruan Akun Copy Trade', $notification->data['title']);
        $this->assertEquals('copytrade_account_updated', $notification->data['type']);
        $this->assertEquals('update', $notification->data['action']);
    }

    public function test_admin_receives_notification_when_investor_confirms_follow_master(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => '12345678',
            'estimasi_modal'     => 1000,
            'tanggal_mulai'      => now()->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => 'Investor',
            'status'             => 'rejected',
            'catatan_admin'      => 'Belum follow master',
        ]);

        $response = $this->actingAs($this->investor)->post('/investor/kyc/account/confirm-follow');

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $this->admin->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $this->admin->unreadNotifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('Konfirmasi Follow Master Copy Trade', $notification->data['title']);
        $this->assertEquals('copytrade_account_updated', $notification->data['type']);
        $this->assertEquals('confirm_follow', $notification->data['action']);
    }
}
