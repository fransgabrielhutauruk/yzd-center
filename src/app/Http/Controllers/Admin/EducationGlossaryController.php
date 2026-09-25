<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GlosariumRequest;
use App\Models\EdukasiGlosarium;
use App\Services\EducationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EducationGlossaryController extends Controller
{
    public function __construct(
        protected EducationService $educationService
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->input('search'),
        ];

        $glossary = $this->educationService->getAdminGlossaryPaginated($filters, 15);

        return Inertia::render('Admin/Education/Glossary/Index', [
            'glossary' => $glossary,
            'filters'  => $filters,
        ]);
    }

    public function store(GlosariumRequest $request): RedirectResponse
    {
        $this->educationService->manageGlossaryItem($request->validated(), null, $request->user());

        return redirect()->route('admin.education.glossary.index')
            ->with('success', 'Istilah glosarium berhasil ditambahkan.');
    }

    public function update(GlosariumRequest $request, int $id): RedirectResponse
    {
        $item = EdukasiGlosarium::findOrFail($id);
        $this->educationService->manageGlossaryItem($request->validated(), $item);

        return redirect()->route('admin.education.glossary.index')
            ->with('success', 'Istilah glosarium berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = EdukasiGlosarium::findOrFail($id);
        $this->educationService->deleteGlossaryItem($item);

        return redirect()->route('admin.education.glossary.index')
            ->with('success', 'Istilah glosarium berhasil dihapus.');
    }
}
