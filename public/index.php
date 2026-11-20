<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;
use App\Core\Router;
use App\Core\Env;

$router = new Router();

// Ruta raíz
$router->get('/', function () {
    header('Location: /salud');
    exit;
});

// Ruta GET /salud requerida en Fase 0
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
        
        // Consulta 1: Versión del motor PostgreSQL
        $stmtVersion = $pdo->query('SELECT version() AS version;');
        $rowVersion = $stmtVersion->fetch();
        $pgVersion = $rowVersion['version'] ?? 'Desconocida';

        // Consulta 2: Conteo de platos registrados en catálogo
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
    <!-- Bootstrap 5.3 por CDN según requerimiento -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4 py-md-5" style="max-width: 900px;">
        <header class="pb-3 mb-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center text-dark text-decoration-none">
                <span class="fs-4 fw-bold">🍽️ Sistema de Gestión de Restaurante</span>
            </div>
            <span class="badge bg-secondary fs-6 py-2 px-3">Fase 0: Entorno y Conexión</span>
        </header>

        <main>
            <?php if ($dbOk): ?>
                <div class="alert alert-success d-flex align-items-center p-3 mb-4 rounded-3 shadow-sm" role="alert">
                    <div class="fs-2 me-3">✅</div>
                    <div>
                        <h4 class="alert-heading mb-1 fw-bold">Conexión a PostgreSQL Exitosa</h4>
                        <p class="mb-0">El sistema se comunicó correctamente con el contenedor PostgreSQL a través de PDO (<code>pdo_pgsql</code>).</p>
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
                                        <th class="bg-light">Versión del Motor PostgreSQL (<code>SELECT version()</code>)</th>
                                        <td><small class="text-break"><code><?= $escapedVersion ?></code></small></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Total de Platos Sembrados (<code>SELECT COUNT(*) FROM plato</code>)</th>
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

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="card-title mb-0 fw-bold">Catálogo de Platos Cargados en Base de Datos</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmtLista = $pdo->query('
                            SELECT p.id, p.nombre, c.nombre AS categoria, p.precio, p.activo
                            FROM plato p
                            INNER JOIN categoria_menu c ON p.categoria_id = c.id
                            ORDER BY p.categoria_id, p.id
                        ');
                        $platos = $stmtLista->fetchAll();
                        ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Categoría</th>
                                        <th>Nombre del Plato</th>
                                        <th class="text-end">Precio ($ USD)</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($platos as $p): ?>
                                        <tr>
                                            <td><?= (int)$p['id'] ?></td>
                                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($p['categoria'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                            <td class="fw-semibold"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-end font-monospace">$<?= number_format((float)$p['precio'], 2) ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $p['activo'] ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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
                <div class="card p-3 shadow-sm">
                    <h6>Pasos de resolución:</h6>
                    <ol class="mb-0">
                        <li>Verifica que el contenedor de Docker esté iniciado ejecutando: <code>docker compose ps</code></li>
                        <li>Si el contenedor no está activo, inícialo con: <code>docker compose up -d</code></li>
                        <li>Verifica los registros de error en <code>storage/logs/database.log</code></li>
                    </ol>
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

// Despachar la petición
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$router->dispatch($method, $uri);
