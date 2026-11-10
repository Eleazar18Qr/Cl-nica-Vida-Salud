<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Session;

function url(string $route = '', array $params = []): string
{
    $query = [];

    if ($route !== '') {
        $query['route'] = $route;
    }

    $query = array_merge($query, $params);
    $url = BASE_URL . '/index.php';

    if ($query !== []) {
        $url .= '?' . str_replace('%2F', '/', http_build_query($query));
    }

    return $url;
}

function asset(string $path): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::getFlash('old', []);

    return $old[$key] ?? $default;
}

function error(string $field): string
{
    $errors = Session::getFlash('errors', []);

    return $errors[$field] ?? '';
}

function flash(string $key): ?string
{
    $value = Session::getFlash($key);

    return is_string($value) ? $value : null;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function current_user(): ?array
{
    return Session::user();
}

function current_role(): ?string
{
    return Session::role();
}

function has_role(array $roles): bool
{
    return in_array(current_role(), $roles, true);
}

function is_active(string $route): bool
{
    $current = $_GET['route'] ?? '';

    return $current === $route || str_starts_with($current, $route . '/');
}

function format_date(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    $timestamp = strtotime($date);

    return $timestamp !== false ? date('d/m/Y', $timestamp) : '—';
}

function format_time(?string $time): string
{
    if ($time === null || $time === '') {
        return '—';
    }

    return substr($time, 0, 5);
}

function day_name(int $day): string
{
    return [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ][$day] ?? '—';
}

function status_badge(string $estado): string
{
    $class = $estado === 'Activo' ? 'success' : 'secondary';

    return '<span class="badge text-bg-' . $class . '">' . e($estado) . '</span>';
}

/**
 * Badge Bootstrap para los estados de una cita.
 * Pendiente = amarillo, Confirmada = azul, Atendida = verde,
 * Cancelada = rojo, No_Asistio = gris.
 */
function cita_estado_badge(string $estado): string
{
    $map = [
        'Pendiente' => ['warning', 'text-dark'],
        'Confirmada' => ['primary', ''],
        'Atendida' => ['success', ''],
        'Cancelada' => ['danger', ''],
        'No_Asistio' => ['secondary', ''],
    ];

    [$background, $extra] = $map[$estado] ?? ['light', 'text-dark'];
    $label = $estado === 'No_Asistio' ? 'No asistió' : $estado;
    $classes = 'badge text-bg-' . $background . ($extra !== '' ? ' ' . $extra : '');

    return '<span class="' . $classes . '">' . e($label) . '</span>';
}

function cita_estado_label(string $estado): string
{
    return $estado === 'No_Asistio' ? 'No asistió' : $estado;
}
