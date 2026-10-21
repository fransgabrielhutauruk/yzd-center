<?php

namespace App\Support\Monitoring;

use Sentry\Event;
use Sentry\EventHint;
use Sentry\UserDataBag;

class SentryScrubber
{
    /**
     * Pola kata kunci data sensitif yang wajib di-scrub dari payload Sentry.
     *
     * @var array<int, string>
     */
    protected const SENSITIVE_PATTERNS = [
        'password',
        'kata_sandi',
        'ktp',
        'selfie',
        'nik',
        'pin',
        'vps_pin',
        'token',
        'secret',
        'mt4_',
        'vps_',
        'nomor_rekening',
        'rekening',
        'kartu',
        'authorization',
        'cookie',
    ];

    /**
     * Scrub event Sentry sebelum dikirim ke server.
     */
    public static function scrub(Event $event, ?EventHint $hint = null): ?Event
    {
        // 1. Bersihkan User Context: hanya izinkan ID dan Role
        $user = $event->getUser();
        if ($user) {
            $userId = $user->getId();
            $role = $user->getMetadata()['role'] ?? $user->getSegment() ?? null;

            $scrubbedUser = new UserDataBag($userId ? (string) $userId : 'anonymous');
            if ($role) {
                $scrubbedUser->setMetadata('role', (string) $role);
            }
            $scrubbedUser->setEmail(null);
            $scrubbedUser->setUsername(null);
            $scrubbedUser->setIpAddress(null);

            $event->setUser($scrubbedUser);
        }

        // 2. Bersihkan Request Context: hapus body payload, cookies, dan header sensitif
        $request = $event->getRequest();
        if (!empty($request)) {
            // Hapus body request seluruhnya (zero request body leakage)
            unset($request['data']);
            unset($request['cookies']);

            // Hapus header sensitif & token otorisasi
            if (isset($request['headers']) && is_array($request['headers'])) {
                $forbiddenHeaders = [
                    'authorization',
                    'cookie',
                    'x-csrf-token',
                    'x-xsrf-token',
                    'php_auth_pw',
                    'php_auth_user',
                ];

                foreach ($request['headers'] as $headerKey => $headerVal) {
                    if (in_array(strtolower((string) $headerKey), $forbiddenHeaders, true)) {
                        unset($request['headers'][$headerKey]);
                    }
                }
            }

            // Scrub query string jika memuat parameter sensitif
            if (isset($request['query_string']) && is_string($request['query_string'])) {
                $request['query_string'] = self::scrubQueryString($request['query_string']);
            }

            $event->setRequest($request);
        }

        // 3. Bersihkan Extra Context & Metadata Rekursif
        $extra = $event->getExtra();
        if (!empty($extra)) {
            $event->setExtra(self::scrubArray($extra));
        }

        // 4. Bersihkan Breadcrumbs
        $breadcrumbs = $event->getBreadcrumbs();
        if (!empty($breadcrumbs)) {
            foreach ($breadcrumbs as $breadcrumb) {
                $data = $breadcrumb->getMetadata();
                if (!empty($data)) {
                    $breadcrumb->setMetadata(self::scrubArray($data));
                }
            }
        }

        return $event;
    }

    /**
     * Bersihkan array dari pasangan kunci/nilai sensitif secara rekursif.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function scrubArray(array $data): array
    {
        $scrubbed = [];

        foreach ($data as $key => $value) {
            $keyStr = strtolower((string) $key);

            if (self::isSensitiveKey($keyStr)) {
                $scrubbed[$key] = '[SCRUBBED]';
            } elseif (is_array($value)) {
                $scrubbed[$key] = self::scrubArray($value);
            } else {
                $scrubbed[$key] = $value;
            }
        }

        return $scrubbed;
    }

    /**
     * Cek apakah nama kunci mengandung kata kunci sensitif.
     */
    protected static function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (str_contains($key, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bersihkan query string URL dari parameter sensitif.
     */
    protected static function scrubQueryString(string $queryString): string
    {
        parse_str($queryString, $params);
        if (empty($params) || !is_array($params)) {
            return $queryString;
        }

        $scrubbedParams = self::scrubArray($params);

        return http_build_query($scrubbedParams);
    }
}
