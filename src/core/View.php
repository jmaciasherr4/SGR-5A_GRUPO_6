<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Motor simple de vistas PHP.
 */
class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/main'): void
    {
        // Extraer variables para la vista
        extract($data);

        // Capturar contenido de la vista
        $viewFile = dirname(__DIR__) . '/views/' . $template . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Vista no encontrada: {$viewFile}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Si se especificó layout, envolver la vista en él
        if ($layout !== null) {
            $layoutFile = dirname(__DIR__) . '/views/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new \RuntimeException("Layout no encontrado: {$layoutFile}");
            }
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function escape(?string $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
