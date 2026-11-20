<?php
use App\Core\Csrf;

$actionUrl = $esEdicion ? '/menu/actualizar' : '/menu/guardar';
$val = $plato ?? [];
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
        
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-muted small mb-0"><?= $esEdicion ? 'Actualiza los datos del plato y su precio de venta' : 'Registra un nuevo plato en la carta del restaurante' ?></p>
            </div>
            <a href="/menu" class="btn btn-outline-secondary px-3">
                ← Volver al Menú
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <form action="<?= $actionUrl ?>" method="POST" autocomplete="off" id="form-plato">
                    <?= Csrf::renderInput('csrf_token_plato') ?>

                    <?php if ($esEdicion): ?>
                        <input type="hidden" name="id" value="<?= (int)($val['id'] ?? 0) ?>">
                    <?php endif; ?>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="categoria_id" class="form-label fw-semibold">Categoría del Menú <span class="text-danger">*</span></label>
                            <select class="form-select" id="categoria_id" name="categoria_id" required>
                                <option value="">-- Seleccionar Categoría --</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option 
                                        value="<?= (int)$cat['id'] ?>" 
                                        <?= (int)($val['categoria_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="precio" class="form-label fw-semibold">Precio de Venta ($ USD) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input 
                                    type="number" 
                                    class="form-control" 
                                    id="precio" 
                                    name="precio" 
                                    step="0.01" 
                                    min="0.01" 
                                    value="<?= htmlspecialchars((string)($val['precio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                                    required 
                                    placeholder="ej. 5.50"
                                >
                            </div>
                            <div class="form-text small">Debe ser un valor positivo mayor a 0 (CHECK precio > 0).</div>
                        </div>

                        <div class="col-12">
                            <label for="nombre" class="form-label fw-semibold">Nombre del Plato <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="nombre" 
                                name="nombre" 
                                value="<?= htmlspecialchars((string)($val['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                                required 
                                minlength="3" 
                                placeholder="ej. Encebollado Mixto Especial"
                            >
                        </div>

                        <div class="col-12">
                            <label for="descripcion" class="form-label fw-semibold">Descripción o Ingredientes</label>
                            <textarea 
                                class="form-control" 
                                id="descripcion" 
                                name="descripcion" 
                                rows="3" 
                                placeholder="Breve detalle de la preparación o guarniciones..."
                            ><?= htmlspecialchars((string)($val['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch pt-2">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    role="switch" 
                                    id="activo" 
                                    name="activo" 
                                    value="1" 
                                    <?= (!isset($val['activo']) || $val['activo']) ? 'checked' : '' ?>
                                >
                                <label class="form-check-label fw-semibold" for="activo">
                                    Plato Activo (Disponible para ser ordenado en pedidos)
                                </label>
                            </div>
                            <div class="form-text small">Si se desactiva, no aparecerá en la toma de pedidos, pero conservará su historial de ventas pasadas.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="/menu" class="btn btn-outline-secondary px-4 py-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm">
                            💾 <?= $esEdicion ? 'Actualizar Plato' : 'Guardar Plato' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
