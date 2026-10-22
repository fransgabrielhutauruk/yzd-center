<?php

namespace App\Traits;

/**
 * @property string $role
 */
trait HasRole
{
    public function isAdmin(): bool
    {
        return $this->role === 'administrator';
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /**
     * Label turunan: Investor = memiliki minimal 1 akun copytrade aktif.
     */
    public function isInvestorLabel(): bool
    {
        if (isset($this->attributes['is_investor_label'])) {
            return (bool) $this->attributes['is_investor_label'];
        }

        if ($this->relationLoaded('akunCopytrade')) {
            return $this->akunCopytrade->contains(fn ($acc) => $acc->status === 'active');
        }

        return $this->akunCopytrade()->where('status', 'active')->exists();
    }

    /**
     * Label turunan: Affiliator = memiliki minimal 1 downline yang aktif.
     */
    public function isAffiliatorLabel(): bool
    {
        if (isset($this->attributes['is_affiliator_label'])) {
            return (bool) $this->attributes['is_affiliator_label'];
        }

        if ($this->relationLoaded('relasiDownlines')) {
            return $this->relasiDownlines->contains(fn ($ref) => $ref->downline?->is_active ?? false);
        }

        return $this->relasiDownlines()->whereHas('downline', fn ($q) => $q->where('is_active', true))->exists();
    }

    /**
     * Daftar label representatif yang disematkan ke pengguna.
     *
     * @return array<int, string>
     */
    public function getDerivedLabels(): array
    {
        if ($this->isAdmin()) {
            return ['Administrator'];
        }

        $labels = [];
        if ($this->isInvestorLabel()) {
            $labels[] = 'Investor';
        }
        if ($this->isAffiliatorLabel()) {
            $labels[] = 'Affiliator';
        }

        return !empty($labels) ? $labels : ['Member'];
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles, true);
        }

        return $this->role === $roles;
    }
}
