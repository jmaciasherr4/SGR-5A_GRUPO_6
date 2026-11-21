<?php
use App\Core\Csrf;

// $pedidos viene del controlador ya agrupado; aquí los reagrupamos por estado para las columnas.
// Los posibles estados del Kanban (sin 'cancelado' ni 'entregado' de más de 12h)
$columnas = [
    'pendiente'      => ['titulo' => 'Pendiente',      'icono' => '⏳', 'class' => 'border-warning',  'header' => 'bg-warning text-dark'],
    'en_preparacion' => ['titulo' => 'En Preparación', 'icono' => '🔥', 'class' => 'border-primary',  'header' => 'bg-primary text-white'],
    'listo'          => ['titulo' => 'Listo',           'icono' => '🔔', 'class' => 'border-info',    'header' => 'bg-info text-dark'],
    'entregado'      => ['titulo' => 'Entregado',       'icono' => '✅', 'class' => 'border-success', 'header' => 'bg-success text-white'],
];

// Agrupar pedidos por estado
$pedidosPorEstado = array_fill_keys(array_keys($columnas), []);
foreach ($pedidos as $ped) {
    $est = $ped['estado'] ?? '';
    if (isset($pedidosPorEstado[$est])) {
        $pedidosPorEstado[$est][] = $ped;
    }
}

// Próximo estado para el botón de avance
$proximoEstado = [
    'pendiente'      => 'en_preparacion',
    'en_preparacion' => 'listo',
    'listo'          => 'entregado',
    'entregado'      => null,
];

$textoBoton = [
    'pendiente'      => '🔥 Iniciar Preparación',
    'en_preparacion' => '🔔 Marcar Listo',
    'listo'          => '✅ Marcar Entregado',
];

// CSRF token para peticiones AJAX
$csrfToken = Csrf::getToken();
?>

<!-- Encabezado del tablero -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1">👨‍🍳 Pantalla de Cocina</h1>
        <p class="text-muted mb-0 small">
            RF-10: Estados sin saltos &mdash; actualización automática cada 3 segundos.
            <span id="estado-sync" class="badge bg-success ms-2">🟢 Sincronizado</span>
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="text-muted small" id="ultima-actualizacion">—</span>
        <a href="/pedidos" class="btn btn-outline-secondary btn-sm">📝 Ir a Pedidos</a>
    </div>
</div>

