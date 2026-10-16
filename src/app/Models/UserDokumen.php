<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDokumen extends Model
{
    use HasFactory;

    protected $table = 'user_dokumen';

    protected $fillable = [
        'user_id',
        'tipe_dokumen',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'status',
        'catatan_admin',
        'is_quality_warning',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size'          => 'integer',
            'is_quality_warning' => 'boolean',
            'verified_at'        => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeRevisionNeeded(Builder $query): Builder
    {
        return $query->where('status', 'revision_needed');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRevisionNeeded(): bool
    {
        return $this->status === 'revision_needed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
