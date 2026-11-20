<?php
declare(strict_types=1);

/**
 * Script de prueba de humo (Smoke Test) para consola CLI.
 * Verifica conexión a PostgreSQL, datos base sembrados y seguridad contra inyección SQL.
 * Ejecución: php tests/smoke.php
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;

// Funciones auxiliares para formato en consola
function printHeader(string $title): void {
    echo PHP_EOL . "============================================================" . PHP_EOL;
    echo "  " . $title . PHP_EOL;
    echo "============================================================" . PHP_EOL;
}

function printResult(string $testName, bool $passed, string $details = ''): void {
    $tag = $passed ? "[OK]  " : "[FAIL]";
    echo sprintf("%s %s%s", $tag, $testName, $details ? " -> {$details}" : "") . PHP_EOL;
}

printHeader("PRUEBAS DE HUMO (SMOKE TEST) - FASE 0 RESTAURANTE");

$allPassed = true;

// ----------------------------------------------------------------------------
// PRUEBA 1: Conexión mediante PDO Singleton
// ----------------------------------------------------------------------------
$pdo = null;
try {
    $pdo = Database::getConnection();
    printResult("Conexión PDO a PostgreSQL", true, "Conectado exitosamente.");
} catch (\Throwable $e) {
    printResult("Conexión PDO a PostgreSQL", false, $e->getMessage());
    $allPassed = false;
    exit(1);
}

// ----------------------------------------------------------------------------
// PRUEBA 2: Consulta SELECT version()
// ----------------------------------------------------------------------------
try {
    $stmt = $pdo->query("SELECT version() AS version;");
    $row = $stmt->fetch();
    $version = $row['version'] ?? 'Desconocida';
    printResult("Consulta SELECT version()", true, substr($version, 0, 50) . "...");
} catch (\Throwable $e) {
    printResult("Consulta SELECT version()", false, $e->getMessage());
    $allPassed = false;
}

// ----------------------------------------------------------------------------
// PRUEBA 3: Conteo de platos del menú sembrados
// ----------------------------------------------------------------------------
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM plato;");
    $row = $stmt->fetch();
    $totalPlatos = (int) ($row['total'] ?? 0);
    $ok = ($totalPlatos >= 15);
    printResult("Conteo de platos del menú", $ok, "{$totalPlatos} platos encontrados (esperado: >= 15)");
    if (!$ok) $allPassed = false;
} catch (\Throwable $e) {
    printResult("Conteo de platos del menú", false, $e->getMessage());
    $allPassed = false;
}

// ----------------------------------------------------------------------------
// PRUEBA 4: Usuarios del sistema y verificación de roles
// ----------------------------------------------------------------------------
try {
    $stmt = $pdo->query("
        SELECT u.username, r.nombre AS rol, u.activo, u.password_hash 
        FROM usuario u 
        INNER JOIN rol r ON u.rol_id = r.id
        ORDER BY u.id
    ");
    $usuarios = $stmt->fetchAll();
    $ok = (count($usuarios) >= 4);
    printResult("Verificación de usuarios base", $ok, count($usuarios) . " usuarios encontrados (esperado: >= 4)");
    
    // Verificar que password_verify funcione con el hash bcrypt generado por pgcrypto
    $adminUser = null;
    foreach ($usuarios as $u) {
        if ($u['username'] === 'admin') {
            $adminUser = $u;
            break;
        }
    }
    
    $passwordCheck = false;
    if ($adminUser && password_verify('admin123', $adminUser['password_hash'])) {
        $passwordCheck = true;
    }
    printResult("Compatibilidad bcrypt (pgcrypto <-> PHP)", $passwordCheck, "Contraseña 'admin123' verificada correctamente.");
    if (!$ok || !$passwordCheck) $allPassed = false;
} catch (\Throwable $e) {
    printResult("Verificación de usuarios base", false, $e->getMessage());
    $allPassed = false;
}

// ----------------------------------------------------------------------------
// PRUEBA 5: Seguridad y Parametrización contra SQL Injection
// Inyección maliciosa clásica: "' OR '1'='1"
// Debe ser tratada como literal y NO debe devolver todas las filas de la tabla
// ----------------------------------------------------------------------------
try {
    $payloadInyeccion = "' OR '1'='1";
    $sql = "SELECT id, username FROM usuario WHERE username = :username;";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $payloadInyeccion]);
    $filas = $stmt->fetchAll();

    $seguro = (count($filas) === 0);
    printResult(
        "Consulta parametrizada inmune a SQL Injection",
        $seguro,
        "Payload: \"{$payloadInyeccion}\" devolvió " . count($filas) . " filas (esperado: 0 filas)."
    );
    if (!$seguro) $allPassed = false;
} catch (\Throwable $e) {
    printResult("Consulta parametrizada inmune a SQL Injection", false, $e->getMessage());
    $allPassed = false;
}

// ----------------------------------------------------------------------------
// PRUEBA 6: Integridad de datos históricos
// ----------------------------------------------------------------------------
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total_pedidos FROM pedido;");
    $totalPedidos = (int) $stmt->fetch()['total_pedidos'];

    $stmtFacturas = $pdo->query("SELECT COUNT(*) AS total_facturas FROM factura;");
    $totalFacturas = (int) $stmtFacturas->fetch()['total_facturas'];

    $ok = ($totalPedidos > 0 && $totalFacturas > 0);
    printResult("Datos históricos para predicciones", $ok, "{$totalPedidos} pedidos y {$totalFacturas} facturas generadas.");
    if (!$ok) $allPassed = false;
} catch (\Throwable $e) {
    printResult("Datos históricos para predicciones", false, $e->getMessage());
    $allPassed = false;
}

printHeader("RESUMEN DE PRUEBAS DE HUMO");
if ($allPassed) {
    echo "  RESULTADO: TODAS LAS PRUEBAS PASARON EXITOSAMENTE (FASE 0 COMPLETADA)  " . PHP_EOL;
} else {
    echo "  RESULTADO: HUBO FALLOS EN LAS PRUEBAS. REVISE EL LOG ANTERIOR.       " . PHP_EOL;
}
echo "============================================================" . PHP_EOL;

exit($allPassed ? 0 : 1);
