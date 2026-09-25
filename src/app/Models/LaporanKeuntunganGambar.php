<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanKeuntunganGambar extends Model
{
    use HasFactory;

    protected $table = 'laporan_keuntungan_gambar';

    protected $fillable = [
        'laporan_keuntungan_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'caption',
        'urutan',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'urutan'    => 'integer',
    ];

    protected $appends = [
        'url',
        'download_url',
        'formatted_file_size',
    ];

    /**
     * Relasi ke laporan keuntungan induk.
     */
    public function laporanKeuntungan(): BelongsTo
    {
        return $this->belongsTo(LaporanKeuntungan::class, 'laporan_keuntungan_id');
    }

    /**
     * URL streaming gambar untuk tampilan web.
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => route('documents.profit_report.view', ['imageId' => $this->id])
        );
    }

    /**
     * URL download gambar untuk promosi affiliator.
     */
    protected function downloadUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => route('documents.profit_report.download', ['imageId' => $this->id])
        );
    }

    /**
     * Ukuran berkas terformat (KB / MB).
     */
    protected function formattedFileSize(): Attribute
    {
        return Attribute::make(
            get: function () {
                $bytes = $this->file_size;
                if ($bytes >= 1048576) {
                    return number_format($bytes / 1048576, 1) . ' MB';
                }
                if ($bytes >= 1024) {
                    return number_format($bytes / 1024, 0) . ' KB';
                }

                return $bytes . ' B';
            }
        );
    }
}
