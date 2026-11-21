<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;
use App\Models\MesaModel;
use App\Models\MenuModel;
use App\Models\PedidoModel;

class PedidoController
{
    private PedidoModel $pedidoModel;
    private MesaModel $mesaModel;
    private MenuModel $menuModel;

    public function __construct()
    {
        // Administrador y Mesero tienen permisos para gestionar pedidos (RF-09, RF-11)
        Auth::requireRole(['administrador', 'mesero']);
        $this->pedidoModel = new PedidoModel();
        $this->mesaModel = new MesaModel();
        $this->menuModel = new MenuModel();
    }

    public function index(): void
    {
        $estadoFiltro = isset($_GET['estado']) ? trim((string)$_GET['estado']) : null;
        $tipoFiltro = isset($_GET['tipo']) ? trim((string)$_GET['tipo']) : null;

        $pedidos = $this->pedidoModel->listar($estadoFiltro, $tipoFiltro);

        View::render('pedidos/index', [
            'titulo'       => 'Gestión de Pedidos',
            'pedidos'      => $pedidos,
            'estadoFiltro' => $estadoFiltro,
            'tipoFiltro'   => $tipoFiltro
        ]);
    }

    public function crear(): void
    {
        // Solo platos activos disponibles para nuevos pedidos (RF-08, RF-09)
        $platos = $this->menuModel->listarPlatos(null, null, true);
        $categorias = $this->menuModel->listarCategorias();
        $mesas = $this->mesaModel->listar();

        View::render('pedidos/crear', [
            'titulo'     => 'Tomar Nuevo Pedido',
            'platos'     => $platos,
            'categorias' => $categorias,
            'mesas'      => $mesas
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
          ->required('tipo', 'Tipo de Pedido')
          ->custom('tipo', function ($tipo) {
              return in_array($tipo, ['mesa', 'llevar'], true);
          }, 'El tipo de pedido debe ser mesa o llevar.')
          ->required('num_comensales', 'Número de Comensales')
          ->numeric('num_comensales', 'Número de Comensales')
          ->custom('num_comensales', function ($c) {
              return (int)$c > 0;
          }, 'El número de comensales debe ser al menos 1.');

        $tipo = $_POST['tipo'] ?? 'mesa';
        if ($tipo === 'mesa') {
            $v->required('mesa_id', 'Mesa Asignada')
              ->numeric('mesa_id', 'Mesa Asignada');
        }

        if (!$v->passes()) {
            Flash::error($v->firstError());
            header('Location: /pedidos/crear');
            exit;
        }

        // Procesar platos seleccionados
        $rawCantidades = $_POST['cantidades'] ?? [];
        $items = [];
        foreach ($rawCantidades as $platoId => $cantidad) {
            $cant = (int)$cantidad;
            if ($cant > 0) {
                $items[] = [
                    'plato_id' => (int)$platoId,
                    'cantidad' => $cant
                ];
            }
        }

        if (empty($items)) {
            Flash::error('Debes seleccionar al menos un plato con cantidad mayor a cero.');
            header('Location: /pedidos/crear');
            exit;
        }

        $cabecera = [
            'tipo'           => $tipo,
            'mesa_id'        => $tipo === 'mesa' ? (int)$_POST['mesa_id'] : null,
            'num_comensales' => (int)$_POST['num_comensales']
        ];

        try {
            // El total se calcula obligatoriamente en el servidor en la transacción (RF-09)
            $pedidoId = $this->pedidoModel->crear($cabecera, $items, Auth::id());
            Flash::success("Pedido #{$pedidoId} creado exitosamente.");
            header('Location: /pedidos');
            exit;
        } catch (\Throwable $e) {
            Flash::error("Error al registrar el pedido: " . $e->getMessage());
            header('Location: /pedidos/crear');
            exit;
        }
    }

    public function ver(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $pedido = $this->pedidoModel->buscarPorId($id);

        if (!$pedido) {
            Flash::error('Pedido no encontrado.');
            header('Location: /pedidos');
            exit;
        }

        $detalles = $this->pedidoModel->obtenerDetalles($id);
        $historial = $this->pedidoModel->obtenerHistorialEstados($id);

        View::render('pedidos/ver', [
            'titulo'    => "Detalle del Pedido #{$pedido['id']}",
            'pedido'    => $pedido,
            'detalles'  => $detalles,
            'historial' => $historial
        ]);
    }

    public function editar(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $pedido = $this->pedidoModel->buscarPorId($id);

        if (!$pedido) {
            Flash::error('Pedido no encontrado.');
            header('Location: /pedidos');
            exit;
        }

        // RF-11: Modificar solo si está pendiente
        if ($pedido['estado'] !== 'pendiente') {
            Flash::error("El pedido #{$id} se encuentra en estado '{$pedido['estado']}' y no puede modificarse. Solo se permite mientras esté pendiente.");
            header('Location: /pedidos');
            exit;
        }

        $detalles = $this->pedidoModel->obtenerDetalles($id);
        $platos = $this->menuModel->listarPlatos(null, null, true);
        $categorias = $this->menuModel->listarCategorias();
        $mesas = $this->mesaModel->listar();

        // Mapear cantidades actuales del pedido
        $cantidadesActuales = [];
        foreach ($detalles as $d) {
            $cantidadesActuales[(int)$d['plato_id']] = (int)$d['cantidad'];
        }

        View::render('pedidos/editar', [
            'titulo'             => "Modificar Pedido #{$pedido['id']}",
            'pedido'             => $pedido,
            'detalles'           => $detalles,
            'platos'             => $platos,
            'categorias'         => $categorias,
            'mesas'              => $mesas,
            'cantidadesActuales' => $cantidadesActuales
        ]);
    }

    public function actualizar(): void
    {
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);
        $tipo = $_POST['tipo'] ?? 'mesa';
        $mesaId = $tipo === 'mesa' ? (int)($_POST['mesa_id'] ?? 0) : null;
        $numComensales = max(1, (int)($_POST['num_comensales'] ?? 1));

        $rawCantidades = $_POST['cantidades'] ?? [];
        $items = [];
        foreach ($rawCantidades as $platoId => $cantidad) {
            $cant = (int)$cantidad;
            if ($cant > 0) {
                $items[] = [
                    'plato_id' => (int)$platoId,
                    'cantidad' => $cant
                ];
            }
        }

        if (empty($items)) {
            Flash::error('Debes seleccionar al menos un plato con cantidad.');
            header('Location: /pedidos/editar?id=' . $id);
            exit;
        }

        $cabecera = [
            'tipo'           => $tipo,
            'mesa_id'        => $mesaId,
            'num_comensales' => $numComensales
        ];

        // RF-11: El modelo verifica el estado 'pendiente' y recalcula en servidor
        $res = $this->pedidoModel->actualizar($id, $cabecera, $items);

        if ($res['ok']) {
            Flash::success($res['mensaje']);
        } else {
            Flash::error($res['mensaje']);
        }

        header('Location: /pedidos');
        exit;
    }

    public function cancelar(): void
    {
        Csrf::verifyPostRequest();

        $id = (int)($_POST['id'] ?? 0);

        // RF-11: Cancelar pedido solo mientras esté pendiente
        $res = $this->pedidoModel->cancelar($id, Auth::id());

        if ($res['ok']) {
            Flash::success($res['mensaje']);
        } else {
            Flash::error($res['mensaje']);
        }

        header('Location: /pedidos');
        exit;
    }
}
