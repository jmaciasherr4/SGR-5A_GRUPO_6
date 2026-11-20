<?php
use App\Core\Csrf;

$actionUrl = $esEdicion ? '/mesas/actualizar' : '/mesas/guardar';
$val = $mesa ?? [];
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-muted small mb-0"><?= $esEdicion ? 'Actualiza los datos de la mesa' : 'Configura una nueva mesa física en el restaurante' ?></p>
            </div>
            <a href="/mesas" class="btn btn-outline-secondary px-3">
                ← Volver a Mesas
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <form action="<?= $actionUrl ?>" method="POST" autocomplete="off" id="form-mesa">
                    <?= Csrf::renderInput('csrf_token_mesa') ?>

                    <?php if ($esEdicion): ?>
                        <input type="hidden" name="id" value="<?= (int)($val['id'] ?? 0) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="numero" class="form-label fw-semibold">Número de Mesa <span class="text-danger">*</span></label>
                        <input 
                            type="number" 
                            class="form-control" 
                            id="numero" 
                            name="numero" 
                            value="<?= htmlspecialchars((string)($val['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                            required 
                            min="1"
                            placeholder="ej. 13"
                        >
                        <div class="form-text small">Debe ser un identificador numérico único mayor a cero.</div>
                    </div>

                    <div class="mb-3">
                        <label for="capacidad" class="form-label fw-semibold">Capacidad de Personas <span class="text-danger">*</span></label>
                        <input 
                            type="number" 
                            class="form-control" 
                            id="capacidad" 
                            name="capacidad" 
                            value="<?= htmlspecialchars((string)($val['capacidad'] ?? '4'), ENT_QUOTES, 'UTF-8') ?>" 
                            required 
                            min="1"
                            max="30"
                            placeholder="ej. 4"
                        >
                        <div class="form-text small">Capacidad máxima recomendada de comensales (mayor a 0).</div>
                    </div>

                    <div class="mb-3">
                        <label for="zona" class="form-label fw-semibold">Zona del Restaurante <span class="text-danger">*</span></label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="zona" 
                            name="zona" 
                            list="lista-zonas"
                            value="<?= htmlspecialchars((string)($val['zona'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                            required 
                            minlength="2"
                            placeholder="ej. Salón Principal, Terraza, Balcón"
                        >
                        <datalist id="lista-zonas">
                            <?php foreach ($zonas as $z): ?>
                                <option value="<?= htmlspecialchars($z, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <div class="form-text small">Puedes elegir una zona existente o escribir el nombre de una nueva.</div>
                    </div>

                    <div class="mb-4">
                        <label for="estado" class="form-label fw-semibold">Estado Inicial <span class="text-danger">*</span></label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="libre" <?= ($val['estado'] ?? 'libre') === 'libre' ? 'selected' : '' ?>>🟢 Libre</option>
                            <option value="ocupada" <?= ($val['estado'] ?? '') === 'ocupada' ? 'selected' : '' ?>>🔴 Ocupada</option>
                            <option value="reservada" <?= ($val['estado'] ?? '') === 'reservada' ? 'selected' : '' ?>>🟡 Reservada</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="/mesas" class="btn btn-outline-secondary px-4 py-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm">
                            💾 <?= $esEdicion ? 'Actualizar Mesa' : 'Guardar Mesa' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
