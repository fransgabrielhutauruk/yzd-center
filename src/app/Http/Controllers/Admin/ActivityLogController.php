<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Tampilkan halaman riwayat audit log aktivitas sistem.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only([
            'search',
            'kategori',
            'user_role',
            'tingkat',
            'start_date',
            'end_date',
            'per_page',
        ]);

        $perPage = max(5, min(100, (int) ($filters['per_page'] ?? 15)));

        $logs = $this->auditLogService->getPaginatedLogs($filters, $perPage);
        $stats = $this->auditLogService->getAuditStats();

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs'            => $logs,
            'stats'           => $stats,
            'filters'         => $filters,
            'kategoriOptions' => AuditLogService::KATEGORI_LABELS,
            'aksiOptions'     => AuditLogService::AKSI_LABELS,
            'roleOptions'     => [
                'admin'      => 'Administrator',
                'investor'   => 'Investor',
                'affiliator' => 'Affiliator',
                'guest'      => 'Tamu / Sistem',
            ],
            'tingkatOptions' => [
                'info'     => 'Info',
                'warning'  => 'Peringatan',
                'critical' => 'Kritis',
            ],
        ]);
    }

    /**
     * Mengekspor audit log sesuai filter aktif ke berkas Excel (.xlsx).
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only([
            'search',
            'kategori',
            'user_role',
            'tingkat',
            'start_date',
            'end_date',
        ]);

        // Catat aktivitas ekspor ke dalam audit log
        $this->auditLogService->record(
            kategori: 'sistem',
            aksi: 'ekspor_audit_log',
            deskripsi: 'Mengekspor laporan XLSX jejak audit log aktivitas sistem',
            tingkat: 'info',
            user: $request->user(),
        );

        return $this->auditLogService->exportXlsx($filters);
    }
}
