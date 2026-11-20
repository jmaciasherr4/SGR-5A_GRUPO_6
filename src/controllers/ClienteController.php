<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;
use App\Models\ClienteModel;

class ClienteController
{
    private ClienteModel $clienteModel;

    public function __construct()
    {
        // RF-04: Administradores y Meseros tienen permiso para gestionar clientes
        Auth::requireRole(['administrador', 'mesero']);
        $this->clienteModel = new ClienteModel();
    }

    public function index(): void
    {
        $busqueda = isset($_GET['buscar']) ? trim((string)$_GET['buscar']) : null;
        $clientes = $this->clienteModel->listar($busqueda);

        View::render('clientes/index', [
            'titulo'   => 'Gestión de Clientes',
            'clientes' => $clientes,
            'busqueda' => $busqueda
        ]);
    }

    public function crear(): void
    {
        View::render('clientes/formulario', [
            'titulo'    => 'Registrar Nuevo Cliente',
            'cliente'   => null,
            'esEdicion' => false
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
          ->required('documento', 'Cédula / Documento')
          ->document('documento', 'Cédula / Documento')
          ->custom('documento', function ($doc) {
              return !$this->clienteModel->existeDocumento(trim((string)$doc));
          }, 'El documento de identidad ya se encuentra registrado para otro cliente o usuario.');

        if (!$v->passes()) {
            Flash::error($v->firstError());
            View::render('clientes/formulario', [
                'titulo'    => 'Registrar Nuevo Cliente',
                'cliente'   => $_POST,
                'esEdicion' => false,
                'errores'   => $v->errors()
            ]);
            return;
        }

        $personaData = [
            'nombres'   => trim((string)$_POST['nombres']),
            'apellidos' => trim((string)$_POST['apellidos']),
            'documento' => trim((string)$_POST['documento']),
            'telefono'  => trim((string)($_POST['telefono'] ?? ''))
        ];

        try {
            $clienteId = $this->clienteModel->crear($personaData);
            Flash::success("Cliente '{$personaData['nombres']} {$personaData['apellidos']}' registrado con éxito.");
            header('Location: /clientes');
            exit;
        } catch (\Throwable $e) {
            Flash::error('Error al registrar el cliente en la base de datos.');
            header('Location: /clientes/crear');
            exit;
        }
    }

    public function editar(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $cliente = $this->clienteModel->buscarPorId($id);

        if (!$cliente) {
            Flash::error('Cliente no encontrado.');
            header('Location: /clientes');
            exit;
        }

        View::render('clientes/formulario', [
            'titulo'    => 'Editar Datos de Cliente',
            'cliente'   => $cliente,
            'esEdicion' => true
        ]);
    }

    public function actualizar(): void
    {
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);
        $cliente = $this->clienteModel->buscarPorId($id);

        if (!$cliente) {
            Flash::error('El cliente a editar no existe.');
            header('Location: /clientes');
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
          ->required('documento', 'Cédula / Documento')
          ->document('documento', 'Cédula / Documento')
          ->custom('documento', function ($doc) use ($cliente) {
              return !$this->clienteModel->existeDocumento(trim((string)$doc), (int)$cliente['persona_id']);
          }, 'El documento de identidad ya está registrado por otra persona.');

        if (!$v->passes()) {
            Flash::error($v->firstError());
            View::render('clientes/formulario', [
                'titulo'    => 'Editar Datos de Cliente',
                'cliente'   => array_merge($cliente, $_POST),
                'esEdicion' => true,
                'errores'   => $v->errors()
            ]);
            return;
        }

        $personaData = [
            'nombres'   => trim((string)$_POST['nombres']),
            'apellidos' => trim((string)$_POST['apellidos']),
            'documento' => trim((string)$_POST['documento']),
            'telefono'  => trim((string)($_POST['telefono'] ?? ''))
        ];

        try {
            $this->clienteModel->actualizar($id, $personaData);
            Flash::success("Datos del cliente actualizados correctamente.");
            header('Location: /clientes');
            exit;
        } catch (\Throwable $e) {
            Flash::error('Error al actualizar los datos del cliente.');
            header('Location: /clientes/editar?id=' . $id);
            exit;
        }
    }

    public function eliminar(): void
    {
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);
        $res = $this->clienteModel->eliminar($id);

        if ($res['ok']) {
            Flash::success($res['mensaje']);
        } else {
            Flash::error($res['mensaje']);
        }

        header('Location: /clientes');
        exit;
    }
}
