<?php
declare(strict_types=1);

/**
 * Tests Fase 4 — Predicciones (P1, P4, P6)
 * Uso: php tests/fase4_predicciones_test.php
 */

require_once __DIR__ . '/../src/bootstrap.php';

use App\Models\PrediccionModel;

$pasados = 0;
$fallidos = 0;

function ok(bool $condicion, string $nombre): void
{
    global $pasados, $fallidos;
    if ($condicion) {
        echo "  ✅ PASS  {$nombre}\n";
        $pasados++;
    } else {
        echo "  ❌ FAIL  {$nombre}\n";
        $fallidos++;
    }
}

function seccion(string $titulo): void
{
    echo "\n── {$titulo} " . str_repeat('─', max(0, 55 - strlen($titulo))) . "\n";
}

echo "════════════════════════════════════════════════════════\n";
echo "  Tests Fase 4 — Predicciones (P1, P4, P6)\n";
echo "════════════════════════════════════════════════════════\n";

$model = new PrediccionModel();
$tz = new DateTimeZone('America/Guayaquil');
// Fecha objetivo: mañana
$fechaObjetivo = (new DateTimeImmutable('today', $tz))->modify('+1 day');

seccion('P1 — Pedidos esperados por franja horaria');
$p1 = $model->p1PedidosPorHora($fechaObjetivo);
ok(is_array($p1), 'p1PedidosPorHora devuelve un arreglo');
ok(isset($p1['datos']) && is_array($p1['datos']), 'Estructura incluye llave "datos"');
ok(count($p1['datos']) === 13, 'Cubre 13 franjas horarias (de 10:00 a 22:00)');
ok($p1['dias_incluidos'] >= 4, "Datos históricos suficientes ({$p1['dias_incluidos']} semanas encontradas)");

// Comprobar que horas pico (12-14 o 19-21) presentan promedios
$horas = array_column($p1['datos'], 'promedio', 'hora');
ok(($horas[12] ?? 0) > 0 || ($horas[13] ?? 0) > 0 || ($horas[14] ?? 0) > 0, 'Pico de almuerzo tiene promedios proyectados > 0');

seccion('P4 — Platos más demandados (unidades esperadas)');
$p4 = $model->p4PlatosMasVendidos($fechaObjetivo, 10);
ok(is_array($p4), 'p4PlatosMasVendidos devuelve un arreglo');
ok(isset($p4['datos']) && is_array($p4['datos']), 'Estructura incluye llave "datos"');
ok(count($p4['datos']) > 0 && count($p4['datos']) <= 10, 'Retorna top platos (hasta 10)');
$primerPlato = $p4['datos'][0] ?? [];
ok(isset($primerPlato['nombre'], $primerPlato['categoria'], $primerPlato['promedio_unidades']), 'Estructura de plato contiene nombre, categoría y promedio_unidades');
ok(($primerPlato['promedio_unidades'] ?? 0) > 0, 'El plato líder tiene proyección mayor a 0');

seccion('P6 — Ventas esperadas del día (USD)');
$p6 = $model->p6VentasEsperadas($fechaObjetivo);
ok(is_array($p6), 'p6VentasEsperadas devuelve un arreglo');
ok(isset($p6['promedio_venta'], $p6['maximo_historico'], $p6['minimo_historico']), 'Contiene métricas de proyección, máximo y mínimo');
ok($p6['promedio_venta'] > 0, "Venta promedio esperada mayor a $0 USD (Valor: \${$p6['promedio_venta']})");
ok($p6['maximo_historico'] >= $p6['promedio_venta'], 'El máximo histórico es mayor o igual al promedio');
ok(isset($p6['ultimos_7_dias']) && is_array($p6['ultimos_7_dias']), 'Incluye datos de los últimos 7 días para el sparkline');

echo "\n════════════════════════════════════════════════════════\n";
$total = $pasados + $fallidos;
echo "  Resultado:  {$pasados}/{$total} pruebas pasaron\n";
if ($fallidos > 0) {
    echo "  ❌ Hubo fallos en las pruebas de Fase 4.\n";
    exit(1);
} else {
    echo "  🎉 ¡Todas las pruebas de la Fase 4 pasaron!\n";
    echo "════════════════════════════════════════════════════════\n";
    exit(0);
}
