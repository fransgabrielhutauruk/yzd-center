<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TiketBantuanBalasan extends Model
{
    use HasFactory;

    protected $table = 'tiket_bantuan_balasan';

    public const PENGIRIM_MEMBER = 'member';

    public const PENGIRIM_ADMIN = 'admin';

    protected $fillable = [
        'tiket_id',
        'user_id',
        'pengirim',
        'pesan',
    ];

    public function tiket(): BelongsTo
    {
        return $this->belongsTo(TiketBantuan::class, 'tiket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lampiran(): HasMany
    {
        return $this->hasMany(TiketBantuanLampiran::class, 'balasan_id');
    }
}
