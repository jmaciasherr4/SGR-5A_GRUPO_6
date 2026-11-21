<?php
use App\Core\Csrf;

// $pedido, $detalles, $platos, $categorias, $mesas, $cantidadesActuales vienen del controlador

$categoriaMap = [];
foreach ($categorias as $cat) {
    $categoriaMap[(int)$cat['id']] = htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8');
}

// Agrupar platos activos por categoría
$platosPorCategoria = [];
foreach ($platos as $plato) {
    $catId = (int)$plato['categoria_id'];
    $platosPorCategoria[$catId][] = $plato;
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Modificar Pedido #<?= (int)$pedido['id'] ?></h1>
        <p class="text-muted mb-0 small">RF-11: Solo se puede modificar mientras el pedido esté en estado <strong>pendiente</strong>.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/pedidos/ver?id=<?= (int)$pedido['id'] ?>" class="btn btn-outline-secondary">
            👁️ Ver Detalle
        </a>
        <a href="/pedidos" class="btn btn-outline-secondary">
            ← Volver
        </a>
    </div>
</div>

<!-- Aviso RF-11 -->
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
    <span class="fs-5">⚠️</span>
    <div>
        <strong>Atención:</strong> Una vez que el pedido pase a <em>en preparación</em>, no podrá modificarse ni cancelarse.
    </div>
</div>

<form action="/pedidos/actualizar" method="POST" id="form-editar" novalidate>
    <?= Csrf::renderInput() ?>
    <input type="hidden" name="id" value="<?= (int)$pedido['id'] ?>">

    <div class="row g-4">

        <!-- ══════════════════════════════════════════════
             COLUMNA IZQUIERDA: Datos del pedido
        ══════════════════════════════════════════════ -->
        <div class="col-12 col-lg-4">

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-bold">📋 Datos del Pedido</h6>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo" id="tipo_mesa"
                                       value="mesa" <?= $pedido['tipo'] === 'mesa' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="tipo_mesa">🪑 Mesa</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo" id="tipo_llevar"
                                       value="llevar" <?= $pedido['tipo'] === 'llevar' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="tipo_llevar">🛍️ Para Llevar</label>
                            </div>
                        </div>
                    </div>

                    <div id="bloque-mesa" class="mb-3">
                        <label for="mesa_id" class="form-label fw-semibold">Mesa <span class="text-danger">*</span></label>
                        <select name="mesa_id" id="mesa_id" class="form-select" style="min-height:44px;">
                            <option value="">— Seleccionar mesa —</option>
                            <?php foreach ($mesas as $mesa): ?>
                                <?php
                                // Permitir seleccionar la mesa actual aunque esté ocupada
                                $esMesaActual = ((int)$mesa['id'] === (int)$pedido['mesa_id']);
                                $desactivar   = !$esMesaActual && $mesa['estado'] !== 'libre';
                                ?>
                                <option value="<?= (int)$mesa['id'] ?>"
                                    <?= $esMesaActual ? 'selected' : '' ?>
                                    <?= $desactivar ? 'disabled' : '' ?>>
                                    Mesa #<?= (int)$mesa['numero'] ?> — <?= htmlspecialchars($mesa['zona'], ENT_QUOTES, 'UTF-8') ?>
                                    (cap. <?= (int)$mesa['capacidad'] ?>)
                                    <?php if ($esMesaActual): ?>[ACTUAL]<?php elseif ($desactivar): ?>[<?= strtoupper($mesa['estado']) ?>]<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="num_comensales" class="form-label fw-semibold">Núm. de Comensales <span class="text-danger">*</span></label>
                        <input type="number" name="num_comensales" id="num_comensales"
                               class="form-control" style="min-height:44px;"
                               min="1" max="50"
                               value="<?= (int)$pedido['num_comensales'] ?>" required>
                    </div>

                </div>
            </div>

            <!-- Resumen actualizado -->
            <div class="card shadow-sm border-0 border-warning sticky-top" style="top: 80px;">
                <div class="card-header bg-warning text-dark py-3">
                    <h6 class="card-title mb-0 fw-bold">🧾 Resumen (indicativo)</h6>
                </div>
                <div class="card-body p-3">
                    <div id="resumen-items" class="mb-3 small text-muted">
                        <em>Ajusta las cantidades para ver el resumen.</em>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-6">Total estimado:</span>
                        <span id="total-estimado" class="fw-bold fs-5 text-primary font-monospace">$0.00</span>
                    </div>
                    <div class="form-text text-center mt-1">El total definitivo lo recalcula el servidor.</div>
                </div>
                <div class="card-footer bg-white p-3">
                    <button type="submit" class="btn btn-warning w-100 fw-bold"
                            style="min-height:48px;" id="btn-guardar" disabled>
                        💾 Guardar Cambios
                    </button>
                </div>
            </div>

        </div>

        <!-- ══════════════════════════════════════════════
             COLUMNA DERECHA: Catálogo con cantidades actuales
        ══════════════════════════════════════════════ -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold">🍽️ Platos del Pedido</h6>
                    <span class="badge bg-warning text-dark" id="badge-items">0 platos</span>
                </div>
                <div class="card-body p-3">

                    <?php if (empty($platosPorCategoria)): ?>
                        <div class="alert alert-warning mb-0">No hay platos activos disponibles.</div>
                    <?php else: ?>

                        <?php foreach ($platosPorCategoria as $catId => $platosGrupo): ?>
                            <h6 class="text-uppercase text-muted fw-bold small border-bottom pb-1 mt-3 mb-2">
                                <?= $categoriaMap[$catId] ?? 'Sin categoría' ?>
                            </h6>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Plato</th>
                                            <th class="text-end" style="width:110px;">Precio</th>
                                            <th class="text-center" style="width:130px;">Cantidad</th>
                                            <th class="text-end" style="width:110px;">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($platosGrupo as $plato): ?>
                                            <?php
                                            $pid      = (int)$plato['id'];
                                            $precio   = (float)$plato['precio'];
                                            $cantActual = (int)($cantidadesActuales[$pid] ?? 0);
                                            ?>
                                            <tr class="fila-plato" data-plato-id="<?= $pid ?>"
                                                data-precio="<?= $precio ?>"
                                                data-nombre="<?= htmlspecialchars($plato['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars($plato['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <?php if (!empty($plato['descripcion'])): ?>
                                                        <small class="text-muted"><?= htmlspecialchars($plato['descripcion'], ENT_QUOTES, 'UTF-8') ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end font-monospace fw-semibold text-secondary">
                                                    $<?= number_format($precio, 2) ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-menos px-2"
                                                                style="min-height:38px;min-width:38px;" aria-label="Quitar uno">−</button>
                                                        <input type="number"
                                                               name="cantidades[<?= $pid ?>]"
                                                               class="form-control form-control-sm text-center input-cantidad"
                                                               style="width:60px;min-height:38px;"
                                                               value="<?= $cantActual ?>" min="0" max="99"
                                                               aria-label="Cantidad de <?= htmlspecialchars($plato['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <button type="button" class="btn btn-outline-primary btn-sm btn-mas px-2"
                                                                style="min-height:38px;min-width:38px;" aria-label="Agregar uno">+</button>
                                                    </div>
                                                </td>
                                                <td class="text-end font-monospace subtotal-celda <?= $cantActual > 0 ? 'fw-semibold text-primary' : 'text-muted' ?>">
                                                    $<?= number_format($cantActual * $precio, 2) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /row -->
</form>

<script>
(function () {
    'use strict';

    // ── Visibilidad del bloque de mesa según tipo ──────────────────────────
    const radios    = document.querySelectorAll('input[name="tipo"]');
    const bloqueMesa = document.getElementById('bloque-mesa');
    const selectMesa = document.getElementById('mesa_id');

    function actualizarBloqueMesa() {
        const esMesa = document.querySelector('input[name="tipo"]:checked')?.value === 'mesa';
        bloqueMesa.style.display = esMesa ? '' : 'none';
        if (!esMesa) selectMesa.value = '';
    }

    radios.forEach(r => r.addEventListener('change', actualizarBloqueMesa));
    actualizarBloqueMesa();

    // ── Botones + / − ─────────────────────────────────────────────────────
    document.querySelectorAll('.fila-plato').forEach(function (fila) {
        const inputCantidad = fila.querySelector('.input-cantidad');
        const btnMas  = fila.querySelector('.btn-mas');
        const btnMenos = fila.querySelector('.btn-menos');

        btnMas.addEventListener('click', function () {
            const v = parseInt(inputCantidad.value, 10) || 0;
            if (v < 99) { inputCantidad.value = v + 1; disparar(inputCantidad); }
        });

        btnMenos.addEventListener('click', function () {
            const v = parseInt(inputCantidad.value, 10) || 0;
            if (v > 0) { inputCantidad.value = v - 1; disparar(inputCantidad); }
        });

        inputCantidad.addEventListener('input', function () { disparar(this); });
    });

    function disparar(input) {
        input.dispatchEvent(new Event('cambio-cantidad', { bubbles: true }));
    }

    // ── Recalcular resumen ─────────────────────────────────────────────────
    document.addEventListener('cambio-cantidad', recalcular);

    function recalcular() {
        let total = 0;
        let itemsSeleccionados = 0;
        const lineasResumen = [];

        document.querySelectorAll('.fila-plato').forEach(function (fila) {
            const cant     = parseInt(fila.querySelector('.input-cantidad').value, 10) || 0;
            const precio   = parseFloat(fila.dataset.precio) || 0;
            const nombre   = fila.dataset.nombre;
            const subtotal = cant * precio;

            const celdaSub = fila.querySelector('.subtotal-celda');
            celdaSub.textContent = '$' + subtotal.toFixed(2);
            celdaSub.classList.toggle('text-muted', cant === 0);
            celdaSub.classList.toggle('fw-semibold', cant > 0);
            celdaSub.classList.toggle('text-primary', cant > 0);

            if (cant > 0) {
                total += subtotal;
                itemsSeleccionados++;
                lineasResumen.push(cant + ' × ' + nombre + ' = $' + subtotal.toFixed(2));
            }
        });

        const resumenDiv = document.getElementById('resumen-items');
        resumenDiv.innerHTML = lineasResumen.length === 0
            ? '<em>Ningún plato seleccionado.</em>'
            : lineasResumen.map(l => '<div>' + escHtml(l) + '</div>').join('');

        document.getElementById('total-estimado').textContent = '$' + total.toFixed(2);
        document.getElementById('badge-items').textContent =
            itemsSeleccionados + ' plato' + (itemsSeleccionados !== 1 ? 's' : '');
        document.getElementById('btn-guardar').disabled = (itemsSeleccionados === 0);
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Recalcular al cargar para reflejar las cantidades actuales
    recalcular();
})();
</script>
