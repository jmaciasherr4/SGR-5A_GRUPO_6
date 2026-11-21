<?php
declare(strict_types=1);

/**
 * Tests Fase 3 — Pedidos (RF-09, RF-10, RF-11)
 * Uso: php tests/fase3_pedidos_test.php
 *
 * Requisitos que se verifican:
 *   RF-09  Creación atómica de pedido, cálculo de total en servidor,
 *          mesa pasa a 'ocupada'.
 *   RF-10  Máquina de estados sin saltos, registro en historial_estado_pedido,
 *          endpoint /cocina/pedidos-json devuelve JSON válido.
 *   RF-11  Bloqueo de modificación/cancelación cuando ya no está en 'pendiente'.
 */

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;
use App\Models\PedidoModel;
use App\Models\MesaModel;
use App\Models\MenuModel;

// ── Helpers de aserciones ──────────────────────────────────────────────────
$pasados  = 0;
$fallidos = 0;
$errores  = [];

function ok(bool $condicion, string $nombre): void
{
    global $pasados, $fallidos, $errores;
    if ($condicion) {
        echo "  ✅ PASS  {$nombre}\n";
        $pasados++;
    } else {
        echo "  ❌ FAIL  {$nombre}\n";
        $fallidos++;
        $errores[] = $nombre;
    }
}

function seccion(string $titulo): void
{
    echo "\n── {$titulo} " . str_repeat('─', max(0, 55 - strlen($titulo))) . "\n";
}

// ── Inicialización ──────────────────────────────────────────────────────────
echo "════════════════════════════════════════════════════════\n";
echo "  Tests Fase 3 — Pedidos (RF-09, RF-10, RF-11)\n";
echo "════════════════════════════════════════════════════════\n";

$db    = Database::getConnection();
$pedidoModel = new PedidoModel();
$mesaModel   = new MesaModel();
$menuModel   = new MenuModel();

// Buscar usuario de prueba (mesero o admin)
$stmtU = $db->query("SELECT u.id FROM usuario u INNER JOIN rol r ON u.rol_id = r.id WHERE r.nombre IN ('mesero','administrador') AND u.activo = true LIMIT 1;");
$usuarioTest = $stmtU->fetchColumn();

if (!$usuarioTest) {
    echo "\n⛔ No se encontró ningún usuario activo (mesero/admin) en la base de datos.\n";
    echo "   Ejecuta: docker compose up -d  y verifica que el seed 02_datos_base.sql se haya cargado.\n";
    exit(1);
}

$usuarioId = (int)$usuarioTest;
echo "\n  Usuario de prueba ID: {$usuarioId}\n";

// ═══════════════════════════════════════════════════════
//  SECCIÓN 1: RF-09 — Creación de pedido (PARA LLEVAR)
// ═══════════════════════════════════════════════════════
seccion('RF-09 — Creación de pedido para llevar');

// Obtener un plato activo
$stmtPl = $db->query("SELECT id, nombre, precio FROM plato WHERE activo = true LIMIT 2;");
$platosDisponibles = $stmtPl->fetchAll(PDO::FETCH_ASSOC);

ok(count($platosDisponibles) >= 1, 'Existe al menos 1 plato activo en el menú');

$plato1 = $platosDisponibles[0];
$plato2 = $platosDisponibles[1] ?? $platosDisponibles[0]; // reutilizar si solo hay uno

$items = [
    ['plato_id' => (int)$plato1['id'], 'cantidad' => 2],
    ['plato_id' => (int)$plato2['id'], 'cantidad' => 1],
];

$cabecera = [
    'tipo'           => 'llevar',
    'mesa_id'        => null,
    'num_comensales' => 2,
];

try {
    $pedidoId = $pedidoModel->crear($cabecera, $items, $usuarioId);
    ok($pedidoId > 0, "Pedido creado con ID: {$pedidoId}");
} catch (Throwable $e) {
    ok(false, "Creación de pedido — excepción: " . $e->getMessage());
    $pedidoId = 0;
}

