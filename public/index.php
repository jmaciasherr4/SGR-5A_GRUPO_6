<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\UsuarioController;
use App\Controllers\ClienteController;
use App\Controllers\MesaController;
use App\Controllers\MenuController;
use App\Controllers\PedidoController;
use App\Controllers\CocinaController;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Env;
use App\Core\Router;
use App\Core\View;

$router = new Router();

// ----------------------------------------------------------------------------
// RUTA RAÍZ
// ----------------------------------------------------------------------------
$router->get('/', function () {
    if (Auth::check()) {
        header('Location: ' . Auth::getHomeRoute());
    } else {
        header('Location: /login');
    }
    exit;
});

// ----------------------------------------------------------------------------
// AUTENTICACIÓN Y SESIÓN (RF-01, RF-02, RF-03)
// ----------------------------------------------------------------------------
$router->get('/login', [AuthController::class, 'mostrarLogin']);
$router->post('/login', [AuthController::class, 'procesarLogin']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/panel', [AuthController::class, 'panel']);

// ----------------------------------------------------------------------------
// MÓDULO DE USUARIOS (RF-02) - Solo Administrador
// ----------------------------------------------------------------------------
$router->get('/usuarios', [UsuarioController::class, 'index']);
$router->get('/usuarios/crear', [UsuarioController::class, 'crear']);
$router->post('/usuarios/guardar', [UsuarioController::class, 'guardar']);
$router->get('/usuarios/editar', [UsuarioController::class, 'editar']);
$router->post('/usuarios/actualizar', [UsuarioController::class, 'actualizar']);
$router->post('/usuarios/estado', [UsuarioController::class, 'cambiarEstado']);
$router->post('/usuarios/eliminar', [UsuarioController::class, 'eliminar']);

// ----------------------------------------------------------------------------
// MÓDULO DE CLIENTES (RF-04) - Administrador y Mesero
// ----------------------------------------------------------------------------
$router->get('/clientes', [ClienteController::class, 'index']);
$router->get('/clientes/crear', [ClienteController::class, 'crear']);
$router->post('/clientes/guardar', [ClienteController::class, 'guardar']);
$router->get('/clientes/editar', [ClienteController::class, 'editar']);
$router->post('/clientes/actualizar', [ClienteController::class, 'actualizar']);
$router->post('/clientes/eliminar', [ClienteController::class, 'eliminar']);

// ----------------------------------------------------------------------------
// MÓDULO DE MESAS (RF-07) - Administrador (CRUD) y Mesero (Ver/Estado)
// ----------------------------------------------------------------------------
$router->get('/mesas', [MesaController::class, 'index']);
$router->get('/mesas/crear', [MesaController::class, 'crear']);
$router->post('/mesas/guardar', [MesaController::class, 'guardar']);
$router->get('/mesas/editar', [MesaController::class, 'editar']);
$router->post('/mesas/actualizar', [MesaController::class, 'actualizar']);
$router->post('/mesas/estado', [MesaController::class, 'cambiarEstado']);
$router->post('/mesas/eliminar', [MesaController::class, 'eliminar']);

// ----------------------------------------------------------------------------
// MÓDULO DE MENÚ Y PRECIOS (RF-08) - Admin (CRUD), Mesero/Cocinero (Consulta)
// ----------------------------------------------------------------------------
$router->get('/menu', [MenuController::class, 'index']);
$router->get('/menu/crear', [MenuController::class, 'crearPlato']);
$router->post('/menu/guardar', [MenuController::class, 'guardarPlato']);
$router->get('/menu/editar', [MenuController::class, 'editarPlato']);
$router->post('/menu/actualizar', [MenuController::class, 'actualizarPlato']);
$router->post('/menu/estado', [MenuController::class, 'cambiarEstadoPlato']);
$router->post('/menu/eliminar', [MenuController::class, 'eliminarPlato']);
$router->get('/menu/categorias', [MenuController::class, 'categorias']);
$router->post('/menu/categorias/guardar', [MenuController::class, 'guardarCategoria']);
$router->post('/menu/categorias/eliminar', [MenuController::class, 'eliminarCategoria']);

// ----------------------------------------------------------------------------
// MÓDULO DE PEDIDOS (RF-09, RF-11) - Administrador y Mesero
// ----------------------------------------------------------------------------
$router->get('/pedidos',           [PedidoController::class, 'index']);
$router->get('/pedidos/crear',     [PedidoController::class, 'crear']);
$router->post('/pedidos/guardar',  [PedidoController::class, 'guardar']);
$router->get('/pedidos/ver',       [PedidoController::class, 'ver']);
$router->get('/pedidos/editar',    [PedidoController::class, 'editar']);
$router->post('/pedidos/actualizar', [PedidoController::class, 'actualizar']);
$router->post('/pedidos/cancelar', [PedidoController::class, 'cancelar']);

// ----------------------------------------------------------------------------
// MÓDULO DE COCINA (RF-10) - Administrador y Cocinero
// ----------------------------------------------------------------------------
$router->get('/cocina',               [CocinaController::class, 'index']);
$router->get('/cocina/pedidos-json',  [CocinaController::class, 'pedidosJson']);
$router->post('/cocina/estado',       [CocinaController::class, 'avanzarEstado']);

$router->get('/predicciones', function () {
    Auth::requireRole('administrador');
    View::render('placeholders/modulo', [
        'titulo'       => 'Predicciones Analíticas',
        'moduloNombre' => 'Predicciones',
        'fase'         => 'Fase 4',
        'descripcion'  => 'Las proyecciones analíticas serán implementadas en la Fase 4 (P1, P4, P6 con SQL parametrizado y gráficos).'
    ]);
});

// ----------------------------------------------------------------------------
// RUTA GET /salud (Fase 0)
// ----------------------------------------------------------------------------
$router->get('/salud', function () {
    $dbOk = false;
    $pgVersion = 'No disponible';
    $totalPlatos = 0;
    $errorMessage = null;
    $dbHost = Env::get('DB_HOST', '127.0.0.1');
    $dbPort = Env::get('DB_PORT', 5434);
    $dbName = Env::get('DB_DATABASE', 'restaurante');

    try {
        $pdo = Database::getConnection();
        
        $stmtVersion = $pdo->query('SELECT version() AS version;');
        $rowVersion = $stmtVersion->fetch();
        $pgVersion = $rowVersion['version'] ?? 'Desconocida';

        $stmtPlatos = $pdo->query('SELECT COUNT(*) AS total FROM plato;');
        $rowPlatos = $stmtPlatos->fetch();
        $totalPlatos = (int) ($rowPlatos['total'] ?? 0);

        $dbOk = true;
    } catch (\Throwable $e) {
        $errorMessage = $e->getMessage();
    }

    $escapedVersion = htmlspecialchars($pgVersion, ENT_QUOTES, 'UTF-8');
    $escapedError = htmlspecialchars($errorMessage ?? '', ENT_QUOTES, 'UTF-8');
    $escapedDbHost = htmlspecialchars((string)$dbHost, ENT_QUOTES, 'UTF-8');
    $escapedDbPort = htmlspecialchars((string)$dbPort, ENT_QUOTES, 'UTF-8');
    $escapedDbName = htmlspecialchars((string)$dbName, ENT_QUOTES, 'UTF-8');
    $horaServidor = date('Y-m-d H:i:s (T)');

    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salud del Sistema - Gestión de Restaurante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4 py-md-5" style="max-width: 900px;">
        <header class="pb-3 mb-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center text-dark text-decoration-none">
                <span class="fs-4 fw-bold">🍽️ Sistema de Gestión de Restaurante</span>
            </div>
            <div class="d-flex gap-2">
                <a href="/login" class="btn btn-outline-primary btn-sm px-3">Ir al Login</a>
                <span class="badge bg-secondary fs-6 py-2 px-3">Fase 1: Seguridad</span>
            </div>
        </header>

        <main>
            <?php if ($dbOk): ?>
                <div class="alert alert-success d-flex align-items-center p-3 mb-4 rounded-3 shadow-sm" role="alert">
                    <div class="fs-2 me-3">✅</div>
                    <div>
                        <h4 class="alert-heading mb-1 fw-bold">Conexión a PostgreSQL Exitosa</h4>
                        <p class="mb-0">El sistema se comunicó correctamente con el contenedor PostgreSQL en el puerto <code><?= $escapedDbPort ?></code>.</p>
                    </div>
                </div>

                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-bold text-primary">Información de Verificación (GET /salud)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 35%;" class="bg-light">Estado de la Base de Datos</th>
                                        <td><span class="badge bg-success py-2 px-3">OPERACIONAL</span></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Versión del Motor PostgreSQL</th>
                                        <td><small class="text-break"><code><?= $escapedVersion ?></code></small></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Total de Platos Sembrados</th>
                                        <td><span class="fw-bold fs-5 text-primary"><?= $totalPlatos ?></span> platos registrados</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Parámetros de Conexión Activos</th>
                                        <td><code>Host: <?= $escapedDbHost ?> | Puerto: <?= $escapedDbPort ?> | BD: <?= $escapedDbName ?></code></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Zona Horaria y Hora Local</th>
                                        <td><code>America/Guayaquil</code> &mdash; <?= $horaServidor ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">PHP y Extensión PDO</th>
                                        <td>PHP <?= PHP_VERSION ?> con driver nativo <code>pdo_pgsql</code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger p-4 rounded-3 shadow-sm" role="alert">
                    <h4 class="alert-heading fw-bold mb-2">❌ Error de Conexión a PostgreSQL</h4>
                    <p class="mb-2">No se pudo establecer la conexión con la base de datos en <code><?= $escapedDbHost ?>:<?= $escapedDbPort ?></code>.</p>
                    <hr>
                    <p class="mb-0 text-muted"><small>Detalle: <?= $escapedError ?></small></p>
                </div>
            <?php endif; ?>
        </main>

        <footer class="pt-4 my-md-5 pt-md-5 border-top text-muted small text-center">
            Sistema de Gestión de Restaurante &bull; UTEQ &bull; Administración de Bases de Datos
        </footer>
    </div>
</body>
</html>
    <?php
});

// Despachar la petición HTTP
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$router->dispatch($method, $uri);
