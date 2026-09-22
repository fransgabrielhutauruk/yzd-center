<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\KlaimReferral;
use App\Models\RelasiReferral;
use App\Models\User;
use App\Notifications\NewDownlineRegisteredNotification;
use App\Notifications\ReferralClaimStatusChangedNotification;
use App\Notifications\ReferralClaimSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReferralNetworkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $affiliator;

    private User $investor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->admin = User::factory()->admin()->create([
            'email'    => 'admin@test.com',
            'username' => 'admin_test',
        ]);

        $this->affiliator = User::factory()->affiliator()->create([
            'name'          => 'Abi Yazid',
            'email'         => 'affiliator@test.com',
            'username'      => 'abiyazid',
            'referral_code' => 'ABI8K2',
            'status'        => 'active',
            'is_active'     => true,
        ]);

        $this->investor = User::factory()->create([
            'name'      => 'Budi Santoso',
            'email'     => 'budi@test.com',
            'username'  => 'budisantoso',
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => true,
        ]);
    }

    public function test_check_referral_endpoint_returns_valid_affiliator_info(): void
    {
        $response = $this->getJson('/register/check-referral?code=ABI8K2');

        $response->assertOk()
            ->assertJson([
                'valid'      => true,
                'affiliator' => [
                    'name'          => 'Abi Yazid',
                    'referral_code' => 'ABI8K2',
                ],
            ]);
    }

    public function test_check_referral_endpoint_returns_422_for_invalid_code(): void
    {
        $response = $this->getJson('/register/check-referral?code=NONEXIST');

        $response->assertStatus(422)
            ->assertJson([
                'valid' => false,
            ]);
    }

    public function test_investor_registers_with_manual_referral_code_creates_relasi_referral(): void
    {
        $invitation = Invitation::create([
            'token'          => 'tokenreginvestor12345678901234',
            'label'          => 'Batch Investor',
            'target_role'    => 'investor',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->addDays(5),
        ]);

        $payload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'Rian Pratama',
            'username'              => 'rianpratama',
            'email'                 => 'rian@test.com',
            'phone'                 => '081298765431',
            'nik'                   => '3201123456789011',
            'alamat'                => 'Jl. Sudirman No. 1, Jakarta',
            'tempat_lahir'          => 'Jakarta',
            'tanggal_lahir'         => '1992-05-10',
            'pekerjaan'             => 'Karyawan',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'referral_code'         => 'ABI8K2',
            'ahli_waris_nama'       => 'Siti Rahma',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081299998888',
            'ahli_waris_alamat'     => 'Jl. Sudirman No. 1, Jakarta',
            'tier_investasi'        => 'tier_1',
            'nomor_akun_trading'    => '11223344',
            'estimasi_modal'        => 2500,
            'tanggal_mulai_trading' => '2026-03-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '9988776655',
            'atas_nama_rekening'    => 'Rian Pratama',
            'dokumen_ktp'           => UploadedFile::fake()->image('ktp.jpg', 800, 600),
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect(route('login'));
        $newUser = User::where('username', 'rianpratama')->firstOrFail();

        $this->assertDatabaseHas('relasi_referral', [
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $newUser->id,
            'sumber'        => 'link_otomatis',
            'status'        => 'active',
        ]);
        $this->assertTrue($newUser->hasUpline());
        $this->assertEquals($this->affiliator->id, $newUser->getUpline()->id);
    }

    public function test_investor_registers_without_referral_code_creates_no_relation(): void
    {
        $invitation = Invitation::create([
            'token'          => 'tokenreginvestor99999999999999',
            'label'          => 'Batch Investor 2',
            'target_role'    => 'investor',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->addDays(5),
        ]);

        $payload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'Doni Mandiri',
            'username'              => 'donimandiri',
            'email'                 => 'doni@test.com',
            'phone'                 => '081298765439',
            'nik'                   => '3201123456789099',
            'alamat'                => 'Jl. Thamrin No. 9, Jakarta',
            'tempat_lahir'          => 'Bandung',
            'tanggal_lahir'         => '1990-08-15',
            'pekerjaan'             => 'Wiraswasta',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'referral_code'         => '', // Kosong
            'ahli_waris_nama'       => 'Ani Mandiri',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081299998899',
            'ahli_waris_alamat'     => 'Jl. Thamrin No. 9, Jakarta',
            'tier_investasi'        => 'tier_1',
            'nomor_akun_trading'    => '55667788',
            'estimasi_modal'        => 3000,
            'tanggal_mulai_trading' => '2026-03-01',
            'nama_bank'             => 'Mandiri',
            'nomor_rekening'        => '1231231234',
            'atas_nama_rekening'    => 'Doni Mandiri',
            'dokumen_ktp'           => UploadedFile::fake()->image('ktp.jpg', 800, 600),
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect(route('login'));
        $newUser = User::where('username', 'donimandiri')->firstOrFail();

        $this->assertDatabaseMissing('relasi_referral', [
            'downline_id' => $newUser->id,
        ]);
        $this->assertFalse($newUser->hasUpline());
    }

    public function test_investor_registration_fails_if_referral_code_invalid(): void
    {
        $invitation = Invitation::create([
            'token'          => 'tokenreginvestor88888888888888',
            'label'          => 'Batch Investor 3',
            'target_role'    => 'investor',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->addDays(5),
        ]);

        $payload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'Gagal Test',
            'username'              => 'gagaltest',
            'email'                 => 'gagal@test.com',
            'phone'                 => '081298765438',
            'nik'                   => '3201123456789088',
            'alamat'                => 'Jl. Gatot Subroto No. 8',
            'tempat_lahir'          => 'Surabaya',
            'tanggal_lahir'         => '1991-01-01',
            'pekerjaan'             => 'PNS',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'referral_code'         => 'SALAHKODE12', // Kode tidak ada
            'ahli_waris_nama'       => 'Ibu Gagal',
            'ahli_waris_hubungan'   => 'Orang Tua',
            'ahli_waris_phone'      => '081299998877',
            'ahli_waris_alamat'     => 'Jl. Gatot Subroto No. 8',
            'tier_investasi'        => 'tier_1',
            'nomor_akun_trading'    => '88776655',
            'estimasi_modal'        => 1000,
            'tanggal_mulai_trading' => '2026-03-01',
            'nama_bank'             => 'BNI',
            'nomor_rekening'        => '3344556677',
            'atas_nama_rekening'    => 'Gagal Test',
            'dokumen_ktp'           => UploadedFile::fake()->image('ktp.jpg', 800, 600),
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ];

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors('referral_code');
        $this->assertDatabaseMissing('users', ['username' => 'gagaltest']);
    }

    public function test_affiliator_can_view_affiliate_dashboard_and_downlines(): void
    {
        // Kaitkan investor dengan affiliator
        RelasiReferral::create([
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $this->investor->id,
            'sumber'        => 'link_otomatis',
            'joined_at'     => now(),
            'status'        => 'active',
        ]);

        $response = $this->actingAs($this->affiliator)->get(route('investor.affiliates.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Investor/Affiliates/Index')
                ->has('stats')
                ->has('downlines.data', 1)
                ->where('stats.referral_code', 'ABI8K2')
                ->where('stats.total_downlines', 1)
            );
    }

    public function test_non_affiliator_cannot_access_affiliate_portal(): void
    {
        $response = $this->actingAs($this->investor)->get(route('investor.affiliates.index'));

        $response->assertForbidden();
    }

    public function test_affiliator_can_submit_referral_claim_with_proof_and_reason(): void
    {
        // Investor tanpa upline
        $unlinkedInvestor = User::factory()->create([
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $chatProof = UploadedFile::fake()->image('chat_wa.png', 720, 1280);

        $response = $this->actingAs($this->affiliator)->post(route('investor.affiliates.claims.store'), [
            'downline_id' => $unlinkedInvestor->id,
            'bukti_chat'  => $chatProof,
            'alasan'      => 'Investor lupa memasukkan kode referral saya saat mendaftar kemarin sore.',
        ]);

        $response->assertRedirect(route('investor.affiliates.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('klaim_referral', [
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $unlinkedInvestor->id,
            'status'        => 'pending',
        ]);

        $claim = KlaimReferral::first();
        Storage::disk('local')->assertExists($claim->bukti_chat_path);
    }

    public function test_affiliator_can_submit_referral_claim_without_proof_file(): void
    {
        $unlinkedInvestor = User::factory()->create([
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->affiliator)->post(route('investor.affiliates.claims.store'), [
            'downline_id' => $unlinkedInvestor->id,
            'alasan'      => 'Investor kenalan langsung offline tanpa chat WhatsApp.',
        ]);

        $response->assertRedirect(route('investor.affiliates.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('klaim_referral', [
            'affiliator_id'   => $this->affiliator->id,
            'downline_id'     => $unlinkedInvestor->id,
            'bukti_chat_path' => null,
            'status'          => 'pending',
        ]);
    }

    public function test_affiliator_can_submit_referral_claim_with_pdf_proof(): void
    {
        $unlinkedInvestor = User::factory()->create([
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $pdfProof = UploadedFile::fake()->create('dokumen_chat.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->affiliator)->post(route('investor.affiliates.claims.store'), [
            'downline_id' => $unlinkedInvestor->id,
            'bukti_chat'  => $pdfProof,
            'alasan'      => 'Bukti percakapan dilampirkan dalam format PDF.',
        ]);

        $response->assertRedirect(route('investor.affiliates.index'));
        $response->assertSessionHas('success');

        $claim = KlaimReferral::where('downline_id', $unlinkedInvestor->id)->firstOrFail();
        $this->assertNotNull($claim->bukti_chat_path);
        $this->assertStringEndsWith('.pdf', $claim->bukti_chat_path);
        Storage::disk('local')->assertExists($claim->bukti_chat_path);
    }

    public function test_affiliator_can_submit_referral_claim_with_long_reason_exceeding_1000_chars(): void
    {
        $unlinkedInvestor = User::factory()->create([
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $longReason = str_repeat('Ini adalah kronologi detail perkenalan offline yang sangat panjang. ', 50); // ~3500 chars

        $response = $this->actingAs($this->affiliator)->post(route('investor.affiliates.claims.store'), [
            'downline_id' => $unlinkedInvestor->id,
            'alasan'      => $longReason,
        ]);

        $response->assertRedirect(route('investor.affiliates.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('klaim_referral', [
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $unlinkedInvestor->id,
            'alasan'        => trim($longReason),
            'status'        => 'pending',
        ]);
    }

    public function test_affiliator_can_check_downline_username_availability_and_eligibility(): void
    {
        // 1. Investor valid tanpa upline
        $validInvestor = User::factory()->create([
            'username'  => 'caloninvestor',
            'name'      => 'Calon Investor',
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $responseValid = $this->actingAs($this->affiliator)->getJson(route('investor.affiliates.check_downline', [
            'username' => '@caloninvestor',
        ]));
        $responseValid->assertOk()
            ->assertJson([
                'valid'    => true,
                'downline' => [
                    'id'       => $validInvestor->id,
                    'username' => 'caloninvestor',
                    'name'     => 'Calon Investor',
                ],
            ]);

        // 2. Username tidak ditemukan
        $responseNotFound = $this->actingAs($this->affiliator)->getJson(route('investor.affiliates.check_downline', [
            'username' => 'tidakadauser',
        ]));
        $responseNotFound->assertStatus(422)
            ->assertJson([
                'valid' => false,
            ]);
        $this->assertStringContainsString('tidak ditemukan', $responseNotFound->json('message'));

        // 3. User bukan investor (misal admin)
        $responseInvalidRole = $this->actingAs($this->affiliator)->getJson(route('investor.affiliates.check_downline', [
            'username' => $this->admin->username,
        ]));
        $responseInvalidRole->assertStatus(422)
            ->assertJson([
                'valid' => false,
            ]);

        // 4. Cek username diri sendiri
        $responseSelf = $this->actingAs($this->affiliator)->getJson(route('investor.affiliates.check_downline', [
            'username' => $this->affiliator->username,
        ]));
        $responseSelf->assertStatus(422)
            ->assertJson([
                'valid'   => false,
                'message' => 'Anda tidak dapat mengklaim akun Anda sendiri.',
            ]);

        // 5. Investor sudah punya upline
        RelasiReferral::create([
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $this->investor->id,
            'sumber'        => 'link_otomatis',
            'joined_at'     => now(),
            'status'        => 'active',
        ]);
        $responseHasUpline = $this->actingAs($this->affiliator)->getJson(route('investor.affiliates.check_downline', [
            'username' => $this->investor->username,
        ]));
        $responseHasUpline->assertStatus(422)
            ->assertJson([
                'valid'   => false,
                'message' => 'Investor ini sudah terdaftar di bawah mitra affiliator lain.',
            ]);
    }

    public function test_affiliator_cannot_claim_investor_already_having_upline(): void
    {
        // Kaitkan investor ke upline lain
        $otherAffiliator = User::factory()->affiliator()->create(['referral_code' => 'OTH777']);
        RelasiReferral::create([
            'affiliator_id' => $otherAffiliator->id,
            'downline_id'   => $this->investor->id,
            'sumber'        => 'link_otomatis',
            'joined_at'     => now(),
            'status'        => 'active',
        ]);

        $chatProof = UploadedFile::fake()->image('chat.jpg', 600, 800);

        $response = $this->actingAs($this->affiliator)->post(route('investor.affiliates.claims.store'), [
            'downline_id' => $this->investor->id,
            'bukti_chat'  => $chatProof,
            'alasan'      => 'Klaim investor yang sudah punya upline.',
        ]);

        $response->assertSessionHasErrors('downline_id');
        $this->assertDatabaseMissing('klaim_referral', [
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $this->investor->id,
        ]);
    }

    public function test_affiliator_cannot_claim_same_investor_twice_while_pending(): void
    {
        $chatProof = UploadedFile::fake()->image('chat.jpg', 600, 800);

        KlaimReferral::create([
            'affiliator_id'   => $this->affiliator->id,
            'downline_id'     => $this->investor->id,
            'bukti_chat_path' => 'dummy/path.jpg',
            'alasan'          => 'Klaim pertama yang pending.',
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($this->affiliator)->post(route('investor.affiliates.claims.store'), [
            'downline_id' => $this->investor->id,
            'bukti_chat'  => $chatProof,
            'alasan'      => 'Klaim kedua untuk orang yang sama.',
        ]);

        $response->assertSessionHasErrors('downline_id');
    }

    public function test_admin_can_approve_referral_claim_and_atomic_relation_created(): void
    {
        $claim = KlaimReferral::create([
            'affiliator_id'   => $this->affiliator->id,
            'downline_id'     => $this->investor->id,
            'bukti_chat_path' => 'dummy/path.jpg',
            'alasan'          => 'Investor lupa memasukkan kode referral.',
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.referral_claims.verify', ['id' => $claim->id]), [
            'status'        => 'approved',
            'catatan_admin' => 'Bukti chat valid dan waktu cocok.',
        ]);

        $response->assertRedirect(route('admin.referral_claims.index'));
        $response->assertSessionHas('success');

        $claim->refresh();
        $this->assertEquals('approved', $claim->status);
        $this->assertEquals($this->admin->id, $claim->reviewed_by);
        $this->assertNotNull($claim->reviewed_at);

        // Relasi resmi harus otomatis terbentuk dengan sumber 'klaim_manual'
        $this->assertDatabaseHas('relasi_referral', [
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $this->investor->id,
            'sumber'        => 'klaim_manual',
            'status'        => 'active',
        ]);
        $this->assertTrue($this->investor->hasUpline());
    }

    public function test_admin_can_reject_referral_claim_with_required_notes(): void
    {
        $claim = KlaimReferral::create([
            'affiliator_id'   => $this->affiliator->id,
            'downline_id'     => $this->investor->id,
            'bukti_chat_path' => 'dummy/path.jpg',
            'alasan'          => 'Mohon disetujui.',
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.referral_claims.verify', ['id' => $claim->id]), [
            'status'        => 'rejected',
            'catatan_admin' => 'Tangkapan layar chat tidak menunjukkan nama atau kontak investor terkait.',
        ]);

        $response->assertRedirect(route('admin.referral_claims.index'));

        $claim->refresh();
        $this->assertEquals('rejected', $claim->status);
        $this->assertEquals('Tangkapan layar chat tidak menunjukkan nama atau kontak investor terkait.', $claim->catatan_admin);

        // Tidak boleh ada relasi yang dibuat
        $this->assertDatabaseMissing('relasi_referral', [
            'downline_id' => $this->investor->id,
        ]);
    }

    public function test_admin_reject_referral_claim_fails_without_notes(): void
    {
        $claim = KlaimReferral::create([
            'affiliator_id'   => $this->affiliator->id,
            'downline_id'     => $this->investor->id,
            'bukti_chat_path' => 'dummy/path.jpg',
            'alasan'          => 'Mohon disetujui.',
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.referral_claims.verify', ['id' => $claim->id]), [
            'status'        => 'rejected',
            'catatan_admin' => '', // Kosong
        ]);

        $response->assertSessionHasErrors('catatan_admin');
        $claim->refresh();
        $this->assertEquals('pending', $claim->status);
    }

    public function test_admin_can_view_claims_and_affiliates_overview(): void
    {
        $responseClaims = $this->actingAs($this->admin)->get(route('admin.referral_claims.index'));
        $responseClaims->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/ReferralClaims/Index'));

        $responseAffiliates = $this->actingAs($this->admin)->get(route('admin.affiliates.index'));
        $responseAffiliates->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Affiliates/Index'));
    }

    public function test_secure_claim_proof_streaming_authorization(): void
    {
        $file = UploadedFile::fake()->image('bukti.png', 400, 400);
        $path = $file->storeAs('referral_claims/' . $this->affiliator->id, 'bukti_test.png', 'local');

        $claim = KlaimReferral::create([
            'affiliator_id'   => $this->affiliator->id,
            'downline_id'     => $this->investor->id,
            'bukti_chat_path' => $path,
            'alasan'          => 'Test secure streaming',
            'status'          => 'pending',
        ]);

        // 1. Guest -> 302 login
        $responseGuest = $this->get(route('documents.claim_proof', ['id' => $claim->id]));
        $responseGuest->assertRedirect(route('login'));

        // 2. Other user -> 403 Forbidden
        $otherUser = User::factory()->create();
        $responseOther = $this->actingAs($otherUser)->get(route('documents.claim_proof', ['id' => $claim->id]));
        $responseOther->assertForbidden();

        // 3. Affiliator owner -> 200 OK stream
        $responseAffiliator = $this->actingAs($this->affiliator)->get(route('documents.claim_proof', ['id' => $claim->id]));
        $responseAffiliator->assertOk();

        // 4. Admin -> 200 OK stream
        $responseAdmin = $this->actingAs($this->admin)->get(route('documents.claim_proof', ['id' => $claim->id]));
        $responseAdmin->assertOk();
    }

    public function test_notification_sent_to_affiliator_when_downline_registers_with_referral_code(): void
    {
        Notification::fake();

        $invitation = Invitation::create([
            'token'          => 'tokenreginvestornotif777777777',
            'label'          => 'Batch Investor Notif',
            'target_role'    => 'investor',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
            'expired_at'     => now()->addDays(5),
        ]);

        $payload = [
            'invite_token'          => $invitation->token,
            'name'                  => 'Budi Downline',
            'username'              => 'budinotifdownline',
            'email'                 => 'budinotifdownline@test.com',
            'phone'                 => '081298765455',
            'nik'                   => '3201123456789055',
            'alamat'                => 'Jl. Sudirman No. 11',
            'tempat_lahir'          => 'Jakarta',
            'tanggal_lahir'         => '1992-05-10',
            'pekerjaan'             => 'Karyawan',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => true,
            'referral_code'         => $this->affiliator->referral_code,
            'ahli_waris_nama'       => 'Siti Downline',
            'ahli_waris_hubungan'   => 'Istri',
            'ahli_waris_phone'      => '081299998811',
            'ahli_waris_alamat'     => 'Jl. Sudirman No. 11',
            'tier_investasi'        => 'tier_1',
            'nomor_akun_trading'    => '11223344',
            'estimasi_modal'        => 1000,
            'tanggal_mulai_trading' => '2026-03-01',
            'nama_bank'             => 'BCA',
            'nomor_rekening'        => '1122334455',
            'atas_nama_rekening'    => 'Budi Downline',
            'dokumen_ktp'           => UploadedFile::fake()->image('ktp.jpg', 800, 600),
            'dokumen_perjanjian'    => UploadedFile::fake()->create('perjanjian.pdf', 300, 'application/pdf'),
        ];

        $response = $this->post('/register', $payload);
        $response->assertRedirect(route('login'));

        $newUser = User::where('username', 'budinotifdownline')->firstOrFail();

        Notification::assertSentTo(
            $this->affiliator,
            NewDownlineRegisteredNotification::class,
            function (NewDownlineRegisteredNotification $notification) use ($newUser) {
                return $notification->downline->id === $newUser->id
                    && $notification->affiliator->id === $this->affiliator->id;
            }
        );
    }

    public function test_notification_sent_to_admins_when_claim_is_submitted(): void
    {
        Notification::fake();

        $unlinkedInvestor = User::factory()->create([
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->affiliator)->post(route('investor.affiliates.claims.store'), [
            'downline_id' => $unlinkedInvestor->id,
            'alasan'      => 'Investor lupa memasukkan kode referral saya saat registrasi.',
        ]);

        $response->assertRedirect(route('investor.affiliates.index'));

        Notification::assertSentTo(
            $this->admin,
            ReferralClaimSubmittedNotification::class,
            function (ReferralClaimSubmittedNotification $notification) use ($unlinkedInvestor) {
                return $notification->claim->affiliator_id === $this->affiliator->id
                    && $notification->claim->downline_id === $unlinkedInvestor->id;
            }
        );
    }

    public function test_notification_sent_to_affiliator_when_claim_is_reviewed(): void
    {
        Notification::fake();

        $claim = KlaimReferral::create([
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $this->investor->id,
            'alasan'        => 'Klaim referral untuk review notifikasi',
            'status'        => 'pending',
        ]);

        // Review approved
        $response = $this->actingAs($this->admin)->post(route('admin.referral_claims.verify', ['id' => $claim->id]), [
            'status'        => 'approved',
            'catatan_admin' => 'Disetujui dan diverifikasi.',
        ]);

        $response->assertRedirect(route('admin.referral_claims.index'));

        Notification::assertSentTo(
            $this->affiliator,
            ReferralClaimStatusChangedNotification::class,
            function (ReferralClaimStatusChangedNotification $notification) {
                return $notification->status === 'approved'
                    && $notification->claim->affiliator_id === $this->affiliator->id;
            }
        );
    }

    public function test_affiliate_portal_tabs_and_filters_are_independent(): void
    {
        // Setup downline
        RelasiReferral::create([
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $this->investor->id,
            'sumber'        => 'link_otomatis',
            'joined_at'     => now(),
            'status'        => 'active',
        ]);

        // Setup claim with different investor
        $secondInvestor = User::factory()->create([
            'name'      => 'Siti Rahma',
            'username'  => 'sitirahma',
            'email'     => 'siti@test.com',
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => true,
        ]);

        KlaimReferral::create([
            'affiliator_id' => $this->affiliator->id,
            'downline_id'   => $secondInvestor->id,
            'alasan'        => 'Klaim susulan untuk Siti Rahma',
            'status'        => 'pending',
        ]);

        // 1. Search on downlines should not filter claims
        $response1 = $this->actingAs($this->affiliator)->get(route('investor.affiliates.index', [
            'search' => 'budi',
        ]));
        $response1->assertOk()->assertInertia(fn ($page) => $page
            ->where('downlineFilters.search', 'budi')
            ->has('downlines.data', 1)
            ->has('claims.data', 1)
        );

        // 2. Search on claims should not filter downlines
        $response2 = $this->actingAs($this->affiliator)->get(route('investor.affiliates.index', [
            'claim_search' => 'siti',
        ]));
        $response2->assertOk()->assertInertia(fn ($page) => $page
            ->where('claimFilters.claim_search', 'siti')
            ->has('claims.data', 1)
            ->has('downlines.data', 1)
        );

        // 3. Status filter on claims does not wipe downlines and activeTab is respected
        $response3 = $this->actingAs($this->affiliator)->get(route('investor.affiliates.index', [
            'claim_status' => 'pending',
            'tab'          => 'claims',
        ]));
        $response3->assertOk()->assertInertia(fn ($page) => $page
            ->where('activeTab', 'claims')
            ->where('claimFilters.claim_status', 'pending')
            ->has('claims.data', 1)
            ->has('downlines.data', 1)
        );
    }
}
