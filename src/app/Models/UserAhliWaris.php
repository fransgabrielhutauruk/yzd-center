<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAhliWaris extends Model
{
    use HasFactory;

    protected $table = 'user_ahli_waris';

    protected $fillable = [
        'user_id',
        'nama',
        'hubungan_keluarga',
        'no_hp',
        'alamat',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