if ($pedidoId > 0) {
    // Verificar que el pedido existe en BD
    $ped = $pedidoModel->buscarPorId($pedidoId);
    ok($ped !== null, 'Pedido encontrado en la BD');
    ok($ped['estado'] === 'pendiente', "Estado inicial es 'pendiente'");
    ok($ped['tipo'] === 'llevar', "Tipo almacenado es 'llevar'");
    ok((int)$ped['num_comensales'] === 2, 'Número de comensales correcto (2)');

    // Verificar que el total fue calculado en el servidor
    $totalEsperado = round(2 * (float)$plato1['precio'] + 1 * (float)$plato2['precio'], 2);
    // Si se reutilizó el mismo plato, el total es precio * 3
    if ($plato1['id'] === $plato2['id']) {
        $totalEsperado = round(3 * (float)$plato1['precio'], 2);
    }
    $totalBD = round((float)$ped['total'], 2);
    ok($totalBD === $totalEsperado, "Total calculado en servidor: \${$totalBD} (esperado: \${$totalEsperado})");

    // Verificar detalles del pedido
    $detalles = $pedidoModel->obtenerDetalles($pedidoId);
    ok(count($detalles) >= 1, 'Detalles del pedido insertados en detalle_pedido');

    // Verificar historial inicial
    $historial = $pedidoModel->obtenerHistorialEstados($pedidoId);
    ok(count($historial) === 1, 'Historial inicial tiene exactamente 1 registro');
    ok(($historial[0]['estado'] ?? '') === 'pendiente', "Primer historial es 'pendiente'");
}

// ═══════════════════════════════════════════════════════
//  SECCIÓN 2: RF-09 — Creación de pedido por MESA
// ═══════════════════════════════════════════════════════
seccion('RF-09 — Creación de pedido por mesa (mesa pasa a ocupada)');

// Buscar una mesa libre
$stmtMesa = $db->query("SELECT id, numero FROM mesa WHERE estado = 'libre' LIMIT 1;");
$mesaLibre = $stmtMesa->fetch(PDO::FETCH_ASSOC);

if ($mesaLibre) {
    $mesaId = (int)$mesaLibre['id'];

    $cabezeraMesa = [
        'tipo'           => 'mesa',
        'mesa_id'        => $mesaId,
        'num_comensales' => 3,
    ];
    $itemsMesa = [['plato_id' => (int)$plato1['id'], 'cantidad' => 1]];

    try {
        $pedidoMesaId = $pedidoModel->crear($cabezeraMesa, $itemsMesa, $usuarioId);
        ok($pedidoMesaId > 0, "Pedido por mesa #{$mesaLibre['numero']} creado con ID: {$pedidoMesaId}");

        // Verificar que la mesa cambió a 'ocupada'
        $mesaActualizada = $mesaModel->buscarPorId($mesaId);
        ok(($mesaActualizada['estado'] ?? '') === 'ocupada', "Mesa #{$mesaLibre['numero']} pasó a estado 'ocupada'");
    } catch (Throwable $e) {
        ok(false, "Pedido por mesa — excepción: " . $e->getMessage());
        $pedidoMesaId = 0;
    }
} else {
    echo "  ⚠ Aviso: No hay mesas libres disponibles. Se omite la prueba de mesa.\n";
    $pedidoMesaId = 0;
}

// ═══════════════════════════════════════════════════════
//  SECCIÓN 3: RF-10 — Máquina de estados sin saltos
// ═══════════════════════════════════════════════════════
seccion('RF-10 — Máquina de estados (transiciones válidas e inválidas)');

