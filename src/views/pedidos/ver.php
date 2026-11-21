<?php
use App\Core\Csrf;

// $pedido, $detalles, $historial vienen del controlador

$estadoBadge = match ($pedido['estado']) {
    'pendiente'      => 'bg-warning text-dark',
    'en_preparacion' => 'bg-primary text-white',
    'listo'          => 'bg-info text-dark',
    'entregado'      => 'bg-success text-white',
    'cancelado'      => 'bg-danger text-white',
    default          => 'bg-secondary'
};

$estadoIcono = match ($pedido['estado']) {
    'pendiente'      => '⏳',
    'en_preparacion' => '🔥',
    'listo'          => '🔔',
    'entregado'      => '✅',
    'cancelado'      => '❌',
    default          => '•'
};
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">
            Detalle del Pedido #<?= (int)$pedido['id'] ?>
            <span class="badge <?= $estadoBadge ?> ms-2 fs-6 align-middle">
                <?= $estadoIcono ?> <?= htmlspecialchars(str_replace('_', ' ', strtoupper($pedido['estado'])), ENT_QUOTES, 'UTF-8') ?>
            </span>
        </h1>
        <p class="text-muted mb-0 small">RF-09 / RF-10 / RF-11: Trazabilidad completa del pedido.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($pedido['estado'] === 'pendiente'): ?>
            <a href="/pedidos/editar?id=<?= (int)$pedido['id'] ?>"
               class="btn btn-outline-warning d-inline-flex align-items-center gap-1">
                ✏️ Modificar
            </a>
            <form action="/pedidos/cancelar" method="POST" class="d-inline m-0"
                  onsubmit="return confirm('¿Cancelar el pedido #<?= (int)$pedido['id'] ?>?');">
                <?= Csrf::renderInput() ?>
                <input type="hidden" name="id" value="<?= (int)$pedido['id'] ?>">
                <button type="submit" class="btn btn-outline-danger">✕ Cancelar Pedido</button>
            </form>
        <?php endif; ?>
        <a href="/pedidos" class="btn btn-outline-secondary">← Volver a Pedidos</a>
    </div>
</div>

<div class="row g-4">

    <!-- ══════════════════════════════════════════════
         Columna izquierda: Cabecera del pedido
    ══════════════════════════════════════════════ -->
    <div class="col-12 col-md-4">

        <!-- Información del pedido -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">📋 Información del Pedido</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered align-middle mb-0">
                    <tbody>
                        <tr>
                            <th class="bg-light" style="width:45%;">Tipo</th>
                            <td>
                                <?php if ($pedido['tipo'] === 'mesa'): ?>
                                    🪑 Mesa #<?= (int)($pedido['mesa_numero'] ?? 0) ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars((string)($pedido['mesa_zona'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                <?php else: ?>
                                    🛍️ Para Llevar
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">Comensales</th>
                            <td><?= (int)$pedido['num_comensales'] ?> persona(s)</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Mesero</th>
                            <td>
                                <span class="fw-semibold text-secondary">@<?= htmlspecialchars($pedido['mesero_username'], ENT_QUOTES, 'UTF-8') ?></span>
                                <small class="text-muted d-block">
                                    <?= htmlspecialchars($pedido['mesero_nombres'] . ' ' . $pedido['mesero_apellidos'], ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">Hora del Pedido</th>
                            <td><code><?= htmlspecialchars((string)$pedido['creado_en'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        </tr>
                        <tr>
                            <th class="bg-light">Estado Actual</th>
                            <td>
                                <span class="badge <?= $estadoBadge ?> px-2 py-1">
                                    <?= $estadoIcono ?> <?= htmlspecialchars(str_replace('_', ' ', $pedido['estado']), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Total calculado en servidor -->
        <div class="card shadow-sm border-0 border-primary">
            <div class="card-body text-center py-4">
                <div class="text-muted small mb-1">Total (calculado en servidor)</div>
                <div class="display-6 fw-bold text-primary font-monospace">
                    $<?= number_format((float)$pedido['total'], 2) ?>
                </div>
                <div class="text-muted small mt-1">USD</div>
            </div>
        </div>

    </div>

    <!-- ══════════════════════════════════════════════
         Columna derecha: Detalle y línea de tiempo
    ══════════════════════════════════════════════ -->
    <div class="col-12 col-md-8">

        <!-- Tabla de platos -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">🍽️ Platos del Pedido</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Plato</th>
                                <th>Categoría</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end pe-3">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($detalles)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Sin platos registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($detalles as $det): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold"><?= htmlspecialchars($det['plato_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($det['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="text-center fw-bold"><?= (int)$det['cantidad'] ?></td>
                                        <td class="text-end font-monospace text-secondary">$<?= number_format((float)$det['precio_unitario'], 2) ?></td>
                                        <td class="text-end font-monospace fw-semibold text-primary pe-3">$<?= number_format((float)$det['subtotal'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <!-- Fila de total -->
                                <tr class="table-light">
                                    <td colspan="4" class="text-end fw-bold pe-3 py-3">TOTAL DEL PEDIDO:</td>
                                    <td class="text-end font-monospace fw-bold fs-5 text-primary pe-3 py-3">
                                        $<?= number_format((float)$pedido['total'], 2) ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Línea de tiempo del historial de estados -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">🕐 Historial de Estados (RF-10)</h6>
            </div>
            <div class="card-body">
                <?php if (empty($historial)): ?>
                    <p class="text-muted mb-0">Sin historial registrado.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php
                        $totalHistorial = count($historial);
                        foreach ($historial as $i => $h):
                            $esUltimo = ($i === $totalHistorial - 1);
                            $badgeH = match ($h['estado']) {
                                'pendiente'      => 'bg-warning text-dark',
                                'en_preparacion' => 'bg-primary text-white',
                                'listo'          => 'bg-info text-dark',
                                'entregado'      => 'bg-success text-white',
                                'cancelado'      => 'bg-danger text-white',
                                default          => 'bg-secondary'
                            };
                            $iconoH = match ($h['estado']) {
                                'pendiente'      => '⏳',
                                'en_preparacion' => '🔥',
                                'listo'          => '🔔',
                                'entregado'      => '✅',
                                'cancelado'      => '❌',
                                default          => '•'
                            };
                        ?>
                            <div class="d-flex gap-3 <?= !$esUltimo ? 'mb-3' : '' ?>">
                                <!-- Icono y línea vertical -->
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fs-5"
                                         style="width:40px;height:40px;flex-shrink:0;">
                                        <?= $iconoH ?>
                                    </div>
                                    <?php if (!$esUltimo): ?>
                                        <div class="border-start border-2 flex-grow-1 mt-1" style="min-height:20px;"></div>
                                    <?php endif; ?>
                                </div>
                                <!-- Contenido del evento -->
                                <div class="flex-grow-1 pb-2">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge <?= $badgeH ?> px-2 py-1 text-uppercase">
                                            <?= htmlspecialchars(str_replace('_', ' ', $h['estado']), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <?php if ($esUltimo): ?>
                                            <span class="badge bg-dark text-white px-2 py-1 small">Estado actual</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-1 small text-muted">
                                        <code><?= htmlspecialchars((string)$h['cambiado_en'], ENT_QUOTES, 'UTF-8') ?></code>
                                        &mdash;
                                        por <strong><?= htmlspecialchars($h['nombres'] . ' ' . $h['apellidos'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span class="text-secondary">(@<?= htmlspecialchars($h['username'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($h['rol_nombre'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /col-md-8 -->

</div><!-- /row -->
