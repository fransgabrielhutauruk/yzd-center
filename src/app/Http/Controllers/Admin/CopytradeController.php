<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyCopytradeRequest;
use App\Models\AkunCopytrade;
use App\Services\CopytradeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CopytradeController extends Controller
{
    public function __construct(
        private readonly CopytradeService $copytradeService
    ) {}

    /**
     * Tampilkan daftar pendaftaran akun copytrade untuk peninjauan Administrator.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $status = $request->string('status', 'pending')->trim()->value();
        $tier = $request->string('tier')->trim()->value();
        $sortField = $request->string('sort_by', $request->string('sort', 'created_at')->value())->value();
        $sortDirection = $request->string('sort_direction', $request->string('direction', 'desc')->value())->value();
        $perPage = $request->integer('per_page', 10);

        $allowedSorts = ['created_at', 'nomor_akun_trading', 'tier', 'modal_riil', 'status', 'copy_started_at'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
        }
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $query = AkunCopytrade::query()
            ->with(['user', 'verifier']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_akun_trading', 'like', "%{$search}%")
                    ->orWhere('nama_bank', 'like', "%{$search}%")
                    ->orWhere('nomor_rekening', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '' && $status !== 'all') {
            if (in_array($status, ['pending', 'active', 'rejected'], true)) {
                $query->where('status', $status);
            }
        }

        if ($tier !== '' && in_array($tier, ['tier_1', 'tier_2', 'tier_3'], true)) {
            $query->where('tier', $tier);
        }

        $copytrades = $query->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (AkunCopytrade $akun) {
                return [
                    'id'   => $akun->id,
                    'user' => [
                        'id'       => $akun->user?->id,
                        'name'     => $akun->user?->name ?? 'Investor',
                        'username' => $akun->user?->username ?? '',
                        'email'    => $akun->user?->email ?? '',
                        'phone'    => $akun->user?->phone ?? '',
                    ],
                    'tier'           => $akun->tier,
                    'tier_investasi' => $akun->tier,
                    'tier_label'     => match ($akun->tier) {
                        'tier_1' => 'Tier 1',
                        'tier_2' => 'Tier 2',
                        'tier_3' => 'Tier 3',
                        default  => $akun->tier,
                    },
                    'broker'               => $akun->broker,
                    'tipe_akun'            => $akun->tipe_akun,
                    'nomor_akun_trading'   => $akun->nomor_akun_trading,
                    'modal_riil'           => (float) $akun->modal_riil,
                    'modal_riil_formatted' => '$' . number_format((float) $akun->modal_riil, 2, '.', ','),
                    'tanggal_mulai'        => $akun->tanggal_mulai ? (function_exists('tanggal_indo') ? tanggal_indo($akun->tanggal_mulai) : $akun->tanggal_mulai->format('d M Y')) : '-',
                    'nama_bank'            => $akun->nama_bank,
                    'nomor_rekening'       => $akun->nomor_rekening,
                    'atas_nama_rekening'   => $akun->atas_nama_rekening,
                    'status'               => $akun->status,
                    'catatan_admin'        => $akun->catatan_admin,
                    'server_broker'        => $akun->server_broker,
                    'master_password'      => $akun->master_password,
                    'investor_password'    => $akun->investor_password,
                    'url_mql5'             => $akun->url_mql5,
                    'has_surat_perjanjian' => !empty($akun->surat_perjanjian_path),
                    'surat_perjanjian_url' => $akun->surat_perjanjian_path ? route('documents.copytrade', $akun->id) : null,
                    'created_at'           => function_exists('tanggal_singkat_waktu') ? tanggal_singkat_waktu($akun->created_at) : $akun->created_at->format('d M Y H:i'),
                    'copy_started_at'      => $akun->copy_started_at ? (function_exists('tanggal_singkat_waktu') ? tanggal_singkat_waktu($akun->copy_started_at) : $akun->copy_started_at->format('d M Y H:i')) : null,
                    'verified_at'          => $akun->verified_at ? (function_exists('tanggal_singkat_waktu') ? tanggal_singkat_waktu($akun->verified_at) : $akun->verified_at->format('d M Y H:i')) : null,
                    'verifier_name'        => $akun->verifier?->name,
                ];
            });

        $counts = [
            'pending'  => AkunCopytrade::where('status', 'pending')->count(),
            'active'   => AkunCopytrade::where('status', 'active')->count(),
            'rejected' => AkunCopytrade::where('status', 'rejected')->count(),
            'all'      => AkunCopytrade::count(),
        ];

        return Inertia::render('Admin/Copytrade/Index', [
            'copytrades' => $copytrades,
            'counts'     => $counts,
            'filters'    => [
                'search'         => $search,
                'status'         => $status,
                'tier'           => $tier,
                'sort_by'        => $sortField,
                'sort_direction' => $sortDirection,
                'per_page'       => $perPage,
            ],
        ]);
    }

    /**
     * Verifikasi keputusan permohonan copy trade.
     */
    public function verify(VerifyCopytradeRequest $request, int $id): RedirectResponse
    {
        $akun = AkunCopytrade::with('user')->findOrFail($id);
        $validated = $request->validated();

        $this->copytradeService->verifyAccount(
            $akun,
            $validated['status'],
            $validated['catatan_admin'] ?? null,
            $request->user()
        );

        $statusText = $validated['status'] === 'active' ? 'disetujui & diaktifkan' : 'ditolak';

        return redirect()->back()->with('success', "Permohonan akun copytrade ({$akun->nomor_akun_trading}) berhasil {$statusText}.");
    }
}
