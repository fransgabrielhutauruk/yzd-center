<?php

namespace App\Models;

use App\Traits\TracksDeletedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfitMasterPostImage extends Model
{
    use HasFactory, SoftDeletes, TracksDeletedBy;

    protected $table = 'profit_master_post_images';

    protected $fillable = [
        'post_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'urutan'    => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(ProfitMasterPost::class, 'post_id');
    }

    public function getUrlAttribute(): string
    {
        return route('documents.profit_image', $this->id);
    }
}
