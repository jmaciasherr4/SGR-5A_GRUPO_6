<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\UsuarioModel;

/**
 * Servicio de Autenticación y Control de Accesos por Rol (RF-01, RF-02, RF-03).
 */
class Auth
{
    private const SESSION_USER = '_auth_user';

    public static function user(): ?array
    {
        Session::start();
        return Session::get(self::SESSION_USER);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function username(): ?string
    {
        $u = self::user();
        return $u ? $u['username'] : null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u ? $u['rol'] : null;
    }

    public static function roleId(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['rol_id'] : null;
    }

    public static function fullName(): string
    {
        $u = self::user();
        if (!$u) {
            return '';
        }
        return trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
    }

    /**
     * Intenta autenticar al usuario con username y password.
     * Retorna array con resultado ['ok' => bool, 'error' => ?string]
     */
    public static function attempt(string $username, string $password): array
    {
        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->buscarPorUsername($username);

        if (!$usuario) {
            return ['ok' => false, 'error' => 'Usuario o contraseña incorrectos.'];
        }

        // RF-02: Comprobar que el usuario esté activo
        if (!$usuario['activo']) {
            return ['ok' => false, 'error' => 'Tu cuenta ha sido desactivada. Comunícate con el administrador.'];
        }

        // Verificar hash con bcrypt
        if (!password_verify($password, $usuario['password_hash'])) {
            return ['ok' => false, 'error' => 'Usuario o contraseña incorrectos.'];
        }

        // RF-01: Regenerar ID de sesión para prevenir Session Fixation
        Session::regenerate();

        // Almacenar datos esenciales de la sesión (sin el password_hash)
        Session::set(self::SESSION_USER, [
            'id'          => (int)$usuario['id'],
            'persona_id'  => (int)$usuario['persona_id'],
            'username'    => $usuario['username'],
            'rol_id'      => (int)$usuario['rol_id'],
            'rol'         => $usuario['rol_nombre'],
            'nombres'     => $usuario['nombres'],
            'apellidos'   => $usuario['apellidos'],
            'documento'   => $usuario['documento'],
        ]);

        return ['ok' => true, 'error' => null];
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    /**
     * Exige que el usuario haya iniciado sesión. Redirige a /login si no.
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            Flash::error('Debes iniciar sesión para acceder a esta sección.');
            header('Location: /login');
            exit;
        }

        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
        }
    }

    /**
     * Exige que el usuario tenga uno de los roles especificados (RF-03).
     * Si no, responde HTTP 403 con pantalla amable.
     */
    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();

        $allowedRoles = is_array($roles) ? $roles : [$roles];
        $currentRole = self::role();

        if (!in_array($currentRole, $allowedRoles, true)) {
            http_response_code(403);
            self::renderForbidden($allowedRoles);
            exit;
        }
    }

    /**
     * Determina la ruta principal del panel según el rol del usuario (RF-01).
     */
    public static function getHomeRoute(): string
    {
        return match (self::role()) {
            'administrador' => '/usuarios',
            'mesero'        => '/mesas',
            'cocinero'      => '/cocina',
            'cajero'        => '/panel',
            default         => '/salud'
        };
    }

    private static function renderForbidden(array $allowedRoles): void
    {
        $currentRole = htmlspecialchars(self::role() ?? 'Sin rol', ENT_QUOTES, 'UTF-8');
        $allowed = htmlspecialchars(implode(', ', $allowedRoles), ENT_QUOTES, 'UTF-8');
        $homeRoute = self::check() ? self::getHomeRoute() : '/login';
        $csrfToken = htmlspecialchars(Csrf::getToken(), ENT_QUOTES, 'UTF-8');

        echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acceso Denegado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container text-center py-5" style="max-width: 600px;">
        <div class="card shadow-sm border-danger p-4">
            <div class="card-body">
                <div class="display-1 text-danger fw-bold mb-3">403</div>
                <h1 class="h3 fw-bold text-dark mb-2">Acceso Denegado</h1>
                <p class="text-muted mb-4">
                    Tu rol actual es <strong>{$currentRole}</strong>, pero este módulo requiere permiso de: <code>{$allowed}</code>.
                </p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="{$homeRoute}" class="btn btn-primary px-4 py-2">Volver al Inicio</a>
                    <form action="/logout" method="POST" class="d-inline m-0">
                        <input type="hidden" name="csrf_token" id="csrf_token_forbidden_logout" value="{$csrfToken}">
                        <button type="submit" class="btn btn-outline-secondary px-4 py-2">Cerrar Sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
