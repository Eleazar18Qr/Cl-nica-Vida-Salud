<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class View
{
    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewFile = BASE_PATH . '/views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException("La vista {$view} no existe.");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = BASE_PATH . '/views/layouts/' . $layout . '.php';

        if (!is_file($layoutFile)) {
            throw new RuntimeException("El layout {$layout} no existe.");
        }

        require $layoutFile;
    }
}
