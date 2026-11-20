<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;
use App\Models\MenuModel;

class MenuController
{
    private MenuModel $menuModel;

    public function __construct()
    {
        // Administrador, Mesero y Cocinero pueden acceder a consultar el menú (RF-08)
        Auth::requireRole(['administrador', 'mesero', 'cocinero']);
        $this->menuModel = new MenuModel();
    }

    public function index(): void
    {
        $catFiltro = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;
        $busqueda = isset($_GET['buscar']) ? trim((string)$_GET['buscar']) : null;
        $esAdmin = Auth::role() === 'administrador';

        // Si es mesero en modo toma de pedidos se filtran solo activos, pero en la consulta general del catálogo se listan todos indicando su estado
        $platos = $this->menuModel->listarPlatos($catFiltro, $busqueda, null);
        $categorias = $this->menuModel->listarCategorias();

        View::render('menu/index', [
            'titulo'     => 'Catálogo de Menú y Precios',
            'platos'     => $platos,
            'categorias' => $categorias,
            'catFiltro'  => $catFiltro,
            'busqueda'   => $busqueda,
            'esAdmin'    => $esAdmin
        ]);
    }

    public function crearPlato(): void
    {
        Auth::requireRole('administrador');

        $categorias = $this->menuModel->listarCategorias();
        View::render('menu/formulario', [
            'titulo'     => 'Nuevo Plato del Menú',
            'plato'      => null,
            'categorias' => $categorias,
            'esEdicion'  => false
        ]);
    }

    public function guardarPlato(): void
    {
        Auth::requireRole('administrador');
        Csrf::verifyPostRequest();

        $v = new Validator($_POST);
        $v->required('csrf_token', 'Token de Seguridad (CSRF)')
          ->custom('csrf_token', function ($token) {
              return Csrf::validate($token);
          }, 'El token de seguridad CSRF es inválido o ha expirado.')
          ->required('categoria_id', 'Categoría')
          ->numeric('categoria_id', 'Categoría')
          ->required('nombre', 'Nombre del Plato')
          ->minLength('nombre', 3, 'Nombre del Plato')
          ->required('precio', 'Precio ($ USD)')
          ->custom('precio', function ($p) {
              return is_numeric($p) && (float)$p > 0;
          }, 'El precio del plato debe ser un número positivo mayor a cero (CHECK precio > 0).');

        if (!$v->passes()) {
            Flash::error($v->firstError());
            View::render('menu/formulario', [
                'titulo'     => 'Nuevo Plato del Menú',
                'plato'      => $_POST,
                'categorias' => $this->menuModel->listarCategorias(),
                'esEdicion'  => false,
                'errores'    => $v->errors()
            ]);
            return;
        }

        try {
            $this->menuModel->crearPlato([
                'categoria_id' => (int)$_POST['categoria_id'],
                'nombre'       => trim((string)$_POST['nombre']),
                'descripcion'  => trim((string)($_POST['descripcion'] ?? '')),
                'precio'       => round((float)$_POST['precio'], 2),
                'activo'       => isset($_POST['activo']) && $_POST['activo'] === '1'
            ]);
            Flash::success("Plato '{$_POST['nombre']}' registrado correctamente en el menú.");
            header('Location: /menu');
            exit;
        } catch (\Throwable $e) {
            Flash::error('Error al registrar el plato en la base de datos.');
            header('Location: /menu/crear');
            exit;
        }
    }

    public function editarPlato(): void
    {
        Auth::requireRole('administrador');

        $id = (int)($_GET['id'] ?? 0);
        $plato = $this->menuModel->buscarPlatoPorId($id);

        if (!$plato) {
            Flash::error('Plato no encontrado.');
            header('Location: /menu');
            exit;
        }

        View::render('menu/formulario', [
            'titulo'     => 'Editar Plato: ' . $plato['nombre'],
            'plato'      => $plato,
            'categorias' => $this->menuModel->listarCategorias(),
            'esEdicion'  => true
        ]);
    }

    public function actualizarPlato(): void
    {
        Auth::requireRole('administrador');
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);
        $plato = $this->menuModel->buscarPlatoPorId($id);

        if (!$plato) {
            Flash::error('El plato a editar no existe.');
            header('Location: /menu');
            exit;
        }

