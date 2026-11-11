<?php

namespace App\Models;

use App\Traits\CascadesSoftDeletes;
use App\Traits\TracksDeletedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EdukasiFaqTopik extends Model
{
    use CascadesSoftDeletes, HasFactory, SoftDeletes, TracksDeletedBy;

    protected $table = 'edukasi_faq_topik';

    protected array $cascadeSoftDeletes = ['faqs'];

    protected $fillable = [
        'nama',
        'deskripsi',
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

    public function faqs(): HasMany
    {
        return $this->hasMany(EdukasiFaq::class, 'topik_id')->orderBy('urutan');
    }

    public function activeFaqs(): HasMany
    {
        return $this->hasMany(EdukasiFaq::class, 'topik_id')
            ->where('is_active', true)
            ->orderBy('urutan');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
