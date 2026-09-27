<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvitationRequest;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService,
    ) {}

    /**
     * Tampilkan daftar manajemen tautan undangan.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'role', 'sort_by', 'sort_direction', 'per_page']);

        return Inertia::render('Admin/Invitations/Index', [
            'invitations' => $this->invitationService->getPaginatedInvitations($filters),
            'filters'     => $filters,
        ]);
    }

    /**
     * Simpan tautan undangan baru.
     */
    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        $invitation = $this->invitationService->createInvitation(
            $request->validated(),
            $request->user()
        );

        return redirect()->route('admin.invitations.index')
            ->with('success', "Tautan undangan baru berhasil dibuat: {$invitation->label}");
    }

    /**
     * Hapus tautan undangan.
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->invitationService->deleteInvitation($id);

        return redirect()->route('admin.invitations.index')
            ->with('success', 'Tautan undangan berhasil dihapus.');
    }

    /**
     * Hapus beberapa tautan undangan sekaligus (batch delete).
     */
    public function batchDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ], [
            'ids.required' => 'Pilih minimal satu tautan undangan untuk dihapus.',
            'ids.min'      => 'Pilih minimal satu tautan undangan untuk dihapus.',
        ]);

        $count = $this->invitationService->batchDeleteInvitations($validated['ids']);

        return redirect()->route('admin.invitations.index')
            ->with('success', "{$count} tautan undangan berhasil dihapus.");
    }
}
