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
use App\Models\PengaturanMasterFee;
use App\Models\ProfitMasterPost;
use App\Models\ProfitMasterPostImage;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\MasterFeeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'     => 'administrator',
            'username' => 'admin_test',
            'status'   => 'active',
        ]);

        $this->member = User::factory()->create([
            'role'     => 'member',
            'username' => 'member_test',
            'status'   => 'active',
        ]);
    }

    public function test_glossary_soft_delete_records_deleted_by_and_hidden_from_list(): void
    {
        $item = EdukasiGlosarium::create([
            'istilah'    => 'Pip Spread',
            'definisi'   => 'Selisih harga beli dan jual.',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.education.glossary.destroy', $item->id));

        $response->assertRedirect(route('admin.education.glossary.index'));

        $this->assertSoftDeleted('edukasi_glosarium', ['id' => $item->id]);

        $item->refresh();
        $this->assertEquals('admin_test', $item->deleted_by);
        $this->assertNotNull($item->deleted_at);

        // Tidak muncul di query reguler
        $this->assertCount(0, EdukasiGlosarium::where('istilah', 'Pip Spread')->get());
    }

    public function test_batch_delete_glossary_fills_deleted_by_and_counts(): void
    {
        $item1 = EdukasiGlosarium::create(['istilah' => 'Item 1', 'definisi' => 'Def 1']);
        $item2 = EdukasiGlosarium::create(['istilah' => 'Item 2', 'definisi' => 'Def 2']);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.education.glossary.batch-destroy'), [
                'ids' => [$item1->id, $item2->id],
            ]);

        $response->assertSessionHas('success', '2 istilah glosarium berhasil dihapus.');

        $this->assertSoftDeleted('edukasi_glosarium', ['id' => $item1->id]);
        $this->assertSoftDeleted('edukasi_glosarium', ['id' => $item2->id]);

        $item1->refresh();
        $item2->refresh();
        $this->assertEquals('admin_test', $item1->deleted_by);
        $this->assertEquals('admin_test', $item2->deleted_by);
    }

    public function test_category_deletion_cascades_to_articles_with_identical_deleted_at_and_slug_renamed(): void
    {
        $category = PanduanKategori::create([
            'nama'      => 'Panduan Trading',
            'slug'      => 'panduan-trading',
            'is_active' => true,
        ]);

        $article = PanduanBantuan::create([
            'kategori_id'  => $category->id,
            'judul'        => 'Cara Buka Posisi',
            'slug'         => 'cara-buka-posisi',
            'konten'       => 'Langkah detail...',
            'is_published' => true,
        ]);

        // Hapus kategori langsung (triggering cascade)
        $category->delete();

        $this->assertSoftDeleted('panduan_kategori', ['id' => $category->id]);
        $this->assertSoftDeleted('panduan_bantuan', ['id' => $article->id]);

        $category->refresh();
        $article->refresh();

        // deleted_at wajib identik
        $this->assertEquals($category->deleted_at->format('Y-m-d H:i:s'), $article->deleted_at->format('Y-m-d H:i:s'));

        // Slug diubah menjadi --trashed-{id}
        $this->assertEquals("panduan-trading--trashed-{$category->id}", $category->slug);
        $this->assertEquals("cara-buka-posisi--trashed-{$article->id}", $article->slug);

        // Slug asli dapat didaftarkan kembali tanpa bentrok
        $newCategory = PanduanKategori::create([
            'nama'      => 'Panduan Trading Baru',
            'slug'      => 'panduan-trading',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('panduan_kategori', ['id' => $newCategory->id, 'slug' => 'panduan-trading']);
    }

    public function test_faq_topic_deletion_cascades_to_faqs_with_identical_deleted_at(): void
    {
        $topic = EdukasiFaqTopik::create([
            'nama'      => 'Topik Umum',
            'is_active' => true,
        ]);

        $faq = EdukasiFaq::create([
            'topik_id'   => $topic->id,
            'pertanyaan' => 'Apa itu Forex?',
            'jawaban'    => 'Forex adalah...',
            'is_active'  => true,
        ]);

        $topic->delete();

        $this->assertSoftDeleted('edukasi_faq_topik', ['id' => $topic->id]);
        $this->assertSoftDeleted('edukasi_faq', ['id' => $faq->id]);

        $topic->refresh();
        $faq->refresh();

        $this->assertEquals($topic->deleted_at->format('Y-m-d H:i:s'), $faq->deleted_at->format('Y-m-d H:i:s'));
    }

    public function test_batch_delete_faqs_fills_deleted_by(): void
    {
        $topic = EdukasiFaqTopik::create(['nama' => 'Topik', 'is_active' => true]);
        $faq1 = EdukasiFaq::create(['topik_id' => $topic->id, 'pertanyaan' => 'Q1', 'jawaban' => 'A1']);
        $faq2 = EdukasiFaq::create(['topik_id' => $topic->id, 'pertanyaan' => 'Q2', 'jawaban' => 'A2']);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.education.faq.batch-destroy'), [
                'ids' => [$faq1->id, $faq2->id],
            ]);

        $response->assertSessionHas('success', '2 pertanyaan FAQ berhasil dihapus.');

        $this->assertSoftDeleted('edukasi_faq', ['id' => $faq1->id]);
        $this->assertSoftDeleted('edukasi_faq', ['id' => $faq2->id]);

        $faq1->refresh();
        $faq2->refresh();
        $this->assertEquals('admin_test', $faq1->deleted_by);
        $this->assertEquals('admin_test', $faq2->deleted_by);
    }

    public function test_batch_delete_invitations_fills_deleted_by(): void
    {
        $inv1 = Invitation::create([
            'token'          => 'token-1',
            'label'          => 'Undangan 1',
            'kuota_maksimal' => 5,
        ]);
        $inv2 = Invitation::create([
            'token'          => 'token-2',
            'label'          => 'Undangan 2',
            'kuota_maksimal' => 5,
        ]);

        $this->actingAs($this->admin);

        $service = app(InvitationService::class);
        $count = $service->batchDeleteInvitations([$inv1->invitation_id, $inv2->invitation_id]);

        $this->assertEquals(2, $count);
        $this->assertSoftDeleted('invitations', ['invitation_id' => $inv1->invitation_id]);
        $this->assertSoftDeleted('invitations', ['invitation_id' => $inv2->invitation_id]);

        $inv1->refresh();
        $inv2->refresh();
        $this->assertEquals('admin_test', $inv1->deleted_by);
        $this->assertEquals('admin_test', $inv2->deleted_by);
    }

    public function test_profit_master_soft_delete_cascades_to_images_and_preserves_files(): void
    {
        Storage::fake('local');

        $post = ProfitMasterPost::create([
            'caption'    => 'Profit luar biasa',
            'created_by' => $this->admin->id,
        ]);

        $filePath = "profit_master/{$post->id}/test.jpg";
        Storage::disk('local')->put($filePath, 'image content');

        $img = ProfitMasterPostImage::create([
            'post_id'   => $post->id,
            'file_path' => $filePath,
            'file_name' => 'test.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'urutan'    => 1,
        ]);

        $this->actingAs($this->admin);
        $post->delete();

        $this->assertSoftDeleted('profit_master_posts', ['id' => $post->id]);
        $this->assertSoftDeleted('profit_master_post_images', ['id' => $img->id]);

        // Berkas fisik tetap ada saat soft delete
        Storage::disk('local')->assertExists($filePath);
    }

    public function test_profit_report_soft_delete_preserves_files_and_allows_recreating_same_period(): void
    {
        Storage::fake('local');

        PengaturanMasterFee::create([
            'fee_persen'     => 30.00,
            'wd_maks_persen' => 60.00,
        ]);

        $akun = AkunCopytrade::create([
            'user_id'            => $this->member->id,
            'tier'               => 'tier_1',
            'broker'             => 'Broker Test',
            'nomor_akun_trading' => '12345678',
            'tipe_akun'          => 'standard',
            'modal_riil'         => 1000.00,
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Member Test',
            'tanggal_mulai'      => Carbon::now()->subMonths(2)->startOfMonth(),
            'myfxbook_url'       => 'https://myfxbook.com/test',
            'status'             => 'active',
        ]);

        $service = app(MasterFeeService::class);

        // 1. Submit report pertama (dengan profit > 0 agar berstatus belum_dibayar dan editable)
        $myfxbookFile = UploadedFile::fake()->create('myfxbook.jpg', 200, 'image/jpeg');
        $wdFile = UploadedFile::fake()->create('wd.jpg', 200, 'image/jpeg');
        $report1 = $service->submitReport($akun, [
            'profit_usd' => 100,
            'wd_usd'     => 50,
            'wd_idr'     => 800000,
        ], $myfxbookFile, $wdFile, $this->member);

        $this->assertEquals(1, $report1->periode_ke);
        $this->assertEquals('MFE-' . Carbon::now()->format('Ym') . '-0001', $report1->nomor);

        $filePath = $report1->bukti_myfxbook_path;
        Storage::disk('local')->assertExists($filePath);

        // 2. Member soft-delete laporan
        $this->actingAs($this->member);
        $service->deleteReport($report1, $this->member);

        $this->assertSoftDeleted('laporan_profit_bulanan', ['id' => $report1->id]);
        $report1->refresh();
        $this->assertEquals('member_test', $report1->deleted_by);

        // Berkas fisik tetap ada
        Storage::disk('local')->assertExists($filePath);

        // 3. Member input ulang periode yang sama: nomor urut baru tidak bentrok
        $myfxbookFile2 = UploadedFile::fake()->create('myfxbook2.jpg', 200, 'image/jpeg');
        $wdFile2 = UploadedFile::fake()->create('wd2.jpg', 200, 'image/jpeg');
        $report2 = $service->submitReport($akun, [
            'profit_usd' => 100,
            'wd_usd'     => 50,
            'wd_idr'     => 800000,
        ], $myfxbookFile2, $wdFile2, $this->member);

        $this->assertEquals(1, $report2->periode_ke);
        $this->assertEquals('MFE-' . Carbon::now()->format('Ym') . '-0002', $report2->nomor);
    }

    public function test_commission_and_dashboard_ignore_soft_deleted_reports(): void
    {
        $akun = AkunCopytrade::create([
            'user_id'            => $this->member->id,
            'tier'               => 'tier_1',
            'broker'             => 'Broker Test',
            'nomor_akun_trading' => '99887766',
            'tipe_akun'          => 'standard',
            'modal_riil'         => 1000.00,
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Member Test',
            'tanggal_mulai'      => Carbon::now()->subMonths(2)->startOfMonth(),
            'myfxbook_url'       => 'https://myfxbook.com/test',
            'status'             => 'active',
        ]);

        $report = LaporanProfitBulanan::create([
            'nomor'               => 'MFE-202611-9999',
            'akun_copytrade_id'   => $akun->id,
            'user_id'             => $this->member->id,
            'periode_ke'          => 1,
            'periode_awal'        => Carbon::now()->subMonths(2)->startOfMonth(),
            'periode_akhir'       => Carbon::now()->subMonths(2)->endOfMonth(),
            'jatuh_tempo'         => Carbon::now()->subMonth()->startOfMonth(),
            'profit_usd'          => 100,
            'bukti_myfxbook_path' => 'test/mb.jpg',
            'fee_persen'          => 30,
            'fee_usd'             => 30,
            'fee_idr'             => 480000,
            'status'              => LaporanProfitBulanan::STATUS_LUNAS,
        ]);

        $this->assertEquals(1, LaporanProfitBulanan::where('akun_copytrade_id', $akun->id)->count());

        $report->delete();

        // Query default otomatis mengabaikan data terhapus
        $this->assertEquals(0, LaporanProfitBulanan::where('akun_copytrade_id', $akun->id)->count());
        $this->assertEquals(1, LaporanProfitBulanan::withTrashed()->where('akun_copytrade_id', $akun->id)->count());
    }
}
