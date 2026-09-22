<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KlaimReferral extends Model
{
    use HasFactory;

    protected $table = 'klaim_referral';

    protected $fillable = [
        'affiliator_id',
        'downline_id',
        'bukti_chat_path',
        'alasan',
        'status',
        'catatan_admin',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
