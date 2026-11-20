<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;
use App\Models\MesaModel;

class MesaController
{
    private MesaModel $mesaModel;

    public function __construct()
    {
        // Administrador y Mesero pueden acceder a este controlador (RF-07)
        Auth::requireRole(['administrador', 'mesero']);
        $this->mesaModel = new MesaModel();
    }

    public function index(): void
    {
        $zonaFiltro = isset($_GET['zona']) ? trim((string)$_GET['zona']) : null;
        $estadoFiltro = isset($_GET['estado']) ? trim((string)$_GET['estado']) : null;

        $mesas = $this->mesaModel->listar($zonaFiltro, $estadoFiltro);
        $zonas = $this->mesaModel->obtenerZonas();

        View::render('mesas/index', [
            'titulo'       => 'Gestión de Mesas y Zonas',
            'mesas'        => $mesas,
            'zonas'        => $zonas,
            'zonaFiltro'   => $zonaFiltro,
            'estadoFiltro' => $estadoFiltro,
            'esAdmin'      => Auth::role() === 'administrador'
        ]);
    }

    /**
     * RF-07: Cambio rápido de estado desde el listado (accesible por Administrador y Mesero).
     */
    public function cambiarEstado(): void
    {
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);
        $nuevoEstado = trim((string)($_POST['estado'] ?? ''));

        if (!in_array($nuevoEstado, MesaModel::ESTADOS_VALIDOS, true)) {
            Flash::error('Estado de mesa no válido.');
            header('Location: /mesas');
            exit;
        }

        $mesa = $this->mesaModel->buscarPorId($id);
        if (!$mesa) {
            Flash::error('Mesa no encontrada.');
            header('Location: /mesas');
            exit;
        }

