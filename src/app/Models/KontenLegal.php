<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class KontenLegal extends Model
{
    use HasFactory;

    protected $table = 'konten_legal';

    protected $fillable = [
        'key_name',
        'judul',
        'konten',
        'updated_by',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Ambil konten Syarat & Ketentuan aktif dengan caching berbasis atribut array
     * untuk mencegah error __PHP_Incomplete_Class saat deserialisasi cache.
     */
    public static function getTerms(): ?self
    {
        $cached = Cache::get('legal_content_terms');

        if (!is_array($cached)) {
            Cache::forget('legal_content_terms');
            $model = self::where('key_name', 'terms_conditions')->first();
            if ($model) {
                Cache::put('legal_content_terms', $model->getAttributes(), 3600);
            }

            return $model;
        }

        return (new self)->newFromBuilder($cached);
    }

    /**
     * Ambil konten Kebijakan Privasi aktif dengan caching berbasis atribut array.
     */
    public static function getPrivacyPolicy(): ?self
    {
        $cached = Cache::get('legal_content_privacy');

        if (!is_array($cached)) {
            Cache::forget('legal_content_privacy');
            $model = self::where('key_name', 'privacy_policy')->first();
            if ($model) {
                Cache::put('legal_content_privacy', $model->getAttributes(), 3600);
            }

            return $model;
        }

        return (new self)->newFromBuilder($cached);
    }

    /**
     * Bersihkan cache saat konten diperbarui.
     */
    public static function clearCache(): void
    {
        Cache::forget('legal_content_terms');
        Cache::forget('legal_content_privacy');
    }
}