        $v = new Validator($_POST);
        $v->required('csrf_token', 'Token de Seguridad (CSRF)')
          ->custom('csrf_token', function ($token) {
              return Csrf::validate($token);
          }, 'El token de seguridad CSRF es inválido o ha expirado.')
          ->required('categoria_id', 'Categoría')
          ->numeric('categoria_id', 'Categoría')
          ->required('nombre', 'Nombre del Plato')
          ->minLength('nombre', 3, 'Nombre del Plato')
          ->required('precio', 'Precio ($ USD)')
          ->custom('precio', function ($p) {
              return is_numeric($p) && (float)$p > 0;
          }, 'El precio debe ser un número positivo mayor a cero.');

        if (!$v->passes()) {
            Flash::error($v->firstError());
            View::render('menu/formulario', [
                'titulo'     => 'Editar Plato: ' . $plato['nombre'],
                'plato'      => array_merge($plato, $_POST),
                'categorias' => $this->menuModel->listarCategorias(),
                'esEdicion'  => true,
                'errores'    => $v->errors()
            ]);
            return;
        }

        try {
            $this->menuModel->actualizarPlato($id, [
                'categoria_id' => (int)$_POST['categoria_id'],
                'nombre'       => trim((string)$_POST['nombre']),
                'descripcion'  => trim((string)($_POST['descripcion'] ?? '')),
                'precio'       => round((float)$_POST['precio'], 2),
                'activo'       => isset($_POST['activo']) && $_POST['activo'] === '1'
            ]);
            Flash::success("Plato '{$_POST['nombre']}' actualizado correctamente.");
            header('Location: /menu');
            exit;
        } catch (\Throwable $e) {
            Flash::error('Error al actualizar el plato en la base de datos.');
            header('Location: /menu/editar?id=' . $id);
            exit;
        }
    }

    /**
     * RF-08: Baja lógica de plato (activo = true/false).
     */
    public function cambiarEstadoPlato(): void
    {
        Auth::requireRole('administrador');
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);
        $activo = ($_POST['activo'] ?? '0') === '1';

        $plato = $this->menuModel->buscarPlatoPorId($id);
        if (!$plato) {
            Flash::error('Plato no encontrado.');
            header('Location: /menu');
            exit;
        }

        $this->menuModel->cambiarEstadoPlato($id, $activo);
        $mensaje = $activo ? "Plato '{$plato['nombre']}' activado para nuevos pedidos." : "Plato '{$plato['nombre']}' desactivado (no aparecerá al tomar pedidos, pero se conserva en pedidos históricos).";
        Flash::success($mensaje);
        header('Location: /menu');
        exit;
    }

    public function eliminarPlato(): void
    {
        Auth::requireRole('administrador');
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);
        $res = $this->menuModel->eliminarPlato($id);

        if ($res['ok']) {
            Flash::success($res['mensaje']);
        } else {
            Flash::error($res['mensaje']);
        }

        header('Location: /menu');
        exit;
    }

    // ========================================================================
    // SECCIÓN CATEGORÍAS
    // ========================================================================

    public function categorias(): void
    {
        Auth::requireRole('administrador');

        $categorias = $this->menuModel->listarCategorias();
        View::render('menu/categorias', [
            'titulo'     => 'Categorías del Menú',
            'categorias' => $categorias
        ]);
    }

    public function guardarCategoria(): void
    {
        Auth::requireRole('administrador');
        Csrf::verifyPostRequest();

        $nombre = trim((string)($_POST['nombre'] ?? ''));

        if (mb_strlen($nombre) < 2) {
            Flash::error('El nombre de la categoría debe tener al menos 2 caracteres.');
            header('Location: /menu/categorias');
            exit;
        }

        if ($this->menuModel->existeCategoriaNombre($nombre)) {
            Flash::error("Ya existe una categoría llamada '{$nombre}'.");
            header('Location: /menu/categorias');
            exit;
        }

        $this->menuModel->crearCategoria($nombre);
        Flash::success("Categoría '{$nombre}' creada correctamente.");
        header('Location: /menu/categorias');
        exit;
    }

    public function eliminarCategoria(): void
    {
        Auth::requireRole('administrador');
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);
        $res = $this->menuModel->eliminarCategoria($id);

        if ($res['ok']) {
            Flash::success($res['mensaje']);
        } else {
            Flash::error($res['mensaje']);
        }

        header('Location: /menu/categorias');
        exit;
    }
}