<!-- Tablero Kanban: 4 columnas -->
<div class="row g-3" id="tablero-cocina">
    <?php foreach ($columnas as $estado => $col): ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-2 <?= $col['class'] ?> shadow-sm h-100">
                <!-- Cabecera de columna -->
                <div class="card-header <?= $col['header'] ?> py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><?= $col['icono'] ?> <?= $col['titulo'] ?></span>
                    <span class="badge bg-white text-dark kanban-contador" data-estado="<?= $estado ?>">
                        <?= count($pedidosPorEstado[$estado]) ?>
                    </span>
                </div>
                <!-- Tarjetas de pedidos -->
                <div class="card-body p-2 kanban-columna" data-estado="<?= $estado ?>" style="min-height: 200px;">
                    <?php if (empty($pedidosPorEstado[$estado])): ?>
                        <div class="text-center text-muted small py-4 kanban-vacio">
                            <div class="fs-2 mb-1"><?= $col['icono'] ?></div>
                            Sin pedidos en este estado.
                        </div>
                    <?php else: ?>
                        <?php foreach ($pedidosPorEstado[$estado] as $ped): ?>
                            <?php
                            $minutosTranscurridos = (int)round((time() - strtotime($ped['creado_en'])) / 60);
                            $urgente = ($estado === 'pendiente' && $minutosTranscurridos > 10)
                                    || ($estado === 'en_preparacion' && $minutosTranscurridos > 20);
                            ?>
                            <div class="card mb-2 border-0 shadow-sm <?= $urgente ? 'border-danger border-2' : '' ?>"
                                 data-pedido-id="<?= (int)$ped['id'] ?>" data-estado="<?= $estado ?>">
                                <div class="card-body p-2">
                                    <!-- Cabecera de tarjeta -->
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="fw-bold text-dark">#<?= (int)$ped['id'] ?></span>
                                            <?php if ($urgente): ?>
                                                <span class="badge bg-danger ms-1 small">⚠ Demorado</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?= $minutosTranscurridos ?>min</small>
                                    </div>

                                    <!-- Destino -->
                                    <div class="mb-2 small">
                                        <?php if ($ped['tipo'] === 'mesa'): ?>
                                            <span class="badge bg-dark px-2">🪑 Mesa #<?= (int)($ped['mesa_numero'] ?? 0) ?></span>
                                            <?php if (!empty($ped['mesa_zona'])): ?>
                                                <span class="text-muted"><?= htmlspecialchars($ped['mesa_zona'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark px-2">🛍️ Para Llevar</span>
                                        <?php endif; ?>
                                        <span class="text-muted ms-1"><?= (int)$ped['num_comensales'] ?> pax</span>
                                    </div>

                                    <!-- Platos -->
                                    <?php if (!empty($ped['items'])): ?>
                                        <ul class="list-unstyled mb-2 small">
                                            <?php foreach ($ped['items'] as $item): ?>
                                                <li class="d-flex gap-1">
                                                    <span class="badge bg-secondary text-white" style="min-width:24px;">
                                                        <?= (int)$item['cantidad'] ?>×
                                                    </span>
                                                    <span><?= htmlspecialchars($item['plato'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>

                                    <!-- Botón de avance de estado -->
                                    <?php if (isset($textoBoton[$estado])): ?>
                                        <button type="button"
                                                class="btn btn-sm w-100 btn-avanzar-estado fw-semibold
                                                       <?= $estado === 'pendiente' ? 'btn-warning' : ($estado === 'en_preparacion' ? 'btn-primary' : 'btn-success') ?>"
                                                style="min-height:44px;"
                                                data-pedido-id="<?= (int)$ped['id'] ?>"
                                                data-nuevo-estado="<?= $proximoEstado[$estado] ?>">
                                            <?= $textoBoton[$estado] ?>
                                        </button>
                                    <?php else: ?>
                                        <div class="text-center text-muted small mt-1">
                                            <em>Estado final</em>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div><!-- /tablero-cocina -->

<script>
(function () {
    'use strict';

    const CSRF_TOKEN   = <?= json_encode($csrfToken) ?>;
    const INTERVALO_MS = 3000;
    let   syncActivo   = true;

    // ── Avanzar estado por AJAX ──────────────────────────────────────────
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-avanzar-estado');
        if (!btn) return;

        const pedidoId   = parseInt(btn.dataset.pedidoId, 10);
        const nuevoEstado = btn.dataset.nuevoEstado;

        if (!pedidoId || !nuevoEstado) return;

        btn.disabled = true;
        btn.textContent = 'Guardando…';

        const form = new FormData();
        form.append('csrf_token', CSRF_TOKEN);
        form.append('pedido_id',  pedidoId);
        form.append('nuevo_estado', nuevoEstado);

        fetch('/cocina/estado', {
            method: 'POST',
            body: form
        })
        .then(function (resp) {
            // El controlador redirige → forzar recarga inmediata del tablero
            return actualizarTablero();
        })
        .catch(function () {
            btn.disabled = false;
            btn.textContent = '⚠ Error — reintentar';
        });
    });

    // ── Polling cada 3 s ────────────────────────────────────────────────
    setInterval(function () {
        if (syncActivo) actualizarTablero();
    }, INTERVALO_MS);

    function actualizarTablero() {
        return fetch('/cocina/pedidos-json')
            .then(function (resp) {
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                return resp.json();
            })
            .then(function (data) {
                if (!data.ok) throw new Error('Respuesta inválida');
                renderTablero(data.pedidos);
                marcarSincronizado(data.timestamp);
            })
            .catch(function () {
                marcarError();
            });
    }

    // ── Renderizar el tablero con los datos recibidos ──────────────────
    function renderTablero(pedidos) {
        const columnas = ['pendiente', 'en_preparacion', 'listo', 'entregado'];
        const ahora    = Math.floor(Date.now() / 1000);

        // Agrupar por estado
        const grupos = {};
        columnas.forEach(function (c) { grupos[c] = []; });
        pedidos.forEach(function (p) {
            if (grupos[p.estado] !== undefined) grupos[p.estado].push(p);
        });

        // Actualizar cada columna
        columnas.forEach(function (estado) {
            const columna   = document.querySelector('.kanban-columna[data-estado="' + estado + '"]');
            const contador  = document.querySelector('.kanban-contador[data-estado="' + estado + '"]');
            if (!columna) return;

            const grupo = grupos[estado];
            contador.textContent = grupo.length;

            if (grupo.length === 0) {
                columna.innerHTML = '<div class="text-center text-muted small py-4 kanban-vacio">'
                    + '<div class="fs-2 mb-1">' + iconoEstado(estado) + '</div>'
                    + 'Sin pedidos en este estado.</div>';
                return;
            }

            // Reconstruir tarjetas
            columna.innerHTML = grupo.map(function (ped) {
                const creado  = Math.floor(new Date(ped.creado_en).getTime() / 1000);
                const minutos = Math.round((ahora - creado) / 60);
                const urgente = (estado === 'pendiente' && minutos > 10)
                             || (estado === 'en_preparacion' && minutos > 20);

                const items = (ped.items || []).map(function (it) {
                    return '<li class="d-flex gap-1">'
                        + '<span class="badge bg-secondary text-white" style="min-width:24px;">' + esc(it.cantidad) + '×</span>'
                        + '<span>' + esc(it.plato) + '</span></li>';
                }).join('');

                const destino = ped.tipo === 'mesa'
                    ? '<span class="badge bg-dark px-2">🪑 Mesa #' + esc(ped.mesa_numero) + '</span>'
                      + (ped.mesa_zona ? ' <span class="text-muted">' + esc(ped.mesa_zona) + '</span>' : '')
                    : '<span class="badge bg-info text-dark px-2">🛍️ Para Llevar</span>';

                const btnTextos = {
                    pendiente:      '🔥 Iniciar Preparación',
                    en_preparacion: '🔔 Marcar Listo',
                    listo:          '✅ Marcar Entregado'
                };
                const proximoMap = {
                    pendiente:      'en_preparacion',
                    en_preparacion: 'listo',
                    listo:          'entregado'
                };
                const btnClasses = {
                    pendiente:      'btn-warning',
                    en_preparacion: 'btn-primary',
                    listo:          'btn-success'
                };

                const btnHtml = btnTextos[estado]
                    ? '<button type="button" class="btn btn-sm w-100 btn-avanzar-estado fw-semibold '
                      + (btnClasses[estado] || 'btn-secondary') + '" '
                      + 'style="min-height:44px;" '
                      + 'data-pedido-id="' + esc(ped.id) + '" '
                      + 'data-nuevo-estado="' + esc(proximoMap[estado]) + '">'
                      + btnTextos[estado] + '</button>'
                    : '<div class="text-center text-muted small mt-1"><em>Estado final</em></div>';

                return '<div class="card mb-2 border-0 shadow-sm' + (urgente ? ' border-danger border-2' : '') + '" '
                    + 'data-pedido-id="' + esc(ped.id) + '" data-estado="' + esc(estado) + '">'
                    + '<div class="card-body p-2">'
                    + '<div class="d-flex justify-content-between align-items-start mb-2">'
                    + '<div><span class="fw-bold text-dark">#' + esc(ped.id) + '</span>'
                    + (urgente ? '<span class="badge bg-danger ms-1 small">⚠ Demorado</span>' : '')
                    + '</div><small class="text-muted">' + minutos + 'min</small></div>'
                    + '<div class="mb-2 small">' + destino + ' <span class="text-muted ms-1">' + esc(ped.num_comensales) + ' pax</span></div>'
                    + (items ? '<ul class="list-unstyled mb-2 small">' + items + '</ul>' : '')
                    + btnHtml
                    + '</div></div>';
            }).join('');
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    function iconoEstado(estado) {
        const iconos = { pendiente: '⏳', en_preparacion: '🔥', listo: '🔔', entregado: '✅' };
        return iconos[estado] || '•';
    }

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function marcarSincronizado(timestamp) {
        const badge = document.getElementById('estado-sync');
        badge.className = 'badge bg-success ms-2';
        badge.textContent = '🟢 Sincronizado';
        document.getElementById('ultima-actualizacion').textContent =
            'Actualizado: ' + (timestamp || new Date().toLocaleTimeString('es-EC'));
    }

    function marcarError() {
        const badge = document.getElementById('estado-sync');
        badge.className = 'badge bg-danger ms-2';
        badge.textContent = '🔴 Sin conexión';
    }

})();
</script>
