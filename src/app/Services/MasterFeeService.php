<?php

namespace App\Services;

use App\Models\AkunCopytrade;
use App\Models\PengaturanMasterFee;
use App\Models\RiwayatBalanceCopytrade;
use App\Models\TagihanMasterFee;
use App\Models\User;
use App\Notifications\MasterFeeIssuedNotification;
use App\Notifications\MasterFeeProofSubmittedNotification;
use App\Notifications\MasterFeeRejectedNotification;
use App\Notifications\MasterFeeVerifiedNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterFeeService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Sinkronisasi tagihan master fee untuk catatan rekap saldo.
     * Dipanggil otomatis saat rekap dibuat atau diubah.
     */
    public function syncInvoiceForBalance(RiwayatBalanceCopytrade $record): ?TagihanMasterFee
    {
        $akun = $record->akunCopytrade;

        // Cari record saldo sebelum tanggal pencatatan ini
        $prev = RiwayatBalanceCopytrade::query()
            ->where('akun_copytrade_id', $akun->id)
            ->where('tanggal_pencatatan', '<', $record->tanggal_pencatatan)
            ->orderByDesc('tanggal_pencatatan')
            ->first();

        $baseBalance = $prev ? (float) $prev->balance : (float) $akun->modal_riil;
        $penarikan = (float) ($record->penarikan ?? 0);
        $setoran = (float) ($record->setoran_tambahan ?? 0);

        // Rumus profit bersih: balance_akhir - balance_sebelumnya + penarikan - setoran_tambahan
        $profitBersih = round((float) $record->balance - $baseBalance + $penarikan - $setoran, 2);

        $invoice = TagihanMasterFee::query()
            ->where('riwayat_balance_id', $record->id)
            ->first();

        // Jika profit bersih <= 0 (impas atau rugi)
        if ($profitBersih <= 0) {
            if ($invoice) {
                // Jika tagihan belum dibayar atau ditolak, hapus tagihannya
                if (in_array($invoice->status, [TagihanMasterFee::STATUS_BELUM_DIBAYAR, TagihanMasterFee::STATUS_DITOLAK], true)) {
                    $invoice->delete();
                }
            }

            return null;
        }

        // Jika profit bersih > 0
        if ($invoice) {
            // Perbarui tagihan yang sudah ada (hanya jika belum terkunci)
            $invoice->profit_bersih = $profitBersih;
            $invoice->nominal_fee = round($profitBersih * ($invoice->fee_persen / 100), 2);
            $invoice->save();

            return $invoice;
        }

        // Buat tagihan baru
        $settings = PengaturanMasterFee::current();
        $pencatatanDate = Carbon::parse($record->tanggal_pencatatan);
        // Periode profit adalah bulan sebelum tanggal_pencatatan
        $periode = $pencatatanDate->copy()->subMonth()->startOfMonth()->toDateString();
        $nomor = $this->generateNomor($pencatatanDate);
        $jatuhTempo = $pencatatanDate->copy()->setDay(10)->toDateString();
        $feePersen = (int) $settings->fee_persen;
        $nominalFee = round($profitBersih * ($feePersen / 100), 2);

        $newInvoice = TagihanMasterFee::create([
            'nomor'              => $nomor,
            'riwayat_balance_id' => $record->id,
            'akun_copytrade_id'  => $akun->id,
            'user_id'            => $record->user_id,
            'periode'            => $periode,
            'profit_bersih'      => $profitBersih,
            'fee_persen'         => $feePersen,
            'nominal_fee'        => $nominalFee,
            'jatuh_tempo'        => $jatuhTempo,
            'status'             => TagihanMasterFee::STATUS_BELUM_DIBAYAR,
        ]);

        // Kirim notifikasi tagihan baru ke member
        $record->user?->notify(new MasterFeeIssuedNotification($newInvoice));

        return $newInvoice;
    }

    /**
     * Menghasilkan nomor tagihan berurutan dengan format MFE-YYYYMM-XXXX.
     */
    public function generateNomor(Carbon $date): string
    {
        $yearMonth = $date->format('Ym');
        $prefix = "MFE-{$yearMonth}-";

        $lastNumber = TagihanMasterFee::query()
            ->where('nomor', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderByDesc('nomor')
            ->value('nomor');

        $sequence = 1;
        if ($lastNumber && preg_match('/-(\d{4})$/', $lastNumber, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('MFE-%s-%04d', $yearMonth, $sequence);
    }

    /**
     * Mengunggah bukti transfer pembayaran master fee oleh member.
     */
    public function submitProof(TagihanMasterFee $tagihan, UploadedFile $file, User $user): TagihanMasterFee
    {
        if ($tagihan->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'bukti' => 'Anda tidak memiliki otorisasi untuk tagihan ini.',
            ]);
        }

        if (!in_array($tagihan->status, [TagihanMasterFee::STATUS_BELUM_DIBAYAR, TagihanMasterFee::STATUS_DITOLAK], true)) {
            throw ValidationException::withMessages([
                'bukti' => 'Bukti pembayaran hanya dapat diunggah untuk tagihan yang belum dibayar atau ditolak.',
            ]);
        }

        return DB::transaction(function () use ($tagihan, $file, $user) {
            $ext = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = 'proof_' . $tagihan->id . '_' . time() . '.' . $ext;
            $path = $file->storeAs("master_fee_proofs/{$tagihan->akun_copytrade_id}", $filename, 'local');

            $tagihan->update([
                'bukti_transfer_path' => $path,
                'status'              => TagihanMasterFee::STATUS_MENUNGGU_VERIFIKASI,
                'dibayar_at'          => Carbon::now(),
                'catatan_admin'       => null,
            ]);

            // Audit Log
            $this->auditLogService->record(
                kategori: 'master_fee',
                aksi: 'unggah_bukti',
                deskripsi: "Member {$user->name} mengunggah bukti pembayaran untuk tagihan Master Fee {$tagihan->nomor} (\${$tagihan->nominal_fee}).",
                subject: $tagihan,
                user: $user
            );

            // Kirim notifikasi ke seluruh administrator
            $admins = User::where('role', 'administrator')->get();
            foreach ($admins as $admin) {
                $admin->notify(new MasterFeeProofSubmittedNotification($tagihan));
            }

            return $tagihan->fresh();
        });
    }

    /**
     * Verifikasi tagihan oleh admin (status menjadi lunas).
     */
    public function verify(TagihanMasterFee|int $id, User $admin): TagihanMasterFee
    {
        $tagihan = $id instanceof TagihanMasterFee ? $id : TagihanMasterFee::findOrFail($id);

        return DB::transaction(function () use ($tagihan, $admin) {
            $tagihan->update([
                'status'      => TagihanMasterFee::STATUS_LUNAS,
                'verified_by' => $admin->id,
                'verified_at' => Carbon::now(),
                'dibayar_at'  => $tagihan->dibayar_at ?? Carbon::now(),
            ]);

            $this->auditLogService->record(
                kategori: 'master_fee',
                aksi: 'verifikasi',
                deskripsi: "Admin {$admin->name} memverifikasi pembayaran tagihan Master Fee {$tagihan->nomor} (\${$tagihan->nominal_fee}) menjadi LUNAS.",
                subject: $tagihan,
                user: $admin
            );

            $tagihan->user?->notify(new MasterFeeVerifiedNotification($tagihan));

            return $tagihan->fresh();
        });
    }

    /**
     * Menolak bukti pembayaran tagihan oleh admin.
     */
    public function reject(TagihanMasterFee|int $id, User $admin, string $alasan): TagihanMasterFee
    {
        $tagihan = $id instanceof TagihanMasterFee ? $id : TagihanMasterFee::findOrFail($id);

        return DB::transaction(function () use ($tagihan, $admin, $alasan) {
            $tagihan->update([
                'status'        => TagihanMasterFee::STATUS_DITOLAK,
                'catatan_admin' => trim($alasan),
                'verified_by'   => $admin->id,
                'verified_at'   => Carbon::now(),
            ]);

            $this->auditLogService->record(
                kategori: 'master_fee',
                aksi: 'tolak',
                deskripsi: "Admin {$admin->name} menolak pembayaran tagihan Master Fee {$tagihan->nomor}. Alasan: {$alasan}",
                subject: $tagihan,
                user: $admin
            );

            $tagihan->user?->notify(new MasterFeeRejectedNotification($tagihan, $alasan));

            return $tagihan->fresh();
        });
    }

    /**
     * Pagination data tagihan untuk portal member.
     */
    public function paginateForMember(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = TagihanMasterFee::query()
            ->with(['akun', 'riwayatBalance'])
            ->where('user_id', $user->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $nowMonth = Carbon::now()->format('Y-m');
        if (!empty($filters['periode_dari']) && $filters['periode_dari'] > $nowMonth) {
            $filters['periode_dari'] = $nowMonth;
        }
        if (!empty($filters['periode_sampai']) && $filters['periode_sampai'] > $nowMonth) {
            $filters['periode_sampai'] = $nowMonth;
        }

        if (!empty($filters['periode_dari'])) {
            $startDate = Carbon::parse($filters['periode_dari'])->startOfMonth()->toDateString();
            $query->whereDate('periode', '>=', $startDate);
        }

        if (!empty($filters['periode_sampai'])) {
            $endDate = Carbon::parse($filters['periode_sampai'])->endOfMonth()->toDateString();
            $query->whereDate('periode', '<=', $endDate);
        }

        if (empty($filters['periode_dari']) && empty($filters['periode_sampai']) && !empty($filters['periode'])) {
            $query->where('periode', 'like', $filters['periode'] . '%');
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhereHas('akun', function ($aq) use ($search) {
                        $aq->where('nomor_akun_trading', 'like', "%{$search}%")
                            ->orWhere('broker', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderByDesc('periode')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Pagination data tagihan untuk portal admin.
     */
    public function paginateForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = TagihanMasterFee::query()
            ->with(['akun', 'user', 'riwayatBalance', 'verifier']);

        $nowMonth = Carbon::now()->format('Y-m');
        if (!empty($filters['periode_dari']) && $filters['periode_dari'] > $nowMonth) {
            $filters['periode_dari'] = $nowMonth;
        }
        if (!empty($filters['periode_sampai']) && $filters['periode_sampai'] > $nowMonth) {
            $filters['periode_sampai'] = $nowMonth;
        }

        if (!empty($filters['periode_dari'])) {
            $startDate = Carbon::parse($filters['periode_dari'])->startOfMonth()->toDateString();
            $query->whereDate('periode', '>=', $startDate);
        }

        if (!empty($filters['periode_sampai'])) {
            $endDate = Carbon::parse($filters['periode_sampai'])->endOfMonth()->toDateString();
            $query->whereDate('periode', '<=', $endDate);
        }

        if (empty($filters['periode_dari']) && empty($filters['periode_sampai']) && !empty($filters['periode'])) {
            $query->where('periode', 'like', $filters['periode'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['terlambat'])) {
            $query->whereIn('status', [TagihanMasterFee::STATUS_BELUM_DIBAYAR, TagihanMasterFee::STATUS_DITOLAK])
                ->whereDate('jatuh_tempo', '<', Carbon::now()->startOfDay());
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    })
                    ->orWhereHas('akun', function ($aq) use ($search) {
                        $aq->where('nomor_akun_trading', 'like', "%{$search}%")
                            ->orWhere('broker', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderByDesc('periode')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'tagihan_page')
            ->withQueryString();
    }

    /**
     * Ringkasan performa tagihan Master Fee.
     */
    public function summary(array|string|null $filters = null): array
    {
        $query = TagihanMasterFee::query();
        $periodeParam = null;
        $nowMonth = Carbon::now()->format('Y-m');

        if (is_string($filters)) {
            $sanitized = $filters > $nowMonth ? $nowMonth : $filters;
            $query->where('periode', 'like', $sanitized . '%');
            $periodeParam = $sanitized;
        } elseif (is_array($filters)) {
            if (!empty($filters['periode_dari']) && $filters['periode_dari'] > $nowMonth) {
                $filters['periode_dari'] = $nowMonth;
            }
            if (!empty($filters['periode_sampai']) && $filters['periode_sampai'] > $nowMonth) {
                $filters['periode_sampai'] = $nowMonth;
            }
            if (!empty($filters['missing_periode']) && $filters['missing_periode'] > $nowMonth) {
                $filters['missing_periode'] = $nowMonth;
            }

            if (!empty($filters['periode_dari'])) {
                $startDate = Carbon::parse($filters['periode_dari'])->startOfMonth()->toDateString();
                $query->whereDate('periode', '>=', $startDate);
            }
            if (!empty($filters['periode_sampai'])) {
                $endDate = Carbon::parse($filters['periode_sampai'])->endOfMonth()->toDateString();
                $query->whereDate('periode', '<=', $endDate);
            }
            if (empty($filters['periode_dari']) && empty($filters['periode_sampai']) && !empty($filters['periode'])) {
                $sanitized = $filters['periode'] > $nowMonth ? $nowMonth : $filters['periode'];
                $query->where('periode', 'like', $sanitized . '%');
            }
            $periodeParam = $filters['missing_periode'] ?? $filters['periode_sampai'] ?? $filters['periode_dari'] ?? $filters['periode'] ?? null;
        }

        $all = $query->get();

        $totalNominal = (float) $all->sum('nominal_fee');
        $totalCount = $all->count();

        $lunas = $all->where('status', TagihanMasterFee::STATUS_LUNAS);
        $menunggu = $all->where('status', TagihanMasterFee::STATUS_MENUNGGU_VERIFIKASI);
        $belumBayar = $all->where('status', TagihanMasterFee::STATUS_BELUM_DIBAYAR);
        $ditolak = $all->where('status', TagihanMasterFee::STATUS_DITOLAK);
        $terlambat = $all->filter(fn (TagihanMasterFee $t) => $t->is_terlambat);

        // Hitung akun yang belum rekap
        $targetPencatatan = $periodeParam
            ? Carbon::parse($periodeParam . '-01')->addMonth()->startOfMonth()->toDateString()
            : Carbon::now()->startOfMonth()->toDateString();

        $belumRekapCount = AkunCopytrade::query()
            ->where('status', 'active')
            ->whereDoesntHave('riwayatBalance', function ($q) use ($targetPencatatan) {
                $q->whereDate('tanggal_pencatatan', $targetPencatatan);
            })
            ->count();

        return [
            'total_tagihan_usd'         => round($totalNominal, 2),
            'total_tagihan_count'       => $totalCount,
            'lunas_usd'                 => round((float) $lunas->sum('nominal_fee'), 2),
            'lunas_count'               => $lunas->count(),
            'menunggu_verifikasi_usd'   => round((float) $menunggu->sum('nominal_fee'), 2),
            'menunggu_verifikasi_count' => $menunggu->count(),
            'belum_dibayar_usd'         => round((float) $belumBayar->sum('nominal_fee'), 2),
            'belum_dibayar_count'       => $belumBayar->count(),
            'ditolak_usd'               => round((float) $ditolak->sum('nominal_fee'), 2),
            'ditolak_count'             => $ditolak->count(),
            'terlambat_usd'             => round((float) $terlambat->sum('nominal_fee'), 2),
            'terlambat_count'           => $terlambat->count(),
            'belum_rekap_count'         => $belumRekapCount,
        ];
    }

    /**
     * Daftar akun copytrade yang belum melakukan rekap saldo pada periode bulan terkait.
     */
    public function missingRecap(?string $periode = null, array $filters = []): LengthAwarePaginator
    {
        $nowMonth = Carbon::now()->format('Y-m');
        if ($periode && $periode > $nowMonth) {
            $periode = $nowMonth;
        }

        $targetPencatatan = $periode
            ? Carbon::parse($periode . '-01')->addMonth()->startOfMonth()->toDateString()
            : Carbon::now()->startOfMonth()->toDateString();

        $query = AkunCopytrade::query()
            ->with('user')
            ->where('status', 'active')
            ->whereDoesntHave('riwayatBalance', function ($q) use ($targetPencatatan) {
                $q->whereDate('tanggal_pencatatan', $targetPencatatan);
            });

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nomor_akun_trading', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('nomor_akun_trading')
            ->paginate(15, ['*'], 'missing_page')
            ->withQueryString();
    }

    /**
     * Mengambil pengaturan Master Fee global.
     */
    public function getSettings(): PengaturanMasterFee
    {
        return PengaturanMasterFee::current();
    }

    /**
     * Memperbarui pengaturan Master Fee global.
     */
    public function updateSettings(array $data, User $admin): PengaturanMasterFee
    {
        $settings = PengaturanMasterFee::current();
        $sebelum = $settings->toArray();

        $settings->update([
            'nama_bank'      => $data['nama_bank'] ?? $settings->nama_bank,
            'nomor_rekening' => $data['nomor_rekening'] ?? $settings->nomor_rekening,
            'atas_nama'      => $data['atas_nama'] ?? $settings->atas_nama,
            'fee_persen'     => isset($data['fee_persen']) ? (int) $data['fee_persen'] : $settings->fee_persen,
        ]);

        $this->auditLogService->record(
            kategori: 'master_fee',
            aksi: 'ubah_pengaturan',
            deskripsi: "Admin {$admin->name} memperbarui pengaturan Master Fee (fee: {$settings->fee_persen}%).",
            subject: $settings,
            perubahan: [
                'sebelum' => $sebelum,
                'sesudah' => $settings->fresh()->toArray(),
            ],
            user: $admin
        );

        return $settings;
    }

    /**
     * Basis fee lunas akun copytrade dalam rentang tanggal pencatatan tertentu.
     * Digunakan untuk perhitungan komisi afiliator.
     */
    public function paidFeeBasis(AkunCopytrade $akun, Carbon $from, Carbon $to): float
    {
        return (float) TagihanMasterFee::query()
            ->where('akun_copytrade_id', $akun->id)
            ->where('status', TagihanMasterFee::STATUS_LUNAS)
            ->whereHas('riwayatBalance', function ($q) use ($from, $to) {
                $q->whereBetween('tanggal_pencatatan', [$from->toDateString(), $to->toDateString()]);
            })
            ->sum('nominal_fee');
    }
}
