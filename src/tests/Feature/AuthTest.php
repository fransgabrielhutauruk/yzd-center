<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
        ];

        $response = $this->post('/register', $payload);

        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertEquals('budisantoso99', $user->username);
        $this->assertEquals('investor', $user->role);
        $this->assertNotNull($user->terms_agreed_at);
        $this->assertEquals('v1.0', $user->terms_version);

        $this->assertEquals(1, $invitation->fresh()->total_terpakai);
        $response->assertRedirect(route('investor.dashboard'));
    }

    public function test_new_affiliator_registration_auto_generates_referral_code(): void
    {
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
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
        ];

        $response = $this->post('/register', $payload);

        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertEquals('affiliator', $user->role);
        $this->assertNotNull($user->referral_code);
        $this->assertStringStartsWith('RUDIMITRA', $user->referral_code);
        $response->assertRedirect(route('investor.dashboard'));
    }

    public function test_user_cannot_register_with_expired_token(): void
    {
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
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
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
        $response->assertInertia(fn ($page) => $page->component('Admin/Invitations/Index'));
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
}
