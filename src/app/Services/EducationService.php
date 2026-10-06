<?php

namespace App\Services;

use App\Models\EdukasiFaq;
use App\Models\EdukasiFaqTopik;
use App\Models\EdukasiGlosarium;
use App\Models\PanduanBantuan;
use App\Models\PanduanKategori;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EducationService
{
    // =========================================================================
    // 1. Logika Glosarium (Investor & Admin)
    // =========================================================================

    /**
     * Mengambil daftar glosarium untuk portal Investor (lengkap dengan filter).
     */
    public function getInvestorGlossary(array $filters = []): array
    {
        $search = $filters['search'] ?? null;
        $huruf = $filters['huruf'] ?? null;

        $query = EdukasiGlosarium::query()
            ->when($search, fn ($q) => $q->search($search))
            ->when($huruf, fn ($q) => $q->where('istilah', 'like', "{$huruf}%"))
            ->orderBy('istilah', 'asc');

        $items = $query->get()->map(function ($item) {
            return [
                'id'       => $item->id,
                'istilah'  => $item->istilah,
                'definisi' => $item->definisi,
            ];
        });

        // Daftar huruf A-Z yang tersedia
        $availableLetters = EdukasiGlosarium::query()
            ->selectRaw('UPPER(SUBSTRING(istilah, 1, 1)) as letter')
            ->distinct()
            ->orderBy('letter')
            ->pluck('letter')
            ->filter(fn ($l) => ctype_alpha($l))
            ->values()
            ->toArray();

        return [
            'items'             => $items,
            'available_letters' => $availableLetters,
            'filters'           => [
                'search' => $search,
                'huruf'  => $huruf,
            ],
        ];
    }

    /**
     * Mengambil paginasi glosarium untuk panel Administrator (AppDataTable contract).
     */
    public function getAdminGlossaryPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;

        $paginator = EdukasiGlosarium::query()
            ->when($search, fn ($q) => $q->search($search))
            ->orderBy('istilah', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->through(function ($item) {
            return [
                'id'         => $item->id,
                'istilah'    => $item->istilah,
                'definisi'   => $item->definisi,
                'updated_at' => $item->updated_at?->format('d/m/Y H:i'),
            ];
        });

        return $paginator;
    }

    public function manageGlossaryItem(array $data, ?EdukasiGlosarium $item = null, ?User $user = null): EdukasiGlosarium
    {
        return DB::transaction(function () use ($data, $item, $user) {
            $payload = [
                'istilah'  => trim($data['istilah']),
                'definisi' => trim($data['definisi']),
            ];

            if ($item) {
                $item->update($payload);

                return $item;
            }

            $payload['created_by'] = $user?->id;

            return EdukasiGlosarium::create($payload);
        });
    }

    public function deleteGlossaryItem(EdukasiGlosarium $item): void
    {
        $item->delete();
    }

    // =========================================================================
    // 2. Logika Pusat Bantuan (Topik Kategori & Artikel)
    // =========================================================================

    /**
     * Mengambil struktur Pusat Bantuan untuk portal Investor.
     */
    public function getInvestorHelpCenter(?string $kategoriSlug = null, ?string $search = null): array
    {
        // 1. Ambil seluruh kategori aktif beserta jumlah artikelnya
        $categories = PanduanKategori::query()
            ->active()
            ->ordered()
            ->withCount(['panduan' => function ($q) {
                $q->published();
            }])
            ->get()
            ->map(function ($cat) {
                return [
                    'id'             => $cat->id,
                    'nama'           => $cat->nama,
                    'slug'           => $cat->slug,
                    'deskripsi'      => $cat->deskripsi,
                    'icon'           => $cat->icon ?? 'HelpCircle',
                    'articles_count' => $cat->panduan_count,
                ];
            });

        // Tentukan kategori aktif (default: kategori pertama jika tidak ditentukan)
        $activeSlug = $kategoriSlug;
        if (!$activeSlug && $categories->isNotEmpty()) {
            $activeSlug = $categories->first()['slug'];
        }

        // 2. Ambil artikel sesuai kategori aktif atau hasil pencarian global
        $articlesQuery = PanduanBantuan::query()
            ->published()
            ->with('kategori')
            ->when($search, function ($q) use ($search) {
                $q->search($search);
            }, function ($q) use ($activeSlug) {
                if ($activeSlug && $activeSlug !== 'all') {
                    $q->whereHas('kategori', function ($catQ) use ($activeSlug) {
                        $catQ->where('slug', $activeSlug);
                    });
                }
            })
            ->orderBy('judul');

        $articles = $articlesQuery->get()->map(function ($art) {
            return [
                'id'            => $art->id,
                'judul'         => $art->judul,
                'slug'          => $art->slug,
                'ringkasan'     => $art->ringkasan,
                'video_url'     => $art->video_url,
                'youtube_id'    => $this->extractYoutubeId($art->video_url),
                'views_count'   => $art->views_count,
                'kategori_nama' => $art->kategori?->nama,
                'kategori_slug' => $art->kategori?->slug,
            ];
        });

        return [
            'categories'  => $categories,
            'active_slug' => $activeSlug,
            'articles'    => $articles,
            'search'      => $search,
        ];
    }

    /**
     * Membaca rincian artikel panduan & menambah views_count.
     */
    public function getHelpArticleDetail(string $slug): ?array
    {
        $article = PanduanBantuan::query()
            ->published()
            ->with('kategori')
            ->where('slug', $slug)
            ->first();

        if (!$article) {
            return null;
        }

        // Increment hit counter
        $article->increment('views_count');

        // Artikel terkait dalam kategori yang sama
        $relatedArticles = PanduanBantuan::query()
            ->published()
            ->where('kategori_id', $article->kategori_id)
            ->where('id', '!=', $article->id)
            ->latest('id')
            ->limit(4)
            ->get(['id', 'judul', 'slug', 'ringkasan'])
            ->toArray();

        return [
            'id'          => $article->id,
            'judul'       => $article->judul,
            'slug'        => $article->slug,
            'ringkasan'   => $article->ringkasan,
            'konten'      => $article->konten,
            'video_url'   => $article->video_url,
            'youtube_id'  => $this->extractYoutubeId($article->video_url),
            'views_count' => $article->views_count,
            'updated_at'  => $article->updated_at?->format('d M Y'),
            'kategori'    => [
                'id'   => $article->kategori?->id,
                'nama' => $article->kategori?->nama,
                'slug' => $article->kategori?->slug,
                'icon' => $article->kategori?->icon,
            ],
            'related_articles' => $relatedArticles,
        ];
    }

    /**
     * Mengambil paginasi artikel untuk panel Admin.
     */
    public function getAdminArticlesPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $kategoriId = $filters['kategori_id'] ?? null;

        $paginator = PanduanBantuan::query()
            ->with('kategori')
            ->when($search, fn ($q) => $q->search($search))
            ->when($kategoriId, fn ($q) => $q->where('kategori_id', $kategoriId))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->through(function ($item) {
            return [
                'id'            => $item->id,
                'kategori_id'   => $item->kategori_id,
                'kategori_nama' => $item->kategori?->nama,
                'judul'         => $item->judul,
                'slug'          => $item->slug,
                'ringkasan'     => $item->ringkasan,
                'konten'        => $item->konten,
                'video_url'     => $item->video_url,
                'is_published'  => (bool) $item->is_published,
                'views_count'   => $item->views_count,
                'updated_at'    => $item->updated_at?->format('d/m/Y H:i'),
            ];
        });

        return $paginator;
    }

    /**
     * Mengambil seluruh kategori bantuan untuk panel Admin.
     */
    public function getAdminCategories(): array
    {
        return PanduanKategori::query()
            ->ordered()
            ->withCount('panduan')
            ->get()
            ->map(function ($cat) {
                return [
                    'id'             => $cat->id,
                    'nama'           => $cat->nama,
                    'slug'           => $cat->slug,
                    'deskripsi'      => $cat->deskripsi,
                    'icon'           => $cat->icon ?? 'HelpCircle',
                    'urutan'         => $cat->urutan,
                    'is_active'      => (bool) $cat->is_active,
                    'articles_count' => $cat->panduan_count,
                ];
            })
            ->toArray();
    }

    public function manageCategory(array $data, ?PanduanKategori $category = null): PanduanKategori
    {
        return DB::transaction(function () use ($data, $category) {
            $slug = !empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['nama']);

            // Pastikan slug unik jika kategori baru
            if (!$category) {
                $baseSlug = $slug;
                $counter = 1;
                while (PanduanKategori::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }
            }

            $payload = [
                'nama'      => trim($data['nama']),
                'slug'      => $slug,
                'deskripsi' => !empty($data['deskripsi']) ? trim($data['deskripsi']) : null,
                'icon'      => !empty($data['icon']) ? trim($data['icon']) : ($category?->icon ?? 'HelpCircle'),
                'urutan'    => (int) ($data['urutan'] ?? 0),
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ];

            if ($category) {
                $category->update($payload);

                return $category;
            }

            return PanduanKategori::create($payload);
        });
    }

    public function deleteCategory(PanduanKategori $category): void
    {
        $category->delete();
    }

    public function manageArticle(array $data, ?PanduanBantuan $article = null, ?User $user = null): PanduanBantuan
    {
        return DB::transaction(function () use ($data, $article, $user) {
            $slug = !empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['judul']);

            // Pastikan slug unik jika dibuat baru
            if (!$article) {
                $baseSlug = $slug;
                $counter = 1;
                while (PanduanBantuan::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }
            }

            $payload = [
                'kategori_id'  => (int) $data['kategori_id'],
                'judul'        => trim($data['judul']),
                'slug'         => $slug,
                'ringkasan'    => !empty($data['ringkasan']) ? trim($data['ringkasan']) : null,
                'konten'       => trim($data['konten']),
                'video_url'    => !empty($data['video_url']) ? trim($data['video_url']) : null,
                'is_published' => isset($data['is_published']) ? (bool) $data['is_published'] : true,
            ];

            if ($article) {
                $article->update($payload);

                return $article;
            }

            $payload['created_by'] = $user?->id;

            return PanduanBantuan::create($payload);
        });
    }

    public function deleteArticle(PanduanBantuan $article): void
    {
        $article->delete();
    }

    // =========================================================================
    // 3. Preset Kalkulator Proyeksi Finansial (Referensi CSV YZD Center)
    // =========================================================================

    /**
     * Menyediakan acuan baku matematis kalkulator proyeksi sesuai dokumen CSV Pak Yazid.
     */
    public function getCalculatorPresets(): array
    {
        $rateData = app(ExchangeRateService::class)->getUsdToIdrRate();

        return [
            'default_initial_capital'     => 100.0,
            'default_monthly_profit_rate' => 5.0,
            'default_duration_years'      => 5,
            'default_exchange_rate'       => (float) ($rateData['rate'] ?? 16796.0),
            'exchange_rate_meta'          => [
                'rate'       => (float) ($rateData['rate'] ?? 16796.0),
                'updated_at' => $rateData['updated_at'] ?? null,
                'date'       => $rateData['date'] ?? null,
                'is_live'    => (bool) ($rateData['is_live'] ?? false),
                'source'     => $rateData['source'] ?? 'default_fallback',
            ],
            'profit_taken_pct' => 30.0, // Max 30% profit yang boleh diambil (investor)
            'compounding_pct'  => 40.0, // 40% compounding penambah modal
            'master_fee_pct'   => 30.0, // 30% bagi hasil master trader
            'system_fee_pct'   => 30.0, // Alias kompatibilitas
            'contact'          => [
                'name'         => 'Yazid',
                'phone'        => '082388986189',
                'whatsapp_url' => 'https://wa.me/6282388986189?text=Halo%20Pak%20Yazid%2C%20saya%20ingin%20konsultasi%20mengenai%20proyeksi%20investasi%20copytrade%20YZD%20Center.',
            ],
        ];
    }

    /**
     * Menghitung progress BEP (Break Even Point) investor berdasarkan akumulasi profit bulanan sejak awal join.
     * Menghitung kuota balik modal mencakup profit yang sudah di-WD + penambahan modal dari compounding.
     */
    public function getInvestorBepSummary(User $user): array
    {
        $activeAccounts = $user->akunCopytrade()
            ->where('status', 'active')
            ->orderBy('copy_started_at')
            ->get();

        $hasActiveCopytrade = $activeAccounts->isNotEmpty();
        $rateData = app(ExchangeRateService::class)->getUsdToIdrRate();
        $kurs = (float) ($rateData['rate'] ?? 16796.0);

        if ($hasActiveCopytrade) {
            $totalModalRiil = (float) $activeAccounts->sum('modal_riil');
            $earliestAccount = $activeAccounts->first();
            $joinDate = $earliestAccount->copy_started_at ?? $earliestAccount->tanggal_mulai;
            $startCarbon = $joinDate ? Carbon::parse($joinDate) : now();
            // Menghitung bulan aktif berjalan sejak join (minimal 1 bulan)
            $diffMonths = (int) $startCarbon->diffInMonths(now());
            $monthsActive = max(1, $diffMonths > 0 ? $diffMonths : 1);
        } else {
            // Mode contoh simulasi bawaan
            $totalModalRiil = 100.0;
            $startCarbon = now()->subMonths(1);
            $monthsActive = 1;
        }

        $monthlyProfitRate = 5.0; // Standar konservatif 5%
        $monthlyRateDecimal = $monthlyProfitRate / 100.0;

        // Hitung simulasi akumulasi profit sejak awal join s/d bulan saat ini
        // Sesuai formula YZD: 30% WD Investor, 40% Compounding Modal, 30% Master Fee
        $currentModal = $totalModalRiil;
        $totalWithdrawn = 0.0;
        $totalCompounded = 0.0;
        $totalGrossProfit = 0.0;

        for ($m = 1; $m <= $monthsActive; $m++) {
            $grossProfit = $currentModal * $monthlyRateDecimal;
            $wd = $grossProfit * 0.30;
            $compounding = $grossProfit * 0.40;

            $totalGrossProfit += $grossProfit;
            $totalWithdrawn += $wd;
            $totalCompounded += $compounding;
            $currentModal += $compounding;
        }

        // Total akumulasi keuntungan bersih yang diterima investor (WD + Compounding)
        $totalProfitAccumulated = $totalWithdrawn + $totalCompounded;

        // Persentase BEP (Berapa persen modal awal sudah kembali)
        $bepProgressPct = $totalModalRiil > 0
            ? min(100.0, round(($totalProfitAccumulated / $totalModalRiil) * 100.0, 1))
            : 0.0;

        $remainingCapitalToBep = max(0.0, $totalModalRiil - $totalProfitAccumulated);
        $isBepAchieved = $totalProfitAccumulated >= $totalModalRiil;

        // Hitung estimasi di bulan ke berapa BEP 100% tercapai sejak awal join
        $tempModal = $totalModalRiil;
        $tempAccumulated = 0.0;
        $monthsToBep = 0;
        for ($m = 1; $m <= 120; $m++) {
            $p = $tempModal * $monthlyRateDecimal;
            $wd = $p * 0.30;
            $comp = $p * 0.40;
            $tempAccumulated += ($wd + $comp);
            $tempModal += $comp;

            if ($tempAccumulated >= $totalModalRiil && $monthsToBep === 0) {
                $monthsToBep = $m;
                break;
            }
        }

        $remainingMonthsToBep = max(0, $monthsToBep - $monthsActive);

        $roundedWithdrawn = round($totalWithdrawn, 2);
        $roundedCompounded = round($totalCompounded, 2);
        $roundedTotalProfit = round($roundedWithdrawn + $roundedCompounded, 2);

        return [
            'has_active_copytrade'     => $hasActiveCopytrade,
            'active_accounts_count'    => $activeAccounts->count(),
            'initial_capital'          => $totalModalRiil,
            'initial_capital_idr'      => $totalModalRiil * $kurs,
            'current_equity'           => round($currentModal, 2),
            'current_equity_idr'       => round($currentModal * $kurs, 0),
            'monthly_profit_rate'      => $monthlyProfitRate,
            'join_date'                => $startCarbon->format('Y-m-d'),
            'join_date_formatted'      => function_exists('tanggal_indo') ? tanggal_indo($startCarbon) : $startCarbon->format('d M Y'),
            'months_active'            => $monthsActive,
            'total_withdrawn'          => $roundedWithdrawn,
            'total_withdrawn_idr'      => round($roundedWithdrawn * $kurs, 0),
            'total_compounded'         => $roundedCompounded,
            'total_compounded_idr'     => round($roundedCompounded * $kurs, 0),
            'total_profit_accumulated' => $roundedTotalProfit,
            'total_profit_idr'         => round($roundedTotalProfit * $kurs, 0),
            'bep_progress_pct'         => $bepProgressPct,
            'remaining_capital_to_bep' => round($remainingCapitalToBep, 2),
            'remaining_capital_idr'    => round($remainingCapitalToBep * $kurs, 0),
            'months_to_bep'            => $monthsToBep,
            'remaining_months_to_bep'  => $remainingMonthsToBep,
            'is_bep_achieved'          => $isBepAchieved,
            'exchange_rate'            => $kurs,
        ];
    }

    // =========================================================================
    // 4. Logika Tanya Jawab (FAQ) - Tanpa Slug & Terkategorisasi per Topik
    // =========================================================================

    /**
     * Mengambil daftar FAQ untuk portal Investor (dikelompokkan per Topik atau dengan filter).
     */
    public function getInvestorFaq(array $filters = []): array
    {
        $search = $filters['search'] ?? null;
        $topikId = $filters['topik_id'] ?? null;

        $topics = EdukasiFaqTopik::query()
            ->active()
            ->when($topikId, fn ($q) => $q->where('id', $topikId))
            ->orderBy('urutan')
            ->with(['activeFaqs' => function ($query) use ($search) {
                $query->when($search, fn ($q) => $q->search($search))
                    ->orderBy('urutan');
            }])
            ->get();

        // Ambil semua topik aktif untuk pills / tab filter
        $availableTopics = EdukasiFaqTopik::query()
            ->active()
            ->withCount('activeFaqs')
            ->orderBy('urutan')
            ->get()
            ->map(fn ($t) => [
                'id'        => $t->id,
                'nama'      => $t->nama,
                'deskripsi' => $t->deskripsi,
                'faq_count' => $t->active_faqs_count,
            ])
            ->toArray();

        // Format data respons
        $grouped = $topics->filter(function ($t) {
            return $t->activeFaqs->isNotEmpty();
        })->map(function ($topic) {
            return [
                'id'        => $topic->id,
                'nama'      => $topic->nama,
                'deskripsi' => $topic->deskripsi,
                'faqs'      => $topic->activeFaqs->map(fn ($faq) => [
                    'id'         => $faq->id,
                    'pertanyaan' => $faq->pertanyaan,
                    'jawaban'    => $faq->jawaban,
                    'urutan'     => $faq->urutan,
                ])->values()->all(),
            ];
        })->values()->all();

        return [
            'topics'           => $grouped,
            'available_topics' => $availableTopics,
        ];
    }

    /**
     * Mengambil daftar FAQ terpaginasi untuk panel Administrator.
     */
    public function getAdminFaqPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $topikId = $filters['topik_id'] ?? null;

        return EdukasiFaq::query()
            ->with('topik')
            ->when($search, fn ($q) => $q->search($search))
            ->when($topikId, fn ($q) => $q->topik((int) $topikId))
            ->orderBy('topik_id')
            ->orderBy('urutan')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Mengambil daftar topik FAQ untuk dropdown / manajemen.
     */
    public function getFaqTopics(): array
    {
        return EdukasiFaqTopik::query()
            ->withCount('faqs')
            ->orderBy('urutan')
            ->get()
            ->toArray();
    }

    /**
     * Tambah atau perbarui data FAQ (tanpa slug).
     */
    public function manageFaq(array $data, ?EdukasiFaq $faq = null, ?User $user = null): EdukasiFaq
    {
        if ($faq) {
            $faq->update($data);

            return $faq;
        }

        $data['created_by'] = $user?->id;

        return EdukasiFaq::create($data);
    }

    public function deleteFaq(EdukasiFaq $faq): void
    {
        $faq->delete();
    }

    /**
     * Tambah atau perbarui topik kategori FAQ.
     */
    public function manageFaqTopic(array $data, ?EdukasiFaqTopik $topic = null): EdukasiFaqTopik
    {
        if ($topic) {
            $topic->update($data);

            return $topic;
        }

        return EdukasiFaqTopik::create($data);
    }

    public function deleteFaqTopic(EdukasiFaqTopik $topic): void
    {
        $topic->delete();
    }

    // -------------------------------------------------------------------------
    // Helper Internal
    // -------------------------------------------------------------------------

    private function extractYoutubeId(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        // Format: https://www.youtube.com/watch?v=VIDEO_ID atau https://youtu.be/VIDEO_ID
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
