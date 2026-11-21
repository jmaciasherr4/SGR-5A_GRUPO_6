<?php
use App\Core\Csrf;
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Gestión de Pedidos</h1>
        <p class="text-muted mb-0 small">Control de órdenes por mesa y para llevar (RF-09, RF-11)</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/cocina" class="btn btn-outline-dark d-inline-flex align-items-center gap-1 px-3">
            <span>👨‍🍳</span>
            <span>Ver Cocina</span>
        </a>
        <a href="/pedidos/crear" class="btn btn-primary d-inline-flex align-items-center gap-1 px-3 shadow-sm">
            <span>➕</span>
            <span>Tomar Pedido</span>
        </a>
    </div>
</div>

<!-- Filtros de Estado y Tipo -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form action="/pedidos" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <select name="estado" class="form-select">
                    <option value="">-- Todos los Estados --</option>
                    <option value="pendiente" <?= $estadoFiltro === 'pendiente' ? 'selected' : '' ?>>⏳ Pendiente</option>
                    <option value="en_preparacion" <?= $estadoFiltro === 'en_preparacion' ? 'selected' : '' ?>>🔥 En Preparación</option>
                    <option value="listo" <?= $estadoFiltro === 'listo' ? 'selected' : '' ?>>🔔 Listo</option>
                    <option value="entregado" <?= $estadoFiltro === 'entregado' ? 'selected' : '' ?>>✅ Entregado</option>
                    <option value="cancelado" <?= $estadoFiltro === 'cancelado' ? 'selected' : '' ?>>❌ Cancelado</option>
                </select>
            </div>
            <div class="col-12 col-md-5">
                <select name="tipo" class="form-select">
                    <option value="">-- Todos los Tipos --</option>
                    <option value="mesa" <?= $tipoFiltro === 'mesa' ? 'selected' : '' ?>>🍽️ Para Servir en Mesa</option>
                    <option value="llevar" <?= $tipoFiltro === 'llevar' ? 'selected' : '' ?>>🛍️ Para Llevar</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
                <?php if (!empty($estadoFiltro) || !empty($tipoFiltro)): ?>
                    <a href="/pedidos" class="btn btn-outline-secondary" title="Limpiar filtros">✕</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Listado de Pedidos -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-bold">Órdenes del Restaurante</h6>
        <span class="badge bg-light text-muted border">Total: <?= count($pedidos) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 80px;">Pedido</th>
                        <th>Destino</th>
                        <th>Comensales</th>
                        <th>Mesero</th>
                        <th class="text-end" style="width: 130px;">Total ($ USD)</th>
                        <th class="text-center" style="width: 150px;">Estado</th>
                        <th>Hora</th>
                        <th class="text-end pe-3" style="width: 220px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pedidos)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No se encontraron pedidos con los filtros seleccionados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pedidos as $ped): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-muted">#<?= (int)$ped['id'] ?></td>
                                <td>
                                    <?php if ($ped['tipo'] === 'mesa'): ?>
                                        <span class="badge bg-dark px-2 py-1">
                                            🪑 Mesa #<?= (int)$ped['mesa_numero'] ?>
                                        </span>
                                        <small class="text-muted d-block"><?= htmlspecialchars((string)($ped['mesa_zona'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark px-2 py-1">
                                            🛍️ Para Llevar
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int)$ped['num_comensales'] ?> persona(s)</td>
                                <td>
                                    <small class="fw-semibold text-secondary">@<?= htmlspecialchars($ped['mesero_username'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="text-end font-monospace fw-bold fs-6 text-primary">
                                    $<?= number_format((float)$ped['total'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $badge = match ($ped['estado']) {
                                        'pendiente'      => 'bg-warning text-dark',
                                        'en_preparacion' => 'bg-primary text-white',
                                        'listo'          => 'bg-info text-dark',
                                        'entregado'      => 'bg-success text-white',
                                        'cancelado'      => 'bg-danger text-white',
                                        default          => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?> px-2 py-1 text-uppercase">
                                        <?= htmlspecialchars(str_replace('_', ' ', $ped['estado']), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= substr((string)$ped['creado_en'], 11, 5) ?></td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Ver Detalle -->
                                        <a href="/pedidos/ver?id=<?= (int)$ped['id'] ?>" class="btn btn-outline-secondary btn-sm px-2" title="Ver platos y trazabilidad">
                                            👁️
                                        </a>

                                        <?php if ($ped['estado'] === 'pendiente'): ?>
                                            <!-- RF-11: Modificar (permitido solo en pendiente) -->
                                            <a href="/pedidos/editar?id=<?= (int)$ped['id'] ?>" class="btn btn-outline-primary btn-sm px-2" title="Modificar platos">
                                                ✏️
                                            </a>

                                            <!-- RF-11: Cancelar (permitido solo en pendiente) -->
                                            <form action="/pedidos/cancelar" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Seguro que deseas cancelar el pedido #<?= (int)$ped['id'] ?>?');">
                                                <?= Csrf::renderInput() ?>
                                                <input type="hidden" name="id" value="<?= (int)$ped['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm px-2" title="Cancelar pedido">
                                                    ✕
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <!-- RF-11: Indicador de bloqueo cuando ya no es pendiente -->
                                            <button type="button" class="btn btn-light btn-sm px-2 text-muted border-0" disabled title="Bloqueado: solo se puede modificar o cancelar mientras esté pendiente">
                                                🔒
                                            </button>
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
