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
            'role'          => 'member',
            'status'        => 'active',
            'is_active'     => true,
            'referral_code' => 'AFF-TOP',
        ]);

        $this->investor = User::factory()->create([
            'role'          => 'member',
            'status'        => 'active',
            'is_active'     => true,
            'referral_code' => 'INV-001',
        ]);

        // Berikan akun copytrade aktif pada $this->investor agar berlabel 'Investor'
        AkunCopytrade::create([
            'user_id'            => $this->investor->id,
            'tier'               => 'tier_1',
            'broker'             => 'Exness',
            'tipe_akun'          => 'Standard',
            'nomor_akun_trading' => '99887766',
            'server_broker'      => 'Exness-Real10',
            'master_password'    => 'secret123',
            'investor_password'  => 'invpass123',
            'modal_riil'         => 5000.00,
            'status'             => 'active',
            'tanggal_mulai'      => now()->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->investor->name,
        ]);

        // Hubungkan investor di bawah affiliator agar $this->affiliator berlabel 'Affiliator'
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

    public function test_regular_member_cannot_access_user_management(): void
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
        User::factory()->create([
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
            ->has('users.data', 2) // Hanya member (investor & affiliator)
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
        // Filter by derived role: investor
        $responseRole = $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['role' => 'investor']));

        $responseRole->assertStatus(200);
        $responseRole->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.id', $this->investor->id)
        );

        // Filter by derived role: affiliator
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

        // Tambah surat perjanjian path pada akun copytrade yang sudah dibuat di setUp
        $this->investor->akunCopytrades()->first()->update([
            'surat_perjanjian_path' => 'documents/agreements/surat.pdf',
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
            ->where('user.role', 'member')
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
            'role'      => 'member',
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
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('member.dashboard'));

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
            ->where('user.role', 'member')
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
        // Investor menambah 1 akun aktif copytrade lagi (total 2 akun)
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
        // Buat 1 member nonaktif yang punya downline
        $inactiveAff = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => false,
        ]);
        $subUser = User::factory()->create(['role' => 'member', 'is_active' => true]);
        RelasiReferral::create([
            'affiliator_id' => $inactiveAff->id,
            'downline_id'   => $subUser->id,
            'status'        => 'active',
            'sumber'        => 'link_otomatis',
            'joined_at'     => now(),
        ]);

        // Buat 2 member nonaktif dengan copytrade
        $inactiveInv1 = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => false,
        ]);
        AkunCopytrade::create([
            'user_id'            => $inactiveInv1->id,
            'tier'               => 'tier_1',
            'broker'             => 'Exness',
            'tipe_akun'          => 'Standard',
            'nomor_akun_trading' => '990011',
            'modal_riil'         => 1000,
            'status'             => 'active',
            'tanggal_mulai'      => now(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $inactiveInv1->name,
        ]);

        $inactiveInv2 = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => false,
        ]);
        AkunCopytrade::create([
            'user_id'            => $inactiveInv2->id,
            'tier'               => 'tier_1',
            'broker'             => 'Exness',
            'tipe_akun'          => 'Standard',
            'nomor_akun_trading' => '990022',
            'modal_riil'         => 1000,
            'status'             => 'active',
            'tanggal_mulai'      => now(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $inactiveInv2->name,
        ]);

        // Total awal: 1 active investor, 1 active affiliator
        // Tambahan: 1 subUser aktif (belum punya copytrade/downline), 1 inactiveAff, 2 inactiveInv
        // Total users = 2 awal + 1 subUser + 1 inactiveAff + 2 inactiveInv = 6
        // Total investors (hanya aktif) = 1
        // Total affiliators (hanya aktif) = 1
        // Total active = 3
        // Total inactive = 3
        // Inactive affiliators = 1
        // Inactive investors = 2

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->where('stats.total_users', 6)
            ->where('stats.total_investors', 1)
            ->where('stats.total_affiliators', 1)
            ->where('stats.total_active', 3)
            ->where('stats.total_inactive', 3)
            ->where('stats.inactive_affiliators', 1)
            ->where('stats.inactive_investors', 2)
        );
    }

    public function test_switch_role_route_is_removed_and_returns_404(): void
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/users/{$this->investor->id}/switch-role");

        $response->assertStatus(404);
    }

    public function test_user_derived_labels_are_dynamically_calculated(): void
    {
        // 1. Member polos tanpa copytrade dan downline
        $plainMember = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);
        $this->assertEquals(['Member'], $plainMember->getDerivedLabels());
        $this->assertFalse($plainMember->isInvestorLabel());
        $this->assertFalse($plainMember->isAffiliatorLabel());

        // 2. Member dengan akun copytrade aktif
        $this->assertTrue($this->investor->isInvestorLabel());
        $this->assertEquals(['Investor'], $this->investor->getDerivedLabels());

        // 3. Member dengan downline aktif
        $this->assertTrue($this->affiliator->isAffiliatorLabel());
        $this->assertEquals(['Affiliator'], $this->affiliator->getDerivedLabels());

        // 4. Member dengan keduanya (Investor & Affiliator)
        AkunCopytrade::create([
            'user_id'            => $this->affiliator->id,
            'tier'               => 'tier_1',
            'broker'             => 'Exness',
            'tipe_akun'          => 'Standard',
            'nomor_akun_trading' => '88889999',
            'modal_riil'         => 3000,
            'status'             => 'active',
            'tanggal_mulai'      => now(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => $this->affiliator->name,
        ]);
        $this->affiliator->refresh();
        $this->assertTrue($this->affiliator->isInvestorLabel());
        $this->assertTrue($this->affiliator->isAffiliatorLabel());
        $this->assertEquals(['Investor', 'Affiliator'], $this->affiliator->getDerivedLabels());
    }
}
