<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvitationRequest;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
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
    public function index(): Response
    {
        return Inertia::render('Admin/Invitations/Index', [
            'invitations' => $this->invitationService->getInvitationsList(),
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
}
