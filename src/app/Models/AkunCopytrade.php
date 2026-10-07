<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AkunCopytrade extends Model
{
    protected $table = 'akun_copytrade';

    protected $fillable = [
        'user_id',
        'tier',
        'broker',
        'tipe_akun',
        'nomor_akun_trading',
        'server_broker',
        'master_password',
        'investor_password',
        'modal_riil',
        'surat_perjanjian_path',
        'tanggal_mulai',
        'tanggal_selesai',
        'nama_bank',
        'nomor_rekening',
        'atas_nama_rekening',
        'status',
        'catatan_admin',
        'verified_by',
        'verified_at',
    ];

    protected $hidden = [
        'master_password',
        'investor_password',
    ];

    protected $appends = [
        'tier_investasi',
        'tanggal_mulai_trading',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai'     => 'date',
            'tanggal_selesai'   => 'date',
            'modal_riil'        => 'decimal:2',
            'master_password'   => 'encrypted',
            'investor_password' => 'encrypted',
            'verified_at'       => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relasi
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function riwayatBalance(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RiwayatBalanceCopytrade::class, 'akun_copytrade_id');
    }

    public function latestRiwayatBalance(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RiwayatBalanceCopytrade::class, 'akun_copytrade_id')->latestOfMany('tanggal_pencatatan');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getTierInvestasiAttribute(): ?string
    {
        return $this->attributes['tier'] ?? null;
    }

    public function getTanggalMulaiTradingAttribute(): ?string
    {
        return isset($this->attributes['tanggal_mulai']) && $this->attributes['tanggal_mulai']
            ? (function_exists('tanggal_indo') ? tanggal_indo($this->attributes['tanggal_mulai']) : $this->attributes['tanggal_mulai'])
            : null;
    }
}
