<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanduanBantuan extends Model
{
    use HasFactory;

    protected $table = 'panduan_bantuan';

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
