<?php

declare(strict_types=1);

namespace App\Core;

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '' || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public static function validateOrFail(?string $token): void
    {
        if (self::validate($token)) {
            return;
        }

        Session::flash('error', 'La sesión ha expirado o el token de seguridad no es válido. Intenta nuevamente.');

        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';

        $refererHost = $referer !== '' ? parse_url($referer, PHP_URL_HOST) : null;
        $requestHost = $host !== '' ? explode(':', $host)[0] : null;

        if ($refererHost === null || $requestHost === null || $refererHost !== $requestHost) {
            $referer = \url('dashboard');
        }

        header('Location: ' . $referer);
        exit;
    }
}
