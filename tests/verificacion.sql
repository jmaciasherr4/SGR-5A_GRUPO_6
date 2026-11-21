-- ============================================================================
-- verificacion.sql: Consultas de verificación para DBeaver / psql
-- Proyecto: Sistema Web de Gestión de Restaurante — UTEQ
-- Uso: Abrir en DBeaver conectado a restaurante_db (localhost:5434)
--      o ejecutar con:
--        docker exec restaurante_db psql -U restaurante_user -d restaurante -f /docker-entrypoint-initdb.d/verificacion.sql
-- ============================================================================

-- ════════════════════════════════════════════════════════════
-- FASE 0 — Entorno y Conexión
-- ════════════════════════════════════════════════════════════

-- V-F0-01: Versión de PostgreSQL instalada
SELECT version();

-- V-F0-02: Zona horaria activa en la sesión
SHOW timezone;

-- V-F0-03: Extensión pgcrypto habilitada (necesaria para bcrypt)
SELECT extname, extversion FROM pg_extension WHERE extname = 'pgcrypto';

-- V-F0-04: Tablas creadas por el schema
SELECT tablename
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY tablename;

-- V-F0-05: Índices creados para optimización de consultas frecuentes
SELECT indexname, tablename
FROM pg_indexes
WHERE schemaname = 'public'
ORDER BY tablename, indexname;

-- ════════════════════════════════════════════════════════════
-- FASE 1 — Seguridad y Accesos (RF-01, RF-02, RF-03)
-- ════════════════════════════════════════════════════════════

-- V-RF01-01: Usuarios con rol y estado (contraseñas deben ser hash bcrypt, no texto plano)
SELECT u.id, u.username,
       r.nombre AS rol,
       LEFT(u.password_hash, 7) AS algoritmo_hash,  -- Debe mostrar '$2a$10'
       u.activo,
       u.creado_en
FROM usuario u
JOIN rol r ON u.rol_id = r.id
ORDER BY u.id;

-- V-RF01-02: Verificar que un usuario inactivo no puede autenticarse
-- (consulta que usa la misma lógica que el controlador de login)
SELECT u.id, u.username, u.activo
FROM usuario u
WHERE u.activo = false;

-- V-RF02-01: Datos completos de personas vinculadas a usuarios
SELECT u.id AS usuario_id, u.username, r.nombre AS rol,
       p.nombres, p.apellidos, p.documento, p.telefono
FROM usuario u
JOIN rol r ON u.rol_id = r.id
JOIN persona p ON u.persona_id = p.id
ORDER BY u.id;

-- V-RF02-02: Roles disponibles en el sistema
SELECT id, nombre FROM rol ORDER BY id;

-- V-RF03-01: Verificar matriz de roles (todos los roles deben existir)
SELECT nombre FROM rol
WHERE nombre IN ('administrador', 'mesero', 'cajero', 'cocinero');

-- ════════════════════════════════════════════════════════════
-- FASE 2 — Maestros: Clientes (RF-04), Mesas (RF-07), Menú (RF-08)
-- ════════════════════════════════════════════════════════════

-- V-RF04-01: Clientes registrados con sus datos de persona
SELECT c.id AS cliente_id,
       p.nombres || ' ' || p.apellidos AS nombre_completo,
       p.documento AS cedula,
       p.telefono,
       c.creado_en
FROM cliente c
JOIN persona p ON c.persona_id = p.id
ORDER BY c.id;

-- V-RF04-02: Comprobar restricción UNIQUE en documento de identidad
-- (debe existir un índice UNIQUE en persona.documento)
SELECT indexname, indexdef
FROM pg_indexes
WHERE tablename = 'persona' AND indexdef LIKE '%documento%';

-- V-RF07-01: Mesas configuradas por zona, capacidad y estado
SELECT id, numero, zona, capacidad, estado
FROM mesa
ORDER BY zona, numero;

