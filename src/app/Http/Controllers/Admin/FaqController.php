<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqRequest;
use App\Http\Requests\Admin\FaqTopikRequest;
use App\Models\EdukasiFaq;
use App\Models\EdukasiFaqTopik;
use App\Services\EducationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function __construct(
        protected EducationService $educationService
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search'   => $request->input('search'),
            'topik_id' => $request->input('topik_id'),
            'tab'      => $request->input('tab', 'faqs'),
        ];

        $faqs = $this->educationService->getAdminFaqPaginated($filters, 15);
        $topics = $this->educationService->getFaqTopics();

        return Inertia::render('Admin/Education/Faq/Index', [
            'faqs'    => $faqs,
            'topics'  => $topics,
            'filters' => $filters,
        ]);
    }

    public function store(FaqRequest $request): RedirectResponse
    {
        $this->educationService->manageFaq($request->validated(), null, $request->user());

        return redirect()->route('admin.education.faq.index', ['tab' => 'faqs'])
            ->with('success', 'Pertanyaan FAQ berhasil ditambahkan.');
    }

    public function update(FaqRequest $request, int $id): RedirectResponse
    {
        $faq = EdukasiFaq::findOrFail($id);
        $this->educationService->manageFaq($request->validated(), $faq);

        return redirect()->route('admin.education.faq.index', ['tab' => 'faqs'])
            ->with('success', 'Pertanyaan FAQ berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $faq = EdukasiFaq::findOrFail($id);
        $this->educationService->deleteFaq($faq);

        return redirect()->route('admin.education.faq.index', ['tab' => 'faqs'])
            ->with('success', 'Pertanyaan FAQ berhasil dihapus.');
    }

    public function storeTopic(FaqTopikRequest $request): RedirectResponse
    {
        $this->educationService->manageFaqTopic($request->validated());

        return redirect()->route('admin.education.faq.index', ['tab' => 'topics'])
            ->with('success', 'Topik FAQ berhasil ditambahkan.');
    }

    public function updateTopic(FaqTopikRequest $request, int $id): RedirectResponse
    {
        $topic = EdukasiFaqTopik::findOrFail($id);
        $this->educationService->manageFaqTopic($request->validated(), $topic);

        return redirect()->route('admin.education.faq.index', ['tab' => 'topics'])
            ->with('success', 'Topik FAQ berhasil diperbarui.');
    }

    public function destroyTopic(int $id): RedirectResponse
    {
        $topic = EdukasiFaqTopik::withCount('faqs')->findOrFail($id);

        if ($topic->faqs_count > 0) {
            return redirect()->route('admin.education.faq.index', ['tab' => 'topics'])
                ->with('error', 'Topik tidak dapat dihapus karena masih memuat pertanyaan FAQ.');
        }

        $this->educationService->deleteFaqTopic($topic);

        return redirect()->route('admin.education.faq.index', ['tab' => 'topics'])
            ->with('success', 'Topik FAQ berhasil dihapus.');
    }
}
