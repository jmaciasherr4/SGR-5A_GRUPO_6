# Documentación del Módulo de Predicciones — Fase 4

Materia: **Administración de Bases de Datos (UTEQ)**  
Proyecto: **Sistema Web de Gestión de Restaurante**

> [!NOTE]
> Todos los datos mostrados en este módulo provienen del conjunto de demostración
> generado con `setseed(0.42)` en `03_datos_historicos.sql`.
> La etiqueta **"Datos de demostración"** es visible en cada sección de la interfaz.

---

## Metodología General

El sistema implementa predicciones basadas en el **promedio de las últimas 4 semanas
del mismo día de la semana** relativo a la fecha objetivo seleccionada por el administrador.

| Criterio | Detalle |
|---|---|
| Ventana histórica | Últimas 4 semanas (28 días) |
| Agrupación | Por día de la semana (DOW 0=domingo … 6=sábado) |
| Fuente de datos | Tabla `pedido` con `estado = 'entregado'` |
| Implementación SQL | CTEs parametrizadas con PDO (sin concatenación) |
| Alerta de suficiencia | Se muestra si hay menos de 4 semanas disponibles |
| Visualización | Chart.js 4.4.3 (CDN, solo en este módulo) |

---

## 1. P1 — Pedidos por Franja Horaria

**Objetivo:** Proyectar la cantidad esperada de pedidos agrupados por hora del día
(10:00 a 22:00) para ayudar al personal a preparar el volumen de atención.

**Consulta base:**
```sql
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
pedidos_referencia AS (
    SELECT EXTRACT(HOUR FROM p.creado_en)::INT AS hora,
           COUNT(*) AS conteo_pedidos,
           p.creado_en::date AS fecha_dia
    FROM pedido p
    INNER JOIN dias_referencia dr ON p.creado_en::date = dr.fecha_ref
    WHERE p.estado = 'entregado'
    GROUP BY hora, fecha_dia
)
SELECT h.hora,
       ROUND(AVG(COALESCE(pr.conteo_pedidos, 0)), 1) AS promedio,
       COALESCE(SUM(pr.conteo_pedidos), 0)::INT AS total_historico
FROM (SELECT generate_series(10, 22) AS hora) h
LEFT JOIN pedidos_referencia pr ON pr.hora = h.hora
GROUP BY h.hora
ORDER BY h.hora;
```

**Visualización:** Gráfico de barras verticales. Franjas pico (12–14 h y 19–21 h)
se resaltan en verde; el resto en azul.

---

## 2. P4 — Platos Más Vendidos (Unidades Esperadas)

**Objetivo:** Proyectar la demanda de los 10 platos activos más pedidos
para facilitar la planificación de cocina e inventario.

**Consulta base:**
```sql
WITH dias_referencia AS ( ... ),
unidades_por_dia AS (
    SELECT dp.plato_id, p.creado_en::date AS fecha_dia,
           SUM(dp.cantidad)::INT AS unidades
    FROM detalle_pedido dp
    INNER JOIN pedido p ON dp.pedido_id = p.id
    INNER JOIN dias_referencia dr ON p.creado_en::date = dr.fecha_ref
    WHERE p.estado = 'entregado'
    GROUP BY dp.plato_id, fecha_dia
)
SELECT pl.id, pl.nombre, cm.nombre AS categoria,
       ROUND(AVG(COALESCE(upd.unidades, 0)), 1) AS promedio_unidades,
       COALESCE(SUM(upd.unidades), 0)::INT AS total_historico
FROM plato pl
INNER JOIN categoria_menu cm ON pl.categoria_id = cm.id
LEFT JOIN unidades_por_dia upd ON upd.plato_id = pl.id
WHERE pl.activo = true
GROUP BY pl.id, pl.nombre, cm.nombre
HAVING COALESCE(SUM(upd.unidades), 0) > 0
ORDER BY promedio_unidades DESC
LIMIT :limite;
```

**Visualización:** Gráfico de barras horizontales (Chart.js `indexAxis: 'y'`)
+ tabla de detalle con barra de progreso relativa al máximo.

---

## 3. P6 — Ventas Esperadas del Día (Total Facturado en USD)

**Objetivo:** Estimar los ingresos totales del día seleccionado
para control de caja y planificación financiera.

**Consulta base:**
```sql
WITH dias_referencia AS ( ... ),
ventas_por_dia AS (
    SELECT p.creado_en::date AS fecha_dia, SUM(p.total) AS venta_dia
    FROM pedido p
    INNER JOIN dias_referencia dr ON p.creado_en::date = dr.fecha_ref
    WHERE p.estado = 'entregado'
    GROUP BY fecha_dia
)
SELECT ROUND(AVG(venta_dia), 2) AS promedio_venta,
       ROUND(MAX(venta_dia), 2) AS maximo_historico,
       ROUND(MIN(venta_dia), 2) AS minimo_historico,
       COUNT(*)::INT            AS dias_incluidos
FROM ventas_por_dia;
```

**Visualización:**
- Tarjeta grande con la proyección en USD.
- Métricas secundarias: máximo y mínimo histórico.
- Sparkline de línea con los últimos 7 días reales vs. línea de proyección (punteada).

---

## Archivos Implementados (Fase 4)

| Archivo | Rol |
|---|---|
| `src/models/PrediccionModel.php` | 3 métodos CTE parametrizados (P1, P4, P6) |
| `src/controllers/PrediccionController.php` | Selector de fecha, 3 llamadas al modelo, render de vista |
| `src/views/predicciones/index.php` | Panel completo con Chart.js; etiqueta "Datos de demostración" |
| `public/index.php` | Ruta `GET /predicciones` → `PrediccionController::index` |

---

## Otras Predicciones (Fuera de Alcance en esta Etapa)

Las predicciones de los grupos A, B y el resto de C, D, E quedan documentadas
como trabajo futuro según los lineamientos de la rúbrica docente.
