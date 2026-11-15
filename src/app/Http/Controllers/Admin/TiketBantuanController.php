<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyTiketRequest;
use App\Http\Requests\Admin\UpdateTiketStatusRequest;
use App\Models\TiketBantuan;
use App\Services\TiketBantuanService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TiketBantuanController extends Controller
{
    public function __construct(
        protected TiketBantuanService $tiketService
    ) {}

    /**
     * Tampilkan antrean dan daftar seluruh tiket bantuan untuk admin.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);
        $tickets = $this->tiketService->paginateForAdmin($filters);

        $counts = [
            'total'         => TiketBantuan::count(),
            'butuh_respons' => TiketBantuan::butuhResponsAdmin()->count(),
            'open'          => TiketBantuan::where('status', TiketBantuan::STATUS_OPEN)->count(),
            'in_progress'   => TiketBantuan::where('status', TiketBantuan::STATUS_IN_PROGRESS)->count(),
            'answered'      => TiketBantuan::where('status', TiketBantuan::STATUS_ANSWERED)->count(),
            'resolved'      => TiketBantuan::where('status', TiketBantuan::STATUS_RESOLVED)->count(),
            'closed'        => TiketBantuan::where('status', TiketBantuan::STATUS_CLOSED)->count(),
        ];

        return Inertia::render('Admin/Tickets/Index', [
            'tickets'       => $tickets,
            'filters'       => $filters,
            'counts'        => $counts,
            'status_labels' => TiketBantuan::STATUS_LABELS,
        ]);
    }

    /**
     * Ekspor data seluruh tiket bantuan ke format berkas XLSX.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['search', 'status']);

        return $this->tiketService->exportXlsx($filters);
    }

    /**
     * Tampilkan detail tiket dan utas percakapan.
     */
    public function show(Request $request, int|string $id): Response
    {
        $ticket = $this->tiketService->getTicketDetail($id);

        return Inertia::render('Admin/Tickets/Show', [
            'ticket'        => $ticket,
            'status_labels' => TiketBantuan::STATUS_LABELS,
        ]);
    }

    /**
     * Kirim balasan/tanggapan dari admin.
     */
    public function reply(ReplyTiketRequest $request, int|string $id): RedirectResponse
    {
        $ticket = $this->tiketService->getTicketDetail($id);

        try {
            $this->tiketService->replyByAdmin(
                tiket: $ticket,
                admin: $request->user(),
                pesan: $request->validated('pesan'),
                images: $request->file('images', [])
            );

            return back()->with('success', 'Tanggapan berhasil dikirim ke member.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Perbarui status tiket secara manual oleh admin.
     */
    public function updateStatus(UpdateTiketStatusRequest $request, int|string $id): RedirectResponse
    {
        $ticket = $this->tiketService->getTicketDetail($id);

        try {
            $this->tiketService->updateStatusByAdmin(
                tiket: $ticket,
                admin: $request->user(),
                newStatus: $request->validated('status'),
                catatan: $request->validated('catatan')
            );

            $label = TiketBantuan::STATUS_LABELS[$request->validated('status')] ?? $request->validated('status');

            return back()->with('success', "Status tiket berhasil diubah menjadi '{$label}'.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
