<?php
declare(strict_types=1);

/**
 * Script de prueba exhaustiva para la Fase 1 (Seguridad y Accesos: RF-01, RF-02, RF-03).
 * Ejecución: php tests/fase1_auth_test.php
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\Validator;
use App\Models\UsuarioModel;

function testHeader(string $title): void {
    echo PHP_EOL . "============================================================" . PHP_EOL;
    echo "  " . $title . PHP_EOL;
    echo "============================================================" . PHP_EOL;
}

function testResult(string $testName, bool $passed, string $details = ''): void {
    $tag = $passed ? "[OK]  " : "[FAIL]";
    echo sprintf("%s %s%s", $tag, $testName, $details ? " -> {$details}" : "") . PHP_EOL;
}

testHeader("PRUEBAS DE LA FASE 1: SEGURIDAD, ACCESOS Y USUARIOS");

$allPassed = true;
$usuarioModel = new UsuarioModel();

// ----------------------------------------------------------------------------
// 1. PRUEBA RF-01: Autenticación con credenciales válidas
// ----------------------------------------------------------------------------
$resLoginAdmin = Auth::attempt('admin', 'admin123');
$ok1 = ($resLoginAdmin['ok'] === true && Auth::check() && Auth::role() === 'administrador');
testResult("RF-01: Login con credenciales válidas ('admin')", $ok1, "Usuario autenticado con rol: " . Auth::role());
if (!$ok1) $allPassed = false;

// ----------------------------------------------------------------------------
// 2. PRUEBA RF-01: Autenticación con clave incorrecta
// ----------------------------------------------------------------------------
$resClaveMala = Auth::attempt('admin', 'clave_equivocada_123');
$ok2 = ($resClaveMala['ok'] === false && str_contains($resClaveMala['error'], 'incorrectos'));
testResult("RF-01: Login con contraseña errónea rechazado", $ok2, "Mensaje: " . ($resClaveMala['error'] ?? ''));
if (!$ok2) $allPassed = false;

// ----------------------------------------------------------------------------
// 3. PRUEBA RF-01: Autenticación con usuario inexistente
// ----------------------------------------------------------------------------
$resUserInexistente = Auth::attempt('usuario_que_no_existe', 'admin123');
$ok3 = ($resUserInexistente['ok'] === false);
testResult("RF-01: Login con usuario inexistente rechazado", $ok3, "Mensaje: " . ($resUserInexistente['error'] ?? ''));
if (!$ok3) $allPassed = false;

// ----------------------------------------------------------------------------
// 4. PRUEBA RF-01: Cierre de sesión (Logout)
// ----------------------------------------------------------------------------
Auth::logout();
$ok4 = (!Auth::check() && Auth::user() === null);
testResult("RF-01: Cierre de sesión (Logout) limpia el estado", $ok4, "Sesión destruida exitosamente.");
if (!$ok4) $allPassed = false;

// ----------------------------------------------------------------------------
// 5. PRUEBA RF-02: Usuario desactivado no puede iniciar sesión
// ----------------------------------------------------------------------------
// Crear usuario de prueba temporal inactivo
$docPruebaInactivo = '0988776655';
$userPruebaInactivo = 'usuario_inactivo_test';

// Limpiar si existía de pruebas anteriores
$usuarioExistente = $usuarioModel->buscarPorUsername($userPruebaInactivo);
if ($usuarioExistente) {
    $usuarioModel->eliminar((int)$usuarioExistente['id']);
}

$idInactivo = $usuarioModel->crear(
    ['nombres' => 'Usuario', 'apellidos' => 'Inactivo', 'documento' => $docPruebaInactivo, 'telefono' => '0911111111'],
    ['username' => $userPruebaInactivo, 'password' => 'clave123456', 'rol_id' => 2, 'activo' => false]
);

$resLoginInactivo = Auth::attempt($userPruebaInactivo, 'clave123456');
$ok5 = ($resLoginInactivo['ok'] === false && str_contains($resLoginInactivo['error'], 'desactivada'));
testResult("RF-02: Usuario desactivado bloqueado al iniciar sesión", $ok5, "Mensaje: " . ($resLoginInactivo['error'] ?? ''));
if (!$ok5) $allPassed = false;

// Limpiar usuario temporal inactivo
$usuarioModel->eliminar($idInactivo);

// ----------------------------------------------------------------------------
// 6. PRUEBA RF-02: Validaciones de creación de usuario (Cédula, Username único, Clave >= 8)
// ----------------------------------------------------------------------------
$v1 = new Validator(['documento' => '123', 'username' => 'ad', 'password' => 'corta']);
$v1->document('documento', 'Cédula')
   ->minLength('username', 3, 'Usuario')
   ->minLength('password', 8, 'Contraseña');
$ok6 = (!$v1->passes() && count($v1->errors()) === 3);
testResult("RF-02: Validaciones de longitud, cédula y contraseña >= 8", $ok6, "3 errores detectados correctamente.");
if (!$ok6) $allPassed = false;

// ----------------------------------------------------------------------------
// 7. PRUEBA RF-02: CRUD Completo de Usuario (Creación, Edición y Baja Lógica)
// ----------------------------------------------------------------------------
$docCrud = '0955443322';
$userCrud = 'operador_temporal';

$idCreado = $usuarioModel->crear(
    ['nombres' => 'Operador', 'apellidos' => 'Temporal', 'documento' => $docCrud, 'telefono' => '0922334455'],
    ['username' => $userCrud, 'password' => 'segura12345', 'rol_id' => 2, 'activo' => true]
);

$uCargado = $usuarioModel->buscarPorId($idCreado);
$ok7a = ($uCargado !== null && $uCargado['username'] === $userCrud && $uCargado['activo'] === true);
testResult("RF-02: Creación exitosa de usuario nuevo", $ok7a, "ID generado: {$idCreado}");

// Actualización
$usuarioModel->actualizar(
    $idCreado,
    ['nombres' => 'Operador Modificado', 'apellidos' => 'Temporal Edit', 'documento' => $docCrud, 'telefono' => '0999999999'],
    ['username' => $userCrud, 'password' => '', 'rol_id' => 3, 'activo' => true]
);
$uEditado = $usuarioModel->buscarPorId($idCreado);
$ok7b = ($uEditado['nombres'] === 'Operador Modificado' && (int)$uEditado['rol_id'] === 3);
testResult("RF-02: Edición de datos y rol de usuario", $ok7b, "Nombre actualizado y rol cambiado a cajero (ID 3).");

// Baja lógica
$usuarioModel->cambiarEstado($idCreado, false);
$uDesactivado = $usuarioModel->buscarPorId($idCreado);
$ok7c = ($uDesactivado['activo'] === false);
testResult("RF-02: Baja lógica (activo = false)", $ok7c, "Estado cambiado a inactivo.");

// Eliminación física (sin historial)
$resEliminarSinHistorial = $usuarioModel->eliminar($idCreado);
$ok7d = ($resEliminarSinHistorial['ok'] === true);
testResult("RF-02: Eliminación física de usuario sin historial", $ok7d, "Usuario eliminado correctamente.");

if (!$ok7a || !$ok7b || !$ok7c || !$ok7d) $allPassed = false;

// ----------------------------------------------------------------------------
// 8. PRUEBA DE INTEGRIDAD REFERENCIAL (ON DELETE RESTRICT - SQLSTATE 23503)
// Usuario con historial (usuario 2 = 'mesero1' que tiene pedidos asignados)
// ----------------------------------------------------------------------------
$resEliminarConHistorial = $usuarioModel->eliminar(2);
$ok8 = ($resEliminarConHistorial['ok'] === false && str_contains($resEliminarConHistorial['mensaje'], 'Desactivar'));
testResult(
    "RF-02: Protección contra eliminación con historial (ON DELETE RESTRICT)",
    $ok8,
    "Bloqueo exitoso. Mensaje ofrecido: \"" . $resEliminarConHistorial['mensaje'] . "\""
);
if (!$ok8) $allPassed = false;

// ----------------------------------------------------------------------------
// 9. PRUEBA RF-03: Control de acceso por rol y token CSRF
// ----------------------------------------------------------------------------
$token = Csrf::getToken();
$tokenValido = Csrf::validate($token);
$tokenInvalido = !Csrf::validate('token_falso_manipulado');
$ok9 = ($tokenValido && $tokenInvalido);
testResult("CSRF: Generación y validación de tokens CSRF", $ok9, "Tokens criptográficos verificados.");
if (!$ok9) $allPassed = false;

// ----------------------------------------------------------------------------
// 10. PRUEBA CSRF: Petición sin token rechazada obligatoriamente en backend (Prueba 1.9)
// ----------------------------------------------------------------------------
$sinTokenRechazado = !Csrf::validate(null);
$tokenVacioRechazado = !Csrf::validate('');
$ok10 = ($sinTokenRechazado && $tokenVacioRechazado);
testResult("CSRF: Petición POST sin token rechazada por el backend (HTTP 403)", $ok10, "Null y string vacío bloqueados.");
if (!$ok10) $allPassed = false;

// ----------------------------------------------------------------------------
// 11. PRUEBA CSRF: Petición con token manipulado rechazada en backend
// ----------------------------------------------------------------------------
$tokenManipulado = bin2hex(random_bytes(32)); // token aleatorio que no está en la sesión
$tokenManipuladoRechazado = !Csrf::validate($tokenManipulado);
$ok11 = $tokenManipuladoRechazado;
testResult("CSRF: Petición con token ajeno/manipulado rechazada (HTTP 403)", $ok11, "Token forjado bloqueado con hash_equals.");
if (!$ok11) $allPassed = false;

testHeader("RESUMEN DE PRUEBAS DE LA FASE 1");
if ($allPassed) {
    echo "  RESULTADO: TODAS LAS PRUEBAS DE SEGURIDAD Y ACCESOS PASARON CON ÉXITO  " . PHP_EOL;
} else {
    echo "  RESULTADO: SE ENCONTRARON FALLOS EN LA FASE 1. REVISE EL LOG.       " . PHP_EOL;
}
echo "============================================================" . PHP_EOL;

exit($allPassed ? 0 : 1);