-- V-RF07-02: Distribución de mesas por zona
SELECT zona,
       COUNT(*) AS total_mesas,
       SUM(capacidad) AS capacidad_total,
       COUNT(*) FILTER (WHERE estado = 'libre') AS libres,
       COUNT(*) FILTER (WHERE estado = 'ocupada') AS ocupadas,
       COUNT(*) FILTER (WHERE estado = 'reservada') AS reservadas
FROM mesa
GROUP BY zona
ORDER BY zona;

-- V-RF08-01: Platos del menú con categoría y estado
SELECT c.nombre AS categoria, pl.id, pl.nombre,
       pl.precio, pl.activo,
       pl.descripcion
FROM plato pl
JOIN categoria_menu c ON pl.categoria_id = c.id
ORDER BY c.id, pl.nombre;

-- V-RF08-02: Categorías del menú
SELECT id, nombre FROM categoria_menu ORDER BY id;

-- V-RF08-03: Restricción CHECK precio > 0 (ningún plato debe tener precio <= 0)
SELECT id, nombre, precio FROM plato WHERE precio <= 0;
-- Resultado esperado: 0 filas

-- V-RF08-04: Platos inactivos (baja lógica)
SELECT id, nombre, precio FROM plato WHERE activo = false;

-- ════════════════════════════════════════════════════════════
-- FASE 3 — Pedidos (RF-09, RF-10, RF-11)
-- ════════════════════════════════════════════════════════════

-- V-RF09-01: Resumen de pedidos por tipo y estado
SELECT tipo, estado, COUNT(*) AS total,
       ROUND(SUM(total), 2) AS suma_total_usd
FROM pedido
GROUP BY tipo, estado
ORDER BY tipo, estado;

-- V-RF09-02: Total calculado en servidor vs. suma de detalles
-- (ninguna fila debe mostrar discrepancia)
SELECT p.id AS pedido_id, p.total AS total_cabecera,
       ROUND(SUM(dp.cantidad * dp.precio_unitario), 2) AS total_calculado,
       ROUND(p.total - SUM(dp.cantidad * dp.precio_unitario), 2) AS diferencia
FROM pedido p
JOIN detalle_pedido dp ON p.id = dp.pedido_id
GROUP BY p.id, p.total
HAVING ROUND(p.total - SUM(dp.cantidad * dp.precio_unitario), 2) <> 0
LIMIT 20;
-- Resultado esperado: 0 filas (totales consistentes)

-- V-RF09-03: Mesas con pedidos activos (estado ocupada / reservada)
SELECT m.numero, m.zona, m.estado,
       COUNT(p.id) AS pedidos_activos
FROM mesa m
LEFT JOIN pedido p ON p.mesa_id = m.id
  AND p.estado IN ('pendiente', 'en_preparacion', 'listo')
GROUP BY m.id, m.numero, m.zona, m.estado
ORDER BY m.numero;

-- V-RF10-01: Volumen de datos históricos para predicciones
SELECT COUNT(p.id)         AS total_pedidos,
       COUNT(f.id)         AS total_facturas,
       ROUND(SUM(f.total), 2) AS total_facturado_usd,
       MIN(p.creado_en::date) AS fecha_mas_antigua,
       MAX(p.creado_en::date) AS fecha_mas_reciente,
       COUNT(DISTINCT p.creado_en::date) AS dias_distintos
FROM pedido p
LEFT JOIN factura f ON p.id = f.pedido_id;

-- V-RF10-02: Historial de estados completo de los 5 pedidos más recientes
SELECT h.pedido_id, h.estado, h.cambiado_en,
       u.username AS usuario_que_cambio,
       r.nombre AS rol
FROM historial_estado_pedido h
JOIN usuario u ON h.usuario_id = u.id
JOIN rol r ON u.rol_id = r.id
WHERE h.pedido_id IN (SELECT id FROM pedido ORDER BY id DESC LIMIT 5)
ORDER BY h.pedido_id DESC, h.cambiado_en ASC;

