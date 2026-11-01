<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TiketBantuan extends Model
{
    use HasFactory;

    protected $table = 'tiket_bantuan';

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_LABELS = [
        self::STATUS_OPEN        => 'Open',
        self::STATUS_IN_PROGRESS => 'Sedang Diproses',
        self::STATUS_ANSWERED    => 'Dijawab Admin',
        self::STATUS_RESOLVED    => 'Selesai',
        self::STATUS_CLOSED      => 'Ditutup',
    ];

    protected $fillable = [
        'nomor',
        'user_id',
        'subjek',
        'status',
        'last_reply_at',
        'last_reply_by',
        'resolved_at',
        'closed_at',
    ];

    /**
     * Konfigurasi casting modern Laravel.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_reply_at' => 'datetime',
            'resolved_at'   => 'datetime',
            'closed_at'     => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function balasan(): HasMany
    {
        return $this->hasMany(TiketBantuanBalasan::class, 'tiket_id')->orderBy('created_at', 'asc');
    }

    /**
     * Helper status aktif untuk member membalas.
     */
    public function canReplyByMember(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_ANSWERED], true);
    }

    /**
     * Helper status apakah member bisa menandai selesai.
     */
    public function canResolveByMember(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_ANSWERED], true);
    }

    /**
     * Helper status apakah member bisa membuka kembali tiket.
     */
    public function canReopenByMember(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Helper status apakah admin bisa menutup tiket.
     */
    public function canCloseByAdmin(): bool
    {
        return $this->status !== self::STATUS_CLOSED;
    }

    /**
     * Scope tiket aktif (belum resolved/closed).
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_ANSWERED]);
    }

    /**
     * Scope tiket butuh respons / perhatian admin.
     */
    public function scopeButuhResponsAdmin(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Accessor label status.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
