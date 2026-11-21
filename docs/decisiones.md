# Registro de Decisiones de Arquitectura y Técnicas (ADR)

Este documento registra las decisiones técnicas tomadas a lo largo del desarrollo del sistema de gestión de restaurante, documentando el contexto, la alternativa elegida y su justificación.

---

## 1. Puerto del Host para PostgreSQL (Docker)
- **Fecha:** Fase 0
- **Decisión:** Asignar el puerto del host `5434` mapeado al puerto interno `5432` del contenedor (`${DB_PORT:-5434}:5432`).
- **Motivo:** Se detectó que el puerto `5433` ya se encuentra en uso por otro contenedor previo (`postgres_abd`). Para no afectar, modificar ni detener dicho contenedor existente, se configuró el puerto externo `5434` manteniendo intacto el puerto interno estándar `5432`.

---

## 2. Configuración de Conexión PDO y Seguridad
- **Fecha:** Fase 0
- **Decisión:** Implementar la clase `App\Core\Database` como Singleton con:
  - `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
  - `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`
  - `PDO::ATTR_EMULATE_PREPARES => false`
- **Motivo:** Garantizar que la base de datos PostgreSQL realice la preparación real de las consultas (preparación nativa en el motor), bloqueando de raíz inyecciones SQL incluso ante tipos de datos complejos o caracteres especiales. Se prohíbe terminantemente la concatenación de variables en consultas SQL.

---

## 3. Ocultamiento de Errores de Base de Datos al Usuario
- **Fecha:** Fase 0
- **Decisión:** Capturar cualquier `PDOException` a nivel de núcleo, registrar el detalle técnico (con timestamp en zona `America/Guayaquil`) en `storage/logs/database.log`, y presentar únicamente mensajes genéricos comprensibles al usuario final.
- **Motivo:** Seguridad (prevenir fugas de información como nombres de tablas, esquemas, usuarios o credenciales).

---

## 4. Hashing de Contraseñas y Compatibilidad `pgcrypto` con PHP
- **Fecha:** Fase 0
- **Decisión:** Generar las contraseñas en los scripts de inicialización SQL utilizando la función `crypt('admin123', gen_salt('bf', 10))` de la extensión `pgcrypto`.
- **Motivo:** Produce hashes estándar de Blowfish (`$2a$10$...`) 100% interoperables con la función estándar `password_verify()` de PHP 8.2+.
- **Credencial de desarrollo común:** Se asignó la contraseña `admin123` a todos los usuarios de prueba iniciales (`admin`, `mesero1`, `cajero1`, `cocinero1`) para facilitar la evaluación y reproducción rápida de pruebas.

---

## 5. Zona Horaria y Moneda
- **Fecha:** Fase 0
- **Decisión:** Zona horaria fijada en `America/Guayaquil` tanto en PostgreSQL (`SET timezone = 'America/Guayaquil'`), como en PHP (`date_default_timezone_set('America/Guayaquil')`) y en el contenedor Docker (`TZ: America/Guayaquil`). Los campos monetarios se definen como `NUMERIC(10,2)` y se manejan en dólares estadounidenses ($ USD).
- **Motivo:** Contexto operativo universitario en Ecuador y precisión contable sin pérdidas por punto flotante.

---

## 6. Generación Determinista de Datos Históricos
- **Fecha:** Fase 0
- **Decisión:** Emplear `setseed(0.42)` en el script `03_datos_historicos.sql` para generar 6 semanas de datos previos a `CURRENT_DATE`.
- **Motivo:** Garantiza que cada vez que se reinicie la base de datos (`docker compose down -v`), los cálculos analíticos y las predicciones de la Fase 4 arrojen resultados reproducibles e idénticos en cualquier máquina.

---

## 7. Manejo Seguro de Sesiones y Protección contra Fijación de Sesión (RF-01)
- **Fecha:** Fase 1
- **Decisión:** Implementar la clase `App\Core\Session` con directivas estrictas: `cookie_httponly = true`, `cookie_samesite = 'Lax'`, `use_strict_mode = 1`, y llamada forzosa a `session_regenerate_id(true)` inmediatamente tras un inicio de sesión exitoso.
- **Motivo:** Mitigar ataques de Session Fixation y Cross-Site Scripting (XSS) al impedir el acceso a la cookie de sesión desde JavaScript.

---

## 8. Protección Antifalsificación de Peticiones en Sitios Cruzados (CSRF)
- **Fecha:** Fase 1
- **Decisión:** Implementar verificación CSRF multinivel obligatoria en el backend: a nivel de enrutador central ([Router.php](file:///C:/Users/Usuario/.gemini/antigravity/scratch/restaurante/src/core/Router.php)), a nivel de controladores y en el validador de entradas, usando tokens criptográficos (`bin2hex(random_bytes(32))`) y comparación de tiempo constante (`hash_equals`).
- **Motivo:** Asegurar que ninguna petición POST pueda ejecutarse en el servidor si no contiene un token CSRF válido y evitar que manipulaciones en el DOM del navegador evadan la seguridad.

---

## 9. Manejo de Integridad Referencial al Eliminar Registros (ON DELETE RESTRICT)
- **Fecha:** Fase 1 y Fase 2
- **Decisión:** Capturar el código de error `23503` de PostgreSQL ante intentos de eliminación de usuarios, clientes, mesas o platos vinculados a pedidos o reservas históricas, transformando el error en un mensaje claro que explica la restricción y ofrece "Desactivar" (baja lógica).
- **Motivo:** Preservar la integridad contable e histórica del restaurante sin exponer excepciones de base de datos no controladas.

---

## 10. Matriz de Autorización por Rol y Respuesta HTTP 403 (RF-03)
- **Fecha:** Fase 1 y Fase 2
- **Decisión:** Enrutamiento y controladores con control de acceso por rol granular:
  - **Clientes:** Administrador (CRUD) y Mesero (CRUD).
  - **Mesas:** Administrador (CRUD completo) y Mesero (solo lectura y cambio rápido de estado).
  - **Menú:** Administrador (CRUD completo) y Mesero/Cocinero (solo consulta).
  Cualquier intento de ejecutar una acción no autorizada responde de inmediato con `HTTP 403 Forbidden` y una vista amigable de acceso denegado.
- **Motivo:** Respetar estrictamente la matriz de permisos especificada en los requerimientos del proyecto.

---

## 11. Transacciones Atómicas en Creación y Actualización de Pedidos (RF-09, RF-11)
- **Fecha:** Fase 3
- **Decisión:** Envolver la creación/modificación de pedidos en transacciones `BEGIN ... COMMIT/ROLLBACK` en PDO, calculando precios y totales estrictamente en el servidor a partir de la tabla `plato`. Si el pedido es para mesa, el cambio a estado `ocupada` y el registro inicial en `historial_estado_pedido` forman parte de la misma transacción.
- **Motivo:** Evitar discrepancias financieras por manipulación de parámetros en el cliente e impedir estados inconsistentes (pedidos sin ítems o mesas desincronizadas).

---

## 12. Máquina de Estados Estricta del Pedido (RF-10)
- **Fecha:** Fase 3
- **Decisión:** Implementar transiciones de estado permitidas en `PedidoModel`:
  `pendiente -> en_preparacion -> listo -> entregado`, permitiendo `cancelado` únicamente desde `pendiente`. Cualquier salto o retroceso es rechazado a nivel de modelo.
- **Motivo:** Garantizar orden operacional en cocina y trazabilidad fidedigna en `historial_estado_pedido`.

---

## 13. Metodología de Proyección Analítica (P1, P4, P6)
- **Fecha:** Fase 4
- **Decisión:** Emplear consultas SQL con Common Table Expressions (CTEs) parametrizadas en PostgreSQL, calculando el promedio de las últimas 4 semanas del mismo día de la semana (`EXTRACT(DOW FROM ...)`). La interfaz muestra la etiqueta obligatoria "Datos de demostración" y restringe el acceso al rol `administrador`.
- **Motivo:** Lograr consultas analíticas optimizadas y reproducibles sin exponer datos reales ni sobrecargar el servidor web.
