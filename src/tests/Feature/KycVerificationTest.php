<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDokumen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingUserWithDocs(): User
    {
        $user = User::factory()->create([
            'role'   => 'investor',
            'status' => 'pending',
        ]);

        UserDokumen::create([
            'user_id'      => $user->id,
            'tipe_dokumen' => 'ktp',
            'file_path'    => "kyc_documents/{$user->id}/ktp.jpg",
            'file_name'    => 'ktp.jpg',
            'mime_type'    => 'image/jpeg',
            'file_size'    => 500000,
            'status'       => 'pending',
        ]);

        UserDokumen::create([
            'user_id'      => $user->id,
            'tipe_dokumen' => 'perjanjian',
            'file_path'    => "kyc_documents/{$user->id}/perjanjian.pdf",
            'file_name'    => 'perjanjian.pdf',
            'mime_type'    => 'application/pdf',
            'file_size'    => 800000,
            'status'       => 'pending',
        ]);

        return $user;
    }

    public function test_only_admin_can_access_kyc_verification_page(): void
    {
        $admin = User::factory()->admin()->create();
        $investor = User::factory()->create(['role' => 'investor']);

        // Tamu ditolak (redirect ke login)
        $this->get('/admin/kyc')->assertRedirect('/login');

        // Investor ditolak (403 Forbidden)
        $this->actingAs($investor)->get('/admin/kyc')->assertForbidden();

        // Admin berhasil (200 OK)
        $this->actingAs($admin)->get('/admin/kyc')->assertOk();
    }

    public function test_admin_can_approve_document(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->createPendingUserWithDocs();
        $ktpDoc = $user->dokumen()->where('tipe_dokumen', 'ktp')->first();

        $response = $this->actingAs($admin)->post("/admin/kyc/documents/{$ktpDoc->id}/verify", [
            'status' => 'approved',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('approved', $ktpDoc->fresh()->status);
        $this->assertEquals($admin->id, $ktpDoc->fresh()->verified_by);

        // Karena dokumen perjanjian belum diapprove, user masih pending
        $this->assertEquals('pending', $user->fresh()->status);
    }

    public function test_user_becomes_active_when_all_documents_are_approved(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->createPendingUserWithDocs();

        $ktpDoc = $user->dokumen()->where('tipe_dokumen', 'ktp')->first();
        $perjanjianDoc = $user->dokumen()->where('tipe_dokumen', 'perjanjian')->first();

        // Approve KTP
        $this->actingAs($admin)->post("/admin/kyc/documents/{$ktpDoc->id}/verify", [
            'status' => 'approved',
        ]);

        // Approve Perjanjian
        $this->actingAs($admin)->post("/admin/kyc/documents/{$perjanjianDoc->id}/verify", [
            'status' => 'approved',
        ]);

        // Kedua dokumen approved -> user otomatis ACTIVE
        $this->assertEquals('approved', $ktpDoc->fresh()->status);
        $this->assertEquals('approved', $perjanjianDoc->fresh()->status);
        $this->assertEquals('active', $user->fresh()->status);
    }

    public function test_admin_must_provide_notes_when_requesting_revision(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->createPendingUserWithDocs();
        $ktpDoc = $user->dokumen()->where('tipe_dokumen', 'ktp')->first();

        // Gagal jika catatan admin kosong saat revision_needed
        $response = $this->actingAs($admin)->post("/admin/kyc/documents/{$ktpDoc->id}/verify", [
            'status'        => 'revision_needed',
            'catatan_admin' => '',
        ]);

        $response->assertSessionHasErrors(['catatan_admin']);
        $this->assertEquals('pending', $ktpDoc->fresh()->status);

        // Berhasil jika catatan admin diisi
        $responseSuccess = $this->actingAs($admin)->post("/admin/kyc/documents/{$ktpDoc->id}/verify", [
            'status'        => 'revision_needed',
            'catatan_admin' => 'Foto KTP buram dan silau pada bagian NIK.',
        ]);

        $responseSuccess->assertSessionHasNoErrors();
        $this->assertEquals('revision_needed', $ktpDoc->fresh()->status);
        $this->assertEquals('Foto KTP buram dan silau pada bagian NIK.', $ktpDoc->fresh()->catatan_admin);
    }

    public function test_investor_can_reupload_document_that_needs_revision(): void
    {
        Storage::fake('local');
        $user = $this->createPendingUserWithDocs();
        $ktpDoc = $user->dokumen()->where('tipe_dokumen', 'ktp')->first();

        // Set status ke revision_needed
        $ktpDoc->update(['status' => 'revision_needed', 'catatan_admin' => 'Foto buram']);

        $response = $this->actingAs($user)->post('/investor/kyc/reupload/ktp', [
            'file' => UploadedFile::fake()->image('ktp_baru.jpg', 1000, 700),
        ]);

        $response->assertSessionHasNoErrors();

        // Status kembali menjadi pending setelah diunggah ulang
        $this->assertEquals('pending', $ktpDoc->fresh()->status);
        $this->assertNull($ktpDoc->fresh()->catatan_admin);
    }

    public function test_investor_cannot_reupload_approved_document(): void
    {
        Storage::fake('local');
        $user = $this->createPendingUserWithDocs();
        $ktpDoc = $user->dokumen()->where('tipe_dokumen', 'ktp')->first();

        // Set status ke approved
        $ktpDoc->update(['status' => 'approved']);

        $response = $this->actingAs($user)->post('/investor/kyc/reupload/ktp', [
            'file' => UploadedFile::fake()->image('ktp_iseng.jpg', 1000, 700),
        ]);

        $response->assertSessionHasErrors(['ktp']);
        $this->assertEquals('approved', $ktpDoc->fresh()->status);
    }
}
