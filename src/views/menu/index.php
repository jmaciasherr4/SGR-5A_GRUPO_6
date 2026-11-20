<?php
use App\Core\Csrf;
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Catálogo de Menú y Precios</h1>
        <p class="text-muted mb-0 small">Gestión de platos, categorías y precios de venta (RF-08)</p>
    </div>
    <?php if ($esAdmin): ?>
        <div class="d-flex gap-2">
            <a href="/menu/categorias" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1 px-3">
                <span>📁</span>
                <span>Categorías</span>
            </a>
            <a href="/menu/crear" class="btn btn-primary d-inline-flex align-items-center gap-1 px-3 shadow-sm">
                <span>➕</span>
                <span>Nuevo Plato</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Filtros de Categoría y Búsqueda -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form action="/menu" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <select name="categoria" class="form-select">
                    <option value="">-- Todas las Categorías --</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= $catFiltro === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?> (<?= (int)$cat['total_platos'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-5">
                <input 
                    type="text" 
                    name="buscar" 
                    class="form-control" 
                    value="<?= htmlspecialchars((string)($busqueda ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                    placeholder="Buscar por nombre o descripción..."
                >
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
                <?php if (!empty($catFiltro) || !empty($busqueda)): ?>
                    <a href="/menu" class="btn btn-outline-secondary" title="Limpiar filtros">✕</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Listado del Menú -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-bold">Platos del Menú</h6>
        <span class="badge bg-light text-muted border">Total: <?= count($platos) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">#</th>
                        <th>Categoría</th>
                        <th>Plato y Descripción</th>
                        <th class="text-end" style="width: 140px;">Precio ($ USD)</th>
                        <th class="text-center" style="width: 120px;">Estado</th>
                        <?php if ($esAdmin): ?>
                            <th class="text-end pe-3" style="width: 220px;">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($platos)): ?>
                        <tr>
                            <td colspan="<?= $esAdmin ? 6 : 5 ?>" class="text-center py-4 text-muted">No se encontraron platos que coincidan con los filtros.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($platos as $p): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-muted"><?= (int)$p['id'] ?></td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <?= htmlspecialchars($p['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($p['descripcion'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($p['descripcion'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-monospace fw-bold fs-6 text-primary">
                                    $<?= number_format((float)$p['precio'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($p['activo']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            ● Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            ○ Desactivado
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($esAdmin): ?>
                                    <td class="text-end pe-3">
                                        <div class="d-inline-flex gap-1">
                                            <a href="/menu/editar?id=<?= (int)$p['id'] ?>" class="btn btn-outline-primary btn-sm px-2" title="Editar plato">
                                                ✏️
                                            </a>

                                            <!-- Baja lógica (RF-08: Activar / Desactivar) -->
                                            <form action="/menu/estado" method="POST" class="d-inline m-0">
                                                <?= Csrf::renderInput() ?>
                                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                                <input type="hidden" name="activo" value="<?= $p['activo'] ? '0' : '1' ?>">
                                                <?php if ($p['activo']): ?>
                                                    <button type="submit" class="btn btn-outline-warning btn-sm px-2" title="Desactivar plato (no aparecerá en nuevos pedidos)">
                                                        🚫
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-outline-success btn-sm px-2" title="Activar plato">
                                                        ✅
                                                    </button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- Eliminación con control referencial -->
                                            <form action="/menu/eliminar" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Seguro que deseas eliminar este plato? Si figura en pedidos previos, será bloqueado por integridad referencial.');">
                                                <?= Csrf::renderInput() ?>
                                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm px-2" title="Eliminar plato">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
