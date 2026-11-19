<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\ProfitMasterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfitMasterController extends Controller
{
    public function __construct(
        protected ProfitMasterService $profitMasterService
    ) {}

    /**
     * Tampilkan halaman feed materi promosi profit master untuk Member.
     */
    public function index(Request $request): Response
    {
        $dateRange = $this->profitMasterService->normalizeDateRange(
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
        );

        return Inertia::render('Member/ProfitMaster/Index', [
            'initialSearch'    => (string) $request->input('search', ''),
            'initialStartDate' => $dateRange['start_date'],
            'initialEndDate'   => $dateRange['end_date'],
            'todayDate'        => now()->toDateString(),
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
            perPage: (int) $request->input('per_page', 10),
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
        );

        return response()->json($feed);
    }
}
