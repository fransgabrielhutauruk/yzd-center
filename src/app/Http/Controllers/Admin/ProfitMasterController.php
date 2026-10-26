<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfitMasterPostRequest;
use App\Models\ProfitMasterPost;
use App\Services\ProfitMasterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfitMasterController extends Controller
{
    public function __construct(
        protected ProfitMasterService $profitMasterService
    ) {}

    /**
     * Tampilkan halaman utama materi promosi profit master untuk Admin.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/ProfitMaster/Index', [
            'initialSearch' => (string) $request->input('search', ''),
        ]);
    }

    /**
     * Endpoint API JSON feed postingan dengan cursor pagination.
     */
    public function feed(Request $request): JsonResponse
    {
        $feed = $this->profitMasterService->feed(
            search: $request->input('search'),
            cursor: $request->input('cursor'),
            perPage: (int) $request->input('per_page', 10)
        );

        return response()->json($feed);
    }

    /**
     * Simpan materi promosi profit master baru.
     */
    public function store(ProfitMasterPostRequest $request): RedirectResponse
    {
        $images = $request->file('images', []);
        if (!is_array($images)) {
            $images = [$images];
        }

        $this->profitMasterService->create(
            caption: $request->validated('caption'),
            images: $images,
            adminId: $request->user()->id
        );

        return redirect()->back()->with('success', 'Materi promosi berhasil diterbitkan.');
    }

    /**
     * Perbarui materi promosi profit master.
     */
    public function update(ProfitMasterPostRequest $request, ProfitMasterPost $post): RedirectResponse
    {
        $newImages = $request->file('images', []);
        if (!is_array($newImages)) {
            $newImages = $newImages ? [$newImages] : [];
        }

        $deletedImageIds = $request->input('deleted_image_ids', []);
        if (!is_array($deletedImageIds)) {
            $deletedImageIds = [];
        }

        $this->profitMasterService->update(
            post: $post,
            caption: $request->validated('caption'),
            newImages: $newImages,
            deletedImageIds: array_map('intval', $deletedImageIds)
        );

        return redirect()->back()->with('success', 'Materi promosi berhasil diperbarui.');
    }

    /**
     * Hapus materi promosi profit master.
     */
    public function destroy(ProfitMasterPost $post): RedirectResponse
    {
        $this->profitMasterService->delete($post);

        return redirect()->back()->with('success', 'Materi promosi berhasil dihapus.');
    }
}
