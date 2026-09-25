<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanKeuntungan extends Model
{
    use HasFactory;

    protected $table = 'laporan_keuntungan';

    protected $fillable = [
        'tipe',
        'judul',
        'tanggal',
        'bulan',
        'tahun',
        'profit_persen',
        'profit_nominal_usd',
        'is_kalender',
        'deskripsi',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal'            => 'date',
        'bulan'              => 'integer',
        'tahun'              => 'integer',
        'profit_persen'      => 'decimal:2',
        'profit_nominal_usd' => 'decimal:2',
        'is_kalender'        => 'boolean',
    ];

    protected $appends = [
        'periode_formatted',
    ];

    /**
     * Relasi ke foto/gambar pendukung (bisa lebih dari 1 foto).
     */
    public function gambar(): HasMany
    {
        return $this->hasMany(LaporanKeuntunganGambar::class, 'laporan_keuntungan_id')->orderBy('urutan');
    }

    /**
     * Relasi ke admin yang membuat postingan.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Accessor label periode yang mudah dibaca.
     */
    protected function periodeFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                $namaBulan = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                ];

                if ($this->tipe === 'bulanan' && $this->bulan && $this->tahun) {
                    $bulanNama = $namaBulan[$this->bulan] ?? "Bulan {$this->bulan}";

                    return "{$bulanNama} {$this->tahun}";
                }

                if ($this->tanggal) {
                    return Carbon::parse($this->tanggal)->translatedFormat('d F Y');
                }

                return '-';
            }
        );
    }
}
