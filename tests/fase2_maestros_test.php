<?php
declare(strict_types=1);

/**
 * Suite de pruebas automatizadas para la Fase 2: Maestros
 * RF-04 (Clientes), RF-07 (Mesas) y RF-08 (Menú y Precios)
 * Ejecución: php tests/fase2_maestros_test.php
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\ClienteModel;
use App\Models\MesaModel;
use App\Models\MenuModel;
use App\Core\Validator;

function testHeader(string $title): void {
    echo PHP_EOL . "============================================================" . PHP_EOL;
    echo "  " . $title . PHP_EOL;
    echo "============================================================" . PHP_EOL;
}

function testResult(string $testName, bool $passed, string $details = ''): void {
    $tag = $passed ? "[OK]  " : "[FAIL]";
    echo sprintf("%s %s%s", $tag, $testName, $details ? " -> {$details}" : "") . PHP_EOL;
}

testHeader("PRUEBAS DE LA FASE 2: MAESTROS (CLIENTES, MESAS, MENÚ)");

$allPassed = true;
$clienteModel = new ClienteModel();
$mesaModel = new MesaModel();
$menuModel = new MenuModel();

// ============================================================================
// 1. PRUEBAS RF-04: CLIENTES
// ============================================================================

// Prueba 1.1: Validación de documento de identidad (10 dígitos numéricos)
$vCedulaCorta = new Validator(['documento' => '12345']);
$vCedulaCorta->document('documento', 'Cédula');
$ok1_1 = (!$vCedulaCorta->passes() && str_contains($vCedulaCorta->firstError() ?? '', '10 dígitos'));
testResult("RF-04: Rechazo de cédula inválida (< 10 dígitos)", $ok1_1, $vCedulaCorta->firstError() ?? '');
if (!$ok1_1) $allPassed = false;

// Prueba 1.2: Creación de cliente válido
$docTest = '0987654329';
// Limpiar si existía
$clientesExistentes = $clienteModel->listar($docTest);
foreach ($clientesExistentes as $ce) {
    if ($ce['documento'] === $docTest) $clienteModel->eliminar((int)$ce['id']);
}

$idNuevoCliente = $clienteModel->crear([
    'nombres'   => 'Cliente',
    'apellidos' => 'Prueba Unitaria',
    'documento' => $docTest,
    'telefono'  => '0981122334'
]);
$clienteCreado = $clienteModel->buscarPorId($idNuevoCliente);
$ok1_2 = ($clienteCreado !== null && $clienteCreado['documento'] === $docTest);
testResult("RF-04: Creación de cliente válido con cédula de 10 dígitos", $ok1_2, "ID generado: {$idNuevoCliente}");
if (!$ok1_2) $allPassed = false;

// Prueba 1.3: Rechazo de documento duplicado
$docExiste = $clienteModel->existeDocumento($docTest);
testResult("RF-04: Detección de cédula duplicada", $docExiste, "Documento {$docTest} detectado como existente.");
if (!$docExiste) $allPassed = false;

// Prueba 1.4: Búsqueda de clientes por término (nombre o cédula)
$resultadoBusqueda = $clienteModel->listar('Prueba Unitaria');
$ok1_4 = (count($resultadoBusqueda) >= 1 && $resultadoBusqueda[0]['documento'] === $docTest);
testResult("RF-04: Búsqueda de clientes por nombre/cédula", $ok1_4, "Encontrados: " . count($resultadoBusqueda));
if (!$ok1_4) $allPassed = false;

// Prueba 1.5: Actualización de datos del cliente
$clienteModel->actualizar($idNuevoCliente, [
    'nombres'   => 'Cliente Modificado',
    'apellidos' => 'Prueba Unitaria Actualizada',
    'documento' => $docTest,
    'telefono'  => '0999999999'
]);
$clienteActualizado = $clienteModel->buscarPorId($idNuevoCliente);
$ok1_5 = ($clienteActualizado['nombres'] === 'Cliente Modificado');
testResult("RF-04: Actualización de datos del cliente", $ok1_5, "Nombre actualizado correctamente.");
if (!$ok1_5) $allPassed = false;

// Prueba 1.6: Eliminación de cliente sin historial
$resEliminarCliente = $clienteModel->eliminar($idNuevoCliente);
$ok1_6 = ($resEliminarCliente['ok'] === true && $clienteModel->buscarPorId($idNuevoCliente) === null);
testResult("RF-04: Eliminación física de cliente sin historial", $ok1_6, "Cliente eliminado exitosamente.");
if (!$ok1_6) $allPassed = false;

// ============================================================================
// 2. PRUEBAS RF-07: MESAS Y CONTROL DE ESTADOS
// ============================================================================

// Prueba 2.1: Validación de capacidad > 0
$vCapacidadInvalida = new Validator(['capacidad' => '0', 'numero' => '99']);
$vCapacidadInvalida->custom('capacidad', fn($c) => (int)$c > 0, 'La capacidad debe ser mayor a 0');
$ok2_1 = (!$vCapacidadInvalida->passes());
testResult("RF-07: Rechazo de mesa con capacidad <= 0", $ok2_1, "Validación servidor detectó capacidad 0.");
if (!$ok2_1) $allPassed = false;

// Prueba 2.2: Creación de mesa con número único
$numeroMesaTest = 88;
// Limpiar si existía
$mesaExistente = $mesaModel->buscarPorNumero($numeroMesaTest);
if ($mesaExistente) $mesaModel->eliminar((int)$mesaExistente['id']);

$idNuevaMesa = $mesaModel->crear([
    'numero'    => $numeroMesaTest,
    'capacidad' => 6,
    'zona'      => 'Terraza Alta',
    'estado'    => 'libre'
]);
$mesaCreada = $mesaModel->buscarPorId($idNuevaMesa);
$ok2_2 = ($mesaCreada !== null && (int)$mesaCreada['numero'] === $numeroMesaTest);
testResult("RF-07: Creación de mesa con número único y capacidad > 0", $ok2_2, "Mesa #{$numeroMesaTest} creada.");
if (!$ok2_2) $allPassed = false;

// Prueba 2.3: Rechazo de número de mesa duplicado
$existeNum = $mesaModel->existeNumero($numeroMesaTest);
testResult("RF-07: Validación de unicidad de número de mesa", $existeNum, "Número #{$numeroMesaTest} detectado duplicado.");
if (!$existeNum) $allPassed = false;

// Prueba 2.4: Cambio rápido de estado (libre -> ocupada -> reservada)
$mesaModel->cambiarEstado($idNuevaMesa, 'ocupada');
$mOcupada = $mesaModel->buscarPorId($idNuevaMesa);
$ok2_4a = ($mOcupada['estado'] === 'ocupada');

$mesaModel->cambiarEstado($idNuevaMesa, 'reservada');
$mReservada = $mesaModel->buscarPorId($idNuevaMesa);
$ok2_4b = ($mReservada['estado'] === 'reservada');

$mesaModel->cambiarEstado($idNuevaMesa, 'libre');
$mLibre = $mesaModel->buscarPorId($idNuevaMesa);
$ok2_4c = ($mLibre['estado'] === 'libre');

$ok2_4 = ($ok2_4a && $ok2_4b && $ok2_4c);
testResult("RF-07: Cambio rápido de estado (libre/ocupada/reservada)", $ok2_4, "Estados transitados y guardados de inmediato.");
if (!$ok2_4) $allPassed = false;

// Prueba 2.5: Eliminación de mesa temporal sin historial
$resElimMesa = $mesaModel->eliminar($idNuevaMesa);
$ok2_5 = ($resElimMesa['ok'] === true);
testResult("RF-07: Eliminación de mesa sin reservas ni pedidos", $ok2_5, "Mesa eliminada correctamente.");
if (!$ok2_5) $allPassed = false;

// Prueba 2.6: Control de integridad referencial (Mesa #1 con pedidos históricos)
$resElimMesaConHistorial = $mesaModel->eliminar(1);
$ok2_6 = ($resElimMesaConHistorial['ok'] === false && str_contains($resElimMesaConHistorial['mensaje'], 'asociadas'));
testResult("RF-07: Bloqueo referencial de mesa con pedidos históricos", $ok2_6, $resElimMesaConHistorial['mensaje']);
if (!$ok2_6) $allPassed = false;

// ============================================================================
// 3. PRUEBAS RF-08: MENÚ Y PRECIOS
// ============================================================================

// Prueba 3.1: Validación de precio > 0
$vPrecioCero = new Validator(['precio' => '0.00']);
$vPrecioCero->custom('precio', fn($p) => is_numeric($p) && (float)$p > 0, 'Precio debe ser > 0');
$ok3_1 = (!$vPrecioCero->passes());
testResult("RF-08: Rechazo de plato con precio <= 0", $ok3_1, "Detectado precio inválido en servidor.");
if (!$ok3_1) $allPassed = false;

// Prueba 3.2: Creación de plato válido
$idNuevoPlato = $menuModel->crearPlato([
    'categoria_id' => 1, // Entradas
    'nombre'       => 'Plato de Prueba Temporal',
    'descripcion'  => 'Deliciosa prueba unitaria',
    'precio'       => 4.75,
    'activo'       => true
]);
$platoCreado = $menuModel->buscarPlatoPorId($idNuevoPlato);
$ok3_2 = ($platoCreado !== null && (float)$platoCreado['precio'] === 4.75 && $platoCreado['activo'] === true);
testResult("RF-08: Creación de plato con categoría y precio > 0", $ok3_2, "Plato ID {$idNuevoPlato} a $4.75.");
if (!$ok3_2) $allPassed = false;

// Prueba 3.3: Baja lógica de plato (desactivar)
$menuModel->cambiarEstadoPlato($idNuevoPlato, false);
$platoInactivo = $menuModel->buscarPlatoPorId($idNuevoPlato);
$ok3_3a = ($platoInactivo['activo'] === false);

// Comprobar que en listado de 'solo activos' NO aparece
$platosSoloActivos = $menuModel->listarPlatos(null, 'Plato de Prueba Temporal', true);
$ok3_3b = (count($platosSoloActivos) === 0);

// Comprobar que en listado general SÍ se conserva
$platosTodos = $menuModel->listarPlatos(null, 'Plato de Prueba Temporal', null);
$ok3_3c = (count($platosTodos) === 1);

$ok3_3 = ($ok3_3a && $ok3_3b && $ok3_3c);
testResult("RF-08: Baja lógica (plato desactivado no aparece en pedidos)", $ok3_3, "Desactivado pero preservado en histórico.");
if (!$ok3_3) $allPassed = false;

// Prueba 3.4: Eliminación física de plato temporal sin pedidos
$resElimPlato = $menuModel->eliminarPlato($idNuevoPlato);
$ok3_4 = ($resElimPlato['ok'] === true);
testResult("RF-08: Eliminación física de plato sin pedidos previos", $ok3_4, "Plato eliminado correctamente.");
if (!$ok3_4) $allPassed = false;

// Prueba 3.5: Bloqueo de eliminación para plato con pedidos históricos (Plato #2: Ceviche de Camarón)
$resElimPlatoHistorial = $menuModel->eliminarPlato(2);
$ok3_5 = ($resElimPlatoHistorial['ok'] === false && str_contains($resElimPlatoHistorial['mensaje'], 'Desactivar'));
testResult("RF-08: Bloqueo de eliminación de plato con historial (sugerir Desactivar)", $ok3_5, $resElimPlatoHistorial['mensaje']);
if (!$ok3_5) $allPassed = false;

testHeader("RESUMEN DE PRUEBAS DE LA FASE 2");
if ($allPassed) {
    echo "  RESULTADO: TODAS LAS PRUEBAS DE MAESTROS PASARON CON ÉXITO (FASE 2 COMPLETADA)  " . PHP_EOL;
} else {
    echo "  RESULTADO: SE ENCONTRARON FALLOS EN LA FASE 2. REVISE EL LOG.       " . PHP_EOL;
}
echo "============================================================" . PHP_EOL;

exit($allPassed ? 0 : 1);
