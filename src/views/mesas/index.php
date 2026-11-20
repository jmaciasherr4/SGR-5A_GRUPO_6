<?php
use App\Core\Csrf;
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Control y Estado de Mesas</h1>
        <p class="text-muted mb-0 small">Disponibilidad en tiempo real por zonas (RF-07)</p>
    </div>
    <?php if ($esAdmin): ?>
        <a href="/mesas/crear" class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 shadow-sm">
            <span>➕</span>
            <span>Nueva Mesa</span>
        </a>
    <?php endif; ?>
</div>

<!-- Filtros de Zona y Estado -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form action="/mesas" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <select name="zona" class="form-select">
                    <option value="">-- Todas las Zonas --</option>
                    <?php foreach ($zonas as $z): ?>
                        <option value="<?= htmlspecialchars($z, ENT_QUOTES, 'UTF-8') ?>" <?= $zonaFiltro === $z ? 'selected' : '' ?>>
                            Zona: <?= htmlspecialchars($z, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-5">
                <select name="estado" class="form-select">
                    <option value="">-- Todos los Estados --</option>
                    <option value="libre" <?= $estadoFiltro === 'libre' ? 'selected' : '' ?>>🟢 Libre</option>
                    <option value="ocupada" <?= $estadoFiltro === 'ocupada' ? 'selected' : '' ?>>🔴 Ocupada</option>
                    <option value="reservada" <?= $estadoFiltro === 'reservada' ? 'selected' : '' ?>>🟡 Reservada</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
                <?php if (!empty($zonaFiltro) || !empty($estadoFiltro)): ?>
                    <a href="/mesas" class="btn btn-outline-secondary" title="Limpiar filtros">✕</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tablero / Listado de Mesas -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-bold">Mesas del Restaurante</h6>
        <span class="badge bg-light text-muted border">Total: <?= count($mesas) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 80px;">Mesa</th>
                        <th>Zona</th>
                        <th>Capacidad</th>
                        <th>Estado Actual</th>
                        <th style="width: 250px;">Cambio Rápido de Estado</th>
                        <?php if ($esAdmin): ?>
                            <th class="text-end pe-3" style="width: 150px;">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mesas)): ?>
                        <tr>
                            <td colspan="<?= $esAdmin ? 6 : 5 ?>" class="text-center py-4 text-muted">No se encontraron mesas registradas con los filtros seleccionados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mesas as $m): ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-dark fs-6 px-3 py-2">
                                        #<?= (int)$m['numero'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        📍 <?= htmlspecialchars($m['zona'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= (int)$m['capacidad'] ?></strong> comensales
                                </td>
                                <td>
                                    <?php
                                    $badgeClase = match ($m['estado']) {
                                        'libre'     => 'bg-success text-white',
                                        'ocupada'   => 'bg-danger text-white',
                                        'reservada' => 'bg-warning text-dark',
                                        default     => 'bg-secondary text-white'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClase ?> text-uppercase px-2 py-1">
                                        <?= htmlspecialchars($m['estado'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Formulario de Cambio Rápido de Estado (RF-07) -->
                                    <form action="/mesas/estado" method="POST" class="d-flex align-items-center gap-1 m-0">
                                        <?= Csrf::renderInput() ?>
                                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                        <select name="estado" class="form-select form-select-sm" style="min-width: 120px;" onchange="this.form.submit()">
                                            <option value="libre" <?= $m['estado'] === 'libre' ? 'selected' : '' ?>>Libre</option>
                                            <option value="ocupada" <?= $m['estado'] === 'ocupada' ? 'selected' : '' ?>>Ocupada</option>
                                            <option value="reservada" <?= $m['estado'] === 'reservada' ? 'selected' : '' ?>>Reservada</option>
                                        </select>
                                    </form>
                                </td>
                                <?php if ($esAdmin): ?>
                                    <td class="text-end pe-3">
                                        <div class="d-inline-flex gap-1">
                                            <a href="/mesas/editar?id=<?= (int)$m['id'] ?>" class="btn btn-outline-primary btn-sm px-2" title="Editar mesa">
                                                ✏️
                                            </a>
                                            <form action="/mesas/eliminar" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Seguro que deseas eliminar la mesa #<?= (int)$m['numero'] ?>?');">
                                                <?= Csrf::renderInput() ?>
                                                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm px-2" title="Eliminar mesa">
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
