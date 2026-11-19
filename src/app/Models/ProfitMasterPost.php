<?php

namespace App\Models;

use App\Traits\CascadesSoftDeletes;
use App\Traits\TracksDeletedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfitMasterPost extends Model
{
    use CascadesSoftDeletes, HasFactory, SoftDeletes, TracksDeletedBy;

    protected $table = 'profit_master_posts';

    protected array $cascadeSoftDeletes = ['images'];

    protected $fillable = [
        'caption',
        'profit_start_date',
        'profit_end_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'profit_start_date' => 'date',
            'profit_end_date'   => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProfitMasterPostImage::class, 'post_id')->orderBy('urutan');
    }
}
