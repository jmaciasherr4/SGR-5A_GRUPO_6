<?php
use App\Core\Auth;
use App\Core\Csrf;
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">💼 Panel de Control — Caja</h1>
        <p class="text-muted mb-0 small">Bienvenido(a) al sistema de gestión de restaurante, <strong><?= htmlspecialchars(Auth::fullName(), ENT_QUOTES, 'UTF-8') ?></strong>.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/salud" class="btn btn-outline-primary d-inline-flex align-items-center gap-1" style="min-height: 44px;">
            <span>🩺</span>
            <span>Salud del Sistema</span>
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Tarjeta de Perfil y Sesión -->
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">👤 Datos de Sesión Activa</h6>
            </div>
            <div class="card-body">
                <div class="text-center py-3">
                    <div class="fs-1 mb-2">💼</div>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars(Auth::fullName(), ENT_QUOTES, 'UTF-8') ?></h5>
                    <span class="badge bg-primary text-uppercase px-3 py-2 fs-6">Rol: <?= htmlspecialchars(Auth::role() ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <hr>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Nombre de usuario:</span>
                        <span class="fw-semibold font-monospace">@<?= htmlspecialchars(Auth::username() ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Documento de identidad:</span>
                        <span class="fw-semibold"><?= htmlspecialchars(Auth::user()['documento'] ?? 'No registrado', ENT_QUOTES, 'UTF-8') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Estado de cuenta:</span>
                        <span class="badge bg-success">Activo</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Hora de ingreso:</span>
                        <span><?= date('H:i:s') ?> (America/Guayaquil)</span>
                    </li>
                </ul>
            </div>
            <div class="card-footer bg-white p-3">
                <form action="/logout" method="POST" class="m-0" id="form-logout-panel">
                    <?= Csrf::renderInput('csrf_token_panel_logout') ?>
                    <button type="submit" class="btn btn-outline-danger w-100 d-inline-flex justify-content-center align-items-center gap-2 fw-semibold" style="min-height: 44px;">
                        <span>🚪</span>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tarjeta Informativa de Permisos y Módulo -->
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">🛡️ Información de Accesos y Matriz de Permisos (RF-03)</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info d-flex align-items-start gap-2 mb-3" role="alert">
                    <span class="fs-4">ℹ️</span>
                    <div>
                        <strong>Control de Acceso por Rol:</strong> Tu usuario cuenta con perfil de <strong>Cajero</strong>. Según las directivas de seguridad del restaurante, tus permisos operativos están orientados a caja y cobros.
                    </div>
                </div>

                <p class="text-muted small mb-3">
                    Los módulos administrativos, de cocina y comandeo están asignados exclusivamente a sus respectivos roles:
                </p>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle small mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>Módulo</th>
                                <th>Estado de Acceso</th>
                                <th>Rol Autorizado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>👥 Usuarios del Sistema</td>
                                <td><span class="badge bg-danger">Restringido (403)</span></td>
                                <td>Administrador</td>
                            </tr>
                            <tr>
                                <td>📇 Clientes</td>
                                <td><span class="badge bg-danger">Restringido (403)</span></td>
                                <td>Administrador, Mesero</td>
                            </tr>
                            <tr>
                                <td>🪑 Mesas</td>
                                <td><span class="badge bg-danger">Restringido (403)</span></td>
                                <td>Administrador, Mesero</td>
                            </tr>
                            <tr>
                                <td>📋 Menú y Precios</td>
                                <td><span class="badge bg-danger">Restringido (403)</span></td>
                                <td>Administrador, Mesero, Cocinero</td>
                            </tr>
                            <tr>
                                <td>📝 Pedidos</td>
                                <td><span class="badge bg-danger">Restringido (403)</span></td>
                                <td>Administrador, Mesero</td>
                            </tr>
                            <tr>
                                <td>👨‍🍳 Pantalla de Cocina</td>
                                <td><span class="badge bg-danger">Restringido (403)</span></td>
                                <td>Administrador, Cocinero</td>
                            </tr>
                            <tr>
                                <td>📊 Predicciones Analíticas</td>
                                <td><span class="badge bg-danger">Restringido (403)</span></td>
                                <td>Administrador</td>
                            </tr>
                            <tr class="table-success">
                                <td>🩺 Verificación / Salud</td>
                                <td><span class="badge bg-success">Permitido</span></td>
                                <td>Todos los usuarios</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card bg-light border-0 p-3 rounded-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <span class="fw-bold small d-block">¿Deseas verificar la base de datos?</span>
                            <span class="text-muted small">Comprueba la conexión con PostgreSQL y el estado general.</span>
                        </div>
                        <a href="/salud" class="btn btn-sm btn-primary px-3" style="min-height: 40px; display: inline-flex; align-items: center;">
                            Ir a Verificación
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
