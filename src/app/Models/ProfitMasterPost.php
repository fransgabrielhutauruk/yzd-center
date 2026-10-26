<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfitMasterPost extends Model
{
    use HasFactory;

    protected $table = 'profit_master_posts';

    protected $fillable = [
        'caption',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProfitMasterPostImage::class, 'post_id')->orderBy('urutan');
    }
}
