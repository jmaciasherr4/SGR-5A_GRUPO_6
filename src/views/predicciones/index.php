<?php
/**
 * Vista Predicciones — Fase 4 (P1, P4, P6)
 * Variables disponibles: $titulo, $fechaObjetivo, $fechaStr, $nombreDia,
 *                        $hoy, $p1, $p4, $p6, $errorP1, $errorP4, $errorP6
 *
 * Chart.js se carga únicamente en esta vista (CDN, según PROMPT MAESTRO).
 * Etiqueta obligatoria "Datos de demostración" visible en todo momento.
 */

// Preparar datos para Chart.js de forma segura (JSON codificado)
$p1Labels   = [];
$p1Valores  = [];
if ($p1 && !empty($p1['datos'])) {
    foreach ($p1['datos'] as $fila) {
        $p1Labels[]  = sprintf('%02d:00', $fila['hora']);
        $p1Valores[] = round($fila['promedio'], 1);
    }
}

$p4Labels  = [];
$p4Valores = [];
if ($p4 && !empty($p4['datos'])) {
    foreach ($p4['datos'] as $fila) {
        $p4Labels[]  = $fila['nombre'];
        $p4Valores[] = round($fila['promedio_unidades'], 1);
    }
}

$p6SparkLabels  = [];
$p6SparkValores = [];
if ($p6 && !empty($p6['ultimos_7_dias'])) {
    foreach ($p6['ultimos_7_dias'] as $fila) {
        $p6SparkLabels[]  = $fila['fecha'];
        $p6SparkValores[] = $fila['venta_total'];
    }
}
?>

<!-- Chart.js CDN — solo en este módulo (PROMPT MAESTRO §Stack) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<!-- ═══════════════════════════════════════════════════════════
     ENCABEZADO DEL MÓDULO
════════════════════════════════════════════════════════════ -->
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">
            📊 Predicciones Analíticas
            <!-- Etiqueta obligatoria "Datos de demostración" -->
            <span class="badge bg-warning text-dark ms-2 fs-6 align-middle">📂 Datos de demostración</span>
        </h1>
        <p class="text-muted mb-0 small">
            Proyecciones basadas en el promedio de las últimas 4 semanas del mismo día de la semana.
            Solo accesible para el rol <strong>Administrador</strong>.
        </p>
    </div>
    <div>
        <!-- Selector de fecha objetivo -->
        <form action="/predicciones" method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <label for="fecha" class="form-label mb-0 fw-semibold small">Fecha a predecir:</label>
            <input type="date" name="fecha" id="fecha" class="form-control form-control-sm"
                   style="min-height:38px;width:160px;"
                   value="<?= htmlspecialchars($fechaStr, ENT_QUOTES, 'UTF-8') ?>"
                   min="<?= htmlspecialchars($hoy, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-primary btn-sm px-3" style="min-height:38px;">
                🔍 Calcular
            </button>
        </form>
    </div>
</div>

<!-- Tarjeta de contexto: fecha seleccionada y metodología -->
<div class="alert alert-info d-flex align-items-start gap-3 mb-4" role="alert">
    <span class="fs-3">📅</span>
    <div>
        <strong>Predicción para:</strong>
        <?= htmlspecialchars($nombreDia . ', ' . $fechaObjetivo->format('d/m/Y'), ENT_QUOTES, 'UTF-8') ?>
        &mdash;
        <strong>Metodología:</strong> promedio de las últimas <strong>4 semanas</strong>
        del mismo día de la semana (<?= htmlspecialchars($nombreDia, ENT_QUOTES, 'UTF-8') ?>s anteriores).
        Los resultados son estimaciones basadas en <em>datos de demostración</em>.
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════
     P6 — VENTA TOTAL ESPERADA DEL DÍA (tarjeta grande + sparkline)
