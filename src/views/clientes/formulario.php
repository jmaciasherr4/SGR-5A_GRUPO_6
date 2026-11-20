<?php
use App\Core\Csrf;

$actionUrl = $esEdicion ? '/clientes/actualizar' : '/clientes/guardar';
$val = $cliente ?? [];
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
        
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-muted small mb-0"><?= $esEdicion ? 'Actualiza los datos del cliente registrado' : 'Ingresa los datos para registrar un nuevo cliente en el sistema' ?></p>
            </div>
            <a href="/clientes" class="btn btn-outline-secondary px-3">
                ← Volver al Directorio
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <form action="<?= $actionUrl ?>" method="POST" autocomplete="off" id="form-cliente">
                    <?= Csrf::renderInput('csrf_token_cliente') ?>

                    <?php if ($esEdicion): ?>
                        <input type="hidden" name="id" value="<?= (int)($val['id'] ?? 0) ?>">
                    <?php endif; ?>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="nombres" class="form-label fw-semibold">Nombres <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="nombres" 
                                name="nombres" 
                                value="<?= htmlspecialchars((string)($val['nombres'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                                required 
                                minlength="2"
                                placeholder="ej. María José"
                            >
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="apellidos" class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="apellidos" 
                                name="apellidos" 
                                value="<?= htmlspecialchars((string)($val['apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                                required 
                                minlength="2"
                                placeholder="ej. Andrade Loor"
                            >
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="documento" class="form-label fw-semibold">Cédula / Documento (10 dígitos) <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="documento" 
                                name="documento" 
                                value="<?= htmlspecialchars((string)($val['documento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                                required 
                                pattern="[0-9]{10}"
                                maxlength="10"
                                placeholder="ej. 0912345678"
                            >
                            <div class="form-text small">Cédula ecuatoriana de 10 dígitos numéricos (única).</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="telefono" class="form-label fw-semibold">Teléfono de Contacto</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="telefono" 
                                name="telefono" 
                                value="<?= htmlspecialchars((string)($val['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                                placeholder="ej. 0987654321"
                            >
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="/clientes" class="btn btn-outline-secondary px-4 py-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm">
                            💾 <?= $esEdicion ? 'Actualizar Cliente' : 'Guardar Cliente' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
