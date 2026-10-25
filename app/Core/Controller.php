<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        View::render($view, $data, $layout);
    }

    protected function redirect(string $route, array $params = []): void
    {
        header('Location: ' . \url($route, $params));
        exit;
    }

    protected function back(string $fallback = 'dashboard'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? \url($fallback);
        header('Location: ' . $referer);
        exit;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function requireCsrf(): void
    {
        Csrf::validateOrFail($_POST['_token'] ?? null);
    }
}
