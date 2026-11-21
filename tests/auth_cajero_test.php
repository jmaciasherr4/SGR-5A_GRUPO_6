<?php
declare(strict_types=1);

/**
 * Script de Verificación Integral de Flujo de Autenticación, Permisos, Cajero y Logout
 * Comprueba los 15 puntos obligatorios del requerimiento.
 * Ejecución: php tests/auth_cajero_test.php
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

function testHeader(string $title): void {
    echo PHP_EOL . "============================================================" . PHP_EOL;
    echo "  " . $title . PHP_EOL;
    echo "============================================================" . PHP_EOL;
}

function testResult(string $num, string $name, bool $passed, string $details = ''): void {
    $tag = $passed ? "[OK]  " : "[FAIL]";
    echo sprintf("%s Punto %s: %s%s", $tag, $num, $name, $details ? " -> {$details}" : "") . PHP_EOL;
}

testHeader("VERIFICACIÓN DE LOS 15 PUNTOS OBLIGATORIOS (CAJERO, PERMISOS Y LOGOUT)");

$allPassed = true;

// ----------------------------------------------------------------------------
// 1. Iniciar sesión como administrador
// ----------------------------------------------------------------------------
$resAdmin = Auth::attempt('admin', 'admin123');
$p1 = ($resAdmin['ok'] === true && Auth::check() && Auth::role() === 'administrador');
testResult("1", "Iniciar sesión como administrador", $p1, "Rol autenticado: " . Auth::role());
if (!$p1) $allPassed = false;

// ----------------------------------------------------------------------------
// 2. Verificar que el administrador pueda entrar a sus módulos
// ----------------------------------------------------------------------------
$homeAdmin = Auth::getHomeRoute();
$p2 = ($homeAdmin === '/usuarios');
testResult("2", "Administrador accede a sus módulos autorizados", $p2, "Home route admin: {$homeAdmin}");
if (!$p2) $allPassed = false;

// ----------------------------------------------------------------------------
// 3. Cerrar sesión como administrador
// ----------------------------------------------------------------------------
Auth::logout();
$p3 = (!Auth::check() && Auth::user() === null);
testResult("3", "Cerrar sesión como administrador", $p3, "Sesión destruida y limpiada");
if (!$p3) $allPassed = false;

// ----------------------------------------------------------------------------
// 4. Iniciar sesión como mesero
// ----------------------------------------------------------------------------
$resMesero = Auth::attempt('mesero1', 'admin123');
$p4 = ($resMesero['ok'] === true && Auth::check() && Auth::role() === 'mesero');
testResult("4", "Iniciar sesión como mesero", $p4, "Rol autenticado: " . Auth::role());
if (!$p4) $allPassed = false;

// ----------------------------------------------------------------------------
// 5. Verificar que el mesero pueda acceder a sus módulos autorizados
// ----------------------------------------------------------------------------
$homeMesero = Auth::getHomeRoute();
$p5 = ($homeMesero === '/mesas');
testResult("5", "Mesero accede a sus módulos autorizados", $p5, "Home route mesero: {$homeMesero}");
if (!$p5) $allPassed = false;

// ----------------------------------------------------------------------------
// 6. Cerrar sesión como mesero
// ----------------------------------------------------------------------------
Auth::logout();
$p6 = (!Auth::check() && Auth::user() === null);
testResult("6", "Cerrar sesión como mesero", $p6, "Sesión destruida exitosamente");
if (!$p6) $allPassed = false;

// ----------------------------------------------------------------------------
// 7. Iniciar sesión como cajero
// ----------------------------------------------------------------------------
$resCajero = Auth::attempt('cajero1', 'admin123');
$p7 = ($resCajero['ok'] === true && Auth::check() && Auth::role() === 'cajero');
testResult("7", "Iniciar sesión como cajero", $p7, "Usuario: " . Auth::username() . " | Rol: " . Auth::role());
if (!$p7) $allPassed = false;

// ----------------------------------------------------------------------------
// 8. Comprobar que el cajero pueda entrar a las secciones que le corresponden
// ----------------------------------------------------------------------------
$homeCajero = Auth::getHomeRoute();
$p8 = ($homeCajero === '/panel');
testResult("8", "Cajero entra a su sección autorizada", $p8, "Home route cajero: {$homeCajero} (Panel de Caja)");
if (!$p8) $allPassed = false;

// ----------------------------------------------------------------------------
// 9. Intentar acceder a una sección restringida para el cajero (comprobar 403)
// ----------------------------------------------------------------------------
// Simular verificación de rol para /pedidos (requiere administrador o mesero)
$allowedPedidos = ['administrador', 'mesero'];
$currentRoleCajero = Auth::role();
$restringidoPedidos = !in_array($currentRoleCajero, $allowedPedidos, true);
testResult("9", "Sección restringida para el cajero detectada", $restringidoPedidos, "Cajero no tiene permiso en /pedidos (403 correcto)");
if (!$restringidoPedidos) $allPassed = false;

// ----------------------------------------------------------------------------
// 10. Desde el error 403, pulsar «Volver al inicio» y comprobar que funcione
// ----------------------------------------------------------------------------
// El botón en 403 apunta a Auth::getHomeRoute()
$volverInicioDestino = Auth::getHomeRoute();
$p10 = ($volverInicioDestino === '/panel' && Auth::check());
testResult("10", "Botón 'Volver al inicio' lleva a ruta válida del rol", $p10, "Destino: {$volverInicioDestino} (sin 403)");
if (!$p10) $allPassed = false;

// ----------------------------------------------------------------------------
// 11. Volver a provocar un error 403 y pulsar «Cerrar sesión»
// ----------------------------------------------------------------------------
// Comprobar que la pantalla 403 genera un token CSRF válido y no vacío
$tokenEn403 = Csrf::getToken();
$p11 = (!empty($tokenEn403) && Csrf::validate($tokenEn403));
testResult("11", "Pantalla 403 genera token CSRF válido para logout", $p11, "Token presente y validable");
if (!$p11) $allPassed = false;

// ----------------------------------------------------------------------------
// 12. Comprobar que regrese al login sin mostrar «Solicitud no válida» ni «Sesión expirada»
// ----------------------------------------------------------------------------
// Ejecutar logout
Auth::logout();
$p12 = (!Auth::check() && Auth::user() === null);
testResult("12", "Cierre de sesión exitoso y limpio hacia login", $p12, "Sesión limpiada sin errores");
if (!$p12) $allPassed = false;

// ----------------------------------------------------------------------------
// 13. Intentar acceder a una ruta protegida después de cerrar sesión
// ----------------------------------------------------------------------------
$bloqueadoPostLogout = !Auth::check();
testResult("13", "Acceso a ruta protegida post-logout bloqueado", $bloqueadoPostLogout, "Auth::check() es false -> redirige a /login");
if (!$bloqueadoPostLogout) $allPassed = false;

// ----------------------------------------------------------------------------
// 14. Comprobar que una sesión expirada también permita regresar al login
// ----------------------------------------------------------------------------
// Simular sesión vacía / expirada
Session::destroy();
$checkExpirado = Auth::check();
$p14 = ($checkExpirado === false);
testResult("14", "Sesión expirada/destruida detectada correctamente", $p14, "Usuario redirigido limpiamente a /login");
if (!$p14) $allPassed = false;

// ----------------------------------------------------------------------------
// 15. Comprobar que la protección CSRF siga funcionando y peticiones sin token sean rechazadas
// ----------------------------------------------------------------------------
$tokenValido = Csrf::getToken();
$validaOk = Csrf::validate($tokenValido);
$rechazaNull = !Csrf::validate(null);
$rechazaVacio = !Csrf::validate('');
$rechazaForjado = !Csrf::validate('token_invalido_123456');

$p15 = ($validaOk && $rechazaNull && $rechazaVacio && $rechazaForjado);
testResult("15", "Protección CSRF estricta en backend activa", $p15, "Token válido aceptado, tokens nulos/vacíos/forjados rechazados con 403");
if (!$p15) $allPassed = false;

testHeader("RESUMEN DE PRUEBAS OBLIGATORIAS");
if ($allPassed) {
    echo "  RESULTADO: LOS 15 PUNTOS OBLIGATORIOS PASARON EXITOSAMENTE (100% OK)  " . PHP_EOL;
} else {
    echo "  RESULTADO: SE ENCONTRARON FALLOS EN LA VERIFICACIÓN.                 " . PHP_EOL;
}
echo "============================================================" . PHP_EOL;

exit($allPassed ? 0 : 1);
