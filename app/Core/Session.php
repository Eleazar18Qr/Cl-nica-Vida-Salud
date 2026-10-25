<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    private static array $flash = [];
    private static bool $flashLoaded = false;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');

            $cookiePath = defined('BASE_URL') && BASE_URL !== '' ? BASE_URL : '/';

            session_name('CLINICA_VIDA_SALUD');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => $cookiePath,
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            session_start();
        }

        if (!self::$flashLoaded) {
            self::$flash = $_SESSION['_flash'] ?? [];
            unset($_SESSION['_flash']);
            self::$flashLoaded = true;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        return self::$flash[$key] ?? $default;
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        self::$flash = [];
        self::$flashLoaded = false;
    }

    public static function user(): ?array
    {
        $user = self::get('user');

        return is_array($user) ? $user : null;
    }

    public static function role(): ?string
    {
        return self::user()['rol'] ?? null;
    }
}
