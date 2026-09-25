<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Services\EducationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EducationController extends Controller
{
    public function __construct(
        protected EducationService $educationService
    ) {}

    public function glossary(Request $request): Response
    {
        $filters = [
            'search' => $request->input('search'),
            'huruf'  => $request->input('huruf'),
        ];

        $glossaryData = $this->educationService->getInvestorGlossary($filters);

        return Inertia::render('Investor/Education/Glossary', [
            'glossary' => $glossaryData,
        ]);
    }

    public function help(Request $request): Response
    {
        $kategoriSlug = $request->input('kategori');
        $search = $request->input('search');

        $helpData = $this->educationService->getInvestorHelpCenter($kategoriSlug, $search);

        return Inertia::render('Investor/Education/HelpCenter', [
            'help' => $helpData,
        ]);
    }

    public function article(string $slug): Response
    {
        $article = $this->educationService->getHelpArticleDetail($slug);

        if (!$article) {
            abort(404, 'Artikel panduan tidak ditemukan.');
        }

        return Inertia::render('Investor/Education/ArticleDetail', [
            'article' => $article,
        ]);
    }

    public function calculator(): Response
    {
        $presets = $this->educationService->getCalculatorPresets();

        return Inertia::render('Investor/Education/Calculator', [
            'presets' => $presets,
        ]);
    }

    public function faq(Request $request): Response
    {
        $filters = [
            'search'   => $request->input('search'),
            'topik_id' => $request->input('topik_id'),
        ];

        $faqData = $this->educationService->getInvestorFaq($filters);

        return Inertia::render('Investor/Education/Faq', [
            'faq'     => $faqData,
            'filters' => $filters,
        ]);
    }
}