if ($pedidoId > 0) {

    // Transición válida: pendiente → en_preparacion
    $res = $pedidoModel->cambiarEstado($pedidoId, 'en_preparacion', $usuarioId);
    ok($res['ok'] === true, "Transición pendiente → en_preparacion: OK");

    $pedActual = $pedidoModel->buscarPorId($pedidoId);
    ok($pedActual['estado'] === 'en_preparacion', "Estado en BD ahora es 'en_preparacion'");

    // Transición INVÁLIDA: en_preparacion → entregado (salto de 'listo')
    $resInvalida = $pedidoModel->cambiarEstado($pedidoId, 'entregado', $usuarioId);
    ok($resInvalida['ok'] === false, "Transición ilegal en_preparacion → entregado rechazada");

    // Transición INVÁLIDA: en_preparacion → pendiente (retroceso)
    $resRetroceso = $pedidoModel->cambiarEstado($pedidoId, 'pendiente', $usuarioId);
    ok($resRetroceso['ok'] === false, "Retroceso en_preparacion → pendiente rechazado");

    // Transición válida: en_preparacion → listo
    $res2 = $pedidoModel->cambiarEstado($pedidoId, 'listo', $usuarioId);
    ok($res2['ok'] === true, "Transición en_preparacion → listo: OK");

    // Transición válida: listo → entregado
    $res3 = $pedidoModel->cambiarEstado($pedidoId, 'entregado', $usuarioId);
    ok($res3['ok'] === true, "Transición listo → entregado: OK");

    // Verificar historial completo
    $histFinal = $pedidoModel->obtenerHistorialEstados($pedidoId);
    // Debe tener: pendiente, en_preparacion, listo, entregado = 4 registros
    ok(count($histFinal) === 4, "Historial tiene 4 registros (todos los estados transitados)");

    // Verificar que todos los registros del historial tienen fecha
    $todosTienenFecha = true;
    foreach ($histFinal as $h) {
        if (empty($h['cambiado_en'])) { $todosTienenFecha = false; break; }
    }
    ok($todosTienenFecha, "Todos los registros del historial tienen 'cambiado_en'");

    // Intentar avanzar desde estado terminal 'entregado'
    $resTerminal = $pedidoModel->cambiarEstado($pedidoId, 'cancelado', $usuarioId);
    ok($resTerminal['ok'] === false, "Estado terminal 'entregado' no admite más transiciones");
}

// ═══════════════════════════════════════════════════════
//  SECCIÓN 4: RF-11 — Bloqueo de modificación y cancelación
// ═══════════════════════════════════════════════════════
seccion('RF-11 — Bloqueo de modificación y cancelación (solo en pendiente)');

// Crear un nuevo pedido para probar RF-11
$cabeceraRF11 = ['tipo' => 'llevar', 'mesa_id' => null, 'num_comensales' => 1];
$itemsRF11    = [['plato_id' => (int)$plato1['id'], 'cantidad' => 1]];

try {
    $pedidoRF11 = $pedidoModel->crear($cabeceraRF11, $itemsRF11, $usuarioId);
    ok($pedidoRF11 > 0, "Pedido de prueba RF-11 creado con ID: {$pedidoRF11}");

    // Avanzar a en_preparacion
    $pedidoModel->cambiarEstado($pedidoRF11, 'en_preparacion', $usuarioId);

    // Intentar modificar cuando ya está en_preparacion
    $resModif = $pedidoModel->actualizar(
        $pedidoRF11,
        ['tipo' => 'llevar', 'mesa_id' => null, 'num_comensales' => 2],
        [['plato_id' => (int)$plato1['id'], 'cantidad' => 3]]
    );
    ok($resModif['ok'] === false, "Modificación bloqueada en estado 'en_preparacion'");
    ok(str_contains($resModif['mensaje'], 'en_preparacion') || str_contains($resModif['mensaje'], 'pendiente'),
       "Mensaje de bloqueo contiene referencia al estado");

    // Intentar cancelar cuando ya está en_preparacion
    $resCancelar = $pedidoModel->cancelar($pedidoRF11, $usuarioId);
    ok($resCancelar['ok'] === false, "Cancelación bloqueada en estado 'en_preparacion'");

} catch (Throwable $e) {
    ok(false, "RF-11 — excepción inesperada: " . $e->getMessage());
}

