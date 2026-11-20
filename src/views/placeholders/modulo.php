<?php
use App\Core\Auth;
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0 text-center py-5 px-3">
            <div class="card-body">
                <div class="display-3 mb-3">🚧</div>
                <h2 class="h3 fw-bold text-dark mb-2"><?= htmlspecialchars($moduloNombre, ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="mb-3">
                    <span class="badge bg-primary fs-6 px-3 py-2"><?= htmlspecialchars($fase, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <p class="text-muted lead fs-6 mb-4">
                    <?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div class="alert alert-info d-inline-block text-start small mb-4">
                    <strong>Acceso por rol verificado:</strong> Tienes permiso para ver este módulo con tu rol actual 
                    <span class="badge bg-dark"><?= htmlspecialchars(Auth::role() ?? '', ENT_QUOTES, 'UTF-8') ?></span>.
                </div>
                <div>
                    <a href="<?= Auth::getHomeRoute() ?>" class="btn btn-outline-primary px-4 py-2">
                        ← Volver a Mi Panel Principal
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
