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

    public function akunCopytrades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AkunCopytrade::class);
    }

    public function ahliWaris(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserAhliWaris::class);
    }

    public function dokumen(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserDokumen::class);
    }

    public function akunCopytrade(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AkunCopytrade::class);
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

        return $ktp?->isApproved() ?? false;
    }

    public function hasKycRevisionNeeded(): bool
    {
        return $this->dokumen()->where('status', 'revision_needed')->exists();
    }

    public function hasKycRejected(): bool
    {
        return $this->dokumen()->where('status', 'rejected')->exists();
    }

    public function relasiUpline(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RelasiReferral::class, 'downline_id');
    }

    public function relasiDownlines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RelasiReferral::class, 'affiliator_id');
    }

    public function downlines(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            RelasiReferral::class,
            'affiliator_id',
            'id',
            'id',
            'downline_id'
        );
    }

    public function klaimReferralDiajukan(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KlaimReferral::class, 'affiliator_id');
    }

    public function klaimReferralSebagaiDownline(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KlaimReferral::class, 'downline_id');
    }

    public function klaimKomisiDiajukan(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KlaimKomisi::class, 'affiliator_id');
    }

    public function klaimKomisiSebagaiDownline(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KlaimKomisiItem::class, 'downline_id');
    }

    public function hasUpline(): bool
    {
        return $this->relasiUpline()->exists();
    }

    public function getUpline(): ?User
    {
        return $this->relasiUpline?->affiliator;
    }
}
