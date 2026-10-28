<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TiketBantuanLampiran extends Model
{
    use HasFactory;

    protected $table = 'tiket_bantuan_lampiran';

    protected $fillable = [
        'balasan_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
    ];

    public function balasan(): BelongsTo
    {
        return $this->belongsTo(TiketBantuanBalasan::class, 'balasan_id');
    }
}
