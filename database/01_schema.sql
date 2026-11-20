-- ============================================================================
-- 01_schema.sql: Estructura de base de datos para Sistema de Restaurante
-- Motor: PostgreSQL 16
-- Reglas de integridad: ON DELETE RESTRICT en FKs, CHECKs, NOT NULL y UNIQUE
-- ============================================================================

-- Habilitar extensión para encriptación de contraseñas con bcrypt
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Configuración de zona horaria de la sesión
SET timezone = 'America/Guayaquil';

-- ----------------------------------------------------------------------------
-- 1. SEGURIDAD Y ACCESOS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rol (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS persona (
    id SERIAL PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    documento VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(20)
);

CREATE TABLE IF NOT EXISTS usuario (
    id SERIAL PRIMARY KEY,
    persona_id INT NOT NULL REFERENCES persona(id) ON DELETE RESTRICT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL REFERENCES rol(id) ON DELETE RESTRICT,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cliente (
    id SERIAL PRIMARY KEY,
    persona_id INT NOT NULL UNIQUE REFERENCES persona(id) ON DELETE RESTRICT,
    creado_en TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- 2. MESAS Y RESERVAS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mesa (
    id SERIAL PRIMARY KEY,
    numero INT NOT NULL UNIQUE,
    capacidad INT NOT NULL CHECK (capacidad > 0),
    zona VARCHAR(50) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'libre' CHECK (estado IN ('libre', 'ocupada', 'reservada'))
);

CREATE TABLE IF NOT EXISTS reserva (
    id SERIAL PRIMARY KEY,
    cliente_id INT NOT NULL REFERENCES cliente(id) ON DELETE RESTRICT,
    mesa_id INT NOT NULL REFERENCES mesa(id) ON DELETE RESTRICT,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    num_personas INT NOT NULL CHECK (num_personas > 0),
    estado VARCHAR(20) NOT NULL DEFAULT 'activa' CHECK (estado IN ('activa', 'cancelada', 'cumplida')),
    asistio BOOLEAN NULL
);

-- ----------------------------------------------------------------------------
-- 3. MENÚ Y PRECIOS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categoria_menu (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS plato (
    id SERIAL PRIMARY KEY,
    categoria_id INT NOT NULL REFERENCES categoria_menu(id) ON DELETE RESTRICT,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio NUMERIC(10,2) NOT NULL CHECK (precio > 0),
    activo BOOLEAN NOT NULL DEFAULT TRUE
);

-- ----------------------------------------------------------------------------
-- 4. PEDIDOS Y ATENCIÓN
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedido (
    id SERIAL PRIMARY KEY,
    mesa_id INT NULL REFERENCES mesa(id) ON DELETE RESTRICT,
    usuario_id INT NOT NULL REFERENCES usuario(id) ON DELETE RESTRICT,
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('mesa', 'llevar')),
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'en_preparacion', 'listo', 'entregado', 'cancelado')),
    num_comensales INT NOT NULL CHECK (num_comensales > 0),
    total NUMERIC(10,2) NOT NULL DEFAULT 0.00 CHECK (total >= 0),
    creado_en TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS detalle_pedido (
    id SERIAL PRIMARY KEY,
    pedido_id INT NOT NULL REFERENCES pedido(id) ON DELETE RESTRICT,
    plato_id INT NOT NULL REFERENCES plato(id) ON DELETE RESTRICT,
    cantidad INT NOT NULL CHECK (cantidad > 0),
    precio_unitario NUMERIC(10,2) NOT NULL CHECK (precio_unitario > 0)
);

CREATE TABLE IF NOT EXISTS historial_estado_pedido (
    id SERIAL PRIMARY KEY,
    pedido_id INT NOT NULL REFERENCES pedido(id) ON DELETE RESTRICT,
    estado VARCHAR(20) NOT NULL CHECK (estado IN ('pendiente', 'en_preparacion', 'listo', 'entregado', 'cancelado')),
    cambiado_en TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT NOT NULL REFERENCES usuario(id) ON DELETE RESTRICT
);

-- ----------------------------------------------------------------------------
-- 5. INVENTARIO Y RECETAS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ingrediente (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    unidad VARCHAR(20) NOT NULL,
    stock_actual NUMERIC(10,2) NOT NULL DEFAULT 0 CHECK (stock_actual >= 0),
    stock_minimo NUMERIC(10,2) NOT NULL DEFAULT 0 CHECK (stock_minimo >= 0)
);

CREATE TABLE IF NOT EXISTS movimiento_inventario (
    id SERIAL PRIMARY KEY,
    ingrediente_id INT NOT NULL REFERENCES ingrediente(id) ON DELETE RESTRICT,
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('entrada', 'salida', 'ajuste')),
    cantidad NUMERIC(10,2) NOT NULL,
    fecha TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT NOT NULL REFERENCES usuario(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS plato_ingrediente (
    plato_id INT NOT NULL REFERENCES plato(id) ON DELETE RESTRICT,
    ingrediente_id INT NOT NULL REFERENCES ingrediente(id) ON DELETE RESTRICT,
    cantidad_por_plato NUMERIC(10,2) NOT NULL CHECK (cantidad_por_plato > 0),
    PRIMARY KEY (plato_id, ingrediente_id)
);

-- ----------------------------------------------------------------------------
-- 6. FACTURACIÓN Y CONTROL
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS factura (
    id SERIAL PRIMARY KEY,
    pedido_id INT NOT NULL UNIQUE REFERENCES pedido(id) ON DELETE RESTRICT,
    usuario_id INT NOT NULL REFERENCES usuario(id) ON DELETE RESTRICT,
    total NUMERIC(10,2) NOT NULL CHECK (total >= 0),
    forma_pago VARCHAR(20) NOT NULL CHECK (forma_pago IN ('efectivo', 'tarjeta', 'transferencia')),
    emitida_en TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS dia_especial (
    id SERIAL PRIMARY KEY,
    fecha DATE NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL
);

-- ----------------------------------------------------------------------------
-- ÍNDICES PARA CLAVES FORÁNEAS Y CONSULTAS FRECUENTES (FECHAS)
-- ----------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_usuario_persona_id ON usuario(persona_id);
CREATE INDEX IF NOT EXISTS idx_usuario_rol_id ON usuario(rol_id);
CREATE INDEX IF NOT EXISTS idx_cliente_persona_id ON cliente(persona_id);
CREATE INDEX IF NOT EXISTS idx_reserva_cliente_id ON reserva(cliente_id);
CREATE INDEX IF NOT EXISTS idx_reserva_mesa_id ON reserva(mesa_id);
CREATE INDEX IF NOT EXISTS idx_reserva_fecha ON reserva(fecha);
CREATE INDEX IF NOT EXISTS idx_plato_categoria_id ON plato(categoria_id);
CREATE INDEX IF NOT EXISTS idx_pedido_mesa_id ON pedido(mesa_id);
CREATE INDEX IF NOT EXISTS idx_pedido_usuario_id ON pedido(usuario_id);
CREATE INDEX IF NOT EXISTS idx_pedido_creado_en ON pedido(creado_en);
CREATE INDEX IF NOT EXISTS idx_pedido_estado ON pedido(estado);
CREATE INDEX IF NOT EXISTS idx_detalle_pedido_pedido_id ON detalle_pedido(pedido_id);
CREATE INDEX IF NOT EXISTS idx_detalle_pedido_plato_id ON detalle_pedido(plato_id);
CREATE INDEX IF NOT EXISTS idx_historial_pedido_id ON historial_estado_pedido(pedido_id);
CREATE INDEX IF NOT EXISTS idx_historial_cambiado_en ON historial_estado_pedido(cambiado_en);
CREATE INDEX IF NOT EXISTS idx_mov_inventario_ingrediente ON movimiento_inventario(ingrediente_id);
CREATE INDEX IF NOT EXISTS idx_mov_inventario_fecha ON movimiento_inventario(fecha);
CREATE INDEX IF NOT EXISTS idx_factura_pedido_id ON factura(pedido_id);
CREATE INDEX IF NOT EXISTS idx_factura_usuario_id ON factura(usuario_id);
CREATE INDEX IF NOT EXISTS idx_factura_emitida_en ON factura(emitida_en);
