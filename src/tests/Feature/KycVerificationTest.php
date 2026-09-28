<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDokumen;
use App\Services\KycService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $investor;

    private KycService $kycService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kycService = app(KycService::class);

        $this->admin = User::factory()->create(['role' => 'administrator', 'status' => 'active']);
        $this->investor = User::factory()->create([
            'role'   => 'investor',
            'status' => 'pending',
        ]);

        Storage::fake('local');
    }

    private function createDummyDokumen(array $overrides = []): UserDokumen
    {
        return UserDokumen::create(array_merge([
            'user_id'       => $this->investor->id,
            'jenis_dokumen' => 'ktp',
            'tipe_dokumen'  => 'ktp',
            'status'        => 'pending',
            'file_name'     => 'test.jpg',
            'file_path'     => 'docs/test.jpg',
            'file_size'     => 1024,
            'mime_type'     => 'image/jpeg',
        ], $overrides));
    }

    public function test_only_admin_can_access_kyc_verification_page(): void
    {
        $response = $this->actingAs($this->investor)->get('/admin/registrations');
        $response->assertStatus(403);

        $response = $this->actingAs($this->admin)->get('/admin/registrations');
        $response->assertStatus(200);
    }

    public function test_admin_can_approve_document(): void
    {
        $dokumen = $this->createDummyDokumen(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->post("/admin/registrations/documents/{$dokumen->id}/verify", [
            'status' => 'approved',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('user_dokumen', [
            'id'     => $dokumen->id,
            'status' => 'approved',
        ]);

        $this->assertEquals('active', $this->investor->fresh()->status);
    }

    public function test_user_becomes_active_when_all_documents_are_approved(): void
    {
        $dokumen = $this->createDummyDokumen(['status' => 'pending']);

        $this->kycService->verifyDocument($dokumen, 'approved', null, $this->admin);

        $this->assertEquals('active', $this->investor->fresh()->status);
    }

    public function test_admin_must_provide_notes_when_requesting_revision(): void
    {
        $dokumen = $this->createDummyDokumen(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->post("/admin/registrations/documents/{$dokumen->id}/verify", [
            'status'        => 'revision_needed',
            'catatan_admin' => '',
        ]);

        $response->assertSessionHasErrors('catatan_admin');
        $this->assertDatabaseHas('user_dokumen', [
            'id'     => $dokumen->id,
            'status' => 'pending',
        ]);
    }

    public function test_investor_can_reupload_document_that_needs_revision(): void
    {
        $dokumen = $this->createDummyDokumen([
            'status'        => 'revision_needed',
            'catatan_admin' => 'Foto buram',
        ]);

        $file = UploadedFile::fake()->image('ktp_baru.jpg');

        $response = $this->actingAs($this->investor)->post("/investor/account/reupload/ktp", [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('user_dokumen', [
            'id'            => $dokumen->id,
            'status'        => 'pending',
            'catatan_admin' => null,
        ]);
    }

    public function test_investor_cannot_reupload_approved_document(): void
    {
        $this->createDummyDokumen(['status' => 'approved']);

        $file = UploadedFile::fake()->image('ktp_baru.jpg');

        $response = $this->actingAs($this->investor)->post('/investor/account/reupload/ktp', [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('ktp');
    }

    public function test_admin_receives_counts_and_investor_details(): void
    {
        $this->createDummyDokumen(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->get('/admin/registrations');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Registrations/Index')
            ->has('counts')
            ->has('investors.data', 1)
            ->where('counts.pending', 1)
            ->where('investors.data.0.name', $this->investor->name)
        );
    }

    public function test_admin_can_filter_kyc_by_status(): void
    {
        $this->createDummyDokumen(['status' => 'revision_needed']);

        $response = $this->actingAs($this->admin)->get('/admin/registrations?status=revision_needed');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Registrations/Index')
            ->where('counts.revision_needed', 1)
            ->has('investors.data', 1)
        );

        $responseEmpty = $this->actingAs($this->admin)->get('/admin/registrations?status=pending');
        $responseEmpty->assertInertia(fn ($page) => $page
            ->where('counts.pending', 0)
            ->has('investors.data', 0)
        );
    }

    public function test_approved_user_is_not_displayed_in_registrations_queue(): void
    {
        // Investor 1 is pending
        $this->createDummyDokumen(['status' => 'pending']);

        // Investor 2 is active (already approved)
        $approvedInvestor = User::factory()->create([
            'role'   => 'investor',
            'status' => 'active',
        ]);
        UserDokumen::create([
            'user_id'       => $approvedInvestor->id,
            'jenis_dokumen' => 'ktp',
            'tipe_dokumen'  => 'ktp',
            'status'        => 'approved',
            'file_name'     => 'approved.jpg',
            'file_path'     => 'docs/approved.jpg',
            'file_size'     => 1024,
            'mime_type'     => 'image/jpeg',
            'verified_at'   => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/registrations');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Registrations/Index')
            ->has('investors.data', 1)
            ->where('investors.data.0.id', $this->investor->id)
            ->where('counts.all', 1)
        );
    }

    public function test_admin_can_search_by_upline_name(): void
    {
        $this->createDummyDokumen(['status' => 'pending']);

        $upline = User::factory()->create([
            'name'          => 'Budi Santoso Upline',
            'username'      => 'budisantoso',
            'role'          => 'affiliator',
            'status'        => 'active',
            'referral_code' => 'BUDI123',
        ]);

        \App\Models\RelasiReferral::create([
            'affiliator_id' => $upline->id,
            'downline_id'   => $this->investor->id,
            'sumber'        => 'link_otomatis',
            'joined_at'     => now(),
            'status'        => 'active',
        ]);

        // Search by upline name
        $response = $this->actingAs($this->admin)->get('/admin/registrations?search=Budi+Santoso');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('investors.data', 1)
            ->where('investors.data.0.id', $this->investor->id)
        );

        // Search by upline referral code
        $responseCode = $this->actingAs($this->admin)->get('/admin/registrations?search=BUDI123');
        $responseCode->assertStatus(200);
        $responseCode->assertInertia(fn ($page) => $page
            ->has('investors.data', 1)
            ->where('investors.data.0.id', $this->investor->id)
        );

        // Search by non-matching string
        $responseEmpty = $this->actingAs($this->admin)->get('/admin/registrations?search=NonExistentName');
        $responseEmpty->assertStatus(200);
        $responseEmpty->assertInertia(fn ($page) => $page
            ->has('investors.data', 0)
        );
    }
}
