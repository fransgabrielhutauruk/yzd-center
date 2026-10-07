<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investor\StoreCopytradeRequest;
use App\Models\AkunCopytrade;
use App\Models\PengaturanMaster;
use App\Services\CopytradeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CopytradeController extends Controller
{
    public function __construct(
        private readonly CopytradeService $copytradeService
    ) {}

    /**
     * Tampilkan halaman daftar copytrade dan informasi master broker.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $sortField = $request->string('sort_by', $request->string('sort', 'created_at')->value())->value();
        $sortDirection = $request->string('sort_direction', $request->string('direction', 'desc')->value())->value();
        $perPage = $request->integer('per_page', 10);

        $allowedSorts = ['nomor_akun_trading', 'tier', 'modal_riil', 'tanggal_mulai', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
        }
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $query = AkunCopytrade::query()
            ->where('user_id', $user->id);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_akun_trading', 'like', "%{$search}%")
                    ->orWhere('nama_bank', 'like', "%{$search}%")
                    ->orWhere('nomor_rekening', 'like', "%{$search}%");
            });
        }

        if ($status !== '' && in_array($status, ['pending', 'active', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $copytrades = $query->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (AkunCopytrade $akun) {
                return [
                    'id'             => $akun->id,
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
                    'has_surat_perjanjian' => !empty($akun->surat_perjanjian_path),
                    'created_at'           => function_exists('tanggal_singkat_waktu') ? tanggal_singkat_waktu($akun->created_at) : $akun->created_at->format('d M Y H:i'),
                ];
            });

        $hasExisting = AkunCopytrade::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->exists();
        $pengaturan = PengaturanMaster::getSettings();

        return Inertia::render('Investor/Copytrade/Index', [
            'copytrades'  => $copytrades,
            'pengaturan'  => $pengaturan,
            'hasExisting' => $hasExisting,
            'filters'     => [
                'search'         => $search,
                'status'         => $status,
                'sort_by'        => $sortField,
                'sort_direction' => $sortDirection,
                'per_page'       => $perPage,
            ],
        ]);
    }

    /**
     * Daftarkan akun copytrade baru.
     */
    public function store(StoreCopytradeRequest $request): RedirectResponse
    {
        $this->copytradeService->storeCopytrade(
            $request->user(),
            $request->validated(),
            $request->file('dokumen_perjanjian')
        );

        return redirect()->route('investor.copytrade.index')
            ->with('success', 'Akun Copytrade berhasil ditambahkan. Silakan menunggu proses verifikasi Administrator.');
    }

    /**
     * Unduh berkas surat perjanjian kemitraan (DOCX) yang terisi otomatis
     * dengan data profil investor dan data formulir pendaftaran.
     */
    public function downloadTemplate(Request $request, string $tier): BinaryFileResponse|RedirectResponse
    {
        $user = $request->user();
        $formData = $request->all();

        $generatedPath = $this->copytradeService->generatePerjanjianDocx($user, $tier, $formData);

        if (!$generatedPath || !file_exists($generatedPath)) {
            return redirect()->back()->with('error', 'Berkas template perjanjian belum tersedia atau gagal diproses.');
        }

        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $user->name);
        $tierLabel = strtoupper(str_replace('_', '', $tier));
        $downloadName = "Surat_Perjanjian_Kemitraan_{$tierLabel}_{$cleanName}.docx";

        return response()->download($generatedPath, $downloadName)->deleteFileAfterSend(true);
    }

    /**
     * Unduh berkas PDF panduan tier copytrade untuk investor.
     */
    public function downloadGuidePdf(string $tier): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!in_array($tier, ['tier_1', 'tier_2', 'tier_3'], true)) {
            abort(404, 'Tier tidak ditemukan.');
        }

        $pengaturan = PengaturanMaster::getSettings();
        $pathColumn = "{$tier}_pdf_path";
        $path = $pengaturan->$pathColumn;

        if (!$path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            abort(404, 'Berkas panduan PDF untuk tier ini belum diunggah oleh Administrator.');
        }

        $tierLabels = [
            'tier_1' => 'Tier-1-Cent',
            'tier_2' => 'Tier-2-Standard',
            'tier_3' => 'Tier-3-Private-VPS',
        ];
        $filename = "Panduan-Copytrade-{$tierLabels[$tier]}.pdf";

        return \Illuminate\Support\Facades\Storage::disk('local')->response(
            $path,
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
