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
        $response = $this->actingAs($this->investor)->get('/admin/kyc');
        $response->assertStatus(403);

        $response = $this->actingAs($this->admin)->get('/admin/kyc');
        $response->assertStatus(200);
    }

    public function test_admin_can_approve_document(): void
    {
        $dokumen = $this->createDummyDokumen(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->post("/admin/kyc/documents/{$dokumen->id}/verify", [
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

        $response = $this->actingAs($this->admin)->post("/admin/kyc/documents/{$dokumen->id}/verify", [
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

        $response = $this->actingAs($this->investor)->post("/investor/kyc/reupload/ktp", [
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

        $response = $this->actingAs($this->investor)->post('/investor/kyc/reupload/ktp', [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('ktp');
    }

    public function test_admin_receives_counts_and_investor_details(): void
    {
        $this->createDummyDokumen(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->get('/admin/kyc');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Kyc/Index')
            ->has('counts')
            ->has('investors.data', 1)
            ->where('counts.pending', 1)
            ->where('investors.data.0.name', $this->investor->name)
        );
    }

    public function test_admin_can_filter_kyc_by_status(): void
    {
        $this->createDummyDokumen(['status' => 'revision_needed']);

        $response = $this->actingAs($this->admin)->get('/admin/kyc?status=revision_needed');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Kyc/Index')
            ->where('counts.revision_needed', 1)
            ->has('investors.data', 1)
        );

        $responseEmpty = $this->actingAs($this->admin)->get('/admin/kyc?status=active');
        $responseEmpty->assertInertia(fn ($page) => $page
            ->where('counts.active', 0)
            ->has('investors.data', 0)
        );
    }
}
