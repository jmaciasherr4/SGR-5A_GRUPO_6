<?php
use App\Core\Csrf;
use App\Core\Flash;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Gestión de Restaurante</title>
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100 py-4">

    <div class="container" style="max-width: 480px;">
        <div class="text-center mb-4">
            <div class="display-5 mb-2">🍽️</div>
            <h1 class="h3 fw-bold text-dark">Gestión de Restaurante</h1>
            <p class="text-muted small">Ingresa tus credenciales para acceder al sistema</p>
        </div>

        <!-- Alertas Flash -->
        <?php foreach (Flash::get() as $f): ?>
            <div class="alert alert-<?= htmlspecialchars($f['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= htmlspecialchars($f['message'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endforeach; ?>

        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-body p-4 p-md-5">
                <form action="/login" method="POST" autocomplete="off">
                    <?= Csrf::renderInput() ?>

                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">Nombre de Usuario</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="username" 
                            name="username" 
                            placeholder="ej. admin" 
                            required 
                            autofocus
                        >
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Contraseña</label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            placeholder="••••••••" 
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                        Ingresar al Sistema
                    </button>
                </form>
            </div>
        </div>

        <!-- Tarjeta de Ayuda con Cuentas de Prueba -->
        <div class="card border-0 bg-white shadow-sm rounded-3">
            <div class="card-body p-3">
                <h6 class="card-subtitle mb-2 text-muted fw-bold small text-uppercase">Cuentas de Prueba (Desarrollo)</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless small mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Clave</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>admin</code></td>
                                <td><span class="badge bg-danger">Administrador</span></td>
                                <td><code>admin123</code></td>
                            </tr>
                            <tr>
                                <td><code>mesero1</code></td>
                                <td><span class="badge bg-success">Mesero</span></td>
                                <td><code>admin123</code></td>
                            </tr>
                            <tr>
                                <td><code>cocinero1</code></td>
                                <td><span class="badge bg-warning text-dark">Cocinero</span></td>
                                <td><code>admin123</code></td>
                            </tr>
                            <tr>
                                <td><code>cajero1</code></td>
                                <td><span class="badge bg-info text-dark">Cajero</span></td>
                                <td><code>admin123</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-center mt-3 text-muted small">
            <a href="/salud" class="text-decoration-none">Ver Estado del Sistema (/salud)</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
