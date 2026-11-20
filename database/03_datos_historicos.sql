-- ============================================================================
-- 03_datos_historicos.sql: 6 semanas de pedidos y facturas de demostración
-- Relativas a CURRENT_DATE, reproducibles con setseed
-- Picos viernes-domingo y franjas 12:00-14:00 y 19:00-21:00
-- ============================================================================

SET timezone = 'America/Guayaquil';

DO $$
DECLARE
    v_fecha DATE;
    v_dia_offset INT;
    v_dow INT;
    v_num_pedidos INT;
    v_p INT;
    v_hora_base TIME;
    v_minuto INT;
    v_segundo INT;
    v_ts_creado TIMESTAMPTZ;
    v_mesa_id INT;
    v_tipo VARCHAR(20);
    v_comensales INT;
    v_pedido_id INT;
    v_total_pedido NUMERIC(10,2);
    v_num_items INT;
    v_i INT;
    v_plato_id INT;
    v_precio_unitario NUMERIC(10,2);
    v_cantidad INT;
    v_forma_pago VARCHAR(20);
    v_rnd FLOAT;
    v_usuario_mesero INT := 2; -- mesero1
    v_usuario_cajero INT := 3; -- cajero1
BEGIN
    -- Semilla fija para generación pseudoaleatoria determinista
    PERFORM setseed(0.42);

    -- Recorremos desde hace 42 días (6 semanas) hasta ayer (hace 1 día)
    FOR v_dia_offset IN REVERSE 42..1 LOOP
        v_fecha := CURRENT_DATE - v_dia_offset;
        v_dow := EXTRACT(DOW FROM v_fecha); -- 0: Domingo, 5: Viernes, 6: Sábado

        -- Determinar volumen de pedidos: más alto en fin de semana (Viernes a Domingo)
        IF v_dow IN (0, 5, 6) THEN
            v_num_pedidos := 18 + FLOOR(random() * 8)::INT; -- 18 a 25 pedidos
        ELSE
            v_num_pedidos := 8 + FLOOR(random() * 6)::INT;   -- 8 a 13 pedidos
        END IF;

        FOR v_p IN 1..v_num_pedidos LOOP
            v_rnd := random();
            v_minuto := FLOOR(random() * 60)::INT;
            v_segundo := FLOOR(random() * 60)::INT;

            -- Distribución por franjas horarias con picos almuerzo y cena
            IF v_rnd < 0.55 THEN
                -- Pico Almuerzo (12:00 a 14:59)
                v_hora_base := ('12:00:00'::TIME + (FLOOR(random() * 3)::INT || ' hours')::INTERVAL 
                                                 + (v_minuto || ' minutes')::INTERVAL 
                                                 + (v_segundo || ' seconds')::INTERVAL);
            ELSIF v_rnd < 0.85 THEN
                -- Pico Cena (19:00 a 21:59)
                v_hora_base := ('19:00:00'::TIME + (FLOOR(random() * 3)::INT || ' hours')::INTERVAL 
                                                 + (v_minuto || ' minutes')::INTERVAL 
                                                 + (v_segundo || ' seconds')::INTERVAL);
            ELSIF v_rnd < 0.95 THEN
                -- Tarde (15:00 a 18:59)
                v_hora_base := ('15:00:00'::TIME + (FLOOR(random() * 4)::INT || ' hours')::INTERVAL 
                                                 + (v_minuto || ' minutes')::INTERVAL 
                                                 + (v_segundo || ' seconds')::INTERVAL);
            ELSE
                -- Mañana (10:00 a 11:59)
                v_hora_base := ('10:00:00'::TIME + (FLOOR(random() * 2)::INT || ' hours')::INTERVAL 
                                                 + (v_minuto || ' minutes')::INTERVAL 
                                                 + (v_segundo || ' seconds')::INTERVAL);
            END IF;

            v_ts_creado := (v_fecha + v_hora_base)::TIMESTAMPTZ;

            -- Tipo de pedido y mesa
            IF random() < 0.80 THEN
                v_tipo := 'mesa';
                v_mesa_id := 1 + FLOOR(random() * 12)::INT;
                v_comensales := 1 + FLOOR(random() * 4)::INT;
            ELSE
                v_tipo := 'llevar';
                v_mesa_id := NULL;
                v_comensales := 1;
            END IF;

            -- Insertar cabecera del pedido en estado 'entregado'
            INSERT INTO pedido (mesa_id, usuario_id, tipo, estado, num_comensales, total, creado_en)
            VALUES (v_mesa_id, v_usuario_mesero, v_tipo, 'entregado', v_comensales, 0.00, v_ts_creado)
            RETURNING id INTO v_pedido_id;

            -- Insertar de 1 a 4 platos por pedido
            v_num_items := 1 + FLOOR(random() * 4)::INT;
            v_total_pedido := 0.00;

            FOR v_i IN 1..v_num_items LOOP
                -- Elegir plato ponderando platos fuertes y bebidas comunes (1 a 15)
                v_plato_id := 1 + FLOOR(random() * 15)::INT;
                SELECT precio INTO v_precio_unitario FROM plato WHERE id = v_plato_id;
                v_cantidad := 1 + FLOOR(random() * 2)::INT;

                INSERT INTO detalle_pedido (pedido_id, plato_id, cantidad, precio_unitario)
                VALUES (v_pedido_id, v_plato_id, v_cantidad, v_precio_unitario);

                v_total_pedido := v_total_pedido + (v_cantidad * v_precio_unitario);
            END LOOP;

            -- Actualizar total consolidado en la cabecera
            UPDATE pedido SET total = v_total_pedido WHERE id = v_pedido_id;

            -- Registrar historial de transición de estados
            INSERT INTO historial_estado_pedido (pedido_id, estado, cambiado_en, usuario_id) VALUES
                (v_pedido_id, 'pendiente', v_ts_creado, v_usuario_mesero),
                (v_pedido_id, 'en_preparacion', v_ts_creado + INTERVAL '5 minutes', 4), -- cocinero1
                (v_pedido_id, 'listo', v_ts_creado + INTERVAL '25 minutes', 4),
                (v_pedido_id, 'entregado', v_ts_creado + INTERVAL '30 minutes', v_usuario_mesero);

            -- Emitir factura para el pedido entregado
            IF random() < 0.50 THEN
                v_forma_pago := 'efectivo';
            ELSIF random() < 0.85 THEN
                v_forma_pago := 'tarjeta';
            ELSE
                v_forma_pago := 'transferencia';
            END IF;

            INSERT INTO factura (pedido_id, usuario_id, total, forma_pago, emitida_en)
            VALUES (v_pedido_id, v_usuario_cajero, v_total_pedido, v_forma_pago, v_ts_creado + INTERVAL '45 minutes');

        END LOOP;
    END LOOP;
END $$;
