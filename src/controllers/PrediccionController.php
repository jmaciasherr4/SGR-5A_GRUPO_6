<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\PrediccionModel;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * PrediccionController — Fase 4 (P1, P4, P6)
 * Solo accesible para el rol 'administrador' (RF-03).
 */
class PrediccionController
{
    private PrediccionModel $model;
    private DateTimeZone    $tz;

    public function __construct()
    {
        Auth::requireRole('administrador');
        $this->model = new PrediccionModel();
        $this->tz    = new DateTimeZone('America/Guayaquil');
    }

    public function index(): void
    {
        $hoy = new DateTimeImmutable('today', $this->tz);

        // ── Fecha objetivo: viene por GET o usamos mañana (próximo día con demanda)
        $fechaStr = trim((string)($_GET['fecha'] ?? ''));
        if ($fechaStr !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaStr)) {
            try {
                $fechaObjetivo = new DateTimeImmutable($fechaStr, $this->tz);
            } catch (Throwable) {
                $fechaObjetivo = $hoy->modify('+1 day');
            }
        } else {
            // Por defecto: mañana (el caso de uso típico: preparar el día siguiente)
            $fechaObjetivo = $hoy->modify('+1 day');
        }

        // ── Ejecutar las tres predicciones ────────────────────────────────
        $errorP1 = $errorP4 = $errorP6 = null;
        $p1 = $p4 = $p6 = null;

        try {
            $p1 = $this->model->p1PedidosPorHora($fechaObjetivo);
        } catch (Throwable $e) {
            $errorP1 = $e->getMessage();
        }

        try {
            $p4 = $this->model->p4PlatosMasVendidos($fechaObjetivo, 10);
        } catch (Throwable $e) {
            $errorP4 = $e->getMessage();
        }

        try {
            $p6 = $this->model->p6VentasEsperadas($fechaObjetivo);
        } catch (Throwable $e) {
            $errorP6 = $e->getMessage();
        }

        // ── Nombre del día en español para la UI ──────────────────────────
        $nombreDia = PrediccionModel::nombreDia($fechaObjetivo);

        View::render('predicciones/index', [
            'titulo'        => 'Predicciones Analíticas',
            'fechaObjetivo' => $fechaObjetivo,
            'fechaStr'      => $fechaObjetivo->format('Y-m-d'),
            'nombreDia'     => $nombreDia,
            'hoy'           => $hoy->format('Y-m-d'),
            'p1'            => $p1,
            'p4'            => $p4,
            'p6'            => $p6,
            'errorP1'       => $errorP1,
            'errorP4'       => $errorP4,
            'errorP6'       => $errorP6,
        ]);
    }
}
