<?php

namespace Tests\Feature;

use App\Models\EdukasiFaq;
use App\Models\EdukasiFaqTopik;
use App\Models\EdukasiGlosarium;
use App\Models\PanduanBantuan;
use App\Models\PanduanKategori;
use App\Models\PengaturanMaster;
use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EducationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $investor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => 'administrator',
            'status' => 'active',
        ]);

        $this->investor = User::factory()->create([
            'role'   => 'investor',
            'status' => 'pending', // Bahkan pending investor dapat mengakses edukasi & kalkulator
        ]);
    }

    public function test_investor_can_view_glossary_ordered_alphabetically_and_filter_by_letter(): void
    {
        EdukasiGlosarium::create([
            'istilah'  => 'Lot',
            'definisi' => 'Ukuran volume standar.',
        ]);

        EdukasiGlosarium::create([
            'istilah'  => 'Drawdown',
            'definisi' => 'Persentase penurunan ekuitas.',
        ]);

        // Default: urut abjad (Drawdown duluan sebelum Lot)
        $responseAll = $this->actingAs($this->investor)
            ->get(route('investor.education.glossary'));

        $responseAll->assertStatus(200);
        $responseAll->assertInertia(fn ($page) => $page
            ->component('Investor/Education/Glossary')
            ->has('glossary.items', 2)
            ->where('glossary.items.0.istilah', 'Drawdown')
            ->where('glossary.items.1.istilah', 'Lot')
        );

        // Filter huruf D
        $response = $this->actingAs($this->investor)
            ->get(route('investor.education.glossary', ['huruf' => 'D']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Education/Glossary')
            ->has('glossary.items', 1)
            ->where('glossary.items.0.istilah', 'Drawdown')
        );
    }

    public function test_investor_live_search_searches_only_term_name(): void
    {
        EdukasiGlosarium::create([
            'istilah'  => 'Pip',
            'definisi' => 'Satuan ukuran pergerakan mata uang.',
        ]);

        EdukasiGlosarium::create([
            'istilah'  => 'Spread',
            'definisi' => 'Selisih harga jual dan beli.',
        ]);

        // Mencari nama istilah "Pip" -> ditemukan
        $response = $this->actingAs($this->investor)
            ->get(route('investor.education.glossary', ['search' => 'Pip']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Education/Glossary')
            ->has('glossary.items', 1)
            ->where('glossary.items.0.istilah', 'Pip')
        );

        // Mencari kata "selisih" (yang hanya ada di definisi) -> tidak ditemukan karena search hanya nama istilah
        $responseDef = $this->actingAs($this->investor)
            ->get(route('investor.education.glossary', ['search' => 'selisih']));

        $responseDef->assertStatus(200);
        $responseDef->assertInertia(fn ($page) => $page
            ->component('Investor/Education/Glossary')
            ->has('glossary.items', 0)
        );
    }

    public function test_investor_can_view_help_center_categorized_by_topics(): void
    {
        $kategori1 = PanduanKategori::create([
            'nama'      => 'Panduan Broker',
            'slug'      => 'panduan-broker',
            'deskripsi' => 'Topik broker.',
            'icon'      => 'Wallet',
            'urutan'    => 1,
            'is_active' => true,
        ]);

        $kategori2 = PanduanKategori::create([
            'nama'      => 'Pendaftaran KYC',
            'slug'      => 'pendaftaran-kyc',
            'deskripsi' => 'Topik KYC.',
            'icon'      => 'UserCheck',
            'urutan'    => 2,
            'is_active' => true,
        ]);

        PanduanBantuan::create([
            'kategori_id'  => $kategori1->id,
            'judul'        => 'Cara Deposit HFM',
            'slug'         => 'cara-deposit-hfm',
            'ringkasan'    => 'Ringkasan deposit.',
            'konten'       => 'Langkah 1, 2, 3.',
            'video_url'    => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'urutan'       => 1,
            'is_published' => true,
        ]);

        PanduanBantuan::create([
            'kategori_id'  => $kategori2->id,
            'judul'        => 'Panduan KTP',
            'slug'         => 'panduan-ktp',
            'ringkasan'    => 'Ringkasan KTP.',
            'konten'       => 'Langkah KTP.',
            'urutan'       => 1,
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->investor)
            ->get(route('investor.education.help', ['kategori' => 'panduan-broker']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Education/HelpCenter')
            ->has('help.categories', 2)
            ->where('help.active_slug', 'panduan-broker')
            ->has('help.articles', 1)
            ->where('help.articles.0.judul', 'Cara Deposit HFM')
            ->where('help.articles.0.youtube_id', 'dQw4w9WgXcQ')
        );
    }

    public function test_investor_can_view_single_article_and_increment_views(): void
    {
        $kategori = PanduanKategori::create([
            'nama'      => 'Copytrade MT4',
            'slug'      => 'copytrade-mt4',
            'icon'      => 'TrendingUp',
            'is_active' => true,
        ]);

        $article = PanduanBantuan::create([
            'kategori_id'  => $kategori->id,
            'judul'        => 'Panduan Surat Perjanjian',
            'slug'         => 'panduan-surat-perjanjian',
            'ringkasan'    => 'Ringkasan surat.',
            'konten'       => 'Isi petunjuk lengkap surat perjanjian bertandatangan.',
            'is_published' => true,
            'views_count'  => 5,
        ]);

        $response = $this->actingAs($this->investor)
            ->get(route('investor.education.article', ['slug' => 'panduan-surat-perjanjian']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Education/ArticleDetail')
            ->where('article.judul', 'Panduan Surat Perjanjian')
            ->where('article.views_count', 6)
        );

        $this->assertDatabaseHas('panduan_bantuan', [
            'id'          => $article->id,
            'views_count' => 6,
        ]);
    }

    public function test_investor_can_access_projection_calculator_with_csv_presets(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([
                'amount' => 1.0,
                'base'   => 'USD',
                'date'   => '2026-09-25',
                'rates'  => ['IDR' => 16796.0],
            ], 200),
        ]);
        Cache::forget(ExchangeRateService::CACHE_KEY);

        $response = $this->actingAs($this->investor)
            ->get(route('investor.education.calculator'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Education/Calculator')
            ->where('presets.default_initial_capital', 100)
            ->where('presets.default_monthly_profit_rate', 5)
            ->where('presets.default_duration_years', 5)
            ->where('presets.profit_taken_pct', 30)
            ->where('presets.compounding_pct', 40)
            ->where('presets.master_fee_pct', 30)
            ->where('presets.default_exchange_rate', 16796)
            ->where('presets.exchange_rate_meta.is_live', true)
            ->where('presets.contact.phone', '082388986189')
        );
    }

    public function test_exchange_rate_service_falls_back_when_api_fails(): void
    {
        Cache::forget(ExchangeRateService::CACHE_KEY);
        PengaturanMaster::getSettings()->update([
            'kurs_usd_idr'            => 16500.00,
            'kurs_usd_idr_updated_at' => now()->subDay(),
        ]);

        Http::fake([
            'api.frankfurter.dev/*' => Http::response(null, 500),
        ]);

        $service = app(ExchangeRateService::class);
        $result = $service->fetchAndStoreRate();

        $this->assertFalse($result['is_live']);
        $this->assertEquals(16500.00, $result['rate']);
        $this->assertEquals('database_fallback', $result['source']);
    }

    public function test_update_usd_exchange_rate_artisan_command_updates_cache_and_db(): void
    {
        Cache::forget(ExchangeRateService::CACHE_KEY);

        Http::fake([
            'api.frankfurter.dev/*' => Http::response([
                'amount' => 1.0,
                'base'   => 'USD',
                'date'   => '2026-09-25',
                'rates'  => ['IDR' => 17250.0],
            ], 200),
        ]);

        $this->artisan('app:update-usd-exchange-rate')
            ->expectsOutputToContain('Berhasil memperbarui kurs USD ke IDR: Rp 17.250')
            ->assertSuccessful();

        $this->assertDatabaseHas('pengaturan_master', [
            'kurs_usd_idr' => 17250.00,
        ]);
        $cached = Cache::get(ExchangeRateService::CACHE_KEY);
        $this->assertEquals(17250.0, $cached['rate']);
        $this->assertTrue($cached['is_live']);
    }

    public function test_admin_can_view_and_create_glossary_item(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.education.glossary.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Education/Glossary/Index'));

        $storeResponse = $this->actingAs($this->admin)
            ->post(route('admin.education.glossary.store'), [
                'istilah'  => 'Leverage',
                'definisi' => 'Daya ungkit rasio margin.',
            ]);

        $storeResponse->assertRedirect(route('admin.education.glossary.index'));
        $this->assertDatabaseHas('edukasi_glosarium', [
            'istilah'  => 'Leverage',
            'definisi' => 'Daya ungkit rasio margin.',
        ]);
    }

    public function test_admin_can_update_and_delete_glossary_item(): void
    {
        $item = EdukasiGlosarium::create([
            'istilah'  => 'Spread Lama',
            'definisi' => 'Definisi lama.',
        ]);

        $updateResponse = $this->actingAs($this->admin)
            ->put(route('admin.education.glossary.update', $item->id), [
                'istilah'  => 'Spread Baru',
                'definisi' => 'Definisi baru yang diperbarui.',
            ]);

        $updateResponse->assertRedirect(route('admin.education.glossary.index'));
        $this->assertDatabaseHas('edukasi_glosarium', [
            'id'      => $item->id,
            'istilah' => 'Spread Baru',
        ]);

        $deleteResponse = $this->actingAs($this->admin)
            ->delete(route('admin.education.glossary.destroy', $item->id));

        $deleteResponse->assertRedirect(route('admin.education.glossary.index'));
        $this->assertDatabaseMissing('edukasi_glosarium', ['id' => $item->id]);
    }

    public function test_admin_can_create_update_and_delete_help_category(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.education.help.categories.store'), [
                'nama'      => 'Panduan Baru',
                'slug'      => 'panduan-baru',
                'deskripsi' => 'Deskripsi panduan baru.',
                'icon'      => 'Wallet',
                'urutan'    => 1,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.education.help.index', ['tab' => 'categories']));
        $this->assertDatabaseHas('panduan_kategori', ['slug' => 'panduan-baru']);

        $category = PanduanKategori::where('slug', 'panduan-baru')->firstOrFail();

        $updateResponse = $this->actingAs($this->admin)
            ->put(route('admin.education.help.categories.update', $category->id), [
                'nama'      => 'Panduan Diperbarui',
                'slug'      => 'panduan-diperbarui',
                'deskripsi' => 'Deskripsi diperbarui.',
                'icon'      => 'TrendingUp',
                'urutan'    => 2,
                'is_active' => true,
            ]);

        $updateResponse->assertRedirect(route('admin.education.help.index', ['tab' => 'categories']));
        $this->assertDatabaseHas('panduan_kategori', ['slug' => 'panduan-diperbarui']);

        $deleteResponse = $this->actingAs($this->admin)
            ->delete(route('admin.education.help.categories.destroy', $category->id));

        $deleteResponse->assertRedirect(route('admin.education.help.index', ['tab' => 'categories']));
        $this->assertDatabaseMissing('panduan_kategori', ['id' => $category->id]);
    }

    public function test_admin_cannot_delete_category_that_has_articles(): void
    {
        $category = PanduanKategori::create([
            'nama'      => 'Kategori Berisi',
            'slug'      => 'kategori-berisi',
            'is_active' => true,
        ]);

        PanduanBantuan::create([
            'kategori_id'  => $category->id,
            'judul'        => 'Artikel Terhubung',
            'slug'         => 'artikel-terhubung',
            'konten'       => 'Isi artikel.',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.education.help.categories.destroy', $category->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('panduan_kategori', ['id' => $category->id]);
    }

    public function test_admin_can_create_update_and_delete_help_article(): void
    {
        $category = PanduanKategori::create([
            'nama'      => 'Kategori Uji',
            'slug'      => 'kategori-uji',
            'is_active' => true,
        ]);

        $storeResponse = $this->actingAs($this->admin)
            ->post(route('admin.education.help.articles.store'), [
                'kategori_id'  => $category->id,
                'judul'        => 'Cara Registrasi Cepat',
                'slug'         => 'cara-registrasi-cepat',
                'ringkasan'    => 'Ringkasan singkat.',
                'konten'       => 'Konten langkah lengkap bertahap.',
                'video_url'    => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'urutan'       => 1,
                'is_published' => true,
            ]);

        $storeResponse->assertRedirect(route('admin.education.help.index', ['tab' => 'articles']));
        $this->assertDatabaseHas('panduan_bantuan', ['slug' => 'cara-registrasi-cepat']);

        $article = PanduanBantuan::where('slug', 'cara-registrasi-cepat')->firstOrFail();

        $updateResponse = $this->actingAs($this->admin)
            ->put(route('admin.education.help.articles.update', $article->id), [
                'kategori_id'  => $category->id,
                'judul'        => 'Cara Registrasi Diperbarui',
                'slug'         => 'cara-registrasi-diperbarui',
                'ringkasan'    => 'Ringkasan baru.',
                'konten'       => 'Konten baru bertahap.',
                'video_url'    => null,
                'urutan'       => 2,
                'is_published' => true,
            ]);

        $updateResponse->assertRedirect(route('admin.education.help.index', ['tab' => 'articles']));
        $this->assertDatabaseHas('panduan_bantuan', ['slug' => 'cara-registrasi-diperbarui']);

        $deleteResponse = $this->actingAs($this->admin)
            ->delete(route('admin.education.help.articles.destroy', $article->id));

        $deleteResponse->assertRedirect(route('admin.education.help.index', ['tab' => 'articles']));
        $this->assertDatabaseMissing('panduan_bantuan', ['id' => $article->id]);
    }

    public function test_non_admin_cannot_access_admin_education_management(): void
    {
        $response = $this->actingAs($this->investor)
            ->get(route('admin.education.glossary.index'));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_education_pages(): void
    {
        $response = $this->get(route('investor.education.calculator'));
        $response->assertRedirect(route('login'));

        $responseAdmin = $this->get(route('admin.education.glossary.index'));
        $responseAdmin->assertRedirect(route('login'));

        $responseFaq = $this->get(route('investor.education.faq'));
        $responseFaq->assertRedirect(route('login'));
    }

    public function test_investor_can_view_faq_list_grouped_by_topic(): void
    {
        $topic = EdukasiFaqTopik::create([
            'nama'      => 'Legalitas & Keamanan',
            'deskripsi' => 'Topik keamanan dana.',
            'urutan'    => 1,
            'is_active' => true,
        ]);

        EdukasiFaq::create([
            'topik_id'   => $topic->id,
            'pertanyaan' => 'Apakah dana saya aman?',
            'jawaban'    => 'Ya, akun non-custodial.',
            'urutan'     => 1,
            'is_active'  => true,
        ]);

        // FAQ nonaktif tidak boleh muncul untuk investor
        EdukasiFaq::create([
            'topik_id'   => $topic->id,
            'pertanyaan' => 'Pertanyaan Rahasia?',
            'jawaban'    => 'Jawaban belum siap.',
            'urutan'     => 2,
            'is_active'  => false,
        ]);

        $response = $this->actingAs($this->investor)
            ->get(route('investor.education.faq'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Investor/Education/Faq')
            ->has('faq.topics', 1)
            ->where('faq.topics.0.nama', 'Legalitas & Keamanan')
            ->has('faq.topics.0.faqs', 1)
            ->where('faq.topics.0.faqs.0.pertanyaan', 'Apakah dana saya aman?')
        );
    }

    public function test_investor_can_filter_faq_by_topic_and_search(): void
    {
        $topicA = EdukasiFaqTopik::create([
            'nama'      => 'Topik A',
            'urutan'    => 1,
            'is_active' => true,
        ]);
        $topicB = EdukasiFaqTopik::create([
            'nama'      => 'Topik B',
            'urutan'    => 2,
            'is_active' => true,
        ]);

        EdukasiFaq::create([
            'topik_id'   => $topicA->id,
            'pertanyaan' => 'Pertanyaan di Topik A',
            'jawaban'    => 'Uraian jawaban A.',
            'urutan'     => 1,
            'is_active'  => true,
        ]);
        EdukasiFaq::create([
            'topik_id'   => $topicB->id,
            'pertanyaan' => 'Pertanyaan di Topik B',
            'jawaban'    => 'Uraian jawaban B.',
            'urutan'     => 1,
            'is_active'  => true,
        ]);

        // Filter per Topik B
        $responseTopic = $this->actingAs($this->investor)
            ->get(route('investor.education.faq', ['topik_id' => $topicB->id]));

        $responseTopic->assertStatus(200);
        $responseTopic->assertInertia(fn ($page) => $page
            ->has('faq.topics', 1)
            ->where('faq.topics.0.nama', 'Topik B')
        );

        // Filter per Search
        $responseSearch = $this->actingAs($this->investor)
            ->get(route('investor.education.faq', ['search' => 'Topik A']));

        $responseSearch->assertStatus(200);
        $responseSearch->assertInertia(fn ($page) => $page
            ->has('faq.topics', 1)
            ->where('faq.topics.0.nama', 'Topik A')
        );
    }

    public function test_admin_can_manage_faq_topics_and_items(): void
    {
        // 1. Admin membuat topik FAQ baru
        $storeTopicResponse = $this->actingAs($this->admin)
            ->post(route('admin.education.faq.topics.store'), [
                'nama'      => 'Topik Baru',
                'deskripsi' => 'Deskripsi topik.',
                'urutan'    => 1,
                'is_active' => true,
            ]);

        $storeTopicResponse->assertRedirect(route('admin.education.faq.index', ['tab' => 'topics']));
        $this->assertDatabaseHas('edukasi_faq_topik', ['nama' => 'Topik Baru']);

        $topic = EdukasiFaqTopik::where('nama', 'Topik Baru')->firstOrFail();

        // 2. Admin membuat pertanyaan FAQ di topik tersebut (tanpa slug)
        $storeFaqResponse = $this->actingAs($this->admin)
            ->post(route('admin.education.faq.store'), [
                'topik_id'   => $topic->id,
                'pertanyaan' => 'Bagaimana cara mulai copytrade?',
                'jawaban'    => 'Buka akun MT4 dan hubungkan ke sistem YZD.',
                'urutan'     => 1,
                'is_active'  => true,
            ]);

        $storeFaqResponse->assertRedirect(route('admin.education.faq.index', ['tab' => 'faqs']));
        $this->assertDatabaseHas('edukasi_faq', [
            'topik_id'   => $topic->id,
            'pertanyaan' => 'Bagaimana cara mulai copytrade?',
        ]);

        $faq = EdukasiFaq::where('pertanyaan', 'Bagaimana cara mulai copytrade?')->firstOrFail();

        // 3. Admin mengupdate FAQ
        $updateFaqResponse = $this->actingAs($this->admin)
            ->put(route('admin.education.faq.update', $faq->id), [
                'topik_id'   => $topic->id,
                'pertanyaan' => 'Bagaimana cara mulai copytrade diperbarui?',
                'jawaban'    => 'Jawaban telah disesuaikan.',
                'urutan'     => 2,
                'is_active'  => true,
            ]);

        $updateFaqResponse->assertRedirect(route('admin.education.faq.index', ['tab' => 'faqs']));
        $this->assertDatabaseHas('edukasi_faq', [
            'id'         => $faq->id,
            'pertanyaan' => 'Bagaimana cara mulai copytrade diperbarui?',
        ]);

        // 4. Admin tidak bisa menghapus topik jika masih ada FAQ terkait
        $deleteTopicFail = $this->actingAs($this->admin)
            ->delete(route('admin.education.faq.topics.destroy', $topic->id));

        $deleteTopicFail->assertSessionHas('error');
        $this->assertDatabaseHas('edukasi_faq_topik', ['id' => $topic->id]);

        // 5. Admin menghapus FAQ
        $deleteFaqResponse = $this->actingAs($this->admin)
            ->delete(route('admin.education.faq.destroy', $faq->id));

        $deleteFaqResponse->assertRedirect(route('admin.education.faq.index', ['tab' => 'faqs']));
        $this->assertDatabaseMissing('edukasi_faq', ['id' => $faq->id]);

        // 6. Sekarang topik dapat dihapus
        $deleteTopicSuccess = $this->actingAs($this->admin)
            ->delete(route('admin.education.faq.topics.destroy', $topic->id));

        $deleteTopicSuccess->assertRedirect(route('admin.education.faq.index', ['tab' => 'topics']));
        $this->assertDatabaseMissing('edukasi_faq_topik', ['id' => $topic->id]);
    }
}
