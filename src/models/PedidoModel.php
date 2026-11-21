<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;
use RuntimeException;

class PedidoModel
{
    private PDO $db;

    public const ESTADOS_VALIDOS = ['pendiente', 'en_preparacion', 'listo', 'entregado', 'cancelado'];

    // Transiciones estrictas permitidas de la máquina de estados (RF-10)
    public const TRANSICIONES_PERMITIDAS = [
        'pendiente'      => ['en_preparacion', 'cancelado'],
        'en_preparacion' => ['listo'],
        'listo'          => ['entregado'],
        'entregado'      => [],
        'cancelado'      => []
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(?string $estado = null, ?string $tipo = null): array
    {
        $sql = "
            SELECT p.id, p.mesa_id, p.usuario_id, p.tipo, p.estado, p.num_comensales, p.total, p.creado_en,
                   m.numero AS mesa_numero, m.zona AS mesa_zona,
                   u.username AS mesero_username,
                   per.nombres AS mesero_nombres, per.apellidos AS mesero_apellidos,
                   (SELECT COUNT(*) FROM detalle_pedido dp WHERE dp.pedido_id = p.id) AS total_items
            FROM pedido p
            LEFT JOIN mesa m ON p.mesa_id = m.id
            INNER JOIN usuario u ON p.usuario_id = u.id
            INNER JOIN persona per ON u.persona_id = per.id
            WHERE 1=1
        ";
        $params = [];

        if ($estado !== null && $estado !== '') {
            $sql .= " AND p.estado = :estado";
            $params['estado'] = $estado;
        }

        if ($tipo !== null && $tipo !== '') {
            $sql .= " AND p.tipo = :tipo";
            $params['tipo'] = $tipo;
        }

        $sql .= " ORDER BY p.id DESC;";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT p.id, p.mesa_id, p.usuario_id, p.tipo, p.estado, p.num_comensales, p.total, p.creado_en,
                   m.numero AS mesa_numero, m.zona AS mesa_zona,
                   u.username AS mesero_username,
                   per.nombres AS mesero_nombres, per.apellidos AS mesero_apellidos
            FROM pedido p
            LEFT JOIN mesa m ON p.mesa_id = m.id
            INNER JOIN usuario u ON p.usuario_id = u.id
            INNER JOIN persona per ON u.persona_id = per.id
            WHERE p.id = :id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function obtenerDetalles(int $pedidoId): array
    {
        $sql = "
            SELECT dp.id, dp.pedido_id, dp.plato_id, dp.cantidad, dp.precio_unitario,
                   (dp.cantidad * dp.precio_unitario) AS subtotal,
                   pl.nombre AS plato_nombre,
                   c.nombre AS categoria_nombre
            FROM detalle_pedido dp
            INNER JOIN plato pl ON dp.plato_id = pl.id
            INNER JOIN categoria_menu c ON pl.categoria_id = c.id
            WHERE dp.pedido_id = :pedido_id
            ORDER BY dp.id ASC;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pedido_id' => $pedidoId]);
        return $stmt->fetchAll();
    }

    public function obtenerHistorialEstados(int $pedidoId): array
    {
        $sql = "
            SELECT h.id, h.pedido_id, h.estado, h.cambiado_en, h.usuario_id,
                   u.username, per.nombres, per.apellidos, r.nombre AS rol_nombre
            FROM historial_estado_pedido h
            INNER JOIN usuario u ON h.usuario_id = u.id
            INNER JOIN persona per ON u.persona_id = per.id
            INNER JOIN rol r ON u.rol_id = r.id
            WHERE h.pedido_id = :pedido_id
            ORDER BY h.cambiado_en ASC, h.id ASC;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pedido_id' => $pedidoId]);
        return $stmt->fetchAll();
    }

    /**
     * RF-09: Creación de pedido en transacción atómica:
     * - Calcula total en el servidor usando precios oficiales de la base de datos.
     * - Inserta cabecera y detalles.
     * - Registra estado 'pendiente' en historial_estado_pedido.
     * - Si es por mesa, actualiza la mesa a 'ocupada'.
     */
    public function crear(array $cabecera, array $items, int $usuarioId): int
    {
        if (empty($items)) {
            throw new RuntimeException("El pedido debe contener al menos un plato.");
        }

        $tipo = $cabecera['tipo'] ?? 'mesa';
        $mesaId = ($tipo === 'mesa' && !empty($cabecera['mesa_id'])) ? (int)$cabecera['mesa_id'] : null;
        $numComensales = max(1, (int)($cabecera['num_comensales'] ?? 1));

        $this->db->beginTransaction();
        try {
            // 1. Validar platos y calcular el total en el servidor
            $totalServidor = 0.00;
            $itemsValidados = [];

            $stmtPlato = $this->db->prepare("SELECT id, nombre, precio, activo FROM plato WHERE id = :id;");

            foreach ($items as $item) {
                $platoId = (int)($item['plato_id'] ?? 0);
                $cantidad = (int)($item['cantidad'] ?? 0);

                if ($platoId <= 0 || $cantidad <= 0) {
                    continue; // Ignorar items no seleccionados o cantidad 0
                }

                $stmtPlato->execute(['id' => $platoId]);
                $plato = $stmtPlato->fetch();

                if (!$plato) {
                    throw new RuntimeException("El plato con ID {$platoId} no existe en el menú.");
                }

                if (!$plato['activo']) {
                    throw new RuntimeException("El plato '{$plato['nombre']}' se encuentra desactivado y no puede ordenarse.");
                }

                $precioUnitario = (float)$plato['precio'];
                $subtotal = round($cantidad * $precioUnitario, 2);
                $totalServidor += $subtotal;

                $itemsValidados[] = [
                    'plato_id'        => $platoId,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioUnitario
                ];
            }

            if (empty($itemsValidados)) {
                throw new RuntimeException("Debes seleccionar al menos un plato con cantidad mayor a cero.");
            }

            $totalServidor = round($totalServidor, 2);

            // 2. Insertar cabecera del pedido
            $sqlPedido = "
                INSERT INTO pedido (mesa_id, usuario_id, tipo, estado, num_comensales, total, creado_en)
                VALUES (:mesa_id, :usuario_id, :tipo, 'pendiente', :num_comensales, :total, CURRENT_TIMESTAMP)
                RETURNING id;
            ";
            $stmtPedido = $this->db->prepare($sqlPedido);
            $stmtPedido->execute([
                'mesa_id'        => $mesaId,
                'usuario_id'     => $usuarioId,
                'tipo'           => $tipo,
                'num_comensales' => $numComensales,
                'total'          => $totalServidor
            ]);
            $pedidoId = (int)$stmtPedido->fetchColumn();

            // 3. Insertar detalles del pedido
            $sqlDetalle = "
                INSERT INTO detalle_pedido (pedido_id, plato_id, cantidad, precio_unitario)
                VALUES (:pedido_id, :plato_id, :cantidad, :precio_unitario);
            ";
            $stmtDetalle = $this->db->prepare($sqlDetalle);

            foreach ($itemsValidados as $iv) {
                $stmtDetalle->execute([
                    'pedido_id'       => $pedidoId,
                    'plato_id'        => $iv['plato_id'],
                    'cantidad'        => $iv['cantidad'],
                    'precio_unitario' => $iv['precio_unitario']
                ]);
            }

            // 4. Registrar estado inicial en historial_estado_pedido (RF-10)
            $sqlHistorial = "
                INSERT INTO historial_estado_pedido (pedido_id, estado, cambiado_en, usuario_id)
                VALUES (:pedido_id, 'pendiente', CURRENT_TIMESTAMP, :usuario_id);
            ";
            $stmtHistorial = $this->db->prepare($sqlHistorial);
            $stmtHistorial->execute([
                'pedido_id'  => $pedidoId,
                'usuario_id' => $usuarioId
            ]);

            // 5. Si es pedido en mesa, cambiar estado de la mesa a 'ocupada' (RF-09)
            if ($mesaId !== null) {
                $stmtMesa = $this->db->prepare("UPDATE mesa SET estado = 'ocupada' WHERE id = :mesa_id;");
                $stmtMesa->execute(['mesa_id' => $mesaId]);
            }

            $this->db->commit();
            return $pedidoId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Database::logError("Error al crear pedido: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * RF-11: Modificar pedido solo si está en estado 'pendiente'.
     */
    public function actualizar(int $pedidoId, array $cabecera, array $items): array
    {
        $pedido = $this->buscarPorId($pedidoId);
        if (!$pedido) {
            return ['ok' => false, 'mensaje' => 'El pedido no existe.'];
        }

        // RF-11: Bloquear si ya no está en estado pendiente
        if ($pedido['estado'] !== 'pendiente') {
            return [
                'ok'      => false,
                'mensaje' => "El pedido #{$pedidoId} se encuentra en estado '{$pedido['estado']}' y no puede ser modificado. Solo se permite mientras esté pendiente."
            ];
        }

        if (empty($items)) {
            return ['ok' => false, 'mensaje' => 'El pedido debe contener al menos un plato.'];
        }

        $tipo = $cabecera['tipo'] ?? $pedido['tipo'];
        $mesaId = ($tipo === 'mesa' && !empty($cabecera['mesa_id'])) ? (int)$cabecera['mesa_id'] : null;
        $numComensales = max(1, (int)($cabecera['num_comensales'] ?? $pedido['num_comensales']));

        $this->db->beginTransaction();
        try {
            // Calcular total en servidor
            $totalServidor = 0.00;
            $itemsValidados = [];
            $stmtPlato = $this->db->prepare("SELECT id, nombre, precio, activo FROM plato WHERE id = :id;");

            foreach ($items as $item) {
                $platoId = (int)($item['plato_id'] ?? 0);
                $cantidad = (int)($item['cantidad'] ?? 0);

                if ($platoId <= 0 || $cantidad <= 0) continue;

                $stmtPlato->execute(['id' => $platoId]);
                $plato = $stmtPlato->fetch();

                if (!$plato) throw new RuntimeException("Plato ID {$platoId} no encontrado.");

                $precioUnitario = (float)$plato['precio'];
                $subtotal = round($cantidad * $precioUnitario, 2);
                $totalServidor += $subtotal;

                $itemsValidados[] = [
                    'plato_id'        => $platoId,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioUnitario
                ];
            }

            if (empty($itemsValidados)) {
                throw new RuntimeException("Debe seleccionar al menos un plato con cantidad válida.");
            }

            $totalServidor = round($totalServidor, 2);

            // Si cambió de mesa, liberar la anterior si aplica
            if ($pedido['mesa_id'] && $pedido['mesa_id'] !== $mesaId) {
                $this->verificarYActualizarEstadoMesa((int)$pedido['mesa_id']);
            }

            // Actualizar cabecera
            $stmtUp = $this->db->prepare("
                UPDATE pedido
                SET mesa_id = :mesa_id, tipo = :tipo, num_comensales = :num_comensales, total = :total
                WHERE id = :id;
            ");
            $stmtUp->execute([
                'mesa_id'        => $mesaId,
                'tipo'           => $tipo,
                'num_comensales' => $numComensales,
                'total'          => $totalServidor,
                'id'             => $pedidoId
            ]);

            // Reemplazar detalles
            $this->db->prepare("DELETE FROM detalle_pedido WHERE pedido_id = :id;")->execute(['id' => $pedidoId]);

            $stmtDetalle = $this->db->prepare("
                INSERT INTO detalle_pedido (pedido_id, plato_id, cantidad, precio_unitario)
                VALUES (:pedido_id, :plato_id, :cantidad, :precio_unitario);
            ");
            foreach ($itemsValidados as $iv) {
                $stmtDetalle->execute([
                    'pedido_id'       => $pedidoId,
                    'plato_id'        => $iv['plato_id'],
                    'cantidad'        => $iv['cantidad'],
                    'precio_unitario' => $iv['precio_unitario']
                ]);
            }

            // Asegurar que la nueva mesa pase a ocupada
            if ($mesaId !== null) {
                $this->db->prepare("UPDATE mesa SET estado = 'ocupada' WHERE id = :mesa_id;")->execute(['mesa_id' => $mesaId]);
            }

            $this->db->commit();
            return ['ok' => true, 'mensaje' => "Pedido #{$pedidoId} modificado correctamente."];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Database::logError("Error al actualizar pedido {$pedidoId}: " . $e->getMessage());
            return ['ok' => false, 'mensaje' => $e->getMessage()];
        }
    }

    /**
     * RF-11: Cancelar pedido solo si está en estado 'pendiente'.
     */
    public function cancelar(int $pedidoId, int $usuarioId): array
    {
        $pedido = $this->buscarPorId($pedidoId);
        if (!$pedido) {
            return ['ok' => false, 'mensaje' => 'El pedido no existe.'];
        }

        if ($pedido['estado'] !== 'pendiente') {
            return [
                'ok'      => false,
                'mensaje' => "No se puede cancelar el pedido #{$pedidoId} porque ya se encuentra en estado '{$pedido['estado']}'. Solo se permite cancelar mientras esté pendiente."
            ];
        }

        return $this->cambiarEstado($pedidoId, 'cancelado', $usuarioId);
    }

    /**
     * RF-10: Máquina de estados sin saltos e inserción en historial_estado_pedido.
     * Transiciones válidas:
     * pendiente -> en_preparacion | cancelado
     * en_preparacion -> listo
     * listo -> entregado
     */
    public function cambiarEstado(int $pedidoId, string $nuevoEstado, int $usuarioId): array
    {
        $pedido = $this->buscarPorId($pedidoId);
        if (!$pedido) {
            return ['ok' => false, 'mensaje' => 'El pedido no existe.'];
        }

        $estadoActual = $pedido['estado'];

        // Validar que la transición sea permitida sin saltos (RF-10)
        $permitidos = self::TRANSICIONES_PERMITIDAS[$estadoActual] ?? [];
        if (!in_array($nuevoEstado, $permitidos, true)) {
            $transicionesTexto = empty($permitidos) ? 'ninguna (estado terminal)' : implode(', ', $permitidos);
            return [
                'ok'      => false,
                'mensaje' => "Transición de estado inválida: no se puede pasar de '{$estadoActual}' a '{$nuevoEstado}'. Transición permitida: [{$transicionesTexto}]."
            ];
        }

        $this->db->beginTransaction();
        try {
            // 1. Actualizar estado del pedido
            $stmtUp = $this->db->prepare("UPDATE pedido SET estado = :estado WHERE id = :id;");
            $stmtUp->execute(['estado' => $nuevoEstado, 'id' => $pedidoId]);

            // 2. Registrar en historial_estado_pedido con fecha y hora exacta
            $stmtHist = $this->db->prepare("
                INSERT INTO historial_estado_pedido (pedido_id, estado, cambiado_en, usuario_id)
                VALUES (:pedido_id, :estado, CURRENT_TIMESTAMP, :usuario_id);
            ");
            $stmtHist->execute([
                'pedido_id'  => $pedidoId,
                'estado'     => $nuevoEstado,
                'usuario_id' => $usuarioId
            ]);

            // 3. Si el pedido pasa a 'entregado' o 'cancelado', verificar si se libera la mesa
            if (in_array($nuevoEstado, ['entregado', 'cancelado'], true) && $pedido['mesa_id']) {
                $this->verificarYActualizarEstadoMesa((int)$pedido['mesa_id']);
            }

            $this->db->commit();
            return ['ok' => true, 'mensaje' => "Pedido #{$pedidoId} avanzado a '{$nuevoEstado}'."];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Database::logError("Error al cambiar estado de pedido {$pedidoId}: " . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'Error al cambiar estado del pedido.'];
        }
    }

    /**
     * Libera la mesa a 'libre' si no tiene otros pedidos activos (pendiente / en_preparacion / listo).
     */
    private function verificarYActualizarEstadoMesa(int $mesaId): void
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS activos 
            FROM pedido 
            WHERE mesa_id = :mesa_id AND estado IN ('pendiente', 'en_preparacion', 'listo');
        ");
        $stmt->execute(['mesa_id' => $mesaId]);
        $activos = (int)$stmt->fetchColumn();

        if ($activos === 0) {
            $this->db->prepare("UPDATE mesa SET estado = 'libre' WHERE id = :mesa_id;")->execute(['mesa_id' => $mesaId]);
        }
    }

    /**
     * Consulta para el tablero de cocina (RF-10): pedidos activos con detalles.
     */
    public function listarParaCocina(): array
    {
        $sql = "
            SELECT p.id, p.mesa_id, p.tipo, p.estado, p.num_comensales, p.creado_en,
                   m.numero AS mesa_numero, m.zona AS mesa_zona,
                   u.username AS mesero_username,
                   (
                       SELECT json_agg(json_build_object(
                           'plato', pl.nombre,
                           'cantidad', dp.cantidad,
                           'categoria', c.nombre
                       ))
                       FROM detalle_pedido dp
                       INNER JOIN plato pl ON dp.plato_id = pl.id
                       INNER JOIN categoria_menu c ON pl.categoria_id = c.id
                       WHERE dp.pedido_id = p.id
                   ) AS items
            FROM pedido p
            LEFT JOIN mesa m ON p.mesa_id = m.id
            INNER JOIN usuario u ON p.usuario_id = u.id
            WHERE p.estado IN ('pendiente', 'en_preparacion', 'listo', 'entregado')
              AND p.creado_en >= (CURRENT_TIMESTAMP - INTERVAL '12 hours')
            ORDER BY 
                CASE p.estado 
                    WHEN 'pendiente' THEN 1 
                    WHEN 'en_preparacion' THEN 2 
                    WHEN 'listo' THEN 3 
                    WHEN 'entregado' THEN 4 
                    ELSE 5 
                END,
                p.id ASC;
        ";
        $stmt = $this->db->query($sql);
        $pedidos = $stmt->fetchAll();

        foreach ($pedidos as &$p) {
            $p['items'] = json_decode($p['items'] ?? '[]', true) ?: [];
        }

        return $pedidos;
    }
}
