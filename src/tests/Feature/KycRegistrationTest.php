<?php

namespace Tests\Feature;

use App\Models\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function createValidInvitation(): Invitation
    {
        return Invitation::create([
            'token'          => 'validinvitationtesttoken1234567',
            'label'          => 'Batch Uji Coba KYC',
            'target_role'    => 'investor',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
        ]);
    }

    private function defaultPayload(Invitation $invitation, array $overrides = []): array
    {
        return array_merge([
            'invite_token'          => $invitation->token,
            'name'                  => 'Investor Baru',
            'username'              => 'investorbaru',
            'email'                 => 'investorbaru@example.com',
            'phone'                 => '081234567890',
            'nik'                   => '3201123456789012',
            'alamat'                => 'Jl. Thamrin No. 10',
            'tempat_lahir'          => 'Jakarta',
            'tanggal_lahir'         => '1990-05-15',
            'pekerjaan'             => 'Karyawan Swasta',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'ahli_waris_nama'       => 'Istri Investor',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081299998888',
            'ahli_waris_alamat'     => 'Jl. Thamrin No. 10',
            'tier_investasi'        => 'tier_1',
            'nomor_akun_trading'    => '66252077',
            'estimasi_modal'        => 1000,
            'tanggal_mulai_trading' => '2026-01-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1234567890',
            'atas_nama_rekening'    => 'Investor Baru',
            'dokumen_ktp'           => UploadedFile::fake()->image('ktp.jpg', 800, 600),
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ], $overrides);
    }

    public function test_registration_fails_if_ahli_waris_phone_is_same_as_investor_phone(): void
    {
        Storage::fake('local');
        $invitation = $this->createValidInvitation();

        $payload = $this->defaultPayload($invitation, [
            'ahli_waris_phone' => '081234567890', // SAMA DENGAN INVESTOR!
        ]);

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors(['ahli_waris_phone']);
        $this->assertGuest();
    }

    public function test_registration_fails_if_ktp_is_not_an_image(): void
    {
        Storage::fake('local');
        $invitation = $this->createValidInvitation();

        $payload = $this->defaultPayload($invitation, [
            'dokumen_ktp' => UploadedFile::fake()->create('dokumen.pdf', 300, 'application/pdf'), // PDF TIDAK BOLEH UNTUK KTP
        ]);

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors(['dokumen_ktp']);
        $this->assertGuest();
    }

    public function test_registration_fails_if_terms_not_accepted(): void
    {
        Storage::fake('local');
        $invitation = $this->createValidInvitation();

        $payload = $this->defaultPayload($invitation, [
            'terms' => false,
        ]);

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors(['terms']);
        $this->assertGuest();
    }

    public function test_registration_fails_if_file_exceeds_10mb(): void
    {
        Storage::fake('local');
        $invitation = $this->createValidInvitation();

        $payload = $this->defaultPayload($invitation, [
            'dokumen_ktp' => UploadedFile::fake()->create('ktp.jpg', 12000, 'image/jpeg'), // > 10 MB
        ]);

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors(['dokumen_ktp']);
        $this->assertGuest();
    }

    public function test_registration_accepts_lainnya_as_ahli_waris_relationship(): void
    {
        Storage::fake('local');
        $invitation = $this->createValidInvitation();

        $payload = $this->defaultPayload($invitation, [
            'ahli_waris_nama'     => 'Wali Investor',
            'ahli_waris_hubungan' => 'Lainnya',
        ]);

        $response = $this->post('/register', $payload);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('registration_success');
        $this->assertGuest();

        $this->assertDatabaseHas('user_ahli_waris', [
            'nama'              => 'Wali Investor',
            'hubungan_keluarga' => 'Lainnya',
            'alamat'            => 'Jl. Thamrin No. 10',
        ]);
    }

    public function test_registration_fails_if_ahli_waris_relationship_is_invalid(): void
    {
        Storage::fake('local');
        $invitation = $this->createValidInvitation();

        $payload = $this->defaultPayload($invitation, [
            'ahli_waris_hubungan' => 'Tetangga', // Tidak ada di enum/in rule
        ]);

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors(['ahli_waris_hubungan']);
        $this->assertGuest();
    }

    public function test_registration_fails_if_ahli_waris_alamat_is_missing(): void
    {
        Storage::fake('local');
        $invitation = $this->createValidInvitation();

        $payload = $this->defaultPayload($invitation, [
            'ahli_waris_alamat' => '',
        ]);

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors(['ahli_waris_alamat']);
        $this->assertGuest();
    }
}
