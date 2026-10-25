<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

// Carga opcional de variables de entorno para desarrollo local.
$envFile = BASE_PATH . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

// Autoloader adaptado a la estructura del proyecto.
spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\Config\\' => BASE_PATH . '/config/',
        'App\\Controllers\\' => BASE_PATH . '/app/Controllers/',
        'App\\Models\\' => BASE_PATH . '/app/Models/',
        'App\\Middlewares\\' => BASE_PATH . '/app/Middlewares/',
        'App\\Core\\' => BASE_PATH . '/app/Core/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (is_file($file)) {
            require $file;
        }

        return;
    }
});

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('America/Lima');

// URL base del proyecto (funciona en la raíz o en subdirectorios).
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
define('BASE_URL', $basePath === '/' ? '' : $basePath);

require BASE_PATH . '/app/Core/helpers.php';

\App\Core\Session::start();
