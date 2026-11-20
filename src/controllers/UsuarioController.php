<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;
use App\Models\RolModel;
use App\Models\UsuarioModel;

class UsuarioController
{
    private UsuarioModel $usuarioModel;
    private RolModel $rolModel;

    public function __construct()
    {
        // RF-03: Solo administradores pueden gestionar usuarios
        Auth::requireRole('administrador');
        $this->usuarioModel = new UsuarioModel();
        $this->rolModel = new RolModel();
    }

    public function index(): void
    {
        $usuarios = $this->usuarioModel->listarTodos();

        View::render('usuarios/index', [
            'titulo'   => 'Gestión de Usuarios',
            'usuarios' => $usuarios
        ]);
    }

    public function crear(): void
    {
        $roles = $this->rolModel->listarTodos();

        View::render('usuarios/formulario', [
            'titulo'   => 'Crear Nuevo Usuario',
            'roles'    => $roles,
            'usuario'  => null,
            'esEdicion'=> false
        ]);
    }

    public function guardar(): void
    {
        Csrf::verifyPostRequest();

        $v = new Validator($_POST);
        $v->required('csrf_token', 'Token de Seguridad (CSRF)')
          ->custom('csrf_token', function ($token) {
              return Csrf::validate($token);
          }, 'El token de seguridad CSRF es inválido o ha expirado.')
          ->required('nombres', 'Nombres')
          ->minLength('nombres', 2, 'Nombres')
          ->required('apellidos', 'Apellidos')
          ->minLength('apellidos', 2, 'Apellidos')
          ->required('documento', 'Documento de Identidad')
          ->document('documento', 'Documento de Identidad')
          ->custom('documento', function ($val) {
              return !$this->usuarioModel->existeDocumento(trim((string)$val));
          }, 'El documento de identidad ya se encuentra registrado por otra persona.')
          ->required('username', 'Nombre de Usuario')
          ->minLength('username', 3, 'Nombre de Usuario')
          ->maxLength('username', 50, 'Nombre de Usuario')
          ->custom('username', function ($val) {
              return !$this->usuarioModel->existeUsername(trim((string)$val));
          }, 'El nombre de usuario ya está en uso. Por favor elija otro.')
          ->required('rol_id', 'Rol')
          ->numeric('rol_id', 'Rol')
          ->required('password', 'Contraseña')
          ->minLength('password', 8, 'Contraseña');

        if (!$v->passes()) {
            Flash::error($v->firstError());
            View::render('usuarios/formulario', [
                'titulo'   => 'Crear Nuevo Usuario',
                'roles'    => $this->rolModel->listarTodos(),
                'usuario'  => $_POST,
                'esEdicion'=> false,
                'errores'  => $v->errors()
            ]);
            return;
        }

        $personaData = [
            'nombres'   => trim((string)$_POST['nombres']),
            'apellidos' => trim((string)$_POST['apellidos']),
            'documento' => trim((string)$_POST['documento']),
            'telefono'  => trim((string)($_POST['telefono'] ?? ''))
        ];

        $usuarioData = [
            'username' => trim((string)$_POST['username']),
            'password' => (string)$_POST['password'],
            'rol_id'   => (int)$_POST['rol_id'],
            'activo'   => isset($_POST['activo']) && $_POST['activo'] === '1'
        ];

        try {
            $this->usuarioModel->crear($personaData, $usuarioData);
            Flash::success("Usuario '{$usuarioData['username']}' creado correctamente.");
            header('Location: /usuarios');
            exit;
        } catch (\Throwable $e) {
            Flash::error('Error al guardar el usuario en la base de datos.');
            header('Location: /usuarios/crear');
            exit;
        }
    }

    public function editar(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $usuario = $this->usuarioModel->buscarPorId($id);

        if (!$usuario) {
            Flash::error('Usuario no encontrado.');
            header('Location: /usuarios');
            exit;
        }

        $roles = $this->rolModel->listarTodos();

        View::render('usuarios/formulario', [
            'titulo'   => 'Editar Usuario',
            'roles'    => $roles,
            'usuario'  => $usuario,
            'esEdicion'=> true
        ]);
    }

    public function actualizar(): void
    {
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);
        $usuario = $this->usuarioModel->buscarPorId($id);

        if (!$usuario) {
            Flash::error('El usuario a editar no existe.');
            header('Location: /usuarios');
            exit;
        }

