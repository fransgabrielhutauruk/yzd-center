<?php

namespace Tests\Feature;

use App\Models\KontenLegal;
use App\Models\User;
use App\Models\UserDokumen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_stream_requires_authentication(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $path = "kyc_documents/{$user->id}/test_ktp.jpg";
        Storage::disk('local')->put($path, 'dummy image content');

        $dokumen = UserDokumen::create([
            'user_id'      => $user->id,
            'tipe_dokumen' => 'ktp',
            'file_path'    => $path,
            'file_name'    => 'test_ktp.jpg',
            'mime_type'    => 'image/jpeg',
            'file_size'    => 1024,
            'status'       => 'pending',
        ]);

        // Tamu tanpa login diarahkan ke login
        $response = $this->get("/documents/{$dokumen->id}");
        $response->assertRedirect('/login');
    }

    public function test_document_cannot_be_viewed_by_other_investors(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $path = "kyc_documents/{$owner->id}/test_ktp.jpg";
        Storage::disk('local')->put($path, 'dummy image content');

        $dokumen = UserDokumen::create([
            'user_id'      => $owner->id,
            'tipe_dokumen' => 'ktp',
            'file_path'    => $path,
            'file_name'    => 'test_ktp.jpg',
            'mime_type'    => 'image/jpeg',
            'file_size'    => 1024,
            'status'       => 'pending',
        ]);

        // User lain ditolak dengan 403 Forbidden
        $response = $this->actingAs($otherUser)->get("/documents/{$dokumen->id}");
        $response->assertForbidden();
    }

    public function test_document_can_be_viewed_by_owner_and_admin(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $path = "kyc_documents/{$owner->id}/test_ktp.jpg";
        Storage::disk('local')->put($path, 'dummy image content');

        $dokumen = UserDokumen::create([
            'user_id'      => $owner->id,
            'tipe_dokumen' => 'ktp',
            'file_path'    => $path,
            'file_name'    => 'test_ktp.jpg',
            'mime_type'    => 'image/jpeg',
            'file_size'    => 1024,
            'status'       => 'pending',
        ]);

        // Pemilik dokumen bisa mengakses
        $this->actingAs($owner)->get("/documents/{$dokumen->id}")->assertOk();

        // Admin bisa mengakses
        $this->actingAs($admin)->get("/documents/{$dokumen->id}")->assertOk();

        // Pastikan audit log tercatat dengan menyebut username target (@username) tanpa duplikasi Administrator
        $this->assertDatabaseHas('log_aktivitas', [
            'user_id'      => $admin->id,
            'kategori'     => 'kyc_dokumen',
            'aksi'         => 'lihat_dokumen',
            'deskripsi'    => \App\Services\AuditLogService::formatActor($admin) . " melihat berkas ktp milik @{$owner->username}.",
            'subject_type' => $dokumen->getMorphClass(),
            'subject_id'   => $dokumen->id,
        ]);
    }

    public function test_admin_can_update_legal_content_and_version(): void
    {
        $admin = User::factory()->admin()->create();
        $investor = User::factory()->create(['role' => 'investor']);

        // Investor tidak bisa mengakses pengaturan dokumen legal
        $this->actingAs($investor)->get('/admin/settings/legal')->assertForbidden();

        // Admin bisa mengakses
        $this->actingAs($admin)->get('/admin/settings/legal')->assertOk();

        // Admin mengupdate Syarat & Ketentuan
        $response = $this->actingAs($admin)->post('/admin/settings/legal', [
            'key_name' => 'terms_conditions',
            'judul'    => 'Syarat & Ketentuan Layanan Terkini',
            'konten'   => 'Pasal 1: Ketentuan umum kemitraan terbaru...',
        ]);

        $response->assertSessionHasNoErrors();
        $terms = KontenLegal::getTerms();
        $this->assertEquals('Syarat & Ketentuan Layanan Terkini', $terms->judul);
        $this->assertEquals('Pasal 1: Ketentuan umum kemitraan terbaru...', $terms->konten);
    }
}
