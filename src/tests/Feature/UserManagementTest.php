<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\RelasiReferral;
use App\Models\User;
use App\Models\UserAhliWaris;
use App\Models\UserDokumen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $investor;

    private User $affiliator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'      => 'administrator',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->affiliator = User::factory()->create([
            'role'          => 'affiliator',
            'status'        => 'active',
            'is_active'     => true,
            'referral_code' => 'AFF-TOP',
        ]);

        $this->investor = User::factory()->create([
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => true,
        ]);

        // Hubungkan investor di bawah affiliator
        RelasiReferral::create([
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $this->investor->id,
            'sumber'        => 'link_otomatis',
            'status'        => 'active',
            'joined_at'     => now(),
        ]);
    }

    public function test_guest_cannot_access_user_management(): void
    {
        $response = $this->get(route('admin.users.index'));
        $response->assertRedirect('/login');

        $responseDetail = $this->get(route('admin.users.show', $this->investor->id));
        $responseDetail->assertRedirect('/login');
    }

    public function test_investor_and_affiliator_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->investor)
            ->get(route('admin.users.index'));
        $response->assertStatus(403);

        $responseAff = $this->actingAs($this->affiliator)
            ->get(route('admin.users.index'));
        $responseAff->assertStatus(403);
    }

    public function test_admin_can_view_users_list_and_excludes_administrators(): void
    {
        $anotherAdmin = User::factory()->create([
            'name'      => 'Another Super Admin',
            'role'      => 'administrator',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 2) // Hanya investor dan affiliator (Opsi B)
            ->where('stats.total_users', 2)
            ->where('stats.total_investors', 1)
            ->where('stats.total_affiliators', 1)
            ->where('stats.total_active', 2)
            ->where('stats.total_inactive', 0)
            ->where('stats.inactive_investors', 0)
            ->where('stats.inactive_affiliators', 0)
        );
    }

    public function test_admin_can_filter_users_by_role_status_and_search(): void
    {
        // Filter by role: investor
        $responseRole = $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['role' => 'investor']));

        $responseRole->assertStatus(200);
        $responseRole->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.id', $this->investor->id)
        );

        // Filter by role: affiliator
        $responseAff = $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['role' => 'affiliator']));

        $responseAff->assertStatus(200);
        $responseAff->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.id', $this->affiliator->id)
        );

        // Filter by search name
        $responseSearch = $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['search' => $this->investor->name]));

        $responseSearch->assertStatus(200);
        $responseSearch->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.id', $this->investor->id)
        );
    }

    public function test_admin_can_view_complete_user_detail_page(): void
    {
        // Lengkapi data ahli waris, KTP, dan akun copytrade
        UserAhliWaris::create([
            'user_id'           => $this->investor->id,
            'nama'              => 'Budi Waris',
            'hubungan_keluarga' => 'Anak Kandung',
            'no_hp'             => '081234567890',
            'alamat'            => 'Jl. Warisan No. 10',
        ]);

        UserDokumen::create([
            'user_id'      => $this->investor->id,
            'tipe_dokumen' => 'ktp',
            'file_name'    => 'ktp_investor.jpg',
            'file_path'    => 'documents/ktp_investor.jpg',
            'file_size'    => 102400,
            'mime_type'    => 'image/jpeg',
            'status'       => 'approved',
            'verified_at'  => now(),
        ]);

        AkunCopytrade::create([
            'user_id'               => $this->investor->id,
            'tier'                  => 'tier_1',
            'broker'                => 'Exness',
            'tipe_akun'             => 'Standard',
            'nomor_akun_trading'    => '99887766',
            'server_broker'         => 'Exness-Real10',
            'master_password'       => 'secret123',
            'investor_password'     => 'invpass123',
            'modal_riil'            => 5000.00,
            'status'                => 'active',
            'surat_perjanjian_path' => 'documents/agreements/surat.pdf',
            'tanggal_mulai'         => now()->toDateString(),
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => $this->investor->name,
        ]);

        // Buat akun copytrade yang masih pending verifikasi admin
        AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_2',
            'broker'             => 'Exness',
            'tipe_akun'          => 'Standard',
            'nomor_akun_trading' => '11223344',
            'modal_riil'         => 1000.00,
            'tanggal_mulai'      => now()->toDateString(),
            'status'             => 'pending',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->investor->name,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.show', $this->investor->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Show')
            ->where('user.id', $this->investor->id)
            ->where('user.name', $this->investor->name)
            ->where('user.ahli_waris.nama', 'Budi Waris')
            ->where('user.ktp.file_name', 'ktp_investor.jpg')
            ->where('user.surat_perjanjian.preview_url', '/documents/copytrade/' . AkunCopytrade::first()->id)
            ->where('user.rekening_bank.nama_bank', 'BCA')
            ->where('user.rekening_bank.nomor_rekening', '1234567890')
            ->has('user.copytrades', 1)
            ->where('user.copytrades.0.nomor_akun_trading', '99887766')
            ->where('user.upline.name', $this->affiliator->name)
            ->missing('user.ip_address')
        );
    }

    public function test_admin_can_deactivate_and_reactivate_user(): void
    {
        // 1. Simulasikan sesi pengguna di tabel sessions
        DB::table('sessions')->insert([
            'id'            => 'sess-test-123',
            'user_id'       => $this->investor->id,
            'ip_address'    => '127.0.0.1',
            'user_agent'    => 'PHPUnit',
            'payload'       => base64_encode('test-payload'),
            'last_activity' => time(),
        ]);

        $this->assertDatabaseHas('sessions', ['user_id' => $this->investor->id]);

        // 2. Nonaktifkan user
        $responseDeactivate = $this->actingAs($this->admin)
            ->post(route('admin.users.toggle_status', $this->investor->id));

        $responseDeactivate->assertSessionHas('success');
        $this->assertFalse($this->investor->fresh()->is_active);

        // Pastikan sesi diputus
        $this->assertDatabaseMissing('sessions', ['user_id' => $this->investor->id]);

        // 3. Aktifkan kembali user
        $responseReactivate = $this->actingAs($this->admin)
            ->post(route('admin.users.toggle_status', $this->investor->id));

        $responseReactivate->assertSessionHas('success');
        $this->assertTrue($this->investor->fresh()->is_active);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $password = 'password123';
        $user = User::factory()->create([
            'email'     => 'inactive@example.com',
            'password'  => Hash::make($password),
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'login'    => 'inactive@example.com',
            'password' => $password,
        ]);

        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_deactivated_user_with_active_session_is_logged_out_by_middleware(): void
    {
        $user = User::factory()->create([
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => false, // akun dinonaktifkan
        ]);

        // User mencoba mengakses halaman yang dilindungi middleware
        $response = $this->actingAs($user)
            ->get(route('investor.dashboard'));

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_admin_cannot_deactivate_administrator(): void
    {
        $anotherAdmin = User::factory()->create([
            'role'      => 'administrator',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.toggle_status', $anotherAdmin->id));

        $response->assertSessionHas('error', 'Akun administrator tidak dapat dinonaktifkan.');
        $this->assertTrue($anotherAdmin->fresh()->is_active);
    }

    public function test_admin_can_view_affiliator_detail_with_downlines_list(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.show', $this->affiliator->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Show')
            ->where('user.id', $this->affiliator->id)
            ->where('user.role', 'affiliator')
            ->where('user.referral_code', 'AFF-TOP')
            ->has('user.downlines', 1)
            ->where('user.downlines.0.id', $this->investor->id)
            ->where('user.downlines.0.name', $this->investor->name)
        );
    }

    public function test_admin_can_filter_by_active_and_inactive_status(): void
    {
        // Nonaktifkan investor
        $this->investor->update(['is_active' => false]);

        // Filter active
        $responseActive = $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['is_active' => 'active']));

        $responseActive->assertStatus(200);
        $responseActive->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.id', $this->affiliator->id)
        );

        // Filter inactive
        $responseInactive = $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['is_active' => 'inactive']));

        $responseInactive->assertStatus(200);
        $responseInactive->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.id', $this->investor->id)
        );
    }

    public function test_admin_users_list_calculates_active_and_pending_copytrades_accurately(): void
    {
        AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'Exness',
            'tipe_akun'          => 'Standard',
            'nomor_akun_trading' => '11112222',
            'server_broker'      => 'Exness-Real1',
            'modal_riil'         => 1000.00,
            'status'             => 'active',
            'tanggal_mulai'      => now()->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->investor->name,
        ]);

        AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'Exness',
            'tipe_akun'          => 'Standard',
            'nomor_akun_trading' => '33334444',
            'server_broker'      => 'Exness-Real1',
            'modal_riil'         => 2000.00,
            'status'             => 'pending',
            'tanggal_mulai'      => now()->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->investor->name,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data')
            ->where('users.data', fn ($users) => collect($users)->contains(function ($u) {
                return $u['id'] === $this->investor->id
                    && $u['active_copytrade_count'] === 1
                    && $u['pending_copytrade_count'] === 1
                    && $u['copytrade_count'] === 2;
            }))
        );
    }

    public function test_admin_can_sort_users_by_copytrade_count(): void
    {
        // Investor memiliki 2 akun aktif copytrade
        AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'Exness',
            'tipe_akun'          => 'Standard',
            'nomor_akun_trading' => '55556666',
            'server_broker'      => 'Exness-Real1',
            'modal_riil'         => 1000.00,
            'status'             => 'active',
            'tanggal_mulai'      => now()->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->investor->name,
        ]);

        AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'Exness',
            'tipe_akun'          => 'Standard',
            'nomor_akun_trading' => '77778888',
            'server_broker'      => 'Exness-Real1',
            'modal_riil'         => 1000.00,
            'status'             => 'active',
            'tanggal_mulai'      => now()->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->investor->name,
        ]);

        // Affiliator memiliki 0 akun aktif copytrade

        // Test Sort DESC: Investor (2 akun) harus paling atas
        $responseDesc = $this->actingAs($this->admin)
            ->get(route('admin.users.index', [
                'sort_by'        => 'copytrade_count',
                'sort_direction' => 'desc',
            ]));

        $responseDesc->assertStatus(200);
        $responseDesc->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->where('users.data.0.id', $this->investor->id)
            ->where('users.data.1.id', $this->affiliator->id)
        );

        // Test Sort ASC: Affiliator (0 akun) harus paling atas
        $responseAsc = $this->actingAs($this->admin)
            ->get(route('admin.users.index', [
                'sort_by'        => 'copytrade_count',
                'sort_direction' => 'asc',
            ]));

        $responseAsc->assertStatus(200);
        $responseAsc->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->where('users.data.0.id', $this->affiliator->id)
            ->where('users.data.1.id', $this->investor->id)
        );
    }

    public function test_admin_users_stats_exclude_inactive_from_investor_and_affiliator_counts(): void
    {
        // Buat 1 affiliator nonaktif tambahan
        User::factory()->create([
            'role'      => 'affiliator',
            'status'    => 'active',
            'is_active' => false,
        ]);

        // Buat 2 investor nonaktif tambahan
        User::factory()->count(2)->create([
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => false,
        ]);

        // Total awal: 1 active investor, 1 active affiliator
        // Sekarang ada tambahan: 1 inactive affiliator, 2 inactive investor
        // Total users = 5 (2 aktif + 3 nonaktif)
        // Total investors (hanya aktif) = 1
        // Total affiliators (hanya aktif) = 1
        // Total inactive = 3 (1 affiliator + 2 investor)
        // Inactive affiliators = 1
        // Inactive investors = 2

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->where('stats.total_users', 5)
            ->where('stats.total_investors', 1)
            ->where('stats.total_affiliators', 1)
            ->where('stats.total_active', 2)
            ->where('stats.total_inactive', 3)
            ->where('stats.inactive_affiliators', 1)
            ->where('stats.inactive_investors', 2)
        );
    }
}
