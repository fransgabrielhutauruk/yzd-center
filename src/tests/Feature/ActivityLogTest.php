<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $investor;

    private User $affiliator;

    private AuditLogService $auditLogService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name'      => 'Admin Utama',
            'username'  => 'adminyzd',
            'role'      => 'administrator',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->investor = User::factory()->create([
            'name'      => 'Investor User',
            'username'  => 'investor01',
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->affiliator = User::factory()->create([
            'name'      => 'Affiliator User',
            'username'  => 'affiliator01',
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->auditLogService = app(AuditLogService::class);
    }

    public function test_guest_cannot_access_activity_logs(): void
    {
        $response = $this->get('/admin/activity-logs');

        $response->assertRedirect('/login');
    }

    public function test_investor_and_affiliator_cannot_access_activity_logs(): void
    {
        $responseInvestor = $this->actingAs($this->investor)->get('/admin/activity-logs');
        $responseInvestor->assertForbidden();

        $responseAffiliator = $this->actingAs($this->affiliator)->get('/admin/activity-logs');
        $responseAffiliator->assertForbidden();
    }

    public function test_admin_can_access_activity_logs_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/activity-logs');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/ActivityLogs/Index')
            ->has('logs')
            ->has('stats')
            ->has('filters')
            ->has('kategoriOptions')
            ->has('aksiOptions')
            ->has('roleOptions')
            ->has('tingkatOptions')
        );
    }

    public function test_audit_log_records_automatic_actor_and_request_snapshot(): void
    {
        $this->actingAs($this->admin);

        $log = $this->auditLogService->record(
            kategori: 'keuangan',
            aksi: 'catat_saldo',
            deskripsi: 'Pencatatan saldo bulanan investor sebesar $500',
            subject: $this->investor,
            perubahan: [
                'sesudah' => ['saldo' => 500, 'bulan' => '2026-10'],
            ],
            tingkat: 'info',
        );

        $this->assertInstanceOf(LogAktivitas::class, $log);
        $this->assertDatabaseHas('log_aktivitas', [
            'id'            => $log->id,
            'user_id'       => $this->admin->id,
            'user_name'     => 'Admin Utama',
            'user_username' => 'adminyzd',
            'user_role'     => 'administrator',
            'kategori'      => 'keuangan',
            'aksi'          => 'catat_saldo',
            'tingkat'       => 'info',
            'subject_type'  => $this->investor->getMorphClass(),
            'subject_id'    => $this->investor->id,
        ]);
    }

    public function test_audit_log_masks_sensitive_data_recursively(): void
    {
        $this->actingAs($this->admin);

        $log = $this->auditLogService->record(
            kategori: 'copytrade',
            aksi: 'ajukan_copytrade',
            deskripsi: 'Pengajuan akun MT4 tier 3',
            subject: $this->investor,
            perubahan: [
                'sesudah' => [
                    'nomor_akun'        => '12345678',
                    'password'          => 'MySecretPassword123!',
                    'master_password'   => 'SuperAdminPass!',
                    'investor_password' => 'ReadonlyPass!',
                    'sub_kredensial'    => [
                        'token'   => 'abc-secret-token',
                        'vps_pin' => '9999',
                        'broker'  => 'Exness',
                    ],
                ],
            ],
            tingkat: 'info',
        );

        $this->assertDatabaseHas('log_aktivitas', [
            'id' => $log->id,
        ]);

        $freshLog = LogAktivitas::find($log->id);
        $sesudah = $freshLog->perubahan['sesudah'];

        $this->assertSame('12345678', $sesudah['nomor_akun']);
        $this->assertSame('[TERENKRIPSI/RAHASIA]', $sesudah['password']);
        $this->assertSame('[TERENKRIPSI/RAHASIA]', $sesudah['master_password']);
        $this->assertSame('[TERENKRIPSI/RAHASIA]', $sesudah['investor_password']);
        $this->assertSame('[TERENKRIPSI/RAHASIA]', $sesudah['sub_kredensial']['token']);
        $this->assertSame('[TERENKRIPSI/RAHASIA]', $sesudah['sub_kredensial']['vps_pin']);
        $this->assertSame('Exness', $sesudah['sub_kredensial']['broker']);
    }

    public function test_log_aktivitas_is_immutable_against_update_and_delete(): void
    {
        $log = $this->auditLogService->record(
            kategori: 'sistem',
            aksi: 'buat_undangan',
            deskripsi: 'Membuat tautan undangan mitra',
            tingkat: 'info',
            user: $this->admin,
        );

        $this->expectException(LogicException::class);
        $log->update(['deskripsi' => 'Data dirusak oleh penyerang']);
    }

    public function test_log_aktivitas_cannot_be_deleted(): void
    {
        $log = $this->auditLogService->record(
            kategori: 'sistem',
            aksi: 'buat_undangan',
            deskripsi: 'Membuat tautan undangan mitra',
            tingkat: 'info',
            user: $this->admin,
        );

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_admin_can_filter_logs_by_kategori_role_and_tingkat(): void
    {
        // Log 1: Keuangan, Critical
        $this->auditLogService->record(
            kategori: 'keuangan',
            aksi: 'hapus_saldo',
            deskripsi: 'Menghapus saldo investor',
            tingkat: 'critical',
            user: $this->admin,
        );

        // Log 2: Autentikasi, Warning
        $this->auditLogService->record(
            kategori: 'autentikasi',
            aksi: 'login_gagal',
            deskripsi: 'Percobaan login akun nonaktif',
            tingkat: 'warning',
            user: $this->investor,
        );

        // Filter Kategori Keuangan
        $responseKeuangan = $this->actingAs($this->admin)->get('/admin/activity-logs?kategori=keuangan');
        $responseKeuangan->assertOk();
        $responseKeuangan->assertInertia(fn ($page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.kategori', 'keuangan')
        );

        // Filter Tingkat Warning
        $responseWarning = $this->actingAs($this->admin)->get('/admin/activity-logs?tingkat=warning');
        $responseWarning->assertOk();
        $responseWarning->assertInertia(fn ($page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.tingkat', 'warning')
        );
    }

    public function test_admin_can_export_activity_logs_as_xlsx(): void
    {
        $this->auditLogService->record(
            kategori: 'komisi',
            aksi: 'proses_klaim_komisi',
            deskripsi: 'Pencairan komisi affiliator senilai Rp 1.500.000',
            tingkat: 'critical',
            user: $this->admin,
        );

        $response = $this->actingAs($this->admin)->get('/admin/activity-logs/export');

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('audit_log_yzd_', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        // Cek bahwa ekspor audit log itu sendiri tercatat
        $this->assertDatabaseHas('log_aktivitas', [
            'aksi'     => 'ekspor_audit_log',
            'kategori' => 'sistem',
            'user_id'  => $this->admin->id,
        ]);
    }

    public function test_cooldown_prevents_duplicate_logs_within_cooldown_window(): void
    {
        $this->actingAs($this->admin);

        // Panggilan pertama
        $log1 = $this->auditLogService->record(
            kategori: 'dokumen',
            aksi: 'lihat_dokumen',
            deskripsi: 'Melihat berkas KTP investor',
            subject: $this->investor,
            tingkat: 'info',
            withCooldown: true
        );

        // Panggilan kedua identik
        $log2 = $this->auditLogService->record(
            kategori: 'dokumen',
            aksi: 'lihat_dokumen',
            deskripsi: 'Melihat berkas KTP investor',
            subject: $this->investor,
            tingkat: 'info',
            withCooldown: true
        );

        $this->assertSame($log1->id, $log2->id);
        $this->assertSame(1, LogAktivitas::where('aksi', 'lihat_dokumen')->count());
    }

    public function test_non_admin_cannot_export_activity_logs(): void
    {
        $response = $this->actingAs($this->investor)->get('/admin/activity-logs/export');
        $response->assertForbidden();
    }

    public function test_audit_log_records_guest_actor_for_unauthenticated_actions(): void
    {
        $log = $this->auditLogService->record(
            kategori: 'autentikasi',
            aksi: 'login_gagal',
            deskripsi: 'Percobaan login dengan password salah untuk user: anonim',
            tingkat: 'warning',
            user: null
        );

        $this->assertNull($log->user_id);
        $this->assertSame('Tamu / Anonim', $log->user_name);
        $this->assertSame('guest', $log->user_role);
        $this->assertSame('login_gagal', $log->aksi);
        $this->assertSame('warning', $log->tingkat);
    }
}
