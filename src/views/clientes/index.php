<?php
use App\Core\Csrf;
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Directorio de Clientes</h1>
        <p class="text-muted mb-0 small">Control y registro de clientes del restaurante (RF-04)</p>
    </div>
    <a href="/clientes/crear" class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 shadow-sm">
        <span>➕</span>
        <span>Nuevo Cliente</span>
    </a>
</div>

<!-- Barra de Búsqueda -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form action="/clientes" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-9 col-lg-10">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">🔍</span>
                    <input 
                        type="text" 
                        class="form-control border-start-0 ps-0" 
                        name="buscar" 
                        value="<?= htmlspecialchars((string)($busqueda ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                        placeholder="Buscar cliente por nombres, apellidos o número de cédula..."
                    >
                </div>
            </div>
            <div class="col-12 col-md-3 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Buscar</button>
                <?php if (!empty($busqueda)): ?>
                    <a href="/clientes" class="btn btn-outline-secondary" title="Limpiar filtro">✕</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Listado de Clientes -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-bold">Listado de Clientes</h6>
        <span class="badge bg-light text-muted border">Total: <?= count($clientes) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 60px;">#</th>
                        <th>Nombre y Apellidos</th>
                        <th>Cédula (10 dígitos)</th>
                        <th>Teléfono de Contacto</th>
                        <th>Fecha de Registro</th>
                        <th class="text-end pe-3" style="width: 180px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <?= !empty($busqueda) ? 'No se encontraron clientes que coincidan con la búsqueda.' : 'No hay clientes registrados en el sistema.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-muted"><?= (int)$c['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?= htmlspecialchars($c['nombres'] . ' ' . $c['apellidos'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td><code><?= htmlspecialchars($c['documento'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= htmlspecialchars($c['telefono'] ?? 'Sin registrar', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small text-muted"><?= substr((string)$c['creado_en'], 0, 10) ?></td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1">
                                        <a href="/clientes/editar?id=<?= (int)$c['id'] ?>" class="btn btn-outline-primary btn-sm px-2" title="Editar cliente">
                                            ✏️ Editar
                                        </a>
                                        <form action="/clientes/eliminar" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Seguro que deseas eliminar este cliente?');">
                                            <?= Csrf::renderInput() ?>
                                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm px-2" title="Eliminar registro">
                                                🗑️
                                            </button>
                                        </form>
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
