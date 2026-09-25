<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PanduanBantuanRequest;
use App\Http\Requests\Admin\PanduanKategoriRequest;
use App\Models\PanduanBantuan;
use App\Models\PanduanKategori;
use App\Services\EducationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpCenterController extends Controller
{
    public function __construct(
        protected EducationService $educationService
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search'      => $request->input('search'),
            'kategori_id' => $request->input('kategori_id'),
            'tab'         => $request->input('tab', 'articles'),
        ];

        $articles = $this->educationService->getAdminArticlesPaginated($filters, 15);
        $categories = $this->educationService->getAdminCategories();

        return Inertia::render('Admin/Education/Help/Index', [
            'articles'   => $articles,
            'categories' => $categories,
            'filters'    => $filters,
        ]);
    }

    public function storeCategory(PanduanKategoriRequest $request): RedirectResponse
    {
        $this->educationService->manageCategory($request->validated());

        return redirect()->route('admin.education.help.index', ['tab' => 'categories'])
            ->with('success', 'Kategori topik bantuan berhasil ditambahkan.');
    }

    public function updateCategory(PanduanKategoriRequest $request, int $id): RedirectResponse
    {
        $category = PanduanKategori::findOrFail($id);
        $this->educationService->manageCategory($request->validated(), $category);

        return redirect()->route('admin.education.help.index', ['tab' => 'categories'])
            ->with('success', 'Kategori topik bantuan berhasil diperbarui.');
    }

    public function destroyCategory(int $id): RedirectResponse
    {
        $category = PanduanKategori::findOrFail($id);
        if ($category->panduan()->count() > 0) {
            return redirect()->route('admin.education.help.index', ['tab' => 'categories'])
                ->with('error', 'Kategori tidak dapat dihapus karena masih memuat artikel bantuan.');
        }

        $this->educationService->deleteCategory($category);

        return redirect()->route('admin.education.help.index', ['tab' => 'categories'])
            ->with('success', 'Kategori topik bantuan berhasil dihapus.');
    }

    public function storeArticle(PanduanBantuanRequest $request): RedirectResponse
    {
        $this->educationService->manageArticle($request->validated(), null, $request->user());

        return redirect()->route('admin.education.help.index', ['tab' => 'articles'])
            ->with('success', 'Artikel panduan bantuan berhasil ditambahkan.');
    }

    public function updateArticle(PanduanBantuanRequest $request, int $id): RedirectResponse
    {
        $article = PanduanBantuan::findOrFail($id);
        $this->educationService->manageArticle($request->validated(), $article);

        return redirect()->route('admin.education.help.index', ['tab' => 'articles'])
            ->with('success', 'Artikel panduan bantuan berhasil diperbarui.');
    }

    public function destroyArticle(int $id): RedirectResponse
    {
        $article = PanduanBantuan::findOrFail($id);
        $this->educationService->deleteArticle($article);

        return redirect()->route('admin.education.help.index', ['tab' => 'articles'])
            ->with('success', 'Artikel panduan bantuan berhasil dihapus.');
    }
}
