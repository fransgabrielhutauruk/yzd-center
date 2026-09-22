<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelasiReferral extends Model
{
    use HasFactory;

    protected $table = 'relasi_referral';

    protected $fillable = [
        'affiliator_id',
        'downline_id',
        'sumber',
        'joined_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public function affiliator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affiliator_id');
    }

    public function downline(): BelongsTo
    {
        return $this->belongsTo(User::class, 'downline_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