════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="card-title fw-bold mb-0">
                        💰 P6 — Venta Total Esperada del Día
                    </h5>
                    <small class="text-muted">Total en USD proyectado para el día seleccionado</small>
                </div>
                <span class="badge bg-warning text-dark">📂 Datos de demostración</span>
            </div>
            <div class="card-body">
                <?php if ($errorP6): ?>
                    <div class="alert alert-danger mb-0">❌ Error: <?= htmlspecialchars($errorP6, ENT_QUOTES, 'UTF-8') ?></div>
                <?php elseif ($p6): ?>
                    <?php if ($p6['advertencia']): ?>
                        <div class="alert alert-warning d-flex gap-2 align-items-start mb-3">
                            <span>⚠️</span>
                            <div>
                                <strong>Datos insuficientes:</strong>
                                <?= htmlspecialchars($p6['advertencia'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="row g-4 align-items-center">
                        <!-- Métricas principales -->
                        <div class="col-12 col-md-5">
                            <div class="text-center py-3">
                                <div class="text-muted small mb-1">Venta proyectada</div>
                                <div class="display-4 fw-bold text-success font-monospace">
                                    $<?= number_format($p6['promedio_venta'], 2) ?>
                                </div>
                                <div class="text-muted">USD</div>
                            </div>
                            <div class="row g-2 mt-2 text-center">
                                <div class="col-6">
                                    <div class="bg-light rounded p-3">
                                        <div class="small text-muted">Máximo histórico</div>
                                        <div class="fw-bold text-primary font-monospace">$<?= number_format($p6['maximo_historico'], 2) ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light rounded p-3">
                                        <div class="small text-muted">Mínimo histórico</div>
                                        <div class="fw-bold text-secondary font-monospace">$<?= number_format($p6['minimo_historico'], 2) ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    Basado en <strong><?= (int)$p6['dias_incluidos'] ?></strong> <?= $p6['dias_incluidos'] === 1 ? 'semana' : 'semanas' ?> de datos históricos
                                </small>
                            </div>
                        </div>
                        <!-- Sparkline: últimos 7 días reales -->
                        <div class="col-12 col-md-7">
                            <div class="small text-muted text-center mb-2">Ventas reales de los últimos 7 días</div>
                            <?php if (!empty($p6['ultimos_7_dias'])): ?>
                                <canvas id="grafico-p6" height="120" aria-label="Ventas últimos 7 días" role="img"></canvas>
                            <?php else: ?>
                                <div class="text-center text-muted py-4 small">Sin datos recientes para el sparkline.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════
     P1 y P4 lado a lado
════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">

    <!-- P1 — Pedidos por franja horaria -->
    <div class="col-12 col-xl-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="card-title fw-bold mb-0">🕐 P1 — Pedidos Esperados por Hora</h5>
                    <small class="text-muted">Distribución horaria proyectada (10:00 – 22:00)</small>
                </div>
                <span class="badge bg-warning text-dark">📂 Datos de demostración</span>
            </div>
            <div class="card-body">
                <?php if ($errorP1): ?>
                    <div class="alert alert-danger mb-0">❌ Error: <?= htmlspecialchars($errorP1, ENT_QUOTES, 'UTF-8') ?></div>
                <?php elseif ($p1): ?>
                    <?php if ($p1['advertencia']): ?>
                        <div class="alert alert-warning d-flex gap-2 align-items-start mb-3 small">
                            <span>⚠️</span>
                            <span><?= htmlspecialchars($p1['advertencia'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="mb-2 small text-muted text-end">
                        Basado en <?= (int)$p1['dias_incluidos'] ?> semana(s) · Método: promedio por hora
                    </div>
                    <canvas id="grafico-p1" height="220" aria-label="Pedidos esperados por hora" role="img"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- P4 — Platos más vendidos -->
    <div class="col-12 col-xl-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="card-title fw-bold mb-0">🍽️ P4 — Platos Más Demandados</h5>
                    <small class="text-muted">Top 10 unidades proyectadas</small>
                </div>
                <span class="badge bg-warning text-dark">📂 Datos de demostración</span>
            </div>
            <div class="card-body">
                <?php if ($errorP4): ?>
                    <div class="alert alert-danger mb-0">❌ Error: <?= htmlspecialchars($errorP4, ENT_QUOTES, 'UTF-8') ?></div>
                <?php elseif ($p4): ?>
                    <?php if ($p4['advertencia']): ?>
                        <div class="alert alert-warning d-flex gap-2 align-items-start mb-3 small">
                            <span>⚠️</span>
                            <span><?= htmlspecialchars($p4['advertencia'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="mb-2 small text-muted text-end">
                        Basado en <?= (int)$p4['dias_incluidos'] ?> semana(s) · Top 10 platos activos
                    </div>
                    <?php if (!empty($p4['datos'])): ?>
                        <canvas id="grafico-p4" height="300" aria-label="Platos más vendidos" role="img"></canvas>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>


<!-- ═══════════════════════════════════════════════════════════
     TABLA DETALLADA P4
════════════════════════════════════════════════════════════ -->
<?php if ($p4 && !empty($p4['datos'])): ?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold mb-0">📋 Detalle P4 — Platos con Mayor Proyección de Demanda</h6>
        <span class="badge bg-warning text-dark">📂 Datos de demostración</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:40px;">#</th>
                        <th>Plato</th>
                        <th>Categoría</th>
                        <th class="text-center">Prom. Unidades</th>
                        <th class="text-center">Histórico (4 sem.)</th>
                        <th class="pe-3" style="width:140px;">Barra</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $maxUnidades = !empty($p4['datos']) ? max(array_column($p4['datos'], 'promedio_unidades')) : 1;
                    foreach ($p4['datos'] as $i => $fila):
                        $pct = $maxUnidades > 0 ? round(($fila['promedio_unidades'] / $maxUnidades) * 100) : 0;
                    ?>
                        <tr>
                            <td class="ps-3 text-muted fw-bold"><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($fila['categoria'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="text-center fw-bold font-monospace text-primary"><?= number_format($fila['promedio_unidades'], 1) ?></td>
                            <td class="text-center text-muted"><?= (int)$fila['total_historico'] ?> u.</td>
                            <td class="pe-3">
                                <div class="progress" style="height:10px;" title="<?= $pct ?>%">
                                    <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════════════════
     PIE: metodología y nota de demostración
════════════════════════════════════════════════════════════ -->
<div class="alert alert-secondary small mb-0" role="note">
    <strong>📌 Metodología:</strong>
    Las predicciones se calculan con <em>consultas SQL parametrizadas y CTEs en PostgreSQL</em>,
    promediando los datos de las últimas <strong>4 semanas</strong> del mismo día de la semana.
    Todos los datos mostrados provienen del conjunto de <strong>demostración</strong> generado con
    <code>setseed(0.42)</code> y no representan ventas reales.
    <span class="badge bg-warning text-dark ms-1">📂 Datos de demostración</span>
</div>


<!-- ═══════════════════════════════════════════════════════════
     INICIALIZACIÓN DE GRÁFICOS Chart.js
════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    // Paleta consistente con Bootstrap 5
    const COLOR_AZUL    = 'rgba(13,  110, 253, 0.8)';
    const COLOR_VERDE   = 'rgba(25,  135,  84, 0.8)';
    const COLOR_BORDE_A = 'rgba(13,  110, 253, 1)';
    const COLOR_BORDE_V = 'rgba(25,  135,  84, 1)';

    // ── P1: Pedidos por hora (gráfico de barras vertical) ─────────────────
    <?php if ($p1 && !empty($p1['datos']) && !empty($p1Labels)): ?>
    (function () {
        const ctx = document.getElementById('grafico-p1');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($p1Labels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: 'Pedidos esperados',
                    data:  <?= json_encode($p1Valores) ?>,
                    backgroundColor: <?= json_encode(array_map(function($h) {
                        // Resaltar franjas pico (12-14 y 19-21) en verde
                        return ($h >= 12 && $h <= 14) || ($h >= 19 && $h <= 21)
                            ? 'rgba(25,135,84,0.8)' : 'rgba(13,110,253,0.6)';
                    }, array_column($p1['datos'], 'hora'))) ?>,
                    borderColor:     <?= json_encode(array_map(function($h) {
                        return ($h >= 12 && $h <= 14) || ($h >= 19 && $h <= 21)
                            ? 'rgba(25,135,84,1)' : 'rgba(13,110,253,1)';
                    }, array_column($p1['datos'], 'hora'))) ?>,
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.parsed.y.toFixed(1) + ' pedidos esperados';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        title: { display: true, text: 'Pedidos (promedio)' }
                    },
                    x: {
                        title: { display: true, text: 'Hora del día' }
                    }
                }
            }
        });
    })();
    <?php endif; ?>

    // ── P4: Platos más vendidos (barras horizontales) ────────────────────
    <?php if ($p4 && !empty($p4['datos']) && !empty($p4Labels)): ?>
    (function () {
        const ctx = document.getElementById('grafico-p4');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($p4Labels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: 'Unidades esperadas',
                    data:  <?= json_encode($p4Valores) ?>,
                    backgroundColor: COLOR_AZUL,
                    borderColor:     COLOR_BORDE_A,
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.parsed.x.toFixed(1) + ' unidades esperadas';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: { display: true, text: 'Unidades (promedio)' }
                    }
                }
            }
        });
    })();
    <?php endif; ?>

    // ── P6: Sparkline ventas últimos 7 días (línea) ───────────────────────
    <?php if ($p6 && !empty($p6['ultimos_7_dias']) && !empty($p6SparkLabels)): ?>
    (function () {
        const ctx = document.getElementById('grafico-p6');
        if (!ctx) return;
        // Línea de proyección (valor constante horizontal)
        const proyeccion = <?= json_encode($p6['promedio_venta']) ?>;
        const nPuntos    = <?= count($p6SparkLabels) ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($p6SparkLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [
                    {
                        label:           'Venta real',
                        data:            <?= json_encode($p6SparkValores) ?>,
                        borderColor:     COLOR_BORDE_V,
                        backgroundColor: 'rgba(25,135,84,0.15)',
                        tension:         0.3,
                        fill:            true,
                        pointRadius:     4,
                        pointHoverRadius: 6,
                    },
                    {
                        label:       'Proyección',
                        data:        Array(nPuntos).fill(proyeccion),
                        borderColor: 'rgba(255,193,7,1)',
                        borderDash:  [6, 4],
                        borderWidth: 2,
                        pointRadius: 0,
                        fill:        false,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' $' + ctx.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: { display: true, text: 'USD' }
                    }
                }
            }
        });
    })();
    <?php endif; ?>

})();
</script>
