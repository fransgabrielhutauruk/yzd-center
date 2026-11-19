<?php

namespace App\Services;

use App\Models\ProfitMasterPost;
use App\Models\ProfitMasterPostImage;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfitMasterService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Memvalidasi dan menormalisasi rentang tanggal filter agar format valid dan start <= end.
     *
     * @return array{start_date: string, end_date: string}
     */
    public function normalizeDateRange(?string $startDate, ?string $endDate): array
    {
        $start = $this->parseDateFilter($startDate);
        $end = $this->parseDateFilter($endDate);

        if ($start && $end && $start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return [
            'start_date' => $start?->format('Y-m-d') ?? '',
            'end_date'   => $end?->format('Y-m-d') ?? '',
        ];
    }

    /**
     * Mem-parsing string tanggal berformat Y-m-d secara ketat tanpa overflow kalender.
     */
    private function parseDateFilter(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed)) {
            return null;
        }

        try {
            $parsed = Carbon::createFromFormat('!Y-m-d', $trimmed);
        } catch (\Throwable) {
            return null;
        }

        if (!$parsed instanceof Carbon || $parsed->format('Y-m-d') !== $trimmed) {
            return null;
        }

        return $parsed;
    }

    /**
     * Mengambil daftar postingan profit master menggunakan cursor pagination.
     *
     * @return array<string, mixed>
     */
    public function feed(
        ?string $search = null,
        ?string $cursor = null,
        int $perPage = 10,
        ?string $startDate = null,
        ?string $endDate = null,
    ): array {
        $query = ProfitMasterPost::with([
            'creator:id,name,username',
            'images',
        ])->orderByDesc('id');

        if (!empty($search)) {
            $term = trim($search);
            $query->where('caption', 'like', "%{$term}%");
        }

        $range = $this->normalizeDateRange($startDate, $endDate);

        if ($range['start_date'] !== '' && $range['end_date'] !== '') {
            $query->whereDate('profit_start_date', '<=', $range['end_date'])
                ->whereDate('profit_end_date', '>=', $range['start_date']);
        } elseif ($range['start_date'] !== '') {
            $query->whereDate('profit_end_date', '>=', $range['start_date']);
        } elseif ($range['end_date'] !== '') {
            $query->whereDate('profit_start_date', '<=', $range['end_date']);
        }

        $paginator = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        $items = collect($paginator->items())->map(function (ProfitMasterPost $post) {
            return [
                'id'                    => $post->id,
                'caption'               => $post->caption,
                'profit_start_date'     => $post->profit_start_date?->format('Y-m-d'),
                'profit_end_date'       => $post->profit_end_date?->format('Y-m-d'),
                'formatted_profit_date' => $this->formatProfitDateRange($post->profit_start_date, $post->profit_end_date),
                'created_at'            => $post->created_at?->toIso8601String(),
                'formatted_date'        => $post->created_at?->translatedFormat('d M Y, H:i'),
                'relative_time'         => $post->created_at?->diffForHumans(),
                'creator'               => [
                    'name'     => $post->creator?->name ?? 'Master Trader',
                    'username' => $post->creator?->username ?? 'admin',
                ],
                'images' => $post->images->map(function (ProfitMasterPostImage $image) {
                    return [
                        'id'        => $image->id,
                        'url'       => route('documents.profit_image', $image->id),
                        'file_name' => $image->file_name,
                        'file_size' => $image->file_size,
                        'mime_type' => $image->mime_type,
                        'urutan'    => $image->urutan,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return [
            'data'        => $items,
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'prev_cursor' => $paginator->previousCursor()?->encode(),
            'has_more'    => $paginator->hasMorePages(),
        ];
    }

    /**
     * Memformat periode tanggal profit untuk badge kartu postingan.
     */
    private function formatProfitDateRange(?Carbon $start, ?Carbon $end): ?string
    {
        if (!$start || !$end) {
            return null;
        }

        if ($start->isSameDay($end)) {
            return $start->translatedFormat('d M Y');
        }

        if ($start->year === $end->year) {
            return $start->translatedFormat('d M') . ' – ' . $end->translatedFormat('d M Y');
        }

        return $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y');
    }

    /**
     * Menerbitkan postingan profit master baru beserta gambar-gambarnya.
     *
     * @param array<int, UploadedFile> $images
     */
    public function create(
        string $caption,
        array $images,
        int $adminId,
        ?string $profitStartDate = null,
        ?string $profitEndDate = null,
    ): ProfitMasterPost {
        return DB::transaction(function () use ($caption, $images, $adminId, $profitStartDate, $profitEndDate) {
            $range = $this->normalizeDateRange($profitStartDate, $profitEndDate);
            $today = now()->toDateString();
            $resolvedStart = $range['start_date'] !== ''
                ? $range['start_date']
                : ($range['end_date'] !== '' ? $range['end_date'] : $today);
            $resolvedEnd = $range['end_date'] !== '' ? $range['end_date'] : $resolvedStart;

            $post = ProfitMasterPost::create([
                'caption'           => $caption,
                'profit_start_date' => $resolvedStart,
                'profit_end_date'   => $resolvedEnd,
                'created_by'        => $adminId,
            ]);

            $disk = Storage::disk('local');
            $urutan = 0;

            foreach ($images as $file) {
                if ($file instanceof UploadedFile) {
                    $ext = $file->getClientOriginalExtension() ?: 'jpg';
                    $fileName = Str::uuid()->toString() . '.' . $ext;
                    $path = $disk->putFileAs("profit_master/{$post->id}", $file, $fileName);

                    ProfitMasterPostImage::create([
                        'post_id'   => $post->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName() ?: $fileName,
                        'file_size' => $file->getSize() ?: 0,
                        'mime_type' => $file->getMimeType() ?: 'image/jpeg',
                        'urutan'    => $urutan++,
                    ]);
                }
            }

            $this->auditLogService->record(
                kategori: 'profit_master',
                aksi: 'buat_profit_master',
                deskripsi: "Menerbitkan materi promosi profit master baru dengan {$urutan} gambar.",
                subject: $post,
            );

            return $post->load(['creator:id,name,username', 'images']);
        });
    }

    /**
     * Memperbarui postingan profit master, menambah gambar baru, dan menghapus gambar terpilih.
     *
     * @param array<int, UploadedFile> $newImages
     * @param array<int, int> $deletedImageIds
     */
    public function update(
        ProfitMasterPost $post,
        string $caption,
        array $newImages = [],
        array $deletedImageIds = [],
        ?string $profitStartDate = null,
        ?string $profitEndDate = null,
    ): ProfitMasterPost {
        return DB::transaction(function () use ($post, $caption, $newImages, $deletedImageIds, $profitStartDate, $profitEndDate) {
            $disk = Storage::disk('local');

            // 1. Hapus gambar yang ditandai hapus
            if (!empty($deletedImageIds)) {
                $imagesToDelete = ProfitMasterPostImage::where('post_id', $post->id)
                    ->whereIn('id', $deletedImageIds)
                    ->get();

                foreach ($imagesToDelete as $img) {
                    $img->delete();
                }
            }

            // 2. Tambah gambar baru jika ada
            if (!empty($newImages)) {
                $maxUrutan = (int) ProfitMasterPostImage::where('post_id', $post->id)->max('urutan');
                $nextUrutan = $maxUrutan + 1;

                foreach ($newImages as $file) {
                    if ($file instanceof UploadedFile) {
                        $ext = $file->getClientOriginalExtension() ?: 'jpg';
                        $fileName = Str::uuid()->toString() . '.' . $ext;
                        $path = $disk->putFileAs("profit_master/{$post->id}", $file, $fileName);

                        ProfitMasterPostImage::create([
                            'post_id'   => $post->id,
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName() ?: $fileName,
                            'file_size' => $file->getSize() ?: 0,
                            'mime_type' => $file->getMimeType() ?: 'image/jpeg',
                            'urutan'    => $nextUrutan++,
                        ]);
                    }
                }
            }

            // 3. Pastikan minimal masih ada 1 gambar tersisa
            $remainingCount = ProfitMasterPostImage::where('post_id', $post->id)->count();
            if ($remainingCount < 1) {
                throw new \InvalidArgumentException('Postingan wajib memiliki minimal 1 gambar bukti profit.');
            }

            // 4. Update caption dan tanggal periode profit
            $updatePayload = ['caption' => $caption];
            if ($profitStartDate !== null || $profitEndDate !== null) {
                $range = $this->normalizeDateRange($profitStartDate, $profitEndDate);
                if ($range['start_date'] !== '' && $range['end_date'] !== '') {
                    $updatePayload['profit_start_date'] = $range['start_date'];
                    $updatePayload['profit_end_date'] = $range['end_date'];
                }
            }

            $post->update($updatePayload);

            $this->auditLogService->record(
                kategori: 'profit_master',
                aksi: 'ubah_profit_master',
                deskripsi: "Memperbarui materi promosi profit master #{$post->id}.",
                subject: $post,
            );

            return $post->fresh(['creator:id,name,username', 'images']);
        });
    }

    /**
     * Menghapus postingan profit master (soft delete, berkas fisik tetap tersimpan).
     */
    public function delete(ProfitMasterPost $post): bool
    {
        return DB::transaction(function () use ($post) {
            $postId = $post->id;

            $deleted = $post->delete();

            $this->auditLogService->record(
                kategori: 'profit_master',
                aksi: 'hapus_profit_master',
                deskripsi: "Menghapus materi promosi profit master #{$postId}.",
                subject: null,
            );

            return (bool) $deleted;
        });
    }
}
