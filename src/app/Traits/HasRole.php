<?php

namespace App\Traits;

trait HasRole
{
    public function isAdmin(): bool
    {
        return $this->role === 'administrator';
    }

    public function isInvestor(): bool
    {
        return in_array($this->role, ['investor', 'affiliator'], true);
    }

    public function isAffiliator(): bool
    {
        return $this->role === 'affiliator';
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles, true);
        }

        return $this->role === $roles;
    }
}
