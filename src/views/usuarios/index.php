<?php
use App\Core\Auth;
use App\Core\Csrf;
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Gestión de Usuarios del Sistema</h1>
        <p class="text-muted mb-0 small">Administración de credenciales, roles y accesos (RF-02)</p>
    </div>
    <a href="/usuarios/crear" class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 shadow-sm">
        <span>➕</span>
        <span>Nuevo Usuario</span>
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h6 class="card-title mb-0 fw-bold">Listado de Usuarios Registrados</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 60px;">#</th>
                        <th>Nombre y Apellidos</th>
                        <th>Cédula / Documento</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="text-end pe-3" style="width: 280px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No existen usuarios registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-muted"><?= (int)$u['id'] ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($u['nombres'] . ' ' . $u['apellidos'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($u['telefono'] ?? 'Sin teléfono', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td><code><?= htmlspecialchars($u['documento'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td class="fw-bold text-primary">@<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php
                                    $rolBadge = match ($u['rol_nombre']) {
                                        'administrador' => 'bg-danger',
                                        'mesero'        => 'bg-success',
                                        'cajero'        => 'bg-info text-dark',
                                        'cocinero'      => 'bg-warning text-dark',
                                        default         => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $rolBadge ?> text-capitalize px-2 py-1">
                                        <?= htmlspecialchars($u['rol_nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u['activo']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            ● Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            ○ Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Botón Editar -->
                                        <a href="/usuarios/editar?id=<?= (int)$u['id'] ?>" class="btn btn-outline-primary btn-sm px-2" title="Editar datos">
                                            ✏️ Editar
                                        </a>

                                        <!-- Botón Cambiar Estado (Activar / Desactivar) -->
                                        <?php if ((int)$u['id'] !== Auth::id()): ?>
                                            <form action="/usuarios/estado" method="POST" class="d-inline m-0">
                                                <?= Csrf::renderInput() ?>
                                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                <input type="hidden" name="activo" value="<?= $u['activo'] ? '0' : '1' ?>">
                                                <?php if ($u['activo']): ?>
                                                    <button type="submit" class="btn btn-outline-warning btn-sm px-2" title="Desactivar acceso (baja lógica)">
                                                        🚫 Desactivar
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-outline-success btn-sm px-2" title="Reactivar acceso">
                                                        ✅ Activar
                                                    </button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- Botón Eliminar Físico -->
                                            <form action="/usuarios/eliminar" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Estás seguro de eliminar este usuario? Si tiene historial de pedidos o facturas, la base de datos lo bloqueará por integridad.');">
                                                <?= Csrf::renderInput() ?>
                                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm px-2" title="Eliminar registro">
                                                    🗑️
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border p-2">Sesión actual</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
