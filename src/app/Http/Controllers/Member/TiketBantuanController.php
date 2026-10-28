<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\ReplyTiketRequest;
use App\Http\Requests\Member\StoreTiketRequest;
use App\Models\TiketBantuan;
use App\Services\TiketBantuanService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TiketBantuanController extends Controller
{
    public function __construct(
        protected TiketBantuanService $tiketService
    ) {}

    /**
     * Tampilkan daftar riwayat tiket bantuan milik member.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['search', 'status']);
        $tickets = $this->tiketService->paginateForMember($user, $filters);

        $counts = [
            'total'    => TiketBantuan::where('user_id', $user->id)->count(),
            'active'   => TiketBantuan::where('user_id', $user->id)->aktif()->count(),
            'resolved' => TiketBantuan::where('user_id', $user->id)->where('status', TiketBantuan::STATUS_RESOLVED)->count(),
            'closed'   => TiketBantuan::where('user_id', $user->id)->where('status', TiketBantuan::STATUS_CLOSED)->count(),
        ];

        return Inertia::render('Member/Tickets/Index', [
            'tickets'       => $tickets,
            'filters'       => $filters,
            'counts'        => $counts,
            'status_labels' => TiketBantuan::STATUS_LABELS,
        ]);
    }

    /**
     * Formulir pembuatan tiket baru.
     */
    public function create(): Response
    {
        return Inertia::render('Member/Tickets/Create');
    }

    /**
     * Simpan tiket bantuan baru.
     */
    public function store(StoreTiketRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $images = $request->file('images', []);

        $tiket = $this->tiketService->createTicket($request->user(), $validated, $images);

        return redirect()->route('member.tickets.show', $tiket->id)
            ->with('success', "Tiket [{$tiket->nomor}] berhasil dibuat. Tim kami akan segera menindaklanjuti.");
    }

    /**
     * Tampilkan rincian percakapan tiket bantuan.
     */
    public function show(Request $request, int|string $id): Response
    {
        $ticket = $this->tiketService->getTicketDetail($id, $request->user());

        return Inertia::render('Member/Tickets/Show', [
            'ticket'        => $ticket,
            'status_labels' => TiketBantuan::STATUS_LABELS,
        ]);
    }

    /**
     * Kirim balasan pada tiket yang sedang aktif.
     */
    public function reply(ReplyTiketRequest $request, int|string $id): RedirectResponse
    {
        $user = $request->user();
        $ticket = $this->tiketService->getTicketDetail($id, $user);

        try {
            $this->tiketService->replyByMember(
                tiket: $ticket,
                user: $user,
                pesan: $request->validated('pesan'),
                images: $request->file('images', [])
            );

            return back()->with('success', 'Balasan berhasil dikirim.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Tandai tiket telah selesai (resolved) oleh member.
     */
    public function resolve(Request $request, int|string $id): RedirectResponse
    {
        $user = $request->user();
        $ticket = $this->tiketService->getTicketDetail($id, $user);

        try {
            $this->tiketService->resolveByMember($ticket, $user);

            return back()->with('success', "Tiket [{$ticket->nomor}] berhasil ditandai selesai.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Buka kembali tiket yang telah resolved oleh member.
     */
    public function reopen(Request $request, int|string $id): RedirectResponse
    {
        $user = $request->user();
        $ticket = $this->tiketService->getTicketDetail($id, $user);

        try {
            $this->tiketService->reopenByMember($ticket, $user);

            return back()->with('success', "Tiket [{$ticket->nomor}] berhasil dibuka kembali.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
