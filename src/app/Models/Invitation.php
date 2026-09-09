<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invitation extends Model
{
    use Blameable, HasFactory, SoftDeletes;

    protected $primaryKey = 'invitation_id';

    protected $fillable = [
        'token',
        'label',
        'target_role',
        'kuota_maksimal',
        'total_terpakai',
        'is_active',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'expired_at'     => 'datetime',
            'kuota_maksimal' => 'integer',
            'total_terpakai' => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expired_at !== null && $this->expired_at->isPast();
    }

    public function isQuotaFull(): bool
    {
        return $this->total_terpakai >= $this->kuota_maksimal;
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired() && !$this->isQuotaFull();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
            })
            ->whereColumn('total_terpakai', '<', 'kuota_maksimal');
    }
}
