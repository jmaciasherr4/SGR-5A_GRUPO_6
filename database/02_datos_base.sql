-- ============================================================================
-- 02_datos_base.sql: Catálogos y datos iniciales para pruebas
-- Roles, usuarios de prueba, clientes, mesas, menú e inventario inicial
-- ============================================================================

SET timezone = 'America/Guayaquil';

-- ----------------------------------------------------------------------------
-- 1. ROLES DEL SISTEMA
-- ----------------------------------------------------------------------------
INSERT INTO rol (id, nombre) VALUES
    (1, 'administrador'),
    (2, 'mesero'),
    (3, 'cajero'),
    (4, 'cocinero')
ON CONFLICT (id) DO NOTHING;

SELECT setval('rol_id_seq', (SELECT MAX(id) FROM rol));

-- ----------------------------------------------------------------------------
-- 2. PERSONAS Y USUARIOS DEL SISTEMA
-- Clave por defecto para desarrollo: admin123 (hasheada con bcrypt 10 rounds)
-- ----------------------------------------------------------------------------
INSERT INTO persona (id, nombres, apellidos, documento, telefono) VALUES
    (1, 'Carlos', 'Administrador', '0912345671', '0991112233'),
    (2, 'Marcos', 'Mesero', '0912345672', '0992223344'),
    (3, 'Carla', 'Cajera', '0912345673', '0993334455'),
    (4, 'Claudio', 'Cocinero', '0912345674', '0994445566')
ON CONFLICT (id) DO NOTHING;

INSERT INTO usuario (id, persona_id, username, password_hash, rol_id, activo) VALUES
    (1, 1, 'admin', crypt('admin123', gen_salt('bf', 10)), 1, TRUE),
    (2, 2, 'mesero1', crypt('admin123', gen_salt('bf', 10)), 2, TRUE),
    (3, 3, 'cajero1', crypt('admin123', gen_salt('bf', 10)), 3, TRUE),
    (4, 4, 'cocinero1', crypt('admin123', gen_salt('bf', 10)), 4, TRUE)
ON CONFLICT (id) DO NOTHING;

SELECT setval('persona_id_seq', (SELECT MAX(id) FROM persona));
SELECT setval('usuario_id_seq', (SELECT MAX(id) FROM usuario));

-- ----------------------------------------------------------------------------
-- 3. CLIENTES DE PRUEBA (10 clientes con cédulas ecuatorianas de 10 dígitos)
-- ----------------------------------------------------------------------------
INSERT INTO persona (id, nombres, apellidos, documento, telefono) VALUES
    (5, 'Juan', 'Pérez', '0928374650', '0987654321'),
    (6, 'María', 'Gómez', '0917283945', '0981234567'),
    (7, 'Luis', 'Rodríguez', '0936475829', '0978912345'),
    (8, 'Ana', 'Martínez', '0945612387', '0965432198'),
    (9, 'Pedro', 'Sánchez', '0954321678', '0956781234'),
    (10, 'Lucía', 'Torres', '0963217845', '0943218765'),
    (11, 'Diego', 'Ramírez', '0972183456', '0932145678'),
    (12, 'Elena', 'Castro', '0981276543', '0921436587'),
    (13, 'Jorge', 'Morales', '0990345671', '0910987654'),
    (14, 'Sofía', 'Vargas', '0901239874', '0909871234')
ON CONFLICT (id) DO NOTHING;

INSERT INTO cliente (id, persona_id) VALUES
    (1, 5),
    (2, 6),
    (3, 7),
    (4, 8),
    (5, 9),
    (6, 10),
    (7, 11),
    (8, 12),
    (9, 13),
    (10, 14)
ON CONFLICT (id) DO NOTHING;

SELECT setval('persona_id_seq', (SELECT MAX(id) FROM persona));
SELECT setval('cliente_id_seq', (SELECT MAX(id) FROM cliente));

-- ----------------------------------------------------------------------------
-- 4. MESAS (12 mesas distribuidas en Salón y Terraza)
-- ----------------------------------------------------------------------------
INSERT INTO mesa (id, numero, capacidad, zona, estado) VALUES
    (1, 1, 2, 'Salón Principal', 'libre'),
    (2, 2, 4, 'Salón Principal', 'libre'),
    (3, 3, 4, 'Salón Principal', 'libre'),
    (4, 4, 6, 'Salón Principal', 'libre'),
    (5, 5, 2, 'Salón Principal', 'libre'),
    (6, 6, 4, 'Salón Principal', 'libre'),
    (7, 7, 2, 'Terraza', 'libre'),
    (8, 8, 4, 'Terraza', 'libre'),
    (9, 9, 6, 'Terraza', 'libre'),
    (10, 10, 4, 'Terraza', 'libre'),
    (11, 11, 8, 'Terraza', 'libre'),
    (12, 12, 2, 'Terraza', 'libre')
ON CONFLICT (id) DO NOTHING;

SELECT setval('mesa_id_seq', (SELECT MAX(id) FROM mesa));

-- ----------------------------------------------------------------------------
-- 5. MENÚ: 4 CATEGORÍAS Y 15 PLATOS ($1.50 - $8.00)
-- ----------------------------------------------------------------------------
INSERT INTO categoria_menu (id, nombre) VALUES
    (1, 'Entradas'),
    (2, 'Platos Fuertes'),
    (3, 'Bebidas'),
    (4, 'Postres')
ON CONFLICT (id) DO NOTHING;

SELECT setval('categoria_menu_id_seq', (SELECT MAX(id) FROM categoria_menu));

