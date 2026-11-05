<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

final class SafeUri
{
    private const ALLOWED_PROTOCOLS = ['http', 'https', 'mailto', 'tel'];

    public static function clean(string $uri): ?string
    {
        $uri = trim((string) preg_replace('/[\x00-\x1F\x7F-\x9F\x{200B}-\x{200D}\x{2028}\x{2029}\x{FEFF}]/u', '', $uri));

        if ($uri === '') {
            return null;
        }

        if (preg_match('/^(?:\/(?![\/\\\\])|#|\?)/', $uri) === 1) {
            return $uri;
        }

        $probe = (string) preg_replace('/\s+/u', '', $uri);

        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $probe, $matches) !== 1) {
            return null;
        }

        return in_array(strtolower($matches[1]), self::ALLOWED_PROTOCOLS, true) ? $uri : null;
    }
}
