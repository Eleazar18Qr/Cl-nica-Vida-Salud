<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Session;

class AuthMiddleware
{
    public static function handle(array $allowedRoles = []): void
    {
        $user = Session::user();

        if ($user === null) {
            Session::flash('error', 'Debes iniciar sesión para acceder a esta sección.');
            header('Location: ' . \url('auth/login'));
            exit;
        }

        if ($allowedRoles !== [] && !in_array($user['rol'] ?? '', $allowedRoles, true)) {
            Session::flash('error', 'No tienes permisos para acceder a esta sección.');
            header('Location: ' . \url('dashboard'));
            exit;
        }
    }

    public static function check(): bool
    {
        return Session::user() !== null;
    }
}