-- V-RF10-03: Verificar que no existan saltos de estado ilegales
-- (ningún pedido debe pasar de 'pendiente' directamente a 'listo' o 'entregado')
WITH transiciones AS (
    SELECT pedido_id,
           estado AS estado_actual,
           LAG(estado) OVER (PARTITION BY pedido_id ORDER BY cambiado_en, id) AS estado_anterior
    FROM historial_estado_pedido
)
SELECT pedido_id, estado_anterior, estado_actual
FROM transiciones
WHERE (estado_anterior = 'pendiente'      AND estado_actual NOT IN ('en_preparacion', 'cancelado'))
   OR (estado_anterior = 'en_preparacion' AND estado_actual NOT IN ('listo'))
   OR (estado_anterior = 'listo'          AND estado_actual NOT IN ('entregado'));
-- Resultado esperado: 0 filas (sin saltos ilegales)

-- V-RF11-01: Pedidos cancelados (solo deben existir si venían de 'pendiente')
SELECT p.id, p.estado, p.creado_en
FROM pedido p
WHERE p.estado = 'cancelado'
ORDER BY p.id DESC
LIMIT 10;

-- V-RF11-02: Verificar que pedidos no-pendientes no fueron modificados
-- (un pedido en_preparacion/listo/entregado no debe tener fecha creado_en posterior a la última modificación del historial)
SELECT p.id, p.estado,
       MAX(h.cambiado_en) AS ultimo_cambio_historial,
       p.creado_en
FROM pedido p
JOIN historial_estado_pedido h ON h.pedido_id = p.id
WHERE p.estado NOT IN ('pendiente', 'cancelado')
GROUP BY p.id, p.estado, p.creado_en
ORDER BY p.id DESC
LIMIT 10;

-- ════════════════════════════════════════════════════════════
-- FASE 4 — Predicciones (P1, P4, P6)
-- ════════════════════════════════════════════════════════════

-- V-P1-01: Distribución de pedidos por hora (últimas 4 semanas)
-- Permite validar manualmente que los promedios de P1 son razonables
SELECT EXTRACT(HOUR FROM creado_en)::INT AS hora,
       COUNT(*) AS total_pedidos,
       ROUND(COUNT(*) / 4.0, 1) AS promedio_por_semana
FROM pedido
WHERE estado = 'entregado'
  AND creado_en >= CURRENT_DATE - INTERVAL '28 days'
GROUP BY hora
ORDER BY hora;

-- V-P1-02: Verificar picos horarios en franjas almuerzo y cena
SELECT
    CASE
        WHEN EXTRACT(HOUR FROM creado_en) BETWEEN 10 AND 11 THEN '10-11 Mañana'
        WHEN EXTRACT(HOUR FROM creado_en) BETWEEN 12 AND 14 THEN '12-14 Almuerzo (PICO)'
        WHEN EXTRACT(HOUR FROM creado_en) BETWEEN 15 AND 18 THEN '15-18 Tarde'
        WHEN EXTRACT(HOUR FROM creado_en) BETWEEN 19 AND 21 THEN '19-21 Cena (PICO)'
        ELSE 'Otro'
    END AS franja,
    COUNT(*) AS pedidos
FROM pedido
WHERE estado = 'entregado'
  AND creado_en >= CURRENT_DATE - INTERVAL '28 days'
GROUP BY franja
ORDER BY pedidos DESC;

-- V-P4-01: Top 10 platos más vendidos (últimas 4 semanas)
-- Permite validar manualmente que los datos de P4 son correctos
SELECT pl.nombre,
       c.nombre AS categoria,
       SUM(dp.cantidad) AS total_unidades,
       ROUND(SUM(dp.cantidad) / 4.0, 1) AS promedio_por_semana
FROM detalle_pedido dp
JOIN plato pl ON dp.plato_id = pl.id
JOIN categoria_menu c ON pl.categoria_id = c.id
JOIN pedido p ON dp.pedido_id = p.id
WHERE p.estado = 'entregado'
  AND p.creado_en >= CURRENT_DATE - INTERVAL '28 days'
GROUP BY pl.id, pl.nombre, c.nombre
ORDER BY total_unidades DESC
LIMIT 10;

