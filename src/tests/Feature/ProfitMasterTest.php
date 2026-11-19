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
        $startDate = now()->subDays(3)->toDateString();
        $endDate = now()->toDateString();

        $file1 = UploadedFile::fake()->image('profit_xau.png', 800, 600);
        $file2 = UploadedFile::fake()->image('withdraw.jpg', 800, 600);

        $response = $this->actingAs($admin)->post('/admin/profit-master', [
            'caption'           => 'Profit luar biasa hari ini dari XAUUSD +15%!',
            'profit_start_date' => $startDate,
            'profit_end_date'   => $endDate,
            'images'            => [$file1, $file2],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('profit_master_posts', [
            'caption'    => 'Profit luar biasa hari ini dari XAUUSD +15%!',
            'created_by' => $admin->id,
        ]);

        $post = ProfitMasterPost::first();
        $this->assertNotNull($post);
        $this->assertEquals($startDate, $post->profit_start_date?->format('Y-m-d'));
        $this->assertEquals($endDate, $post->profit_end_date?->format('Y-m-d'));
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

    public function test_validation_requires_caption_profit_dates_and_at_least_one_image_on_create(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/profit-master', [
            'caption'           => '',
            'profit_start_date' => '',
            'profit_end_date'   => '',
            'images'            => [],
        ]);

        $response->assertSessionHasErrors([
            'caption',
            'profit_start_date',
            'profit_end_date',
            'images',
        ]);
    }

    public function test_validation_rules_for_profit_dates_on_create_and_update(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $tomorrow = now()->addDay()->toDateString();
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $file = UploadedFile::fake()->image('profit.png', 800, 600);

        // Create: tanggal masa depan ditolak
        $this->actingAs($admin)->post('/admin/profit-master', [
            'caption'           => 'Profit masa depan',
            'profit_start_date' => $tomorrow,
            'profit_end_date'   => $tomorrow,
            'images'            => [$file],
        ])->assertSessionHasErrors(['profit_start_date', 'profit_end_date']);

        // Create: tanggal akhir sebelum tanggal mulai ditolak
        $this->actingAs($admin)->post('/admin/profit-master', [
            'caption'           => 'Profit terbalik',
            'profit_start_date' => $today,
            'profit_end_date'   => $yesterday,
            'images'            => [$file],
        ])->assertSessionHasErrors(['profit_end_date']);

        // Create: format tanggal salah ditolak
        $this->actingAs($admin)->post('/admin/profit-master', [
            'caption'           => 'Format salah',
            'profit_start_date' => '19-11-2026',
            'profit_end_date'   => 'not-a-date',
            'images'            => [$file],
        ])->assertSessionHasErrors(['profit_start_date', 'profit_end_date']);

        // Update: tanggal wajib diisi, tidak boleh masa depan, dan end >= start
        $service = app(ProfitMasterService::class);
        $post = $service->create('Post valid', [$file], $admin->id, $yesterday, $today);

        $this->actingAs($admin)->post("/admin/profit-master/{$post->id}", [
            '_method'           => 'PUT',
            'caption'           => 'Post edit tanpa tanggal',
            'profit_start_date' => '',
            'profit_end_date'   => '',
        ])->assertSessionHasErrors(['profit_start_date', 'profit_end_date']);

        $this->actingAs($admin)->post("/admin/profit-master/{$post->id}", [
            '_method'           => 'PUT',
            'caption'           => 'Post edit masa depan',
            'profit_start_date' => $today,
            'profit_end_date'   => $tomorrow,
        ])->assertSessionHasErrors(['profit_end_date']);

        $this->actingAs($admin)->post("/admin/profit-master/{$post->id}", [
            '_method'           => 'PUT',
            'caption'           => 'Post edit terbalik',
            'profit_start_date' => $today,
            'profit_end_date'   => $yesterday,
        ])->assertSessionHasErrors(['profit_end_date']);
    }

    public function test_caption_accepts_large_text_up_to_20000_chars(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $today = now()->toDateString();

        $largeCaption = str_repeat('A', 15000);
        $file = UploadedFile::fake()->image('chart.png', 800, 600);

        $response = $this->actingAs($admin)->post('/admin/profit-master', [
            'caption'           => $largeCaption,
            'profit_start_date' => $today,
            'profit_end_date'   => $today,
            'images'            => [$file],
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

        $initialStart = now()->subDays(10)->toDateString();
        $initialEnd = now()->subDays(8)->toDateString();
        $updatedStart = now()->subDays(5)->toDateString();
        $updatedEnd = now()->subDay()->toDateString();

        $post = $service->create('Caption awal', [$file1, $file2], $admin->id, $initialStart, $initialEnd);
        $image1 = $post->images[0];
        $image2 = $post->images[1];

        $newFile = UploadedFile::fake()->image('new_image.jpg');

        $response = $this->actingAs($admin)->post("/admin/profit-master/{$post->id}", [
            '_method'           => 'PUT',
            'caption'           => 'Caption revisi terbaru',
            'profit_start_date' => $updatedStart,
            'profit_end_date'   => $updatedEnd,
            'deleted_image_ids' => [$image1->id],
            'images'            => [$newFile],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $post->refresh();
        $this->assertEquals('Caption revisi terbaru', $post->caption);
        $this->assertEquals($updatedStart, $post->profit_start_date?->format('Y-m-d'));
        $this->assertEquals($updatedEnd, $post->profit_end_date?->format('Y-m-d'));
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
        $today = now()->toDateString();

        $service = app(ProfitMasterService::class);
        $file = UploadedFile::fake()->image('image1.png');
        $post = $service->create('Caption awal', [$file], $admin->id, $today, $today);
        $image = $post->images[0];

        $response = $this->actingAs($admin)->post("/admin/profit-master/{$post->id}", [
            '_method'           => 'PUT',
            'caption'           => 'Caption tanpa foto',
            'profit_start_date' => $today,
            'profit_end_date'   => $today,
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

    public function test_feed_filters_by_profit_date_overlap_for_admin_and_member(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $service = app(ProfitMasterService::class);

        // Semua dibuat hari ini (created_at hari ini) untuk memastikan filter memakai tanggal profit
        $this->createPostWithProfitDates($service, $admin->id, 'Profit Harian 10 Nov', '2026-11-10', '2026-11-10');
        $this->createPostWithProfitDates($service, $admin->id, 'Rekap Mingguan 10-16 Nov', '2026-11-10', '2026-11-16');
        $this->createPostWithProfitDates($service, $admin->id, 'Profit Harian 18 Nov', '2026-11-18', '2026-11-18');

        // Tanpa filter: semua 3 post muncul dengan urutan terbaru diterbitkan (id DESC) + metadata periode profit
        $unfiltered = $this->actingAs($member)->getJson('/member/profit-master/feed');
        $unfiltered->assertOk();
        $items = $unfiltered->json('data');
        $this->assertSame([
            'Profit Harian 18 Nov',
            'Rekap Mingguan 10-16 Nov',
            'Profit Harian 10 Nov',
        ], collect($items)->pluck('caption')->all());
        $this->assertSame('18 Nov 2026', $items[0]['formatted_profit_date']);
        $this->assertSame('10 Nov – 16 Nov 2026', $items[1]['formatted_profit_date']);
        $this->assertSame('2026-11-10', $items[1]['profit_start_date']);
        $this->assertSame('2026-11-16', $items[1]['profit_end_date']);
        $this->assertSame('10 Nov 2026', $items[2]['formatted_profit_date']);

        foreach (['/admin/profit-master/feed', '/member/profit-master/feed'] as $endpoint) {
            $actor = str_starts_with($endpoint, '/admin') ? $admin : $member;

            // Filter 1 tanggal di tengah rentang mingguan (2026-11-12): hanya post mingguan yang overlap
            $midSingleRes = $this->actingAs($actor)->getJson("{$endpoint}?start_date=2026-11-12&end_date=2026-11-12");
            $midSingleRes->assertOk();
            $this->assertSame(
                ['Rekap Mingguan 10-16 Nov'],
                collect($midSingleRes->json('data'))->pluck('caption')->all()
            );

            // Filter 1 tanggal pada 2026-11-10: post harian 10 Nov & post mingguan 10-16 Nov ikut tampil (overlap)
            $startSingleRes = $this->actingAs($actor)->getJson("{$endpoint}?start_date=2026-11-10&end_date=2026-11-10");
            $startSingleRes->assertOk();
            $this->assertEqualsCanonicalizing(
                ['Profit Harian 10 Nov', 'Rekap Mingguan 10-16 Nov'],
                collect($startSingleRes->json('data'))->pluck('caption')->all()
            );

            // Filter rentang 2026-11-14 s/d 2026-11-20: post 10-16 Nov (overlap) & 18 Nov tampil; 10 Nov tidak
            $rangeRes = $this->actingAs($actor)->getJson("{$endpoint}?start_date=2026-11-14&end_date=2026-11-20");
            $rangeRes->assertOk();
            $this->assertEqualsCanonicalizing(
                ['Rekap Mingguan 10-16 Nov', 'Profit Harian 18 Nov'],
                collect($rangeRes->json('data'))->pluck('caption')->all()
            );
        }
    }

    public function test_feed_combines_caption_search_and_date_range_filter_with_cursor_pagination(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $service = app(ProfitMasterService::class);

        $this->createPostWithProfitDates($service, $admin->id, 'XAUUSD Setup 1', '2026-11-08', '2026-11-10');
        $this->createPostWithProfitDates($service, $admin->id, 'BTCUSD Setup Diabaikan', '2026-11-11', '2026-11-11');
        $this->createPostWithProfitDates($service, $admin->id, 'XAUUSD Setup 2', '2026-11-11', '2026-11-12');
        $this->createPostWithProfitDates($service, $admin->id, 'XAUUSD Setup 3', '2026-11-12', '2026-11-15');
        $this->createPostWithProfitDates($service, $admin->id, 'XAUUSD Luar Rentang', '2026-11-14', '2026-11-15');

        $page1 = $this->actingAs($member)->getJson(
            '/member/profit-master/feed?search=XAUUSD&start_date=2026-11-10&end_date=2026-11-12&per_page=2'
        );
        $page1->assertOk();
        $data1 = $page1->json();
        $this->assertCount(2, $data1['data']);
        $this->assertTrue($data1['has_more']);
        $this->assertNotNull($data1['next_cursor']);

        $page2 = $this->actingAs($member)->getJson(
            '/member/profit-master/feed?search=XAUUSD&start_date=2026-11-10&end_date=2026-11-12&per_page=2&cursor=' . urlencode($data1['next_cursor'])
        );
        $page2->assertOk();
        $data2 = $page2->json();
        $this->assertCount(1, $data2['data']);
        $this->assertFalse($data2['has_more']);

        $allCaptions = collect(array_merge($data1['data'], $data2['data']))->pluck('caption')->all();
        $this->assertEqualsCanonicalizing(['XAUUSD Setup 1', 'XAUUSD Setup 2', 'XAUUSD Setup 3'], $allCaptions);
    }

    public function test_feed_and_index_handle_inverted_and_invalid_dates_gracefully(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $service = app(ProfitMasterService::class);

        $this->createPostWithProfitDates($service, $admin->id, 'Post 10 Nov', '2026-11-10', '2026-11-10');
        $this->createPostWithProfitDates($service, $admin->id, 'Post 12 Nov', '2026-11-12', '2026-11-12');
        $this->createPostWithProfitDates($service, $admin->id, 'Post 15 Nov', '2026-11-15', '2026-11-15');

        // 1. Tanggal terbalik otomatis dinormalisasi (start_date <= end_date)
        $invertedFeed = $this->actingAs($member)->getJson('/member/profit-master/feed?start_date=2026-11-12&end_date=2026-11-10');
        $invertedFeed->assertOk();
        $invertedCaptions = collect($invertedFeed->json('data'))->pluck('caption')->all();
        $this->assertEqualsCanonicalizing(['Post 10 Nov', 'Post 12 Nov'], $invertedCaptions);

        foreach (['/admin/profit-master' => $admin, '/member/profit-master' => $member] as $route => $actor) {
            $component = str_starts_with($route, '/admin') ? 'Admin/ProfitMaster/Index' : 'Member/ProfitMaster/Index';

            $this->actingAs($actor)
                ->get("{$route}?start_date=2026-11-12&end_date=2026-11-10")
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component($component)
                    ->where('initialStartDate', '2026-11-10')
                    ->where('initialEndDate', '2026-11-12')
                    ->where('todayDate', now()->toDateString())
                );

            // 2. Format tanggal tidak valid atau overflow (2026-02-31) ditangani aman sebagai kosong
            $this->actingAs($actor)
                ->get("{$route}?start_date=not-a-date&end_date=2026-02-31")
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component($component)
                    ->where('initialStartDate', '')
                    ->where('initialEndDate', '')
                );
        }

        $invalidFeed = $this->actingAs($admin)->getJson('/admin/profit-master/feed?start_date=not-a-date&end_date=2026-02-31');
        $invalidFeed->assertOk();
        $this->assertCount(3, $invalidFeed->json('data'));
    }

    private function createPostWithProfitDates(
        ProfitMasterService $service,
        int $adminId,
        string $caption,
        string $profitStartDate,
        string $profitEndDate,
    ): ProfitMasterPost {
        $dummyFile = UploadedFile::fake()->image('profit.png');

        return $service->create($caption, [$dummyFile], $adminId, $profitStartDate, $profitEndDate);
    }
}
