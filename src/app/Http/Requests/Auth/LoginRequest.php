<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.required'    => 'Username atau email wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }

    /**
     * Dapatkan sisa waktu lockout (dalam detik) untuk IP request.
     */
    public static function getLockoutSeconds(\Illuminate\Http\Request $request): int
    {
        $ipKey = 'login.ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            return RateLimiter::availableIn($ipKey);
        }

        return 0;
    }

    /**
     * Pastikan request login belum melebihi batas percobaan.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $ipKey = $this->ipThrottleKey();
        $accountKey = $this->accountThrottleKey();

        $isIpLimited = RateLimiter::tooManyAttempts($ipKey, 5);
        $isAccountLimited = RateLimiter::tooManyAttempts($accountKey, 5);

        if (!$isIpLimited && !$isAccountLimited) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(
            $isIpLimited ? RateLimiter::availableIn($ipKey) : 0,
            $isAccountLimited ? RateLimiter::availableIn($accountKey) : 0
        );

        throw ValidationException::withMessages([
            'login' => "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$seconds} detik.",
        ]);
    }

    /**
     * Cek apakah percobaan login saat ini telah mencapai batas rate limit.
     */
    public function isRateLimited(): bool
    {
        return RateLimiter::tooManyAttempts($this->ipThrottleKey(), 5)
            || RateLimiter::tooManyAttempts($this->accountThrottleKey(), 5);
    }

    /**
     * Dapatkan sisa detik lockout maksimum.
     */
    public function availableIn(): int
    {
        $ipKey = $this->ipThrottleKey();
        $accountKey = $this->accountThrottleKey();

        return max(
            RateLimiter::tooManyAttempts($ipKey, 5) ? RateLimiter::availableIn($ipKey) : 0,
            RateLimiter::tooManyAttempts($accountKey, 5) ? RateLimiter::availableIn($accountKey) : 0
        );
    }

    /**
     * Clear rate limit hits.
     */
    public function clearRateLimit(): void
    {
        RateLimiter::clear($this->ipThrottleKey());
        RateLimiter::clear($this->accountThrottleKey());
    }

    /**
     * Hit rate limiter on failed attempt.
     */
    public function hitRateLimit(int $decaySeconds = 60): void
    {
        RateLimiter::hit($this->ipThrottleKey(), $decaySeconds);
        RateLimiter::hit($this->accountThrottleKey(), $decaySeconds);
    }

    /**
     * Throttle key berbasis IP klien.
     */
    public function ipThrottleKey(): string
    {
        return 'login.ip:' . $this->ip();
    }

    /**
     * Throttle key berbasis akun pengguna + IP.
     */
    public function accountThrottleKey(): string
    {
        return 'login.account:' . Str::transliterate(Str::lower($this->input('login')) . '|' . $this->ip());
    }

    /**
     * Get the rate limiting throttle key for the request (backward compatibility).
     */
    public function throttleKey(): string
    {
        return $this->accountThrottleKey();
    }
}
