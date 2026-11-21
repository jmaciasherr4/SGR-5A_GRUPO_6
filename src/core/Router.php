<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Csrf;

/**
 * Enrutador básico sin framework
 * Soporta registro de rutas GET/POST y ejecución de controladores o funciones anónimas.
 */
class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        $this->routes[$method][$normalizedPath] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $parsedUri = parse_url($uri, PHP_URL_PATH);
        $normalizedPath = '/' . trim($parsedUri, '/');
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        $method = strtoupper($method);

        // Verificación obligatoria en backend de protección CSRF para peticiones POST
        // Excepción de seguridad: /logout permite destruir la sesión de forma limpia
        // aun si la sesión previa o el token expiraron, evitando que el usuario quede atrapado.
        if ($method === 'POST' && $normalizedPath !== '/logout') {
            Csrf::verifyPostRequest();
        }

        if (isset($this->routes[$method][$normalizedPath])) {
            $handler = $this->routes[$method][$normalizedPath];
            $this->executeHandler($handler);
            return;
        }

        // Ruta no encontrada - HTTP 404
        http_response_code(404);
        echo $this->renderNotFound($normalizedPath);
    }

    private function executeHandler(callable|array $handler): void
    {
        if (is_callable($handler)) {
            call_user_func($handler);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $action] = $handler;
            if (class_exists($class)) {
                $controller = new $class();
                if (method_exists($controller, $action)) {
                    $controller->$action();
                    return;
                }
            }
        }

        http_response_code(500);
        echo "Error 500: Controlador o acción no válida.";
    }

    private function renderNotFound(string $path): string
    {
        $escapedPath = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
        $homeRoute = Auth::check() ? Auth::getHomeRoute() : '/login';
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - Error 404</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container text-center py-5" style="max-width: 600px;">
        <div class="card shadow-sm border-0 p-4">
            <div class="card-body">
                <h1 class="display-1 fw-bold text-danger mb-3">404</h1>
                <h2 class="h4 mb-3">Página no encontrada</h2>
                <p class="text-muted mb-4">La ruta <code>{$escapedPath}</code> no existe en el sistema.</p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="{$homeRoute}" class="btn btn-primary px-4 py-2">Volver al Inicio</a>
                    <a href="/salud" class="btn btn-outline-secondary px-4 py-2">Verificación de Salud</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
