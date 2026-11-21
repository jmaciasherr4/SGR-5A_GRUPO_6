<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Models\PedidoModel;

class CocinaController
{
    private PedidoModel $pedidoModel;

    public function __construct()
    {
        // Administrador y Cocinero tienen permisos para ver y operar la pantalla de cocina (RF-10)
        Auth::requireRole(['administrador', 'cocinero']);
        $this->pedidoModel = new PedidoModel();
    }

    public function index(): void
    {
        $pedidos = $this->pedidoModel->listarParaCocina();

        View::render('cocina/index', [
            'titulo'  => 'Pantalla de Cocina (Tiempo Real)',
            'pedidos' => $pedidos
        ]);
    }

    /**
     * RF-10: Endpoint JSON para auto-actualización del tablero cada 3 segundos con fetch().
     */
    public function pedidosJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $pedidos = $this->pedidoModel->listarParaCocina();
        echo json_encode([
            'ok'        => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'total'     => count($pedidos),
            'pedidos'   => $pedidos
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * RF-10: Avanzar estado del pedido sin saltos:
     * pendiente -> en_preparacion -> listo -> entregado
     */
    public function avanzarEstado(): void
    {
        Csrf::verifyPostRequest();

        $pedidoId = (int)($_POST['pedido_id'] ?? 0);
        $nuevoEstado = trim((string)($_POST['nuevo_estado'] ?? ''));

        $res = $this->pedidoModel->cambiarEstado($pedidoId, $nuevoEstado, Auth::id());

        if ($res['ok']) {
            Flash::success($res['mensaje']);
        } else {
            Flash::error($res['mensaje']);
        }

        header('Location: /cocina');
        exit;
    }
}
