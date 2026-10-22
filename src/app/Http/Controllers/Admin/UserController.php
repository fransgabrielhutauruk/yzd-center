<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Tampilkan daftar seluruh pengguna (investor & affiliator).
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'role', 'status', 'is_active', 'sort_by', 'sort_direction', 'per_page']);
        $data = $this->userService->getUsersPaginated($filters);

        return Inertia::render('Admin/Users/Index', [
            'users'   => $data['users'],
            'stats'   => $data['stats'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Tampilkan halaman detail profil lengkap pengguna.
     */
    public function show(int $id): Response
    {
        $user = $this->userService->getUserDetail($id);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Nonaktifkan atau aktifkan kembali akun pengguna.
     */
    public function toggleStatus(Request $request, int $id): RedirectResponse
    {
        try {
            $user = $this->userService->toggleUserStatus($id, $request->user()->id);

            $statusText = $user->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

            return back()->with('success', "Akun pengguna {$user->name} berhasil {$statusText}.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
