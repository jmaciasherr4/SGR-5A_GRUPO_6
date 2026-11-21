<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;

class AuthController
{
    public function mostrarLogin(): void
    {
        // Si ya está autenticado, llevar a su panel correspondiente
        if (Auth::check()) {
            header('Location: ' . Auth::getHomeRoute());
            exit;
        }

        View::render('auth/login', [
            'titulo' => 'Iniciar Sesión'
        ], null); // Sin layout principal
    }

    public function procesarLogin(): void
    {
        Csrf::verifyPostRequest();

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            Flash::error('Debes ingresar tu usuario y contraseña.');
            header('Location: /login');
            exit;
        }

        $resultado = Auth::attempt($username, $password);

        if (!$resultado['ok']) {
            Flash::error($resultado['error']);
            header('Location: /login');
            exit;
        }

        // Login exitoso -> Redirigir según el rol asignado
        Flash::success('Bienvenido(a), ' . Auth::fullName() . ' (' . Auth::role() . ').');
        header('Location: ' . Auth::getHomeRoute());
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        Flash::info('Has cerrado sesión exitosamente.');
        header('Location: /login');
        exit;
    }

    public function panel(): void
    {
        Auth::requireLogin();
        
        $rol = Auth::role();
        if ($rol === 'administrador') {
            header('Location: /usuarios');
            exit;
        }
        if ($rol === 'mesero') {
            header('Location: /mesas');
            exit;
        }
        if ($rol === 'cocinero') {
            header('Location: /cocina');
            exit;
        }

        // Panel para cajero u otros roles base
        View::render('panel/cajero', [
            'titulo' => 'Panel de Control - Caja'
        ]);
    }
}
