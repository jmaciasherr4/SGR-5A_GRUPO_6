<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;

$rol = Auth::role();
$usuario = Auth::user();
$tituloPagina = $titulo ?? 'Sistema de Gestión de Restaurante';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') ?> - Restaurante</title>
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Barra de Navegación Principal Adaptativa por Rol (RF-03) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-2">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/panel">
                <span>🍽️ Restaurante</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Navegación">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navMenu">
                <!-- Enlaces según matriz de permisos por rol -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    
                    <?php if ($rol === 'administrador'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/usuarios') ? 'active fw-bold' : '' ?>" href="/usuarios">
                                👥 Usuarios
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($rol, ['administrador', 'mesero'], true)): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/clientes') ? 'active fw-bold' : '' ?>" href="/clientes">
                                📇 Clientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/mesas') ? 'active fw-bold' : '' ?>" href="/mesas">
                                🪑 Mesas
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($rol, ['administrador', 'mesero', 'cocinero'], true)): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/menu') ? 'active fw-bold' : '' ?>" href="/menu">
                                📋 Menú
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($rol, ['administrador', 'mesero'], true)): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/pedidos') ? 'active fw-bold' : '' ?>" href="/pedidos">
                                📝 Pedidos
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($rol, ['administrador', 'cocinero'], true)): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/cocina') ? 'active fw-bold' : '' ?>" href="/cocina">
                                👨‍🍳 Pantalla de Cocina
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($rol === 'administrador'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/predicciones') ? 'active fw-bold' : '' ?>" href="/predicciones">
                                📊 Predicciones
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/salud') ? 'active fw-bold' : '' ?>" href="/salud">
                            🩺 Verificación / Salud
                        </a>
                    </li>
                </ul>

                <!-- Información del usuario logueado y Logout -->
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="text-light text-end d-none d-md-block">
                        <div class="fw-bold small"><?= htmlspecialchars(Auth::fullName(), ENT_QUOTES, 'UTF-8') ?></div>
                        <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($rol ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <form action="/logout" method="POST" class="m-0" id="form-logout">
                        <?= Csrf::renderInput('csrf_token_logout') ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm px-3" title="Cerrar Sesión">
                            🚪 Salir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenedor Principal -->
    <main class="container-fluid px-3 px-lg-4 py-4 flex-grow-1" style="max-width: 1400px;">
        
        <!-- Mensajes Flash de Notificación -->
        <?php foreach (Flash::get() as $f): ?>
            <div class="alert alert-<?= htmlspecialchars($f['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= htmlspecialchars($f['message'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endforeach; ?>

        <!-- Contenido inyectado de la vista -->
        <?= $content ?>
    </main>

    <!-- Pie de Página -->
    <footer class="bg-white border-top py-3 text-center text-muted small mt-auto">
        <div class="container">
            Sistema de Gestión de Restaurante &bull; UTEQ &bull; Usuario activo: <strong><?= htmlspecialchars(Auth::username() ?? '', ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($rol ?? '', ENT_QUOTES, 'UTF-8') ?>)
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
