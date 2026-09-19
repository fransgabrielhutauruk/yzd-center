<?php

namespace Tests\Feature;

use App\Models\KontenLegal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_legal_content_page(): void
    {
        $admin = User::factory()->admin()->create();
        $investor = User::factory()->create(['role' => 'investor']);

        // Tamu ditolak (redirect ke login)
        $this->get('/admin/settings/legal')->assertRedirect('/login');

        // Investor ditolak (403 Forbidden)
        $this->actingAs($investor)->get('/admin/settings/legal')->assertForbidden();

        // Admin berhasil (200 OK)
        $this->actingAs($admin)->get('/admin/settings/legal')->assertOk();
    }

    public function test_admin_can_update_terms_conditions(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/settings/legal', [
            'key_name' => 'terms_conditions',
            'judul'    => 'Syarat & Ketentuan Layanan Revisi 2026',
            'konten'   => 'Pasal 1: Ketentuan Baru Platform YZD Center.',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $terms = KontenLegal::where('key_name', 'terms_conditions')->first();
        $this->assertNotNull($terms);
        $this->assertEquals('Syarat & Ketentuan Layanan Revisi 2026', $terms->judul);
        $this->assertEquals('Pasal 1: Ketentuan Baru Platform YZD Center.', $terms->konten);
        $this->assertEquals($admin->id, $terms->updated_by);
    }

    public function test_admin_can_update_privacy_policy(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/settings/legal', [
            'key_name' => 'privacy_policy',
            'judul'    => 'Kebijakan Privasi Data Revisi',
            'konten'   => 'Data pengguna dilindungi dengan enkripsi AES-256.',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $privacy = KontenLegal::where('key_name', 'privacy_policy')->first();
        $this->assertNotNull($privacy);
        $this->assertEquals('Kebijakan Privasi Data Revisi', $privacy->judul);
        $this->assertEquals('Data pengguna dilindungi dengan enkripsi AES-256.', $privacy->konten);
    }

    public function test_legal_content_update_validation_fails_for_invalid_payload(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/settings/legal', [
            'key_name' => 'invalid_key',
            'judul'    => '',
            'konten'   => '',
        ]);

        $response->assertSessionHasErrors(['key_name', 'judul', 'konten']);
    }

    public function test_get_terms_and_privacy_policy_caching_and_auto_recovery_from_corrupted_cache(): void
    {
        KontenLegal::create([
            'key_name' => 'terms_conditions',
            'judul'    => 'Syarat Layanan v1',
            'konten'   => 'Isi pasal...',
        ]);

        // 1. Cache miss -> ambil dari DB dan cache atribut
        $terms1 = KontenLegal::getTerms();
        $this->assertInstanceOf(KontenLegal::class, $terms1);
        $this->assertEquals('Isi pasal...', $terms1->konten);

        // 2. Cache hit -> ambil dari cache array yang dihidrasi
        $terms2 = KontenLegal::getTerms();
        $this->assertInstanceOf(KontenLegal::class, $terms2);
        $this->assertEquals('Isi pasal...', $terms2->konten);

        // 3. Simulasi cache rusak (bukan array, misal serialized object asing / __PHP_Incomplete_Class)
        \Illuminate\Support\Facades\Cache::put('legal_content_terms', 'invalid_corrupted_data', 3600);

        // 4. Auto-recovery -> deteksi data rusak, bersihkan, ambil ulang dari DB
        $terms3 = KontenLegal::getTerms();
        $this->assertInstanceOf(KontenLegal::class, $terms3);
        $this->assertEquals('Isi pasal...', $terms3->konten);
    }
}
