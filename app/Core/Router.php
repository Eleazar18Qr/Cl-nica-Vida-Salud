<?php

declare(strict_types=1);

namespace App\Core;

use App\Middlewares\AuthMiddleware;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, array $roles = []): void
    {
        $this->addRoute('GET', $path, $handler, $roles);
    }

    public function post(string $path, array $handler, array $roles = []): void
    {
        $this->addRoute('POST', $path, $handler, $roles);
    }

    private function addRoute(string $method, string $path, array $handler, array $roles): void
    {
        $normalized = trim($path, '/');
        $pattern = '#^' . preg_replace(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            '(?P<$1>[^/]+)',
            $normalized
        ) . '$#';

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'handler' => $handler,
            'roles' => $roles,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = trim($uri, '/');

        foreach ($this->routes[$method] ?? [] as $route) {
            if (!preg_match($route['pattern'], $uri, $matches)) {
                continue;
            }

            if ($route['roles'] !== []) {
                AuthMiddleware::handle($route['roles']);
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            [$controllerClass, $action] = $route['handler'];
            $controller = new $controllerClass();
            $controller->$action(...array_values($params));

            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'Página no encontrada'], 'auth');
    }
}
