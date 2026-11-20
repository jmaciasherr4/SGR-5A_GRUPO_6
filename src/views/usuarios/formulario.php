<?php
use App\Core\Csrf;

$actionUrl = $esEdicion ? '/usuarios/actualizar' : '/usuarios/guardar';
$val = $usuario ?? [];
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
        
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-muted small mb-0"><?= $esEdicion ? 'Actualiza los datos del usuario seleccionado' : 'Completa todos los campos obligatorios para registrar un nuevo usuario' ?></p>
            </div>
            <a href="/usuarios" class="btn btn-outline-secondary px-3">
                ← Volver al Listado
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <form action="<?= $actionUrl ?>" method="POST" autocomplete="off" id="form-usuario">
                    <!-- Token CSRF específico del formulario de usuario -->
                    <?= Csrf::renderInput('csrf_token_usuario') ?>

                    <?php if ($esEdicion): ?>
                        <input type="hidden" name="id" value="<?= (int)($val['id'] ?? 0) ?>">
                    <?php endif; ?>

                    <h5 class="fw-bold text-primary mb-3">1. Datos Personales</h5>
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
                                placeholder="ej. Carlos Alberto"
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
                                placeholder="ej. Gómez Mendoza"
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
                            <div class="form-text small">Exactamente 10 números, sin guiones ni espacios.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="telefono" class="form-label fw-semibold">Teléfono</label>
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

                    <hr class="my-4">

                    <h5 class="fw-bold text-primary mb-3">2. Credenciales y Permisos del Sistema</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="username" class="form-label fw-semibold">Nombre de Usuario <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="username" 
                                name="username" 
                                value="<?= htmlspecialchars((string)($val['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                                required 
                                minlength="3" 
                                maxlength="50"
                                placeholder="ej. carlosa"
                            >
                            <div class="form-text small">Único en el sistema (mínimo 3 caracteres).</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="rol_id" class="form-label fw-semibold">Rol Asignado <span class="text-danger">*</span></label>
                            <select class="form-select" id="rol_id" name="rol_id" required>
                                <option value="">-- Seleccionar Rol --</option>
                                <?php foreach ($roles as $r): ?>
                                    <option 
                                        value="<?= (int)$r['id'] ?>" 
                                        <?= (int)($val['rol_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars(ucfirst($r['nombre']), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="password" class="form-label fw-semibold">
                                <?= $esEdicion ? 'Nueva Contraseña (opcional)' : 'Contraseña de Acceso' ?> 
                                <?= $esEdicion ? '' : '<span class="text-danger">*</span>' ?>
                            </label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password" 
                                name="password" 
                                <?= $esEdicion ? '' : 'required' ?> 
                                minlength="8"
                                placeholder="Mínimo 8 caracteres"
                            >
                            <div class="form-text small">
                                <?= $esEdicion ? 'Déjalo en blanco si deseas mantener la contraseña actual. Si ingresas una nueva, debe tener al menos 8 caracteres.' : 'La contraseña debe contener un mínimo de 8 caracteres.' ?>
                            </div>
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
                                    Usuario Activo (Permite iniciar sesión en el sistema)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="/usuarios" class="btn btn-outline-secondary px-4 py-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm">
                            💾 <?= $esEdicion ? 'Guardar Cambios' : 'Crear Usuario' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
