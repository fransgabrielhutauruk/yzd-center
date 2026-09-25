<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdukasiFaq extends Model
{
    use HasFactory;

    protected $table = 'edukasi_faq';

    protected $fillable = [
        'topik_id',
        'pertanyaan',
        'jawaban',
        'urutan',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'topik_id'  => 'integer',
            'urutan'    => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function topik(): BelongsTo
    {
        return $this->belongsTo(EdukasiFaqTopik::class, 'topik_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeTopik(Builder $query, int $topikId): Builder
    {
        return $query->where('topik_id', $topikId);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('pertanyaan', 'like', "%{$search}%")
                ->orWhere('jawaban', 'like', "%{$search}%");
        });
    }
}
