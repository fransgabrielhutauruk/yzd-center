<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanMasterFee extends Model
{
    protected $table = 'pengaturan_master_fee';

    protected $fillable = [
        'nama_bank',
        'nomor_rekening',
        'atas_nama',
        'fee_persen',
    ];

    protected function casts(): array
    {
        return [
            'fee_persen' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'nama_bank'      => 'BCA',
                'nomor_rekening' => '1234567890',
                'atas_nama'      => 'Master YZD Trading',
                'fee_persen'     => 30,
            ]
        );
    }
}
