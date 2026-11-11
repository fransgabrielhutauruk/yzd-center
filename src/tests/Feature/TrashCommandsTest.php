<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\EdukasiFaq;
use App\Models\EdukasiFaqTopik;
use App\Models\EdukasiGlosarium;
use App\Models\Invitation;
use App\Models\LaporanProfitBulanan;
use App\Models\PanduanBantuan;
use App\Models\PanduanKategori;
use App\Models\ProfitMasterPost;
use App\Models\ProfitMasterPostImage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrashCommandsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'     => 'administrator',
            'username' => 'admin_artisan',
            'status'   => 'active',
        ]);

        $this->member = User::factory()->create([
            'role'     => 'member',
            'username' => 'member_artisan',
            'status'   => 'active',
        ]);
    }

    public function test_trash_list_displays_trashed_items_and_filters_by_model(): void
    {
        $glosarium = EdukasiGlosarium::create(['istilah' => 'Spread Forex', 'definisi' => 'Selisih harga']);
        $glosarium->delete();

        $invitation = Invitation::create(['token' => 'inv-trash-1', 'label' => 'Undangan VIP', 'kuota_maksimal' => 10]);
        $invitation->delete();

        // 1. Tampilkan semua
        $this->artisan('app:trash:list')
            ->expectsOutputToContain('Spread Forex')
            ->expectsOutputToContain('Undangan VIP')
            ->assertSuccessful();

        // 2. Filter per model
        $this->artisan('app:trash:list', ['model' => 'glosarium'])
            ->expectsOutputToContain('Spread Forex')
            ->doesntExpectOutputToContain('Undangan VIP')
            ->assertSuccessful();

        // 3. Modul tidak valid
        $this->artisan('app:trash:list', ['model' => 'invalid-model'])
            ->assertFailed();
    }

    public function test_trash_restore_restores_parent_and_cascaded_children_with_same_deleted_at_only(): void
    {
        $category = PanduanKategori::create([
            'nama'      => 'Kategori Uji',
            'slug'      => 'kategori-uji',
            'is_active' => true,
        ]);

        $article1 = PanduanBantuan::create([
            'kategori_id'  => $category->id,
            'judul'        => 'Artikel Bersama',
            'slug'         => 'artikel-bersama',
            'konten'       => 'Konten 1',
            'is_published' => true,
        ]);

        $article2 = PanduanBantuan::create([
            'kategori_id'  => $category->id,
            'judul'        => 'Artikel Terpisah',
            'slug'         => 'artikel-terpisah',
            'konten'       => 'Konten 2',
            'is_published' => true,
        ]);

        // Hapus artikel 2 lebih dulu di masa lalu
        $article2->delete();
        $article2->deleted_at = Carbon::now()->subMinutes(10);
        $article2->saveQuietly();

        // Hapus kategori (otomatis cascade ke artikel 1 dengan deleted_at sama)
        $category->delete();

        $this->assertSoftDeleted('panduan_kategori', ['id' => $category->id]);
        $this->assertSoftDeleted('panduan_bantuan', ['id' => $article1->id]);
        $this->assertSoftDeleted('panduan_bantuan', ['id' => $article2->id]);

        // Pulihkan kategori via Artisan
        $this->artisan('app:trash:restore', [
            'model' => 'panduan-kategori',
            'id'    => $category->id,
        ])->assertSuccessful();

        // Kategori pulih
        $this->assertNotSoftDeleted('panduan_kategori', ['id' => $category->id]);
        $category->refresh();
        $this->assertEquals('kategori-uji', $category->slug);

        // Artikel 1 ikut pulih
        $this->assertNotSoftDeleted('panduan_bantuan', ['id' => $article1->id]);
        $article1->refresh();
        $this->assertEquals('artikel-bersama', $article1->slug);

        // Artikel 2 TETAP terhapus karena dihapus terpisah (deleted_at berbeda)
        $this->assertSoftDeleted('panduan_bantuan', ['id' => $article2->id]);
    }

    public function test_trash_restore_rejects_restoring_child_if_parent_is_trashed(): void
    {
        $topic = EdukasiFaqTopik::create([
            'nama'      => 'Topik Induk',
            'is_active' => true,
        ]);

        $faq = EdukasiFaq::create([
            'topik_id'   => $topic->id,
            'pertanyaan' => 'Pertanyaan Terikat',
            'jawaban'    => 'Jawaban Terikat',
            'is_active'  => true,
        ]);

        // Hapus topik sehingga topik dan FAQ terhapus
        $topic->delete();

        $this->assertSoftDeleted('edukasi_faq_topik', ['id' => $topic->id]);
        $this->assertSoftDeleted('edukasi_faq', ['id' => $faq->id]);

        // Mencoba memulihkan FAQ secara terpisah ketika topik masih terhapus
        $this->artisan('app:trash:restore', [
            'model' => 'faq',
            'id'    => $faq->id,
        ])
            ->expectsOutputToContain('karena Topik FAQ induknya')
            ->assertFailed();

        $this->assertSoftDeleted('edukasi_faq', ['id' => $faq->id]);
    }

    public function test_trash_restore_rejects_if_slug_conflicts(): void
    {
        $category = PanduanKategori::create([
            'nama'      => 'Panduan Forex',
            'slug'      => 'panduan-forex',
            'is_active' => true,
        ]);

        $category->delete();
        $this->assertSoftDeleted('panduan_kategori', ['id' => $category->id]);

        // Buat kategori baru dengan slug yang sama persis
        PanduanKategori::create([
            'nama'      => 'Panduan Forex Baru',
            'slug'      => 'panduan-forex',
            'is_active' => true,
        ]);

        // Coba pulihkan kategori lama
        $this->artisan('app:trash:restore', [
            'model' => 'panduan-kategori',
            'id'    => $category->id,
        ])
            ->expectsOutputToContain("Slug 'panduan-forex' sudah digunakan")
            ->assertFailed();

        $this->assertSoftDeleted('panduan_kategori', ['id' => $category->id]);
    }

    public function test_trash_restore_rejects_profit_report_if_active_period_already_exists(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->member->id,
            'tier'               => 'tier_1',
            'broker'             => 'Broker Test',
            'nomor_akun_trading' => '77788899',
            'tipe_akun'          => 'standard',
            'modal_riil'         => 1000.00,
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Member Test',
            'tanggal_mulai'      => Carbon::now()->subMonths(2)->startOfMonth(),
            'myfxbook_url'       => 'https://myfxbook.com/test',
            'status'             => 'active',
        ]);

        $report1 = LaporanProfitBulanan::create([
            'nomor'               => 'MFE-202611-0001',
            'akun_copytrade_id'   => $akun->id,
            'user_id'             => $this->member->id,
            'periode_ke'          => 1,
            'periode_awal'        => Carbon::now()->subMonths(2)->startOfMonth(),
            'periode_akhir'       => Carbon::now()->subMonths(2)->endOfMonth(),
            'jatuh_tempo'         => Carbon::now()->subMonth()->startOfMonth(),
            'profit_usd'          => 100,
            'bukti_myfxbook_path' => 'path/mb.jpg',
            'fee_persen'          => 30,
            'fee_usd'             => 30,
            'fee_idr'             => 480000,
            'status'              => LaporanProfitBulanan::STATUS_BELUM_DIBAYAR,
        ]);

        $report1->delete();
        $this->assertSoftDeleted('laporan_profit_bulanan', ['id' => $report1->id]);

        // Buat laporan baru aktif untuk akun dan periode_ke yang sama
        $report2 = LaporanProfitBulanan::create([
            'nomor'               => 'MFE-202611-0002',
            'akun_copytrade_id'   => $akun->id,
            'user_id'             => $this->member->id,
            'periode_ke'          => 1,
            'periode_awal'        => Carbon::now()->subMonths(2)->startOfMonth(),
            'periode_akhir'       => Carbon::now()->subMonths(2)->endOfMonth(),
            'jatuh_tempo'         => Carbon::now()->subMonth()->startOfMonth(),
            'profit_usd'          => 200,
            'bukti_myfxbook_path' => 'path/mb2.jpg',
            'fee_persen'          => 30,
            'fee_usd'             => 60,
            'fee_idr'             => 960000,
            'status'              => LaporanProfitBulanan::STATUS_BELUM_DIBAYAR,
        ]);

        // Coba pulihkan report 1
        $this->artisan('app:trash:restore', [
            'model' => 'laporan-profit',
            'id'    => $report1->id,
        ])
            ->expectsOutputToContain('sudah ada laporan aktif')
            ->assertFailed();

        $this->assertSoftDeleted('laporan_profit_bulanan', ['id' => $report1->id]);
    }

    public function test_trash_purge_requires_days_option(): void
    {
        $this->artisan('app:trash:purge')
            ->expectsOutputToContain('Parameter --days wajib diisi secara eksplisit')
            ->assertFailed();
    }

    public function test_trash_purge_asks_confirmation_without_force_and_can_cancel(): void
    {
        $item = EdukasiGlosarium::create(['istilah' => 'Test Glosarium', 'definisi' => 'Def']);
        $item->delete();

        $this->artisan('app:trash:purge', ['--days' => '0'])
            ->expectsConfirmation('Apakah Anda yakin ingin melanjutkan pembersihan permanen ini? Data dan berkas fisik tidak dapat dipulihkan.', 'no')
            ->expectsOutputToContain('Pembersihan data dibatalkan.')
            ->assertSuccessful();

        $this->assertSoftDeleted('edukasi_glosarium', ['id' => $item->id]);
    }

    public function test_trash_purge_with_force_permanently_deletes_records_and_physical_files(): void
    {
        Storage::fake('local');

        $post = ProfitMasterPost::create([
            'caption'    => 'Promosi Purge',
            'created_by' => $this->admin->id,
        ]);

        $filePath = "profit_master/{$post->id}/purge_test.jpg";
        Storage::disk('local')->put($filePath, 'purge content');

        $image = ProfitMasterPostImage::create([
            'post_id'   => $post->id,
            'file_path' => $filePath,
            'file_name' => 'purge_test.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'urutan'    => 1,
        ]);

        // Soft delete post & image
        $post->delete();

        $this->assertSoftDeleted('profit_master_posts', ['id' => $post->id]);
        $this->assertSoftDeleted('profit_master_post_images', ['id' => $image->id]);
        Storage::disk('local')->assertExists($filePath);

        // Jalankan purge --days=0 --force
        $this->artisan('app:trash:purge', [
            '--days'  => '0',
            '--force' => true,
            '--model' => 'profit-master',
        ])
            ->expectsOutputToContain('Pembersihan permanen selesai')
            ->assertSuccessful();

        // Data terhapus permanen dari DB
        $this->assertDatabaseMissing('profit_master_posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('profit_master_post_images', ['id' => $image->id]);

        // Berkas fisik & foldernya terhapus dari disk
        Storage::disk('local')->assertMissing($filePath);
    }
}
