<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_email(): void
    {
        $user = User::factory()->create([
            'email'    => 'investor@example.com',
            'password' => bcrypt('password123'),
            'role'     => 'investor',
        ]);

        $response = $this->post('/login', [
            'login'    => 'investor@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('investor.dashboard'));
    }

    public function test_users_can_authenticate_using_username(): void
    {
        $user = User::factory()->create([
            'username' => 'investor_pro',
            'password' => bcrypt('password123'),
            'role'     => 'investor',
        ]);

        $response = $this->post('/login', [
            'login'    => 'investor_pro',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('investor.dashboard'));
    }

    public function test_admin_is_redirected_to_admin_dashboard_after_login(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'admin_super',
            'password' => bcrypt('adminpass123'),
        ]);

        $response = $this->post('/login', [
            'login'    => 'admin_super',
            'password' => 'adminpass123',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email'    => 'investor@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->post('/login', [
            'login'    => 'investor@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_registration_screen_renders_with_valid_invite_token(): void
    {
        $invitation = Invitation::create([
            'token'          => 'validtoken1234567890123456789012',
            'label'          => 'Batch Uji Coba',
            'target_role'    => 'investor',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->addDay(),
        ]);

        $response = $this->get('/register?invite=' . $invitation->token);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->where('invitation.valid', true)
            ->where('invitation.invitation.label', 'Batch Uji Coba')
        );
    }

    public function test_registration_screen_shows_invalid_status_when_no_token_provided(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->where('invitation.valid', false)
        );
    }

    public function test_registration_screen_shows_invalid_status_for_expired_token(): void
    {
        $invitation = Invitation::create([
            'token'          => 'expiredtoken12345678901234567890',
            'label'          => 'Undangan Kedaluwarsa',
            'target_role'    => 'investor',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->subDay(),
        ]);

        $response = $this->get('/register?invite=' . $invitation->token);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->where('invitation.valid', false)
        );
    }

    public function test_registration_screen_shows_invalid_status_for_quota_full_token(): void
    {
        $invitation = Invitation::create([
            'token'          => 'fulltoken1234567890123456789012',
            'label'          => 'Undangan Kuota Habis',
            'target_role'    => 'investor',
            'kuota_maksimal' => 2,
            'total_terpakai' => 2,
            'is_active'      => true,
            'expired_at'     => now()->addDays(2),
        ]);

        $response = $this->get('/register?invite=' . $invitation->token);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->where('invitation.valid', false)
        );
    }

    public function test_new_user_can_register_with_valid_investor_invitation(): void
    {
        Storage::fake('local');

        $invitation = Invitation::create([
            'token'          => 'tokenreginvestor123456789012345',
            'label'          => 'Investor Batch A',
            'target_role'    => 'investor',
            'kuota_maksimal' => 3,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->addDay(),
        ]);

        $payload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'Budi Santoso',
            'username'              => 'budisantoso99',
            'email'                 => 'budi99@example.com',
            'phone'                 => '081234567890',
            'nik'                   => '3201123456789012',
            'alamat'                => 'Jl. Pahlawan No. 10, Surabaya',
            'tempat_lahir'          => 'Surabaya',
            'tanggal_lahir'         => '1988-08-17',
            'pekerjaan'             => 'Wiraswasta',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'ahli_waris_nama'       => 'Siti Rahma',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081299998888',
            'ahli_waris_alamat'     => 'Jl. Pahlawan No. 10, Surabaya',
            'dokumen_ktp'           => UploadedFile::fake()->image('ktp.jpg', 800, 600),
        ];

        $response = $this->post('/register', $payload);

        $this->assertGuest();
        $user = User::where('username', 'budisantoso99')->firstOrFail();
        $this->assertEquals('budisantoso99', $user->username);
        $this->assertEquals('investor', $user->role);
        $this->assertEquals('pending', $user->status);
        $this->assertEquals('Surabaya', $user->tempat_lahir);
        $this->assertEquals('1988-08-17', $user->tanggal_lahir->format('Y-m-d'));
        $this->assertEquals('Wiraswasta', $user->pekerjaan);
        $this->assertNotNull($user->terms_agreed_at);

        $this->assertNotNull($user->ahliWaris);
        $this->assertEquals('Siti Rahma', $user->ahliWaris->nama);
        $this->assertEquals('Jl. Pahlawan No. 10, Surabaya', $user->ahliWaris->alamat);
        $this->assertCount(1, $user->dokumen);
        $this->assertEquals('ktp', $user->dokumen->first()->tipe_dokumen);

        // Akun copytrade belum ada saat registrasi awal (dibuat pada portal copytrade)
        $this->assertCount(0, $user->akunCopytrades);

        $this->assertEquals(1, $invitation->fresh()->total_terpakai);
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('registration_success');
    }

    public function test_new_affiliator_registration_auto_generates_referral_code(): void
    {
        Storage::fake('local');

        $invitation = Invitation::create([
            'token'          => 'tokenregaffiliator1234567890123',
            'label'          => 'Affiliator Batch A',
            'target_role'    => 'affiliator',
            'kuota_maksimal' => 2,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->addDays(5),
        ]);

        $payload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'Rudi Mitra',
            'username'              => 'rudimitra',
            'email'                 => 'rudi@example.com',
            'phone'                 => '081298765432',
            'nik'                   => '3202123456789012',
            'alamat'                => 'Jl. Sudirman No. 5, Jakarta',
            'tempat_lahir'          => 'Jakarta',
            'tanggal_lahir'         => '1995-12-12',
            'pekerjaan'             => 'Freelancer',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'ahli_waris_nama'       => 'Dewi Lestari',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081299997777',
            'ahli_waris_alamat'     => 'Jl. Sudirman No. 5, Jakarta',
            'tier_investasi'        => 'tier_2',
            'nomor_akun_trading'    => '77889900',
            'estimasi_modal'        => 10000,
            'tanggal_mulai_trading' => '2026-02-01',
            'nama_bank'             => 'Mandiri',
            'nomor_rekening'        => '5544332211',
            'atas_nama_rekening'    => 'Rudi Mitra',
            'dokumen_ktp'           => UploadedFile::fake()->image('ktp.jpg', 800, 600),
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ];

        $response = $this->post('/register', $payload);

        $this->assertGuest();
        $user = User::where('username', 'rudimitra')->firstOrFail();
        $this->assertEquals('affiliator', $user->role);
        $this->assertEquals('pending', $user->status);
        $this->assertNotNull($user->referral_code);
        $this->assertEquals(6, strlen($user->referral_code));
        $this->assertStringStartsWith('RUD', $user->referral_code);
        $this->assertMatchesRegularExpression('/^[A-Z]{3}[A-Z0-9]{3}$/', $user->referral_code);
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('registration_success');
    }

    public function test_user_cannot_register_with_expired_token(): void
    {
        Storage::fake('local');

        $invitation = Invitation::create([
            'token'          => 'expiredforreg123456789012345678',
            'label'          => 'Expired Batch',
            'target_role'    => 'investor',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->subMinute(),
        ]);

        $payload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'Gatot Subroto',
            'username'              => 'gatotsubroto',
            'email'                 => 'gatot@example.com',
            'phone'                 => '081234567899',
            'nik'                   => '3203123456789012',
            'alamat'                => 'Jl. Gatot Subroto No. 1',
            'tempat_lahir'          => 'Semarang',
            'tanggal_lahir'         => '1975-05-05',
            'pekerjaan'             => 'Pensiunan',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'ahli_waris_nama'       => 'Ani Subroto',
            'ahli_waris_hubungan'   => 'Anak',
            'ahli_waris_phone'      => '081299996666',
            'ahli_waris_alamat'     => 'Jl. Gatot Subroto No. 1',
            'tier_investasi'        => 'tier_1',
            'nomor_akun_trading'    => '12344321',
            'estimasi_modal'        => 500,
            'tanggal_mulai_trading' => '2026-01-01',
            'nama_bank'             => 'BRI',
            'nomor_rekening'        => '0987654321',
            'atas_nama_rekening'    => 'Gatot Subroto',
            'dokumen_ktp'           => UploadedFile::fake()->image('ktp.jpg', 800, 600),
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ];

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors(['invite_token']);
        $this->assertGuest();
    }

    public function test_admin_can_view_invitations_list(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/invitations');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Invitations/Index')
            ->has('invitations.data')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_invitations_with_search_and_pagination(): void
    {
        $admin = User::factory()->admin()->create();

        Invitation::create([
            'token'          => 'tokenbatchalpha123456789012345',
            'label'          => 'Batch Alpha Khusus',
            'target_role'    => 'investor',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
        ]);

        Invitation::create([
            'token'          => 'tokenbatchbeta1234567890123456',
            'label'          => 'Batch Beta Umum',
            'target_role'    => 'affiliator',
            'kuota_maksimal' => 10,
            'total_terpakai' => 0,
            'is_active'      => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/invitations?search=Alpha');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Invitations/Index')
            ->has('invitations.data', 1)
            ->where('invitations.data.0.label', 'Batch Alpha Khusus')
        );

        $roleResponse = $this->actingAs($admin)->get('/admin/invitations?role=affiliator');

        $roleResponse->assertStatus(200);
        $roleResponse->assertInertia(fn ($page) => $page
            ->component('Admin/Invitations/Index')
            ->has('invitations.data', 1)
            ->where('invitations.data.0.target_role', 'affiliator')
        );
    }

    public function test_non_admin_cannot_access_invitations_list(): void
    {
        $investor = User::factory()->create(['role' => 'investor']);

        $response = $this->actingAs($investor)->get('/admin/invitations');

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_invitations_list(): void
    {
        $response = $this->get('/admin/invitations');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_create_new_invitation(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/invitations', [
            'label'          => 'Undangan Seminar Investor',
            'target_role'    => 'investor',
            'kuota_maksimal' => 10,
            'expired_hours'  => 48,
        ]);

        $response->assertRedirect(route('admin.invitations.index'));
        $this->assertDatabaseHas('invitations', [
            'label'          => 'Undangan Seminar Investor',
            'target_role'    => 'investor',
            'kuota_maksimal' => 10,
        ]);
    }

    public function test_admin_can_delete_invitation(): void
    {
        $admin = User::factory()->admin()->create();
        $invitation = Invitation::create([
            'token'          => 'tokendelete12345678901234567890',
            'label'          => 'Undangan Dihapus',
            'target_role'    => 'investor',
            'kuota_maksimal' => 1,
            'total_terpakai' => 0,
            'is_active'      => true,
        ]);

        $response = $this->actingAs($admin)->delete('/admin/invitations/' . $invitation->invitation_id);

        $response->assertRedirect(route('admin.invitations.index'));
        $this->assertSoftDeleted('invitations', [
            'invitation_id' => $invitation->invitation_id,
        ]);
    }

    public function test_admin_can_view_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Dashboard'));
    }

    public function test_non_admin_cannot_view_admin_dashboard(): void
    {
        $investor = User::factory()->create(['role' => 'investor']);

        $response = $this->actingAs($investor)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_investor_can_view_investor_dashboard(): void
    {
        $investor = User::factory()->create(['role' => 'investor']);

        $response = $this->actingAs($investor)->get('/investor/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Investor/Dashboard'));
    }

    public function test_guest_cannot_view_investor_dashboard(): void
    {
        $response = $this->get('/investor/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_guest_accessing_root_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_admin_accessing_root_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_authenticated_investor_accessing_root_is_redirected_to_investor_dashboard(): void
    {
        $investor = User::factory()->create(['role' => 'investor']);

        $response = $this->actingAs($investor)->get('/');

        $response->assertRedirect(route('investor.dashboard'));
    }

    public function test_authenticated_admin_accessing_login_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/login');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_authenticated_investor_accessing_login_is_redirected_to_investor_dashboard(): void
    {
        $investor = User::factory()->create(['role' => 'investor']);

        $response = $this->actingAs($investor)->get('/login');

        $response->assertRedirect(route('investor.dashboard'));
    }

    public function test_unverified_investor_is_redirected_to_kyc_after_login(): void
    {
        $investor = User::factory()->pending()->create([
            'email'    => 'pending_investor@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'login'    => 'pending_investor@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($investor);
        $response->assertRedirect(route('investor.kyc'));
    }

    public function test_unverified_investor_accessing_dashboard_is_redirected_to_kyc(): void
    {
        $investor = User::factory()->pending()->create();

        $response = $this->actingAs($investor)->get('/investor/dashboard');

        $response->assertRedirect(route('investor.kyc'));
        $response->assertSessionHas('error');
    }

    public function test_unverified_investor_accessing_root_is_redirected_to_kyc(): void
    {
        $investor = User::factory()->pending()->create();

        $response = $this->actingAs($investor)->get('/');

        $response->assertRedirect(route('investor.kyc'));
    }

    public function test_unverified_investor_can_access_kyc_page(): void
    {
        $investor = User::factory()->pending()->create();

        $response = $this->actingAs($investor)->get('/investor/kyc');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Investor/Kyc/Index'));
    }

    public function test_login_rate_limiting_locks_out_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'email'    => 'throttle_user@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        // 4 failed attempts should yield standard credential error
        for ($i = 1; $i <= 4; $i++) {
            $response = $this->post('/login', [
                'login'    => 'throttle_user@example.com',
                'password' => 'wrong-password',
            ]);
            $response->assertSessionHasErrors([
                'login' => 'Username/email atau kata sandi tidak cocok.',
            ]);
        }

        // 5th failed attempt should immediately trigger rate limiter
        $fifthResponse = $this->post('/login', [
            'login'    => 'throttle_user@example.com',
            'password' => 'wrong-password',
        ]);
        $fifthResponse->assertSessionHasErrors('login');
        $errorMessage = session('errors')->first('login');
        $this->assertStringContainsString('Terlalu banyak percobaan masuk', $errorMessage);

        // 6th attempt should be blocked before reaching authentication
        $sixthResponse = $this->post('/login', [
            'login'    => 'throttle_user@example.com',
            'password' => 'correct-password',
        ]);
        $sixthResponse->assertSessionHasErrors('login');
        $errorMessage6 = session('errors')->first('login');
        $this->assertStringContainsString('Terlalu banyak percobaan masuk', $errorMessage6);
    }

    public function test_login_screen_retains_lockout_seconds_when_refreshed(): void
    {
        User::factory()->create([
            'email'    => 'lockout_refresh@example.com',
            'password' => bcrypt('password123'),
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/login', [
                'login'    => 'lockout_refresh@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // Simulate page refresh via GET /login
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->where('lockoutSeconds', fn ($seconds) => $seconds > 0)
        );
    }

    public function test_successful_login_clears_rate_limiter(): void
    {
        $user = User::factory()->create([
            'email'    => 'clear_throttle@example.com',
            'password' => bcrypt('validpass123'),
        ]);

        // Failed attempts
        $this->post('/login', [
            'login'    => 'clear_throttle@example.com',
            'password' => 'wrong-password',
        ]);
        $this->post('/login', [
            'login'    => 'clear_throttle@example.com',
            'password' => 'wrong-password',
        ]);

        // Successful login
        $response = $this->post('/login', [
            'login'    => 'clear_throttle@example.com',
            'password' => 'validpass123',
        ]);
        $this->assertAuthenticatedAs($user);

        // Logout
        $this->post('/logout');

        // Check login screen
        $loginScreen = $this->get('/login');
        $loginScreen->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->where('lockoutSeconds', 0)
        );
    }
}