        $v = new Validator($_POST);
        $v->required('csrf_token', 'Token de Seguridad (CSRF)')
          ->custom('csrf_token', function ($token) {
              return Csrf::validate($token);
          }, 'El token de seguridad CSRF es inválido o ha expirado.')
          ->required('nombres', 'Nombres')
          ->minLength('nombres', 2, 'Nombres')
          ->required('apellidos', 'Apellidos')
          ->minLength('apellidos', 2, 'Apellidos')
          ->required('documento', 'Documento de Identidad')
          ->document('documento', 'Documento de Identidad')
          ->custom('documento', function ($val) use ($usuario) {
              return !$this->usuarioModel->existeDocumento(trim((string)$val), (int)$usuario['persona_id']);
          }, 'El documento ya está en uso por otra persona.')
          ->required('username', 'Nombre de Usuario')
          ->minLength('username', 3, 'Nombre de Usuario')
          ->maxLength('username', 50, 'Nombre de Usuario')
          ->custom('username', function ($val) use ($id) {
              return !$this->usuarioModel->existeUsername(trim((string)$val), $id);
          }, 'El nombre de usuario ya está en uso.')
          ->required('rol_id', 'Rol')
          ->numeric('rol_id', 'Rol');

        // Contraseña opcional al editar; si se proporciona, mínimo 8 caracteres
        if (!empty($_POST['password'])) {
            $v->minLength('password', 8, 'Nueva Contraseña');
        }

        if (!$v->passes()) {
            Flash::error($v->firstError());
            View::render('usuarios/formulario', [
                'titulo'   => 'Editar Usuario',
                'roles'    => $this->rolModel->listarTodos(),
                'usuario'  => array_merge($usuario, $_POST),
                'esEdicion'=> true,
                'errores'  => $v->errors()
            ]);
            return;
        }

        $personaData = [
            'nombres'   => trim((string)$_POST['nombres']),
            'apellidos' => trim((string)$_POST['apellidos']),
            'documento' => trim((string)$_POST['documento']),
            'telefono'  => trim((string)($_POST['telefono'] ?? ''))
        ];

        $usuarioData = [
            'username' => trim((string)$_POST['username']),
            'password' => (string)($_POST['password'] ?? ''),
            'rol_id'   => (int)$_POST['rol_id'],
            'activo'   => isset($_POST['activo']) && $_POST['activo'] === '1'
        ];

        try {
            $this->usuarioModel->actualizar($id, $personaData, $usuarioData);
            Flash::success("Usuario '{$usuarioData['username']}' actualizado correctamente.");
            header('Location: /usuarios');
            exit;
        } catch (\Throwable $e) {
            Flash::error('Error al actualizar los datos en la base de datos.');
            header('Location: /usuarios/editar?id=' . $id);
            exit;
        }
    }

    public function cambiarEstado(): void
    {
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);
        $activo = ($_POST['activo'] ?? '0') === '1';

        // Prevenir que el administrador activo se desactive a sí mismo
        if ($id === Auth::id() && !$activo) {
            Flash::warning('No puedes desactivar tu propia cuenta mientras estás en sesión.');
            header('Location: /usuarios');
            exit;
        }

        $usuario = $this->usuarioModel->buscarPorId($id);
        if (!$usuario) {
            Flash::error('Usuario no encontrado.');
            header('Location: /usuarios');
            exit;
        }

        $this->usuarioModel->cambiarEstado($id, $activo);
        $mensaje = $activo ? "Usuario '{$usuario['username']}' activado." : "Usuario '{$usuario['username']}' desactivado. Ya no podrá iniciar sesión.";
        Flash::success($mensaje);
        header('Location: /usuarios');
        exit;
    }

    public function eliminar(): void
    {
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);

        // Prevenir eliminar la propia cuenta
        if ($id === Auth::id()) {
            Flash::warning('No puedes eliminar tu propia cuenta en sesión.');
            header('Location: /usuarios');
            exit;
        }

        $res = $this->usuarioModel->eliminar($id);

        if ($res['ok']) {
            Flash::success($res['mensaje']);
        } else {
            // Mensaje que ofrece "desactivar" ante restricciones de integridad referencial
            Flash::error($res['mensaje']);
        }

        header('Location: /usuarios');
        exit;
    }
}
