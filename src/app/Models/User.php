<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\Blameable;
use App\Traits\HasRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Blameable, HasFactory, HasRole, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'status',
        'nik',
        'phone',
        'alamat',
        'tempat_lahir',
        'tanggal_lahir',
        'pekerjaan',
        'referral_code',
        'is_active',
        'avatar',
        'terms_agreed_at',
        'ip_address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_agreed_at'   => 'datetime',
            'tanggal_lahir'     => 'date',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function akunCopytrade(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AkunCopytrade::class);
    }

    public function ahliWaris(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserAhliWaris::class);
    }

    public function dokumen(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserDokumen::class);
    }

    public function isPendingKyc(): bool
    {
        return $this->status === 'pending';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isKycApproved(): bool
    {
        $ktp = $this->dokumen()->where('tipe_dokumen', 'ktp')->first();
        $perjanjian = $this->dokumen()->where('tipe_dokumen', 'perjanjian')->first();

        return $ktp?->isApproved() && $perjanjian?->isApproved();
    }

    public function hasKycRevisionNeeded(): bool
    {
        return $this->dokumen()->where('status', 'revision_needed')->exists();
    }
}
