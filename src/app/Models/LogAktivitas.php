<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class LogAktivitas extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'log_aktivitas';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_username',
        'user_role',
        'kategori',
        'aksi',
        'tingkat',
        'deskripsi',
        'subject_type',
        'subject_id',
        'perubahan',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * Relasi ke user pelaku aktivitas jika masih ada di database.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi polimorfik ke objek subjek aktivitas.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Atribut type casting modern.
     */
    protected function casts(): array
    {
        return [
            'perubahan'  => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Proteksi immutability: Audit log bersifat permanen dan tidak boleh diubah atau dihapus.
     */
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Data audit log bersifat permanen (immutable) dan tidak dapat diubah.');
        });

        static::deleting(function (): void {
            throw new LogicException('Data audit log bersifat permanen (immutable) dan tidak dapat dihapus.');
        });
    }
}
