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

        $baseQuery = User::query()
            ->whereIn('role', ['investor', 'affiliator']);

        $counts = [
            'all'             => (clone $baseQuery)->count(),
            'pending'         => (clone $baseQuery)->where('status', 'pending')->count(),
            'active'          => (clone $baseQuery)->where('status', 'active')->count(),
            'revision_needed' => (clone $baseQuery)->whereHas('dokumen', function ($q) {
                $q->where('status', 'revision_needed');
            })->count(),
        ];

        $query = (clone $baseQuery)
            ->with(['ahliWaris', 'dokumen', 'relasiUpline.affiliator']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($statusFilter !== '') {
            if ($statusFilter === 'pending') {
                $query->where('status', 'pending');
            } elseif ($statusFilter === 'active') {
                $query->where('status', 'active');
            } elseif ($statusFilter === 'revision_needed') {
                $query->whereHas('dokumen', function ($q) {
                    $q->where('status', 'revision_needed');
                });
            }
        }

        $users = $query->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (User $user) {
                $ktp = $user->dokumen->firstWhere('tipe_dokumen', 'ktp');
                $upline = $user->relasiUpline?->affiliator;

                return [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'username'        => $user->username,
                    'email'           => $user->email,
                    'nik'             => $user->nik,
                    'phone'           => $user->phone,
                    'alamat'          => $user->alamat,
                    'tempat_lahir'    => $user->tempat_lahir,
                    'tanggal_lahir'   => $user->tanggal_lahir ? tanggal_indo($user->tanggal_lahir) : null,
                    'pekerjaan'       => $user->pekerjaan,
                    'role'            => $user->role,
                    'status'          => $user->status,
                    'created_at'      => tanggal_waktu_indo($user->created_at),
                    'created_at_date' => $user->created_at ? tanggal_indo($user->created_at) : '-',
                    'created_at_time' => $user->created_at ? $user->created_at->format('H:i:s \W\I\B') : '',
                    'terms_agreed_at' => $user->terms_agreed_at ? tanggal_waktu_indo($user->terms_agreed_at) : null,
                    'upline'          => $upline ? [
                        'name'          => $upline->name,
                        'username'      => $upline->username,
                        'referral_code' => $upline->referral_code,
                    ] : null,
                    'ahli_waris' => $user->ahliWaris ? [
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
                            'verified_at'        => $ktp->verified_at ? tanggal_waktu_indo($ktp->verified_at) : null,
                            'preview_url'        => route('documents.show', $ktp->id, false),
                        ] : null,
                    ],
                ];
            });

        return Inertia::render('Admin/Kyc/Index', [
            'investors' => $users,
            'counts'    => $counts,
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
     * Verifikasi status dokumen individual (approved, revision_needed).
     */
    public function verify(Request $request, int $id): RedirectResponse
    {
        $dokumen = UserDokumen::with('user')->findOrFail($id);

        $validated = $request->validate([
            'status'        => ['required', 'string', 'in:approved,revision_needed'],
            'catatan_admin' => ['nullable', 'string', 'max:500'],
        ], [
            'status.required' => 'Pilih status verifikasi berkas.',
            'status.in'       => 'Pilihan status verifikasi tidak valid. Hanya disetujui atau minta revisi yang diperbolehkan.',
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
        };

        return redirect()->back()
            ->with('success', "Berkas {$dokumen->tipe_dokumen} milik {$dokumen->user->name} berhasil {$label}.");
    }
}
