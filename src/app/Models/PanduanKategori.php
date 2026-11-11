<?php

namespace App\Models;

use App\Traits\CascadesSoftDeletes;
use App\Traits\TracksDeletedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PanduanKategori extends Model
{
    use CascadesSoftDeletes, HasFactory, SoftDeletes, TracksDeletedBy;

    protected $table = 'panduan_kategori';

    protected array $cascadeSoftDeletes = ['panduan'];

    protected static function booted(): void
    {
        static::deleting(function (self $category): void {
            if (!$category->isForceDeleting()) {
                $category->applyTrashSlug();
            }
        });
    }

    public function applyTrashSlug(): void
    {
        if (!str_contains($this->slug, '--trashed-')) {
            $maxLen = 100;
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
                throw new \DomainException("Slug '{$originalSlug}' sudah digunakan oleh kategori bantuan aktif lainnya.");
            }

            $this->slug = $originalSlug;
            $this->saveQuietly();
        }
    }

    protected $fillable = [
        'nama',
        'slug',
        'deskripsi',
        'icon',
        'urutan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'urutan'    => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relasi
    // -------------------------------------------------------------------------

    public function panduan(): HasMany
    {
        return $this->hasMany(PanduanBantuan::class, 'kategori_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('nama');
    }
}
