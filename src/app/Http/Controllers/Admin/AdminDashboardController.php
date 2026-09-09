<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Tampilkan halaman utama dasbor admin.
     */
    public function index(): Response
    {
        $investorCount = User::where('role', 'investor')->count();
        $affiliatorCount = User::where('role', 'affiliator')->count();
        $activeInviteCount = Invitation::query()->active()->count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'investor_count'   => $investorCount,
                'affiliator_count' => $affiliatorCount,
                'active_invites'   => $activeInviteCount,
            ],
        ]);
    }
}
