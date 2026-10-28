<?php

namespace Tests\Feature;

use App\Models\TiketBantuan;
use App\Models\TiketBantuanBalasan;
use App\Models\TiketBantuanLampiran;
use App\Models\User;
use App\Notifications\TiketBalasanAdminNotification;
use App\Notifications\TiketBalasanMemberNotification;
use App\Notifications\TiketBaruNotification;
use App\Notifications\TiketStatusDiubahNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TiketBantuanTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected User $otherMember;

    protected User $pendingMember;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::factory()->create([
            'role'      => 'administrator',
            'is_active' => true,
        ]);

        $this->member = User::factory()->create([
            'role'      => 'member',
            'is_active' => true,
            'status'    => 'active',
        ]);

        $this->otherMember = User::factory()->create([
            'role'      => 'member',
            'is_active' => true,
            'status'    => 'active',
        ]);

        $this->pendingMember = User::factory()->create([
            'role'      => 'member',
            'is_active' => true,
            'status'    => 'pending',
        ]);
    }

    public function test_guest_cannot_access_ticket_routes(): void
    {
        $response = $this->get(route('member.tickets.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('admin.tickets.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_member_and_pending_kyc_member_can_access_ticket_pages(): void
    {
        $response = $this->actingAs($this->member)->get(route('member.tickets.index'));
        $response->assertOk();

        $response = $this->actingAs($this->pendingMember)->get(route('member.tickets.index'));
        $response->assertOk();

        $response = $this->actingAs($this->pendingMember)->get(route('member.tickets.create'));
        $response->assertOk();
    }

    public function test_member_can_create_ticket_with_images_and_receives_correct_monthly_number(): void
    {
        Notification::fake();

        $image1 = UploadedFile::fake()->image('bukti1.jpg', 600, 400)->size(500);
        $image2 = UploadedFile::fake()->image('bukti2.png', 800, 600)->size(800);

        $response = $this->actingAs($this->member)->post(route('member.tickets.store'), [
            'subjek' => 'Kendala deposit MT4 tidak terdeteksi',
            'pesan'  => 'Saya sudah melakukan transfer deposit sebesar $1000 namun saldo di web belum terupdate.',
            'images' => [$image1, $image2],
        ]);

        $expectedPrefix = 'TKT-' . now()->format('Ym') . '-0001';

        $this->assertDatabaseHas('tiket_bantuan', [
            'nomor'   => $expectedPrefix,
            'user_id' => $this->member->id,
            'subjek'  => 'Kendala deposit MT4 tidak terdeteksi',
            'status'  => TiketBantuan::STATUS_OPEN,
        ]);

        $tiket = TiketBantuan::where('nomor', $expectedPrefix)->firstOrFail();

        $response->assertRedirect(route('member.tickets.show', $tiket->id));

        $this->assertDatabaseHas('tiket_bantuan_balasan', [
            'tiket_id' => $tiket->id,
            'user_id'  => $this->member->id,
            'pengirim' => 'member',
        ]);

        $this->assertEquals(2, $tiket->balasan->first()->lampiran()->count());

        Notification::assertSentTo(
            $this->admin,
            TiketBaruNotification::class,
            fn ($n) => $n->tiket->id === $tiket->id
        );

        // Tiket kedua pada bulan yang sama harus mendapat nomor berurutan -0002
        $this->actingAs($this->member)->post(route('member.tickets.store'), [
            'subjek' => 'Pertanyaan kedua',
            'pesan'  => 'Pertanyaan lanjutan mengenai sistem.',
        ]);

        $expectedSecond = 'TKT-' . now()->format('Ym') . '-0002';
        $this->assertDatabaseHas('tiket_bantuan', [
            'nomor' => $expectedSecond,
        ]);
    }

    public function test_member_cannot_view_or_reply_other_members_ticket(): void
    {
        $tiket = TiketBantuan::create([
            'nomor'         => 'TKT-202610-0001',
            'user_id'       => $this->member->id,
            'subjek'        => 'Tiket Rahasia Member A',
            'status'        => TiketBantuan::STATUS_OPEN,
            'last_reply_at' => now(),
        ]);

        // Member B mengakses tiket Member A -> 404 Not Found
        $response = $this->actingAs($this->otherMember)->get(route('member.tickets.show', $tiket->id));
        $response->assertNotFound();

        // Member B membalas tiket Member A -> 404 Not Found
        $response = $this->actingAs($this->otherMember)->post(route('member.tickets.reply', $tiket->id), [
            'pesan' => 'Mencoba membalas tiket orang lain.',
        ]);
        $response->assertNotFound();
    }

    public function test_admin_and_member_reply_flow_and_status_transitions(): void
    {
        Notification::fake();

        $tiket = TiketBantuan::create([
            'nomor'         => 'TKT-202610-0001',
            'user_id'       => $this->member->id,
            'subjek'        => 'Kendala Login',
            'status'        => TiketBantuan::STATUS_OPEN,
            'last_reply_at' => now()->subHour(),
            'last_reply_by' => 'member',
        ]);

        // 1. Admin membalas tiket -> status menjadi Answered
        $response = $this->actingAs($this->admin)->post(route('admin.tickets.reply', $tiket->id), [
            'pesan' => 'Halo, silakan bersihkan cache browser atau gunakan fitur lupa kata sandi.',
        ]);
        $response->assertSessionHas('success');

        $tiket->refresh();
        $this->assertEquals(TiketBantuan::STATUS_ANSWERED, $tiket->status);
        $this->assertEquals('admin', $tiket->last_reply_by);

        Notification::assertSentTo(
            $this->member,
            TiketBalasanAdminNotification::class
        );

        // 2. Member membalas kembali -> status kembali menjadi Open
        $response = $this->actingAs($this->member)->post(route('member.tickets.reply', $tiket->id), [
            'pesan' => 'Sudah dicoba namun masih muncul error 401.',
        ]);
        $response->assertSessionHas('success');

        $tiket->refresh();
        $this->assertEquals(TiketBantuan::STATUS_OPEN, $tiket->status);
        $this->assertEquals('member', $tiket->last_reply_by);

        Notification::assertSentTo(
            $this->admin,
            TiketBalasanMemberNotification::class
        );
    }

    public function test_member_cannot_reply_to_resolved_or_closed_ticket(): void
    {
        $tiket = TiketBantuan::create([
            'nomor'         => 'TKT-202610-0001',
            'user_id'       => $this->member->id,
            'subjek'        => 'Kendala Selesai',
            'status'        => TiketBantuan::STATUS_RESOLVED,
            'last_reply_at' => now(),
            'resolved_at'   => now(),
        ]);

        $response = $this->actingAs($this->member)->post(route('member.tickets.reply', $tiket->id), [
            'pesan' => 'Mencoba membalas tiket yang sudah selesai.',
        ]);
        $response->assertSessionHas('error');

        $tiket->update(['status' => TiketBantuan::STATUS_CLOSED]);

        $response = $this->actingAs($this->member)->post(route('member.tickets.reply', $tiket->id), [
            'pesan' => 'Mencoba membalas tiket yang sudah ditutup.',
        ]);
        $response->assertSessionHas('error');
    }

    public function test_member_can_resolve_and_reopen_ticket(): void
    {
        $tiket = TiketBantuan::create([
            'nomor'         => 'TKT-202610-0001',
            'user_id'       => $this->member->id,
            'subjek'        => 'Kendala KYC',
            'status'        => TiketBantuan::STATUS_OPEN,
            'last_reply_at' => now(),
        ]);

        // Member menandai selesai
        $response = $this->actingAs($this->member)->post(route('member.tickets.resolve', $tiket->id));
        $response->assertSessionHas('success');

        $tiket->refresh();
        $this->assertEquals(TiketBantuan::STATUS_RESOLVED, $tiket->status);
        $this->assertNotNull($tiket->resolved_at);

        // Member membuka kembali tiket
        $response = $this->actingAs($this->member)->post(route('member.tickets.reopen', $tiket->id));
        $response->assertSessionHas('success');

        $tiket->refresh();
        $this->assertEquals(TiketBantuan::STATUS_OPEN, $tiket->status);
        $this->assertNull($tiket->resolved_at);
    }

    public function test_admin_can_update_status_manually(): void
    {
        Notification::fake();

        $tiket = TiketBantuan::create([
            'nomor'         => 'TKT-202610-0001',
            'user_id'       => $this->member->id,
            'subjek'        => 'Kendala Bug',
            'status'        => TiketBantuan::STATUS_OPEN,
            'last_reply_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.tickets.status', $tiket->id), [
            'status'  => TiketBantuan::STATUS_IN_PROGRESS,
            'catatan' => 'Sedang dicek oleh tim developer.',
        ]);
        $response->assertSessionHas('success');

        $tiket->refresh();
        $this->assertEquals(TiketBantuan::STATUS_IN_PROGRESS, $tiket->status);

        Notification::assertSentTo(
            $this->member,
            TiketStatusDiubahNotification::class,
            fn ($n) => $n->statusBaru === TiketBantuan::STATUS_IN_PROGRESS
        );
    }

    public function test_non_admin_cannot_access_admin_ticket_routes(): void
    {
        $response = $this->actingAs($this->member)->get(route('admin.tickets.index'));
        $response->assertForbidden();
    }

    public function test_ticket_attachment_streaming_authorization(): void
    {
        $tiket = TiketBantuan::create([
            'nomor'         => 'TKT-202610-0001',
            'user_id'       => $this->member->id,
            'subjek'        => 'Tes Lampiran',
            'status'        => TiketBantuan::STATUS_OPEN,
            'last_reply_at' => now(),
        ]);

        $balasan = TiketBantuanBalasan::create([
            'tiket_id' => $tiket->id,
            'user_id'  => $this->member->id,
            'pengirim' => 'member',
            'pesan'    => 'Ini lampiran saya.',
        ]);

        // Simpan file tiruan di fake disk local
        $filePath = "tiket_bantuan/{$tiket->id}/test_image.jpg";
        Storage::disk('local')->put($filePath, 'fake image content');

        $lampiran = TiketBantuanLampiran::create([
            'balasan_id' => $balasan->id,
            'file_path'  => $filePath,
            'file_name'  => 'test_image.jpg',
            'file_size'  => 1024,
            'mime_type'  => 'image/jpeg',
        ]);

        // 1. Pemilik tiket dapat mengakses
        $response = $this->actingAs($this->member)->get(route('documents.ticket_attachment', $lampiran->id));
        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        // 2. Admin dapat mengakses
        $response = $this->actingAs($this->admin)->get(route('documents.ticket_attachment', $lampiran->id));
        $response->assertOk();

        // 3. Member lain dilarang mengakses -> 403
        $response = $this->actingAs($this->otherMember)->get(route('documents.ticket_attachment', $lampiran->id));
        $response->assertForbidden();

        // 4. Guest dilarang mengakses -> 302 redirect login
        Auth::logout();
        $response = $this->get(route('documents.ticket_attachment', $lampiran->id));
        $response->assertRedirect(route('login'));
    }

    public function test_console_command_closes_resolved_tickets_after_7_days(): void
    {
        // Tiket 1: Resolved 8 hari yang lalu -> harus ditutup otomatis
        $oldResolved = TiketBantuan::create([
            'nomor'         => 'TKT-202610-0001',
            'user_id'       => $this->member->id,
            'subjek'        => 'Tiket Lama Selesai',
            'status'        => TiketBantuan::STATUS_RESOLVED,
            'last_reply_at' => now()->subDays(8),
            'resolved_at'   => now()->subDays(8),
        ]);

        // Tiket 2: Resolved 2 hari yang lalu -> harus tetap Resolved
        $recentResolved = TiketBantuan::create([
            'nomor'         => 'TKT-202610-0002',
            'user_id'       => $this->member->id,
            'subjek'        => 'Tiket Baru Selesai',
            'status'        => TiketBantuan::STATUS_RESOLVED,
            'last_reply_at' => now()->subDays(2),
            'resolved_at'   => now()->subDays(2),
        ]);

        $this->artisan('app:close-resolved-tickets', ['--days' => 7])
            ->assertSuccessful();

        $oldResolved->refresh();
        $recentResolved->refresh();

        $this->assertEquals(TiketBantuan::STATUS_CLOSED, $oldResolved->status);
        $this->assertNotNull($oldResolved->closed_at);

        $this->assertEquals(TiketBantuan::STATUS_RESOLVED, $recentResolved->status);
        $this->assertNull($recentResolved->closed_at);
    }

    public function test_admin_dashboard_shows_pending_tickets_queue(): void
    {
        TiketBantuan::create([
            'nomor'         => 'TKT-202610-0001',
            'user_id'       => $this->member->id,
            'subjek'        => 'Tiket Antrean 1',
            'status'        => TiketBantuan::STATUS_OPEN,
            'last_reply_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('pending_actions.tickets', 1)
        );
    }
}