// RF-11: Cancelación SÍ debe funcionar en 'pendiente'
$cabeceraCancel = ['tipo' => 'llevar', 'mesa_id' => null, 'num_comensales' => 1];
$itemsCancel    = [['plato_id' => (int)$plato1['id'], 'cantidad' => 1]];

try {
    $pedidoParaCancelar = $pedidoModel->crear($cabeceraCancel, $itemsCancel, $usuarioId);
    $resCancel = $pedidoModel->cancelar($pedidoParaCancelar, $usuarioId);
    ok($resCancel['ok'] === true, "Cancelación exitosa en estado 'pendiente'");
    $pedCancelado = $pedidoModel->buscarPorId($pedidoParaCancelar);
    ok($pedCancelado['estado'] === 'cancelado', "Pedido cancelado tiene estado 'cancelado' en BD");
} catch (Throwable $e) {
    ok(false, "RF-11 — cancelación pendiente — excepción: " . $e->getMessage());
}

// ═══════════════════════════════════════════════════════
//  SECCIÓN 5: RF-10 — Validación del endpoint JSON de cocina
// ═══════════════════════════════════════════════════════
seccion('RF-10 — Validación del método listarParaCocina()');

$pedidosCocina = $pedidoModel->listarParaCocina();
ok(is_array($pedidosCocina), 'listarParaCocina() devuelve un array');

// Verificar estructura de cada elemento
$estructuraOk = true;
foreach ($pedidosCocina as $pc) {
    if (!isset($pc['id'], $pc['estado'], $pc['tipo'], $pc['items'])) {
        $estructuraOk = false;
        break;
    }
    if (!is_array($pc['items'])) {
        $estructuraOk = false;
        break;
    }
}
ok($estructuraOk, 'Cada pedido de cocina tiene id, estado, tipo e items (array)');

// Verificar que solo trae estados activos
$estadosInvalidos = array_filter($pedidosCocina, fn($p) => $p['estado'] === 'cancelado');
ok(empty($estadosInvalidos), "El endpoint de cocina no incluye pedidos 'cancelado'");

// ═══════════════════════════════════════════════════════
//  SECCIÓN 6: RF-09 — Rechazo de platos inactivos
// ═══════════════════════════════════════════════════════
seccion('RF-09 — Rechazo de plato inactivo en nuevo pedido');

// Buscar o crear un plato inactivo temporalmente
$stmtPlatInactivo = $db->query("SELECT id, nombre FROM plato WHERE activo = false LIMIT 1;");
$platoInactivo = $stmtPlatInactivo->fetch(PDO::FETCH_ASSOC);

if ($platoInactivo) {
    $excepcionLanzada = false;
    try {
        $pedidoModel->crear(
            ['tipo' => 'llevar', 'mesa_id' => null, 'num_comensales' => 1],
            [['plato_id' => (int)$platoInactivo['id'], 'cantidad' => 1]],
            $usuarioId
        );
    } catch (Throwable $e) {
        $excepcionLanzada = str_contains($e->getMessage(), 'desactivado') || str_contains($e->getMessage(), 'inactivo');
    }
    ok($excepcionLanzada, "Pedido con plato inactivo '{$platoInactivo['nombre']}' rechazado con excepción");
} else {
    echo "  ⚠ Aviso: No hay platos inactivos para probar. Se omite esta prueba.\n";
}

// ── Resultado final ────────────────────────────────────────────────────────
echo "\n════════════════════════════════════════════════════════\n";
$total = $pasados + $fallidos;
echo "  Resultado:  {$pasados}/{$total} pruebas pasaron\n";

if ($fallidos > 0) {
    echo "  Fallidas:\n";
    foreach ($errores as $err) {
        echo "    • {$err}\n";
    }
    echo "════════════════════════════════════════════════════════\n";
    exit(1);
} else {
    echo "  🎉 ¡Todas las pruebas de la Fase 3 pasaron!\n";
    echo "════════════════════════════════════════════════════════\n";
    exit(0);
}
