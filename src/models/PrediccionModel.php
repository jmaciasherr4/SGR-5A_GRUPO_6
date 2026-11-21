<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Módulo de Predicciones (Fase 4)
 * ─────────────────────────────────────────────────────────────────────────────
 * Metodología: promedio de las ÚLTIMAS 4 SEMANAS del mismo día de la semana
 * (DOW 0=domingo … 6=sábado) relativo a la fecha objetivo.
 *
 * Requisitos implementados:
 *   P1 – Pedidos esperados por franja horaria (gráfico de barras)
 *   P4 – Platos más vendidos / unidades esperadas (gráfico horizontal + tabla)
 *   P6 – Venta total esperada del día en USD (tarjeta + sparkline semanal)
 *
 * Solo se consideran pedidos con estado = 'entregado'.
 * Todas las consultas usan SQL parametrizado con CTEs.
 */
class PrediccionModel
{
    private PDO $db;

    /** Número de semanas hacia atrás que se promedian */
    private const SEMANAS = 4;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // P1 — Pedidos esperados por franja horaria
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Devuelve la predicción de pedidos por hora para la fecha objetivo.
     *
     * Estructura de retorno:
     * [
     *   'dias_incluidos' => int,          // cuántas semanas reales se hallaron
     *   'advertencia'    => string|null,  // null si hay 4 semanas
     *   'datos'          => [
     *       ['hora' => int, 'promedio' => float, 'total_historico' => int], …
     *   ]
     * ]
     */
    public function p1PedidosPorHora(\DateTimeImmutable $fechaObjetivo): array
    {
        $dow = (int)$fechaObjetivo->format('w'); // 0=domingo … 6=sábado

        // Encontrar los hasta 4 días anteriores del mismo DOW
        $sql = "
            WITH dias_referencia AS (
                -- Los hasta :semanas días del mismo DOW en las últimas :semanas semanas
                SELECT DISTINCT creado_en::date AS fecha_ref
                FROM pedido
                WHERE estado = 'entregado'
                  AND EXTRACT(DOW FROM creado_en) = :dow
                  AND creado_en::date < :fecha_objetivo
                  AND creado_en::date >= :fecha_objetivo::date - (:semanas * 7)
                ORDER BY fecha_ref DESC
                LIMIT :semanas
            ),
            pedidos_referencia AS (
                SELECT
                    EXTRACT(HOUR FROM p.creado_en)::INT AS hora,
                    COUNT(*) AS conteo_pedidos,
                    p.creado_en::date AS fecha_dia
                FROM pedido p
                INNER JOIN dias_referencia dr ON p.creado_en::date = dr.fecha_ref
                WHERE p.estado = 'entregado'
                GROUP BY hora, fecha_dia
            ),
            dias_encontrados AS (
                SELECT COUNT(*) AS total FROM dias_referencia
            )
            SELECT
                h.hora,
                ROUND(AVG(COALESCE(pr.conteo_pedidos, 0)), 1)::FLOAT AS promedio,
                COALESCE(SUM(pr.conteo_pedidos), 0)::INT                AS total_historico,
                (SELECT total FROM dias_encontrados)::INT                AS dias_incluidos
            FROM (
                -- Generar todas las horas de servicio (10 a 22)
                SELECT generate_series(10, 22) AS hora
            ) h
            LEFT JOIN pedidos_referencia pr ON pr.hora = h.hora
            GROUP BY h.hora
            ORDER BY h.hora;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'dow'            => $dow,
            'fecha_objetivo' => $fechaObjetivo->format('Y-m-d'),
            'semanas'        => self::SEMANAS,
        ]);

        $filas = $stmt->fetchAll();

        $diasIncluidos = isset($filas[0]['dias_incluidos']) ? (int)$filas[0]['dias_incluidos'] : 0;

        return [
            'dias_incluidos' => $diasIncluidos,
            'advertencia'    => $diasIncluidos < self::SEMANAS
                ? "Solo se encontraron {$diasIncluidos} semana(s) de datos para este día de la semana. Se recomienda esperar más histórico para mayor precisión."
                : null,
            'datos'          => array_map(fn($f) => [
                'hora'              => (int)$f['hora'],
                'promedio'          => (float)$f['promedio'],
                'total_historico'   => (int)$f['total_historico'],
            ], $filas),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // P4 — Platos más vendidos (unidades esperadas)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Devuelve los N platos con mayor demanda proyectada para la fecha objetivo.
     *
     * Estructura de retorno:
     * [
     *   'dias_incluidos' => int,
     *   'advertencia'    => string|null,
     *   'datos'          => [
     *       ['plato_id' => int, 'nombre' => string, 'categoria' => string,
     *        'promedio_unidades' => float, 'total_historico' => int], …
     *   ]
     * ]
     */
    public function p4PlatosMasVendidos(\DateTimeImmutable $fechaObjetivo, int $limite = 10): array
    {
        $dow = (int)$fechaObjetivo->format('w');

        $sql = "
            WITH dias_referencia AS (
                SELECT DISTINCT creado_en::date AS fecha_ref
                FROM pedido
                WHERE estado = 'entregado'
                  AND EXTRACT(DOW FROM creado_en) = :dow
                  AND creado_en::date < :fecha_objetivo
                  AND creado_en::date >= :fecha_objetivo::date - (:semanas * 7)
                ORDER BY fecha_ref DESC
                LIMIT :semanas
            ),
            dias_encontrados AS (
                SELECT COUNT(*)::INT AS total FROM dias_referencia
            ),
            unidades_por_dia AS (
                SELECT
                    dp.plato_id,
                    p.creado_en::date AS fecha_dia,
                    SUM(dp.cantidad)::INT AS unidades
                FROM detalle_pedido dp
                INNER JOIN pedido p ON dp.pedido_id = p.id
                INNER JOIN dias_referencia dr ON p.creado_en::date = dr.fecha_ref
                WHERE p.estado = 'entregado'
                GROUP BY dp.plato_id, fecha_dia
            )
            SELECT
                pl.id                                                                AS plato_id,
                pl.nombre,
                cm.nombre                                                            AS categoria,
                ROUND(AVG(COALESCE(upd.unidades, 0)), 1)::FLOAT                     AS promedio_unidades,
                COALESCE(SUM(upd.unidades), 0)::INT                                 AS total_historico,
                (SELECT total FROM dias_encontrados)::INT                            AS dias_incluidos
            FROM plato pl
            INNER JOIN categoria_menu cm ON pl.categoria_id = cm.id
            LEFT JOIN unidades_por_dia upd ON upd.plato_id = pl.id
            WHERE pl.activo = true
            GROUP BY pl.id, pl.nombre, cm.nombre
            HAVING COALESCE(SUM(upd.unidades), 0) > 0
            ORDER BY promedio_unidades DESC
            LIMIT :limite;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'dow'            => $dow,
            'fecha_objetivo' => $fechaObjetivo->format('Y-m-d'),
            'semanas'        => self::SEMANAS,
            'limite'         => $limite,
        ]);

        $filas = $stmt->fetchAll();
        $diasIncluidos = isset($filas[0]['dias_incluidos']) ? (int)$filas[0]['dias_incluidos'] : 0;

        return [
            'dias_incluidos' => $diasIncluidos,
            'advertencia'    => $diasIncluidos < self::SEMANAS
                ? "Solo se encontraron {$diasIncluidos} semana(s) de datos para este día."
                : null,
            'datos' => array_map(fn($f) => [
                'plato_id'          => (int)$f['plato_id'],
                'nombre'            => $f['nombre'],
                'categoria'         => $f['categoria'],
                'promedio_unidades' => (float)$f['promedio_unidades'],
                'total_historico'   => (int)$f['total_historico'],
            ], $filas),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // P6 — Venta total esperada del día
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Devuelve la proyección de ventas totales (USD) para la fecha objetivo
     * más un desglose de los últimos 7 días para un sparkline comparativo.
     *
     * Estructura de retorno:
     * [
     *   'dias_incluidos'   => int,
     *   'advertencia'      => string|null,
     *   'promedio_venta'   => float,   // proyección para el día objetivo
     *   'maximo_historico' => float,
     *   'minimo_historico' => float,
     *   'ultimos_7_dias'   => [
     *       ['fecha' => string, 'dia_semana' => string, 'venta_total' => float], …
     *   ]
     * ]
     */
    public function p6VentasEsperadas(\DateTimeImmutable $fechaObjetivo): array
    {
        $dow = (int)$fechaObjetivo->format('w');

        // Consulta principal: promedio de ventas para ese DOW en las últimas 4 semanas
        $sqlPrincipal = "
            WITH dias_referencia AS (
                SELECT DISTINCT creado_en::date AS fecha_ref
                FROM pedido
                WHERE estado = 'entregado'
                  AND EXTRACT(DOW FROM creado_en) = :dow
                  AND creado_en::date < :fecha_objetivo
                  AND creado_en::date >= :fecha_objetivo::date - (:semanas * 7)
                ORDER BY fecha_ref DESC
                LIMIT :semanas
            ),
            ventas_por_dia AS (
                SELECT
                    p.creado_en::date AS fecha_dia,
                    SUM(p.total)      AS venta_dia
                FROM pedido p
                INNER JOIN dias_referencia dr ON p.creado_en::date = dr.fecha_ref
                WHERE p.estado = 'entregado'
                GROUP BY fecha_dia
            )
            SELECT
                ROUND(AVG(venta_dia), 2)::FLOAT  AS promedio_venta,
                ROUND(MAX(venta_dia), 2)::FLOAT  AS maximo_historico,
                ROUND(MIN(venta_dia), 2)::FLOAT  AS minimo_historico,
                COUNT(*)::INT                    AS dias_incluidos
            FROM ventas_por_dia;
        ";

        $stmtP = $this->db->prepare($sqlPrincipal);
        $stmtP->execute([
            'dow'            => $dow,
            'fecha_objetivo' => $fechaObjetivo->format('Y-m-d'),
            'semanas'        => self::SEMANAS,
        ]);
        $principal = $stmtP->fetch();

        // Consulta secundaria: ventas reales de los últimos 7 días (para sparkline)
        $sqlSpark = "
            SELECT
                p.creado_en::date                                   AS fecha,
                TO_CHAR(p.creado_en, 'Day')                         AS dia_semana,
                ROUND(SUM(p.total), 2)::FLOAT                       AS venta_total
            FROM pedido p
            WHERE p.estado = 'entregado'
              AND p.creado_en::date >= CURRENT_DATE - 7
              AND p.creado_en::date < :fecha_objetivo
            GROUP BY p.creado_en::date, dia_semana
            ORDER BY fecha ASC;
        ";
        $stmtS = $this->db->prepare($sqlSpark);
        $stmtS->execute(['fecha_objetivo' => $fechaObjetivo->format('Y-m-d')]);
        $spark = $stmtS->fetchAll();

        $diasIncluidos = (int)($principal['dias_incluidos'] ?? 0);

        return [
            'dias_incluidos'   => $diasIncluidos,
            'advertencia'      => $diasIncluidos < self::SEMANAS
                ? "Solo se encontraron {$diasIncluidos} semana(s) de datos para este día."
                : null,
            'promedio_venta'   => (float)($principal['promedio_venta'] ?? 0),
            'maximo_historico' => (float)($principal['maximo_historico'] ?? 0),
            'minimo_historico' => (float)($principal['minimo_historico'] ?? 0),
            'ultimos_7_dias'   => array_map(fn($f) => [
                'fecha'       => $f['fecha'],
                'dia_semana'  => trim($f['dia_semana']),
                'venta_total' => (float)$f['venta_total'],
            ], $spark),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: nombre del día de la semana en español
    // ─────────────────────────────────────────────────────────────────────────
    public static function nombreDia(\DateTimeImmutable $fecha): string
    {
        $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        return $dias[(int)$fecha->format('w')];
    }
}
