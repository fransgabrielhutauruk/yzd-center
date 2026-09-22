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
            'tier_investasi'        => 'tier_1',
            'nomor_akun_trading'    => '66252077',
            'estimasi_modal'        => 1000,
            'tanggal_mulai_trading' => '2026-01-15',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Budi Santoso',
            'dokumen_ktp'           => UploadedFile::fake()->image('ktp.jpg', 800, 600),
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
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
        $this->assertCount(2, $user->dokumen);

        // Validasi AkunCopytrade Tier 1
        $this->assertNotNull($user->akunCopytrade);
        $this->assertEquals('tier_1', $user->akunCopytrade->tier);
        $this->assertEquals('HFM', $user->akunCopytrade->broker);
        $this->assertEquals('cent', $user->akunCopytrade->tipe_akun);
        $this->assertEquals('66252077', $user->akunCopytrade->nomor_akun_trading);
        $this->assertEquals('1000.00', $user->akunCopytrade->estimasi_modal);
        $this->assertEquals('2031-12-31', $user->akunCopytrade->tanggal_selesai->format('Y-m-d'));
        $this->assertEquals('pending', $user->akunCopytrade->status);

        $this->assertEquals(1, $invitation->fresh()->total_terpakai);
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('registration_success');
    }

    public function test_new_user_can_register_tier_3_with_encrypted_credentials(): void
    {
        Storage::fake('local');

        $invitation = Invitation::create([
            'token'          => 'tokenreginvestortier3test123456',
            'label'          => 'Investor VIP Batch',
            'target_role'    => 'investor',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->addDay(),
        ]);

        $payload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'Hendro VIP',
            'username'              => 'hendrovip',
            'email'                 => 'hendro@example.com',
            'phone'                 => '081211112222',
            'nik'                   => '3201123456789099',
            'alamat'                => 'Jl. Menteng No. 1, Jakarta Pusat',
            'tempat_lahir'          => 'Jakarta',
            'tanggal_lahir'         => '1980-01-01',
            'pekerjaan'             => 'Direktur Utama',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'ahli_waris_nama'       => 'Ratna Hendro',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081233334444',
            'ahli_waris_alamat'     => 'Jl. Menteng No. 1, Jakarta Pusat',
            'tier_investasi'        => 'tier_3',
            'nomor_akun_trading'    => '88889999',
            'estimasi_modal'        => 500000,
            'tanggal_mulai_trading' => '2026-03-01',
            'nama_bank'             => 'Mandiri',
            'nomor_rekening'        => '9876543210',
            'atas_nama_rekening'    => 'Hendro VIP',
            'server_broker'         => 'XMGlobal-Real 50',
            'master_password'       => 'masterSecretPass99',
            'investor_password'     => 'investorSecretPass99',
            'dokumen_ktp'           => UploadedFile::fake()->image('ktp.jpg', 800, 600),
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ];

        $response = $this->post('/register', $payload);

        $this->assertGuest();
        $user = User::where('username', 'hendrovip')->firstOrFail();

        $akun = $user->akunCopytrade;
        $this->assertNotNull($akun);
        $this->assertEquals('tier_3', $akun->tier);
        $this->assertEquals('XM', $akun->broker);
        $this->assertEquals('private_vps', $akun->tipe_akun);
        $this->assertEquals('XMGlobal-Real 50', $akun->server_broker);

        // Dekripsi via getter model
        $this->assertEquals('masterSecretPass99', $akun->master_password);
        $this->assertEquals('investorSecretPass99', $akun->investor_password);

        // Verifikasi di DB tersimpan terenkripsi (bukan plaintext)
        $rawRow = \Illuminate\Support\Facades\DB::table('akun_copytrade')->where('user_id', $user->id)->first();
        $this->assertNotEquals('masterSecretPass99', $rawRow->master_password);
        $this->assertNotEquals('investorSecretPass99', $rawRow->investor_password);

        // Durasi 5 tahun untuk Tier 3
        $this->assertEquals('2031-03-01', $akun->tanggal_selesai->format('Y-m-d'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('registration_success');
    }

    public function test_registration_fails_if_modal_out_of_tier_range(): void
    {
        Storage::fake('local');

        $invitation = Invitation::create([
            'token'          => 'tokenregmodalrangetest12345678',
            'label'          => 'Modal Range Test',
            'target_role'    => 'investor',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->addDay(),
        ]);

        $basePayload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'Modal Tester',
            'username'              => 'modaltester',
            'email'                 => 'modal@example.com',
            'phone'                 => '081234567899',
            'nik'                   => '3201123456789088',
            'alamat'                => 'Jl. Test No. 1',
            'tempat_lahir'          => 'Bandung',
            'tanggal_lahir'         => '1992-02-02',
            'pekerjaan'             => 'PNS',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'ahli_waris_nama'       => 'Waris Tester',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081299990000',
            'ahli_waris_alamat'     => 'Jl. Test No. 1',
            'nomor_akun_trading'    => '11223344',
            'tanggal_mulai_trading' => '2026-01-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Modal Tester',
            'dokumen_ktp'           => UploadedFile::fake()->image('ktp.jpg', 800, 600),
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ];

        // Tier 1 modal $6000 (maks < 5000)
        $payloadTier1 = array_merge($basePayload, [
            'tier_investasi' => 'tier_1',
            'estimasi_modal' => 6000,
        ]);
        $response = $this->post('/register', $payloadTier1);
        $response->assertSessionHasErrors(['estimasi_modal']);

        // Tier 2 modal $2000 (harus $5,000 - $300,000)
        $payloadTier2 = array_merge($basePayload, [
            'tier_investasi' => 'tier_2',
            'estimasi_modal' => 2000,
        ]);
        $response2 = $this->post('/register', $payloadTier2);
        $response2->assertSessionHasErrors(['estimasi_modal']);

        // Tier 3 modal $50000 (harus >= $300,000)
        $payloadTier3 = array_merge($basePayload, [
            'tier_investasi'    => 'tier_3',
            'estimasi_modal'    => 50000,
            'server_broker'     => 'XMGlobal-Real 50',
            'master_password'   => 'secret123',
            'investor_password' => 'secret123',
        ]);
        $response3 = $this->post('/register', $payloadTier3);
        $response3->assertSessionHasErrors(['estimasi_modal']);
    }

    public function test_registration_tier_3_requires_broker_credentials(): void
    {
        Storage::fake('local');

        $invitation = Invitation::create([
            'token'          => 'tokenregcredtest12345678901234',
            'label'          => 'Credential Test',
            'target_role'    => 'investor',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->addDay(),
        ]);

        $payload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'VIP Missing Cred',
            'username'              => 'vipmissing',
            'email'                 => 'vipmissing@example.com',
            'phone'                 => '081234567811',
            'nik'                   => '3201123456789077',
            'alamat'                => 'Jl. Test No. 2',
            'tempat_lahir'          => 'Medan',
            'tanggal_lahir'         => '1985-03-03',
            'pekerjaan'             => 'Wiraswasta',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'ahli_waris_nama'       => 'Waris VIP',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081299991111',
            'ahli_waris_alamat'     => 'Jl. Test No. 2',
            'tier_investasi'        => 'tier_3',
            'nomor_akun_trading'    => '99887766',
            'estimasi_modal'        => 350000,
            'tanggal_mulai_trading' => '2026-01-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'VIP Missing Cred',
            // Field broker sengaja dikosongkan
            'server_broker'      => null,
            'master_password'    => null,
            'investor_password'  => null,
            'dokumen_ktp'        => UploadedFile::fake()->image('ktp.jpg', 800, 600),
            'dokumen_perjanjian' => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ];

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors(['server_broker', 'master_password', 'investor_password']);
    }

    public function test_guest_can_download_agreement_template_for_all_tiers(): void
    {
        foreach (['tier_1', 'tier_2', 'tier_3'] as $tier) {
            $response = $this->get("/register/template-perjanjian/{$tier}");
            $response->assertStatus(200);
            $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        }

        // Tier tidak valid harus 404
        $invalidResponse = $this->get('/register/template-perjanjian/tier_invalid');
        $invalidResponse->assertStatus(404);
    }

    public function test_guest_can_generate_populated_agreement_template_tier_1(): void
    {
        $payload = [
            'name'                  => 'Budi Santoso',
            'nik'                   => '3201123456789012',
            'tempat_lahir'          => 'Surabaya',
            'tanggal_lahir'         => '1988-08-17',
            'pekerjaan'             => 'Wiraswasta',
            'alamat'                => 'Jl. Pahlawan No. 10, Surabaya',
            'phone'                 => '081234567890',
            'email'                 => 'budi99@example.com',
            'tier_investasi'        => 'tier_1',
            'nomor_akun_trading'    => '66252077',
            'estimasi_modal'        => 1000,
            'tanggal_mulai_trading' => '2026-01-15',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Budi Santoso',
            'ahli_waris_nama'       => 'Siti Rahma',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081299998888',
        ];

        $response = $this->post('/register/template-perjanjian/tier_1', $payload);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        // Simpan binary response dan periksa isi XML di dalam docx
        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($filePath));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('Budi Santoso', $xml);
        $this->assertStringContainsString('3201123456789012', $xml);
        $this->assertStringContainsString('Surabaya, 17 Agustus 1988', $xml);
        $this->assertStringContainsString('Jl. Pahlawan No. 10, Surabaya', $xml);
        $this->assertStringContainsString('081234567890', $xml);
        $this->assertStringContainsString('budi99@example.com', $xml);
        $this->assertStringContainsString('Wiraswasta', $xml);
        $this->assertStringContainsString('15 Januari 2026', $xml);
        $this->assertStringContainsString('66252077', $xml);
        $this->assertStringContainsString('$1,000.00', $xml);
        $this->assertStringContainsString('BCA - 1234567890 a.n. Budi Santoso', $xml);
        $this->assertStringContainsString('Siti Rahma', $xml);
        $this->assertStringContainsString('Istri', $xml);
        $this->assertStringContainsString('081299998888', $xml);
        $this->assertStringContainsString('........................,', $xml);
    }

    public function test_guest_can_generate_populated_agreement_template_tier_3_with_credentials(): void
    {
        $payload = [
            'name'                  => 'Hendro VIP',
            'nik'                   => '3201123456789099',
            'tempat_lahir'          => 'Jakarta',
            'tanggal_lahir'         => '1980-01-01',
            'pekerjaan'             => 'Direktur Utama',
            'alamat'                => 'Jl. Menteng No. 1, Jakarta Pusat',
            'phone'                 => '081211112222',
            'email'                 => 'hendro@example.com',
            'tier_investasi'        => 'tier_3',
            'nomor_akun_trading'    => '88889999',
            'estimasi_modal'        => 500000,
            'tanggal_mulai_trading' => '2026-03-01',
            'nama_bank'             => 'Mandiri',
            'nomor_rekening'        => '9876543210',
            'atas_nama_rekening'    => 'Hendro VIP',
            'server_broker'         => 'XMGlobal-Real 50',
            'master_password'       => 'MasterSecret99',
            'investor_password'     => 'InvestorSecret99',
            'ahli_waris_nama'       => 'Ratna Hendro',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081233334444',
        ];

        $response = $this->post('/register/template-perjanjian/tier_3', $payload);

        $response->assertStatus(200);

        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($filePath));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('Hendro VIP', $xml);
        $this->assertStringContainsString('XMGlobal-Real 50', $xml);
        $this->assertStringContainsString('MasterSecret99', $xml);
        $this->assertStringContainsString('InvestorSecret99', $xml);
        $this->assertStringContainsString('$500,000.00', $xml);
        $this->assertStringContainsString('01 Maret 2031', $xml); // +5 tahun
    }

    public function test_guest_can_download_template_with_partial_data_falling_back_to_placeholders(): void
    {
        // Kirimkan hanya nama dan NIK, sisanya kosong
        $payload = [
            'name' => 'Calon Investor',
            'nik'  => '3201111122223333',
        ];

        $response = $this->post('/register/template-perjanjian/tier_1', $payload);

        $response->assertStatus(200);

        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($filePath));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        // Yang diisi harus muncul
        $this->assertStringContainsString('Calon Investor', $xml);
        $this->assertStringContainsString('3201111122223333', $xml);

        // Yang kosong harus tetap berupa placeholder titik-titik
        $this->assertStringContainsString('[ ..................................................... ]', $xml);
        $this->assertStringContainsString('[ DD / MM / YYYY ]', $xml);
        $this->assertStringContainsString('[ Nama Bank - Nomor Rekening ]', $xml);
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
}
