<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvestorDashboardController extends Controller
{
    /**
     * Tampilkan halaman utama dasbor investor.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Investor/Dashboard', [
            'user' => [
                'name'          => $user->name,
                'email'         => $user->email,
                'role'          => $user->role,
                'referral_code' => $user->referral_code,
                'is_active'     => $user->is_active,
            ],
        ]);
    }
}
