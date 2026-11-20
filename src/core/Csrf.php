<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Protección contra ataques CSRF (Cross-Site Request Forgery).
 */
class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function getToken(): string
    {
        Session::start();
        $token = Session::get(self::SESSION_KEY);
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    public static function renderInput(?string $id = null): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        $idAttr = $id ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';
        return '<input type="hidden" name="csrf_token"' . $idAttr . ' value="' . $token . '">';
    }

    public static function validate(?string $token): bool
    {
        Session::start();
        $stored = Session::get(self::SESSION_KEY);
        if (!$stored || !$token) {
            return false;
        }
        return hash_equals($stored, $token);
    }

    public static function verifyPostRequest(): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $submittedToken = $_POST['csrf_token'] ?? null;
            if (!self::validate($submittedToken)) {
                http_response_code(403);
                echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Error de Validación CSRF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container text-center py-5">
        <h1 class="display-3 text-danger fw-bold">403</h1>
        <h2 class="h4 mb-3">Solicitud no válida o sesión expirada (Token CSRF inválido)</h2>
        <p class="text-muted mb-4">Por razones de seguridad, la acción no pudo ser procesada. Regresa a la página anterior e inténtalo de nuevo.</p>
        <a href="/login" class="btn btn-primary px-4 py-2">Volver al Inicio</a>
    </div>
</body>
</html>
HTML;
                exit;
            }
        }
    }
}
