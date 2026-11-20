<?php
use App\Core\Csrf;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted small mb-0">Organización y clasificación de los platos del restaurante</p>
    </div>
    <a href="/menu" class="btn btn-outline-secondary px-3">
        ← Volver al Menú
    </a>
</div>

<div class="row g-4">
    <!-- Formulario para Nueva Categoría -->
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">Agregar Categoría</h6>
            </div>
            <div class="card-body p-4">
                <form action="/menu/categorias/guardar" method="POST" autocomplete="off" id="form-categoria">
                    <?= Csrf::renderInput('csrf_token_cat') ?>

                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-semibold">Nombre de la Categoría <span class="text-danger">*</span></label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="nombre" 
                            name="nombre" 
                            required 
                            minlength="2" 
                            placeholder="ej. Bebidas Calientes"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100 shadow-sm py-2">
                        ➕ Guardar Categoría
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Listado de Categorías Existentes -->
    <div class="col-12 col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">Categorías Registradas</h6>
                <span class="badge bg-light text-muted border">Total: <?= count($categorias) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 60px;">#</th>
                                <th>Nombre</th>
                                <th class="text-center" style="width: 140px;">Platos Asociados</th>
                                <th class="text-end pe-3" style="width: 120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $cat): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-muted"><?= (int)$cat['id'] ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= (int)$cat['total_platos'] ?> platos</span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <form action="/menu/categorias/eliminar" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría? Si contiene platos asociados será bloqueada.');">
                                            <?= Csrf::renderInput() ?>
                                            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm px-2" title="Eliminar categoría">
                                                🗑️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
