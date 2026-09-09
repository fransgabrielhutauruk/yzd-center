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

    public function test_registration_fails_if_ahli_waris_phone_is_same_as_investor_phone(): void
    {
        Storage::fake('local');
        $invitation = $this->createValidInvitation();

        $payload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'Investor Baru',
            'username'              => 'investorbaru',
            'email'                 => 'investorbaru@example.com',
            'phone'                 => '081234567890',
            'nik'                   => '3201123456789012',
            'alamat'                => 'Jl. Thamrin No. 10',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'ahli_waris_nama'       => 'Istri Investor',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081234567890', // SAMA DENGAN INVESTOR!
            'ahli_waris_alamat'     => 'Jl. Thamrin No. 10',
            'dokumen_ktp'           => UploadedFile::fake()->image('ktp.jpg', 800, 600),
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ];

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors(['ahli_waris_phone']);
        $this->assertGuest();
    }

    public function test_registration_fails_if_ktp_is_not_an_image(): void
    {
        Storage::fake('local');
        $invitation = $this->createValidInvitation();

        $payload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'Investor Baru',
            'username'              => 'investorbaru',
            'email'                 => 'investorbaru@example.com',
            'phone'                 => '081234567890',
            'nik'                   => '3201123456789012',
            'alamat'                => 'Jl. Thamrin No. 10',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'ahli_waris_nama'       => 'Istri Investor',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081299998888',
            'ahli_waris_alamat'     => 'Jl. Thamrin No. 10',
            'dokumen_ktp'           => UploadedFile::fake()->create('dokumen.pdf', 300, 'application/pdf'), // PDF TIDAK BOLEH UNTUK KTP
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ];

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors(['dokumen_ktp']);
        $this->assertGuest();
    }

    public function test_registration_fails_if_terms_not_accepted(): void
    {
        Storage::fake('local');
        $invitation = $this->createValidInvitation();

        $payload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'Investor Baru',
            'username'              => 'investorbaru',
            'email'                 => 'investorbaru@example.com',
            'phone'                 => '081234567890',
            'nik'                   => '3201123456789012',
            'alamat'                => 'Jl. Thamrin No. 10',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => false, // TIDAK CENTANG TERMS
            'ahli_waris_nama'       => 'Istri Investor',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081299998888',
            'ahli_waris_alamat'     => 'Jl. Thamrin No. 10',
            'dokumen_ktp'           => UploadedFile::fake()->image('ktp.jpg', 800, 600),
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ];

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors(['terms']);
        $this->assertGuest();
    }

    public function test_registration_fails_if_file_exceeds_10mb(): void
    {
        Storage::fake('local');
        $invitation = $this->createValidInvitation();

        $payload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'Investor Baru',
            'username'              => 'investorbaru',
            'email'                 => 'investorbaru@example.com',
            'phone'                 => '081234567890',
            'nik'                   => '3201123456789012',
            'alamat'                => 'Jl. Thamrin No. 10',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'ahli_waris_nama'       => 'Istri Investor',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081299998888',
            'ahli_waris_alamat'     => 'Jl. Thamrin No. 10',
            'dokumen_ktp'           => UploadedFile::fake()->create('ktp.jpg', 12000, 'image/jpeg'), // > 10 MB
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ];

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors(['dokumen_ktp']);
        $this->assertGuest();
    }
}
