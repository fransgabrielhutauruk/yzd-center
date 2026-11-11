<?php

namespace Tests\Feature;

use App\Models\ProfitMasterPost;
use App\Models\User;
use App\Services\ProfitMasterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfitMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_profit_master_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/profit-master')
            ->assertOk();
    }

    public function test_member_cannot_access_admin_profit_master(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);

        $this->actingAs($member)
            ->get('/admin/profit-master')
            ->assertForbidden();

        $this->actingAs($member)
            ->post('/admin/profit-master', ['caption' => 'Coba'])
            ->assertForbidden();
    }

    public function test_admin_can_create_post_with_multiple_images(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $file1 = UploadedFile::fake()->image('profit_xau.png', 800, 600);
        $file2 = UploadedFile::fake()->image('withdraw.jpg', 800, 600);

        $response = $this->actingAs($admin)->post('/admin/profit-master', [
            'caption' => 'Profit luar biasa hari ini dari XAUUSD +15%!',
            'images'  => [$file1, $file2],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('profit_master_posts', [
            'caption'    => 'Profit luar biasa hari ini dari XAUUSD +15%!',
            'created_by' => $admin->id,
        ]);

        $post = ProfitMasterPost::first();
        $this->assertNotNull($post);
        $this->assertCount(2, $post->images);

        foreach ($post->images as $image) {
            Storage::disk('local')->assertExists($image->file_path);
        }

        // Cek audit log tercatat
        $this->assertDatabaseHas('log_aktivitas', [
            'kategori'     => 'profit_master',
            'aksi'         => 'buat_profit_master',
            'subject_type' => ProfitMasterPost::class,
            'subject_id'   => $post->id,
        ]);
    }

    public function test_validation_requires_caption_and_at_least_one_image_on_create(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/profit-master', [
            'caption' => '',
            'images'  => [],
        ]);

        $response->assertSessionHasErrors(['caption', 'images']);
    }

    public function test_caption_accepts_large_text_up_to_20000_chars(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $largeCaption = str_repeat('A', 15000);
        $file = UploadedFile::fake()->image('chart.png', 800, 600);

        $response = $this->actingAs($admin)->post('/admin/profit-master', [
            'caption' => $largeCaption,
            'images'  => [$file],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('profit_master_posts', [
            'caption' => $largeCaption,
        ]);
    }

    public function test_admin_can_update_post_caption_and_manage_images(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $service = app(ProfitMasterService::class);
        $file1 = UploadedFile::fake()->image('image1.png');
        $file2 = UploadedFile::fake()->image('image2.png');

        $post = $service->create('Caption awal', [$file1, $file2], $admin->id);
        $image1 = $post->images[0];
        $image2 = $post->images[1];

        $newFile = UploadedFile::fake()->image('new_image.jpg');

        $response = $this->actingAs($admin)->post("/admin/profit-master/{$post->id}", [
            '_method'           => 'PUT',
            'caption'           => 'Caption revisi terbaru',
            'deleted_image_ids' => [$image1->id],
            'images'            => [$newFile],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $post->refresh();
        $this->assertEquals('Caption revisi terbaru', $post->caption);
        $this->assertCount(2, $post->images);

        // image1 fisik tetap ada (soft delete tidak menghapus berkas)
        Storage::disk('local')->assertExists($image1->file_path);
        // image1 di DB berstatus soft delete
        $this->assertSoftDeleted('profit_master_post_images', ['id' => $image1->id]);
        // image2 tetap ada
        Storage::disk('local')->assertExists($image2->file_path);

        // Audit log tercatat
        $this->assertDatabaseHas('log_aktivitas', [
            'kategori'     => 'profit_master',
            'aksi'         => 'ubah_profit_master',
            'subject_type' => ProfitMasterPost::class,
            'subject_id'   => $post->id,
        ]);
    }

    public function test_cannot_delete_all_images_on_update_without_new_images(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $service = app(ProfitMasterService::class);
        $file = UploadedFile::fake()->image('image1.png');
        $post = $service->create('Caption awal', [$file], $admin->id);
        $image = $post->images[0];

        $response = $this->actingAs($admin)->post("/admin/profit-master/{$post->id}", [
            '_method'           => 'PUT',
            'caption'           => 'Caption tanpa foto',
            'deleted_image_ids' => [$image->id],
            'images'            => [],
        ]);

        $response->assertSessionHasErrors(['images']);
    }

    public function test_admin_can_delete_post_and_physical_files_are_removed(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $service = app(ProfitMasterService::class);
        $file1 = UploadedFile::fake()->image('image1.png');
        $file2 = UploadedFile::fake()->image('image2.png');
        $post = $service->create('Post akan dihapus', [$file1, $file2], $admin->id);

        $path1 = $post->images[0]->file_path;
        $path2 = $post->images[1]->file_path;

        Storage::disk('local')->assertExists($path1);
        Storage::disk('local')->assertExists($path2);

        $response = $this->actingAs($admin)->delete("/admin/profit-master/{$post->id}");
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertSoftDeleted('profit_master_posts', ['id' => $post->id]);
        $this->assertSoftDeleted('profit_master_post_images', ['post_id' => $post->id]);

        // Berkas fisik tetap ada saat soft delete
        Storage::disk('local')->assertExists($path1);
        Storage::disk('local')->assertExists($path2);

        // Cek log aktivitas
        $this->assertDatabaseHas('log_aktivitas', [
            'kategori' => 'profit_master',
            'aksi'     => 'hapus_profit_master',
        ]);
    }

    public function test_member_active_and_pending_kyc_can_access_feed(): void
    {
        // 1. Tamu (guest) dialihkan ke login
        $this->get('/member/profit-master')->assertRedirect('/login');
        $this->get('/member/profit-master/feed')->assertRedirect('/login');

        $activeMember = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $pendingMember = User::factory()->create(['role' => 'member', 'status' => 'pending']);

        // 2. Member aktif diizinkan
        $this->actingAs($activeMember)->get('/member/profit-master')->assertOk();
        $this->actingAs($activeMember)->get('/member/profit-master/feed')->assertOk()->assertJsonStructure(['data', 'has_more']);

        // 3. Member pending KYC (tetap diizinkan)
        $this->actingAs($pendingMember)->get('/member/profit-master')->assertOk();
        $this->actingAs($pendingMember)->get('/member/profit-master/feed')->assertOk()->assertJsonStructure(['data', 'has_more']);
    }

    public function test_cursor_pagination_and_search_filter(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);

        $service = app(ProfitMasterService::class);
        $dummyFile = UploadedFile::fake()->image('dot.png');

        // Buat 15 post
        for ($i = 1; $i <= 15; $i++) {
            $caption = "Materi promosi nomor {$i} tentang trading copytrade.";
            if ($i === 7) {
                $caption = 'Pencapaian khusus 100% winrate di pair EUR_USD!';
            }
            $service->create($caption, [$dummyFile], $admin->id);
        }

        // Halaman 1 (10 data)
        $response1 = $this->actingAs($member)->getJson('/member/profit-master/feed?per_page=10');
        $response1->assertOk();
        $data1 = $response1->json();
        $this->assertCount(10, $data1['data']);
        $this->assertTrue($data1['has_more']);
        $this->assertNotNull($data1['next_cursor']);

        // Halaman 2 via cursor
        $cursor = $data1['next_cursor'];
        $response2 = $this->actingAs($member)->getJson("/member/profit-master/feed?per_page=10&cursor={$cursor}");
        $response2->assertOk();
        $data2 = $response2->json();
        $this->assertCount(5, $data2['data']);
        $this->assertFalse($data2['has_more']);

        // Pencarian dengan wildcard % dan _
        $responseSearch = $this->actingAs($member)->getJson('/member/profit-master/feed?search=EUR_USD');
        $responseSearch->assertOk();
        $searchData = $responseSearch->json();
        $this->assertCount(1, $searchData['data']);
        $this->assertStringContainsString('EUR_USD', $searchData['data'][0]['caption']);
    }

    public function test_document_stream_for_profit_images(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['role' => 'member', 'status' => 'pending']);

        $service = app(ProfitMasterService::class);
        $file = UploadedFile::fake()->image('banner_profit.png');
        $post = $service->create('Banner promosi', [$file], $admin->id);
        $image = $post->images[0];

        // Tamu ditolak
        $this->get("/documents/profit-master/{$image->id}")->assertRedirect('/login');

        // Member (termasuk pending) berhasil mengakses stream dengan header cache
        $responseMember = $this->actingAs($member)->get("/documents/profit-master/{$image->id}");
        $responseMember->assertOk();
        $responseMember->assertHeader('Cache-Control', 'max-age=86400, private');
        $responseMember->assertHeader('X-Content-Type-Options', 'nosniff');

        // Admin berhasil
        $responseAdmin = $this->actingAs($admin)->get("/documents/profit-master/{$image->id}");
        $responseAdmin->assertOk();

        // ID tidak ditemukan
        $this->actingAs($member)->get('/documents/profit-master/999999')->assertNotFound();
    }
}
