<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDokumen;
use App\Services\KycService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminKycController extends Controller
{
    public function __construct(
        private readonly KycService $kycService,
    ) {}

    /**
     * Tampilkan daftar verifikasi berkas KYC investor.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $statusFilter = $request->string('status')->trim()->value();
        $sortField = $request->string('sort_by', $request->string('sort', 'created_at')->value())->value();
        $sortDirection = $request->string('sort_direction', $request->string('direction', 'desc')->value())->value();
        $perPage = $request->integer('per_page', 10);

        $allowedSorts = ['name', 'email', 'created_at', 'status'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
        }
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $query = User::query()
            ->whereIn('role', ['investor', 'affiliator'])
            ->with(['ahliWaris', 'dokumen']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['pending', 'active', 'suspended', 'rejected'], true)) {
            $query->where('status', $statusFilter);
        }

        $users = $query->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (User $user) {
                $ktp = $user->dokumen->firstWhere('tipe_dokumen', 'ktp');
                $perjanjian = $user->dokumen->firstWhere('tipe_dokumen', 'perjanjian');

                return [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'username'        => $user->username,
                    'email'           => $user->email,
                    'nik'             => $user->nik,
                    'phone'           => $user->phone,
                    'alamat'          => $user->alamat,
                    'role'            => $user->role,
                    'status'          => $user->status,
                    'created_at'      => tanggal_waktu_indo($user->created_at),
                    'terms_version'   => $user->terms_version,
                    'terms_agreed_at' => tanggal_waktu_indo($user->terms_agreed_at),
                    'ahli_waris'      => $user->ahliWaris ? [
                        'nama'              => $user->ahliWaris->nama,
                        'hubungan_keluarga' => $user->ahliWaris->hubungan_keluarga,
                        'no_hp'             => $user->ahliWaris->no_hp,
                        'alamat'            => $user->ahliWaris->alamat,
                    ] : null,
                    'dokumen' => [
                        'ktp' => $ktp ? [
                            'id'                 => $ktp->id,
                            'file_name'          => $ktp->file_name,
                            'file_size'          => $ktp->file_size,
                            'mime_type'          => $ktp->mime_type,
                            'status'             => $ktp->status,
                            'catatan_admin'      => $ktp->catatan_admin,
                            'is_quality_warning' => $ktp->is_quality_warning,
                            'verified_at'        => tanggal_waktu_indo($ktp->verified_at),
                            'preview_url'        => route('documents.show', $ktp->id, false),
                        ] : null,
                        'perjanjian' => $perjanjian ? [
                            'id'                 => $perjanjian->id,
                            'file_name'          => $perjanjian->file_name,
                            'file_size'          => $perjanjian->file_size,
                            'mime_type'          => $perjanjian->mime_type,
                            'status'             => $perjanjian->status,
                            'catatan_admin'      => $perjanjian->catatan_admin,
                            'is_quality_warning' => $perjanjian->is_quality_warning,
                            'verified_at'        => tanggal_waktu_indo($perjanjian->verified_at),
                            'preview_url'        => route('documents.show', $perjanjian->id, false),
                        ] : null,
                    ],
                ];
            });

        return Inertia::render('Admin/Kyc/Index', [
            'investors' => $users,
            'filters'   => [
                'search'         => $search,
                'status'         => $statusFilter,
                'sort'           => $sortField,
                'direction'      => $sortDirection,
                'sort_by'        => $sortField,
                'sort_direction' => $sortDirection,
                'per_page'       => $perPage,
            ],
        ]);
    }

    /**
     * Verifikasi status dokumen individual (approve, revision_needed, reject).
     */
    public function verify(Request $request, int $id): RedirectResponse
    {
        $dokumen = UserDokumen::with('user')->findOrFail($id);

        $validated = $request->validate([
            'status'        => ['required', 'string', 'in:approved,revision_needed,rejected'],
            'catatan_admin' => ['nullable', 'string', 'max:500'],
        ], [
            'status.required' => 'Pilih status verifikasi berkas.',
            'status.in'       => 'Pilihan status verifikasi tidak valid.',
        ]);

        $this->kycService->verifyDocument(
            $dokumen,
            $validated['status'],
            $validated['catatan_admin'] ?? null,
            $request->user()
        );

        $label = match ($validated['status']) {
            'approved'        => 'disetujui',
            'revision_needed' => 'diminta revisi',
            'rejected'        => 'ditolak',
        };

        return redirect()->back()
            ->with('success', "Berkas {$dokumen->tipe_dokumen} milik {$dokumen->user->name} berhasil {$label}.");
    }
}
