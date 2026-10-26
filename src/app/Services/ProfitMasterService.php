<?php

namespace App\Services;

use App\Models\ProfitMasterPost;
use App\Models\ProfitMasterPostImage;
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
     * Mengambil daftar postingan profit master menggunakan cursor pagination.
     *
     * @return array<string, mixed>
     */
    public function feed(?string $search = null, ?string $cursor = null, int $perPage = 10): array
    {
        $query = ProfitMasterPost::with([
            'creator:id,name,username',
            'images',
        ])->orderByDesc('id');

        if (!empty($search)) {
            $term = trim($search);
            $query->where('caption', 'like', "%{$term}%");
        }

        $paginator = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        $items = collect($paginator->items())->map(function (ProfitMasterPost $post) {
            return [
                'id'             => $post->id,
                'caption'        => $post->caption,
                'created_at'     => $post->created_at?->toIso8601String(),
                'formatted_date' => $post->created_at?->translatedFormat('d M Y, H:i'),
                'relative_time'  => $post->created_at?->diffForHumans(),
                'creator'        => [
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
     * Menerbitkan postingan profit master baru beserta gambar-gambarnya.
     *
     * @param array<int, UploadedFile> $images
     */
    public function create(string $caption, array $images, int $adminId): ProfitMasterPost
    {
        return DB::transaction(function () use ($caption, $images, $adminId) {
            $post = ProfitMasterPost::create([
                'caption'    => $caption,
                'created_by' => $adminId,
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
        array $deletedImageIds = []
    ): ProfitMasterPost {
        return DB::transaction(function () use ($post, $caption, $newImages, $deletedImageIds) {
            $disk = Storage::disk('local');

            // 1. Hapus gambar yang ditandai hapus
            if (!empty($deletedImageIds)) {
                $imagesToDelete = ProfitMasterPostImage::where('post_id', $post->id)
                    ->whereIn('id', $deletedImageIds)
                    ->get();

                foreach ($imagesToDelete as $img) {
                    if ($disk->exists($img->file_path)) {
                        $disk->delete($img->file_path);
                    }
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

            // 4. Update caption
            $post->update(['caption' => $caption]);

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
     * Menghapus postingan profit master beserta seluruh berkas gambar fisiknya.
     */
    public function delete(ProfitMasterPost $post): bool
    {
        return DB::transaction(function () use ($post) {
            $postId = $post->id;
            $disk = Storage::disk('local');

            // Hapus direktori fisik dan seluruh isinya
            $folder = "profit_master/{$postId}";
            if ($disk->exists($folder)) {
                $disk->deleteDirectory($folder);
            }

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
