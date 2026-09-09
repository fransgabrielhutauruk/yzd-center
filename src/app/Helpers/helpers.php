<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return Auth::check() && Auth::user()->role === 'administrator';
    }
}

if (!function_exists('is_investor')) {
    function is_investor(): bool
    {
        return Auth::check() && in_array(Auth::user()->role, ['investor', 'affiliator'], true);
    }
}

if (!function_exists('is_affiliator')) {
    function is_affiliator(): bool
    {
        return Auth::check() && Auth::user()->role === 'affiliator';
    }
}

if (!function_exists('rupiah')) {
    function rupiah(int|float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

if (!function_exists('tanggal_indo')) {
    function tanggal_indo(string|DateTimeInterface|null $date): string
    {
        if (!$date) {
            return '-';
        }

        return Carbon::parse($date)->locale('id')->translatedFormat('d F Y');
    }
}

if (!function_exists('tanggal_singkat')) {
    function tanggal_singkat(string|DateTimeInterface|null $date): string
    {
        if (!$date) {
            return '-';
        }

        return Carbon::parse($date)->locale('id')->translatedFormat('d M Y');
    }
}

if (!function_exists('tanggal_waktu_indo')) {
    function tanggal_waktu_indo(string|DateTimeInterface|null $date, bool $withWib = false): string
    {
        if (!$date) {
            return '-';
        }

        $format = $withWib ? 'd M Y H:i \W\I\B' : 'd M Y H:i';

        return Carbon::parse($date)->locale('id')->translatedFormat($format);
    }
}