-- V-P6-01: Ventas por día de la semana (últimas 4 semanas)
-- Permite validar manualmente que los promedios de P6 son correctos
SELECT
    EXTRACT(DOW FROM creado_en)::INT AS dow,
    CASE EXTRACT(DOW FROM creado_en)::INT
        WHEN 0 THEN 'Domingo'  WHEN 1 THEN 'Lunes'   WHEN 2 THEN 'Martes'
        WHEN 3 THEN 'Miércoles' WHEN 4 THEN 'Jueves' WHEN 5 THEN 'Viernes'
        WHEN 6 THEN 'Sábado'
    END AS dia_semana,
    COUNT(DISTINCT creado_en::date)       AS semanas_con_datos,
    ROUND(SUM(total), 2)                  AS venta_total,
    ROUND(AVG(subtotal_dia.venta_dia), 2) AS promedio_diario_usd
FROM pedido
JOIN (
    SELECT creado_en::date AS fecha_dia, SUM(total) AS venta_dia
    FROM pedido
    WHERE estado = 'entregado'
      AND creado_en >= CURRENT_DATE - INTERVAL '28 days'
    GROUP BY fecha_dia
) subtotal_dia ON subtotal_dia.fecha_dia = pedido.creado_en::date
WHERE estado = 'entregado'
  AND creado_en >= CURRENT_DATE - INTERVAL '28 days'
GROUP BY dow
ORDER BY dow;

-- V-P6-02: Verificar que hay datos suficientes (mínimo 4 semanas para DOW de mañana)
SELECT
    EXTRACT(DOW FROM CURRENT_DATE + 1)::INT AS dow_objetivo,
    COUNT(DISTINCT creado_en::date)          AS semanas_disponibles
FROM pedido
WHERE estado = 'entregado'
  AND EXTRACT(DOW FROM creado_en) = EXTRACT(DOW FROM CURRENT_DATE + 1)
  AND creado_en::date >= CURRENT_DATE - 28;
-- Resultado esperado: semanas_disponibles >= 4

-- ════════════════════════════════════════════════════════════
-- INTEGRIDAD GENERAL DEL SISTEMA
-- ════════════════════════════════════════════════════════════

-- V-INT-01: Verificar que no existen orphans en detalle_pedido
SELECT COUNT(*) AS orphans_detalle
FROM detalle_pedido dp
LEFT JOIN pedido p ON dp.pedido_id = p.id
WHERE p.id IS NULL;
-- Resultado esperado: 0

-- V-INT-02: Verificar que no existen orphans en historial_estado_pedido
SELECT COUNT(*) AS orphans_historial
FROM historial_estado_pedido h
LEFT JOIN pedido p ON h.pedido_id = p.id
WHERE p.id IS NULL;
-- Resultado esperado: 0

-- V-INT-03: Verificar que toda factura tiene un pedido entregado
SELECT COUNT(*) AS facturas_sin_pedido_entregado
FROM factura f
JOIN pedido p ON f.pedido_id = p.id
WHERE p.estado <> 'entregado';
-- Resultado esperado: 0

-- V-INT-04: Resumen de integridad del sistema completo
SELECT
    (SELECT COUNT(*) FROM rol)                   AS roles,
    (SELECT COUNT(*) FROM persona)               AS personas,
    (SELECT COUNT(*) FROM usuario)               AS usuarios,
    (SELECT COUNT(*) FROM cliente)               AS clientes,
    (SELECT COUNT(*) FROM mesa)                  AS mesas,
    (SELECT COUNT(*) FROM categoria_menu)        AS categorias_menu,
    (SELECT COUNT(*) FROM plato)                 AS platos_total,
    (SELECT COUNT(*) FROM plato WHERE activo)    AS platos_activos,
    (SELECT COUNT(*) FROM pedido)                AS pedidos_total,
    (SELECT COUNT(*) FROM pedido WHERE estado='entregado') AS pedidos_entregados,
    (SELECT COUNT(*) FROM detalle_pedido)        AS lineas_detalle,
    (SELECT COUNT(*) FROM historial_estado_pedido) AS registros_historial,
    (SELECT COUNT(*) FROM factura)               AS facturas;
