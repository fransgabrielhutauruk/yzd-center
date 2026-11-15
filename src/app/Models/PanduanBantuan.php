<?php

namespace App\Models;

use App\Traits\TracksDeletedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PanduanBantuan extends Model
{
    use HasFactory, SoftDeletes, TracksDeletedBy;

    protected $table = 'panduan_bantuan';

    protected static function booted(): void
    {
        static::deleting(function (self $article): void {
            if (!$article->isForceDeleting()) {
                $article->applyTrashSlug();
            }
        });
    }

    public function applyTrashSlug(): void
    {
        if (!str_contains($this->slug, '--trashed-')) {
            $maxLen = 255;
            $suffix = "--trashed-{$this->id}";
            $baseSlug = Str::limit($this->slug, $maxLen - strlen($suffix), '');
            $this->slug = $baseSlug . $suffix;
            $this->saveQuietly();
        }
    }

    public function restoreSlugOrFail(): void
    {
        if (preg_match('/^(.*)--trashed-' . $this->id . '$/', $this->slug, $matches)) {
            $originalSlug = $matches[1];
            $exists = static::where('slug', $originalSlug)
                ->where('id', '!=', $this->id)
                ->exists();

            if ($exists) {
                throw new \DomainException("Slug '{$originalSlug}' sudah digunakan oleh artikel panduan aktif lainnya.");
            }

            $this->slug = $originalSlug;
            $this->saveQuietly();
        }
    }

    protected $fillable = [
        'kategori_id',
        'judul',
        'slug',
        'ringkasan',
        'konten',
        'video_url',
        'is_published',
        'views_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'views_count'  => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relasi
    // -------------------------------------------------------------------------

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(PanduanKategori::class, 'kategori_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('judul', 'like', "%{$search}%")
                ->orWhere('ringkasan', 'like', "%{$search}%")
                ->orWhere('konten', 'like', "%{$search}%");
        });
    }
}