        $this->mesaModel->cambiarEstado($id, $nuevoEstado);
        Flash::success("Mesa #{$mesa['numero']} actualizada a estado '{$nuevoEstado}'.");
        header('Location: /mesas');
        exit;
    }

    public function crear(): void
    {
        // Solo administradores pueden crear mesas
        Auth::requireRole('administrador');

        View::render('mesas/formulario', [
            'titulo'    => 'Registrar Nueva Mesa',
            'mesa'      => null,
            'zonas'     => $this->mesaModel->obtenerZonas(),
            'esEdicion' => false
        ]);
    }

    public function guardar(): void
    {
        Auth::requireRole('administrador');
        Csrf::verifyPostRequest();

        $v = new Validator($_POST);
        $v->required('csrf_token', 'Token de Seguridad (CSRF)')
          ->custom('csrf_token', function ($token) {
              return Csrf::validate($token);
          }, 'El token de seguridad CSRF es inválido o ha expirado.')
          ->required('numero', 'Número de Mesa')
          ->numeric('numero', 'Número de Mesa')
          ->custom('numero', function ($num) {
              return (int)$num > 0;
          }, 'El número de mesa debe ser un entero positivo mayor a cero.')
          ->custom('numero', function ($num) {
              return !$this->mesaModel->existeNumero((int)$num);
          }, 'Ya existe una mesa registrada con este número. El número debe ser único.')
          ->required('capacidad', 'Capacidad de Comensales')
          ->numeric('capacidad', 'Capacidad de Comensales')
          ->custom('capacidad', function ($cap) {
              return (int)$cap > 0;
          }, 'La capacidad de comensales debe ser mayor a cero (CHECK capacidad > 0).')
          ->required('zona', 'Zona de Ubicación')
          ->minLength('zona', 2, 'Zona de Ubicación')
          ->required('estado', 'Estado')
          ->custom('estado', function ($est) {
              return in_array($est, MesaModel::ESTADOS_VALIDOS, true);
          }, 'El estado debe ser: libre, ocupada o reservada.');

        if (!$v->passes()) {
            Flash::error($v->firstError());
            View::render('mesas/formulario', [
                'titulo'    => 'Registrar Nueva Mesa',
                'mesa'      => $_POST,
                'zonas'     => $this->mesaModel->obtenerZonas(),
                'esEdicion' => false,
                'errores'   => $v->errors()
            ]);
            return;
        }

        try {
            $this->mesaModel->crear([
                'numero'    => (int)$_POST['numero'],
                'capacidad' => (int)$_POST['capacidad'],
                'zona'      => trim((string)$_POST['zona']),
                'estado'    => $_POST['estado']
            ]);
            Flash::success("Mesa #{$_POST['numero']} creada exitosamente.");
            header('Location: /mesas');
            exit;
        } catch (\Throwable $e) {
            Flash::error('Error al registrar la mesa en la base de datos.');
            header('Location: /mesas/crear');
            exit;
        }
    }

    public function editar(): void
    {
        Auth::requireRole('administrador');

        $id = (int)($_GET['id'] ?? 0);
        $mesa = $this->mesaModel->buscarPorId($id);

        if (!$mesa) {
            Flash::error('Mesa no encontrada.');
            header('Location: /mesas');
            exit;
        }

        View::render('mesas/formulario', [
            'titulo'    => 'Editar Mesa #' . $mesa['numero'],
            'mesa'      => $mesa,
            'zonas'     => $this->mesaModel->obtenerZonas(),
            'esEdicion' => true
        ]);
    }

    public function actualizar(): void
    {
        Auth::requireRole('administrador');
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);
        $mesa = $this->mesaModel->buscarPorId($id);

        if (!$mesa) {
            Flash::error('La mesa a editar no existe.');
            header('Location: /mesas');
            exit;
        }

        $v = new Validator($_POST);
        $v->required('csrf_token', 'Token de Seguridad (CSRF)')
          ->custom('csrf_token', function ($token) {
              return Csrf::validate($token);
          }, 'El token de seguridad CSRF es inválido o ha expirado.')
          ->required('numero', 'Número de Mesa')
          ->numeric('numero', 'Número de Mesa')
          ->custom('numero', function ($num) {
              return (int)$num > 0;
          }, 'El número de mesa debe ser un entero positivo.')
          ->custom('numero', function ($num) use ($id) {
              return !$this->mesaModel->existeNumero((int)$num, $id);
          }, 'El número de mesa ya está en uso por otra mesa.')
          ->required('capacidad', 'Capacidad de Comensales')
          ->numeric('capacidad', 'Capacidad de Comensales')
          ->custom('capacidad', function ($cap) {
              return (int)$cap > 0;
          }, 'La capacidad debe ser mayor a cero.')
          ->required('zona', 'Zona de Ubicación')
          ->minLength('zona', 2, 'Zona de Ubicación')
          ->required('estado', 'Estado')
          ->custom('estado', function ($est) {
              return in_array($est, MesaModel::ESTADOS_VALIDOS, true);
          }, 'Estado no válido.');

        if (!$v->passes()) {
            Flash::error($v->firstError());
            View::render('mesas/formulario', [
                'titulo'    => 'Editar Mesa #' . $mesa['numero'],
                'mesa'      => array_merge($mesa, $_POST),
                'zonas'     => $this->mesaModel->obtenerZonas(),
                'esEdicion' => true,
                'errores'   => $v->errors()
            ]);
            return;
        }

        try {
            $this->mesaModel->actualizar($id, [
                'numero'    => (int)$_POST['numero'],
                'capacidad' => (int)$_POST['capacidad'],
                'zona'      => trim((string)$_POST['zona']),
                'estado'    => $_POST['estado']
            ]);
            Flash::success("Mesa #{$_POST['numero']} actualizada correctamente.");
            header('Location: /mesas');
            exit;
        } catch (\Throwable $e) {
            Flash::error('Error al actualizar la mesa en la base de datos.');
            header('Location: /mesas/editar?id=' . $id);
            exit;
        }
    }

    public function eliminar(): void
    {
        Auth::requireRole('administrador');
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);
        $res = $this->mesaModel->eliminar($id);

        if ($res['ok']) {
            Flash::success($res['mensaje']);
        } else {
            Flash::error($res['mensaje']);
        }

        header('Location: /mesas');
        exit;
    }
}