INSERT INTO plato (id, categoria_id, nombre, descripcion, precio, activo) VALUES
    -- Entradas
    (1, 1, 'Empanadas de Viento (3 uds)', 'Crujientes empanadas rellenas de queso con un toque de azúcar', 2.00, TRUE),
    (2, 1, 'Ceviche de Camarón', 'Camarones marinados en jugo de naranja y limón con cebolla y cilantro', 6.50, TRUE),
    (3, 1, 'Patacones con Queso', 'Plátano verde frito aplastado acompañado de abundante queso fresco', 3.00, TRUE),
    (4, 1, 'Sopa Marinera Especial', 'Caldo concentrado de mariscos frescos con cilantro y aguacate', 5.50, TRUE),
    -- Platos Fuertes
    (5, 2, 'Seco de Pollo', 'Pollo cocinado a fuego lento en salsa de chicha y cilantro con arroz', 4.50, TRUE),
    (6, 2, 'Encebollado Mixto', 'Tradicional sopa de albacora y camarón con yuca y cebolla encurtida', 5.00, TRUE),
    (7, 2, 'Churrasco Ecuatoriano', 'Carne de res asada con arroz, papas fritas, huevo frito y ensalada', 7.50, TRUE),
    (8, 2, 'Guatita Tradicional', 'Mondongo tierno en salsa cremosa de maní con papas y arroz', 4.00, TRUE),
    (9, 2, 'Pargo Frito Entero', 'Pargo fresco dorado servido con patacones, arroz y ensalada criolla', 8.00, TRUE),
    -- Bebidas
    (10, 3, 'Jugo Natural de Mora', 'Vaso de jugo natural preparado al instante con fruta fresca', 1.50, TRUE),
    (11, 3, 'Limonada Imperial', 'Limonada refrescante preparada con limón sutil y menta', 2.00, TRUE),
    (12, 3, 'Cerveza Nacional 330ml', 'Cerveza rubia fría tipo pilsener', 2.50, TRUE),
    -- Postres
    (13, 4, 'Flan de Caramelo Casero', 'Postre suave horneado al baño maría con caramelo líquido', 2.50, TRUE),
    (14, 4, 'Torta Tres Leches', 'Bizcochuelo esponjoso bañado en mezcla de tres leches y canela', 3.00, TRUE),
    (15, 4, 'Helado Artesanal de Paila', 'Porción de helado artesanal de mora o maracuyá', 1.75, TRUE)
ON CONFLICT (id) DO NOTHING;

SELECT setval('plato_id_seq', (SELECT MAX(id) FROM plato));

-- ----------------------------------------------------------------------------
-- 6. INVENTARIO: 12 INGREDIENTES
-- ----------------------------------------------------------------------------
INSERT INTO ingrediente (id, nombre, unidad, stock_actual, stock_minimo) VALUES
    (1, 'Camarón Fresco', 'kg', 25.00, 5.00),
    (2, 'Pechuga de Pollo', 'kg', 30.00, 8.00),
    (3, 'Carne de Res Lomo', 'kg', 20.00, 5.00),
    (4, 'Pescado Albacora / Pargo', 'kg', 22.00, 6.00),
    (5, 'Arroz Blanco', 'kg', 100.00, 20.00),
    (6, 'Plátano Verde', 'unidad', 150.00, 30.00),
    (7, 'Yuca Fresca', 'kg', 40.00, 10.00),
    (8, 'Cebolla Paiteña Colorada', 'kg', 35.00, 10.00),
    (9, 'Limón Sutil', 'kg', 20.00, 5.00),
    (10, 'Queso Manaba', 'kg', 18.00, 4.00),
    (11, 'Leche Entera', 'litro', 25.00, 5.00),
    (12, 'Cerveza Nacional Botella', 'unidad', 80.00, 24.00)
ON CONFLICT (id) DO NOTHING;

SELECT setval('ingrediente_id_seq', (SELECT MAX(id) FROM ingrediente));

-- ----------------------------------------------------------------------------
-- 7. RECETAS PARA 6 PLATOS (plato_ingrediente)
-- ----------------------------------------------------------------------------
INSERT INTO plato_ingrediente (plato_id, ingrediente_id, cantidad_por_plato) VALUES
    -- Plato 2: Ceviche de Camarón (Camarón, Cebolla, Limón)
    (2, 1, 0.20),
    (2, 8, 0.10),
    (2, 9, 0.08),
    -- Plato 6: Encebollado Mixto (Pescado, Camarón, Yuca, Cebolla)
    (6, 4, 0.15),
    (6, 1, 0.10),
    (6, 7, 0.20),
    (6, 8, 0.08),
    -- Plato 5: Seco de Pollo (Pollo, Arroz)
    (5, 2, 0.25),
    (5, 5, 0.12),
    -- Plato 3: Patacones con Queso (Plátano Verde, Queso)
    (3, 6, 2.00),
    (3, 10, 0.12),
    -- Plato 7: Churrasco Ecuatoriano (Carne de Res, Arroz)
    (7, 3, 0.25),
    (7, 5, 0.12),
    -- Plato 11: Limonada Imperial (Limón)
    (11, 9, 0.10)
ON CONFLICT (plato_id, ingrediente_id) DO NOTHING;

-- Días especiales iniciales
INSERT INTO dia_especial (fecha, nombre) VALUES
    ('2026-01-01', 'Año Nuevo'),
    ('2026-05-01', 'Día del Trabajo'),
    ('2026-08-10', 'Primer Grito de Independencia'),
    ('2026-10-09', 'Independencia de Guayaquil'),
    ('2026-11-02', 'Día de los Difuntos'),
    ('2026-11-03', 'Independencia de Cuenca'),
    ('2026-12-25', 'Navidad')
ON CONFLICT (fecha) DO NOTHING;
