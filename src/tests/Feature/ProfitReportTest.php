<?php

namespace Tests\Feature;

use App\Models\LaporanKeuntungan;
use App\Models\LaporanKeuntunganGambar;
use App\Models\PengaturanMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfitReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $affiliator;

    private User $investor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        PengaturanMaster::create([
            'link_referral_broker'    => 'https://broker.example.com',
            'link_master_akun'        => 'https://mql5.example.com',
            'tier_1_min'              => 100,
            'tier_2_min'              => 5000,
            'tier_3_min'              => 300000,
            'kurs_usd_idr'            => 16796.00,
            'kurs_usd_idr_updated_at' => now(),
        ]);

        $this->admin = User::create([
            'name'      => 'Admin Test',
            'username'  => 'admin_test',
            'email'     => 'admin_test@yzd.test',
            'password'  => bcrypt('password'),
            'role'      => 'administrator',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->affiliator = User::create([
            'name'          => 'Affiliator Test',
            'username'      => 'affiliator_test',
            'email'         => 'affiliator_test@yzd.test',
            'password'      => bcrypt('password'),
            'role'          => 'affiliator',
            'status'        => 'active',
            'is_active'     => true,
            'referral_code' => 'AFF999',
        ]);

        $this->investor = User::create([
            'name'      => 'Investor Test',
            'username'  => 'investor_test',
            'email'     => 'investor_test@yzd.test',
            'password'  => bcrypt('password'),
            'role'      => 'investor',
            'status'    => 'active',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_profit_reports_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.profit_reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/ProfitReports/Index')
            ->has('reports')
            ->has('statistics')
        );
    }

    public function test_admin_can_upload_monthly_profit_report_with_multiple_images(): void
    {
        $image1 = UploadedFile::fake()->image('kalender_januari.png', 800, 600);
        $image2 = UploadedFile::fake()->image('grafik_profit.png', 800, 600);

        $response = $this->actingAs($this->admin)->post(route('admin.profit_reports.store'), [
            'tipe'               => 'bulanan',
            'judul'              => 'Kalender Profit Januari 2026',
            'bulan'              => 1,
            'tahun'              => 2026,
            'profit_persen'      => 18.40,
            'profit_nominal_usd' => 1840.50,
            'is_kalender'        => true,
            'deskripsi'          => 'Hasil trading bulan Januari sangat memuaskan.',
            'status'             => 'published',
            'images'             => [$image1, $image2],
            'captions'           => ['Kalender Myfxbook', 'Kurva Ekuitas'],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('laporan_keuntungan', [
            'tipe'          => 'bulanan',
            'judul'         => 'Kalender Profit Januari 2026',
            'bulan'         => 1,
            'tahun'         => 2026,
            'profit_persen' => 18.40,
            'is_kalender'   => true,
            'status'        => 'published',
        ]);

        $report = LaporanKeuntungan::first();
        $this->assertNotNull($report);
        $this->assertCount(2, $report->gambar);

        $gambar1 = $report->gambar()->where('caption', 'Kalender Myfxbook')->first();
        $this->assertNotNull($gambar1);
        Storage::disk('local')->assertExists($gambar1->file_path);
    }

    public function test_admin_can_upload_daily_profit_report(): void
    {
        $image = UploadedFile::fake()->image('daily_25sept.png', 600, 400);

        $response = $this->actingAs($this->admin)->post(route('admin.profit_reports.store'), [
            'tipe'               => 'harian',
            'judul'              => 'Profit Harian 25 Sept 2026',
            'tanggal'            => '2026-09-25',
            'profit_persen'      => 2.10,
            'profit_nominal_usd' => 210.00,
            'is_kalender'        => false,
            'deskripsi'          => 'Profit harian sesi London & New York',
            'status'             => 'published',
            'images'             => [$image],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('laporan_keuntungan', [
            'tipe'          => 'harian',
            'judul'         => 'Profit Harian 25 Sept 2026',
            'profit_persen' => 2.10,
            'status'        => 'published',
        ]);
    }

    public function test_admin_can_update_report_and_delete_image(): void
    {
        $image1 = UploadedFile::fake()->image('foto1.png');
        $image2 = UploadedFile::fake()->image('foto2.png');

        $this->actingAs($this->admin)->post(route('admin.profit_reports.store'), [
            'tipe'          => 'bulanan',
            'judul'         => 'Laporan Awal',
            'bulan'         => 3,
            'tahun'         => 2026,
            'profit_persen' => 10.00,
            'status'        => 'published',
            'images'        => [$image1, $image2],
        ]);

        $report = LaporanKeuntungan::with('gambar')->first();
        $this->assertCount(2, $report->gambar);
        $firstImageId = $report->gambar[0]->id;

        // Update laporan dan hapus gambar pertama
        $newImage = UploadedFile::fake()->image('foto_baru.png');
        $response = $this->actingAs($this->admin)->post(route('admin.profit_reports.update', $report->id), [
            'tipe'              => 'bulanan',
            'judul'             => 'Laporan Diperbarui',
            'bulan'             => 3,
            'tahun'             => 2026,
            'profit_persen'     => 12.50,
            'status'            => 'published',
            'deleted_image_ids' => [$firstImageId],
            'images'            => [$newImage],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('laporan_keuntungan', [
            'id'            => $report->id,
            'judul'         => 'Laporan Diperbarui',
            'profit_persen' => 12.50,
        ]);

        $this->assertDatabaseMissing('laporan_keuntungan_gambar', [
            'id' => $firstImageId,
        ]);

        $freshReport = $report->fresh(['gambar']);
        $this->assertCount(2, $freshReport->gambar); // 1 lama tersisa + 1 baru
    }

    public function test_admin_can_delete_profit_report(): void
    {
        $image = UploadedFile::fake()->image('foto.png');
        $this->actingAs($this->admin)->post(route('admin.profit_reports.store'), [
            'tipe'   => 'bulanan',
            'judul'  => 'Laporan Akan Dihapus',
            'bulan'  => 4,
            'tahun'  => 2026,
            'status' => 'published',
            'images' => [$image],
        ]);

        $report = LaporanKeuntungan::first();
        $gambar = $report->gambar->first();

        $response = $this->actingAs($this->admin)->delete(route('admin.profit_reports.destroy', $report->id));
        $response->assertRedirect();

        $this->assertDatabaseMissing('laporan_keuntungan', ['id' => $report->id]);
        $this->assertDatabaseMissing('laporan_keuntungan_gambar', ['id' => $gambar->id]);
        Storage::disk('local')->assertMissing($gambar->file_path);
    }

    public function test_affiliator_can_view_promotions_index_with_referral_link(): void
    {
        LaporanKeuntungan::create([
            'tipe'          => 'bulanan',
            'judul'         => 'Materi Promosi Maret 2026',
            'bulan'         => 3,
            'tahun'         => 2026,
            'profit_persen' => 15.00,
            'status'        => 'published',
            'created_by'    => $this->admin->id,
        ]);

        $response = $this->actingAs($this->affiliator)->get(route('investor.promotions.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Promotions/Index')
            ->has('reports.data', 1)
            ->where('referralCode', 'AFF999')
            ->where('isAffiliator', true)
        );
    }

    public function test_investor_cannot_view_promotions_index(): void
    {
        $response = $this->actingAs($this->investor)->get(route('investor.promotions.index'));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_stream_and_download_image(): void
    {
        $file = UploadedFile::fake()->image('bukti_profit.png');
        $filePath = $file->storeAs('profit_reports/1', 'bukti_profit.png', 'local');

        $report = LaporanKeuntungan::create([
            'tipe'       => 'harian',
            'judul'      => 'Profit Harian',
            'tanggal'    => '2026-09-25',
            'status'     => 'published',
            'created_by' => $this->admin->id,
        ]);

        $gambar = LaporanKeuntunganGambar::create([
            'laporan_keuntungan_id' => $report->id,
            'file_path'             => $filePath,
            'file_name'             => 'bukti_profit.png',
            'file_size'             => 1024,
            'mime_type'             => 'image/png',
        ]);

        // Stream view
        $viewResponse = $this->actingAs($this->affiliator)
            ->get(route('documents.profit_report.view', ['imageId' => $gambar->id]));
        $viewResponse->assertStatus(200);

        // Download
        $downloadResponse = $this->actingAs($this->affiliator)
            ->get(route('documents.profit_report.download', ['imageId' => $gambar->id]));
        $downloadResponse->assertStatus(200);
    }

    public function test_non_admin_cannot_upload_profit_report(): void
    {
        $response = $this->actingAs($this->affiliator)->post(route('admin.profit_reports.store'), [
            'tipe'  => 'harian',
            'judul' => 'Coba Upload',
        ]);

        $response->assertStatus(403);
    }
}
