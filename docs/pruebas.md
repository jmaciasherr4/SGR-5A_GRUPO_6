# Plan y Registro de Pruebas con Evidencias

Materia: **Administración de Bases de Datos (UTEQ)**  
Proyecto: **Sistema Web de Gestión de Restaurante**

Este documento registra los casos de prueba ejecutados para cada requisito funcional, incluyendo casos positivos y negativos (pruebas de límites y de inyección).

---

## Pruebas de la Fase 0 — Entorno y Conexión

| ID Prueba | Requisito / Componente | Datos de Entrada | Resultado Esperado | Resultado Obtenido | Estado | Evidencia |
|---|---|---|---|---|---|---|
| **CP-F0-01** | Conexión PDO PostgreSQL | Variables `.env` (`localhost:5434`) | Conexión exitosa mediante PDO Singleton | Conexión establecida | APROBADA | `docs/evidencias/F0_conexion_pdo.png` |
| **CP-F0-02** | Consulta `SELECT version()` | `SELECT version()` | Cadena con versión de PostgreSQL 16 | Versión obtenida correctamente | APROBADA | `docs/evidencias/F0_select_version.png` |
| **CP-F0-03** | Conteo de platos sembrados | `SELECT COUNT(*) FROM plato` | Retorna 15 platos | 15 platos retornados | APROBADA | `docs/evidencias/F0_conteo_platos.png` |
| **CP-F0-04** | Compatibilidad bcrypt | Clave `admin123` contra hash pgcrypto | `password_verify` retorna `true` | Retorna `true` | APROBADA | `docs/evidencias/F0_bcrypt.png` |
| **CP-F0-05** | Prevención SQL Injection | Payload: `' OR '1'='1` con `:username` | 0 filas retornadas (no evade la consulta) | 0 filas retornadas | APROBADA | `docs/evidencias/F0_sql_injection.png` |
| **CP-F0-06** | Endpoint Web `GET /salud` | Solicitud HTTP a `http://localhost:8000/salud` | Página HTML con Bootstrap mostrando estado OPERACIONAL y tabla de platos | Renderizado correcto con HTTP 200 | APROBADA | `docs/evidencias/F0_get_salud.png` |

---

## Pruebas de la Fase 1 — Seguridad y Accesos (RF-01, RF-02, RF-03)

| ID Prueba | Requisito / Componente | Datos de Entrada | Resultado Esperado | Resultado Obtenido | Estado | Evidencia |
|---|---|---|---|---|---|---|
| **CP-RF01-01** | Inicio de sesión válido (RF-01) | Usuario: `admin`, Clave: `admin123` | Login exitoso, `session_regenerate_id`, redirección a panel según rol (`/usuarios`) | Autenticado con rol `administrador` | APROBADA | `docs/evidencias/RF-01_login_exitoso.png` |
| **CP-RF01-02** | Inicio de sesión inválido (RF-01) | Usuario: `admin`, Clave: `erronea123` | Rechazo con mensaje de error sin revelar detalles internos | "Usuario o contraseña incorrectos" | APROBADA | `docs/evidencias/RF-01_login_invalido.png` |
| **CP-RF01-03** | Cierre de sesión (Logout) (RF-01) | Petición POST a `/logout` con token CSRF | Sesión destruida, cookie invalidada, redirección a `/login`; no se puede volver a pantallas internas | Sesión destruida, acceso interno bloqueado | APROBADA | `docs/evidencias/RF-01_logout.png` |
| **CP-RF02-01** | Bloqueo de usuario inactivo (RF-02) | Usuario: `usuario_inactivo`, Clave válida, `activo = false` | Login rechazado con mensaje indicando cuenta desactivada | "Tu cuenta ha sido desactivada. Comunícate con el administrador." | APROBADA | `docs/evidencias/RF-02_usuario_desactivado.png` |
| **CP-RF02-02** | Validación en creación de usuario (RF-02) | Cédula: 3 dígitos, Usuario: 2 caracteres, Clave: 5 caracteres | Errores en servidor detectados y mostrados al usuario | 3 errores de validación reportados | APROBADA | `docs/evidencias/RF-02_validacion_usuario.png` |
| **CP-RF02-03** | Creación y edición de usuario (RF-02) | Datos completos válidos, cédula 10 dígitos, rol mesero | Registro insertado en transacción `persona` + `usuario` y actualización de datos | Usuario registrado y actualizado en BD | APROBADA | `docs/evidencias/RF-02_crud_usuario.png` |
| **CP-RF02-04** | Baja lógica de usuario (RF-02) | Petición POST `/usuarios/estado` con `activo=0` | `activo` pasa a `false`, usuario no puede loguearse pero conserva historial | Estado actualizado a inactivo en BD | APROBADA | `docs/evidencias/RF-02_baja_logica.png` |
| **CP-RF02-05** | Integridad referencial en borrado (RF-02) | Intentar eliminar usuario 2 (`mesero1`) con pedidos históricos | Captura SQLSTATE 23503 (`ON DELETE RESTRICT`), bloqueo y sugerencia de desactivar | Bloqueo exitoso ofreciendo opción "Desactivar" | APROBADA | `docs/evidencias/RF-02_fk_restrict.png` |
| **CP-RF03-01** | Control de acceso por rol (HTTP 403) (RF-03) | Usuario `mesero1` intentando ingresar a `/usuarios` | HTTP 403 Acceso Denegado con pantalla amable y botón para volver al panel permitido | HTTP 403 mostrado con aviso claro | APROBADA | `docs/evidencias/RF-03_acceso_denegado_403.png` |
| **CP-RF03-02** | Menú adaptativo por rol (RF-03) | Sesión de `mesero1` vs `admin` | `mesero1` solo ve Clientes, Mesas, Menú y Pedidos; `admin` ve todos los módulos | Menú renderiza solo opciones autorizadas | APROBADA | `docs/evidencias/RF-03_menu_adaptativo.png` |
| **CP-CSRF-01** | Protección CSRF en backend | Petición POST con token ausente o inválido | HTTP 403 Solicitud no válida por token CSRF | Petición abortada de forma segura | APROBADA | `docs/evidencias/CSRF_validacion.png` |

---

## Pruebas de la Fase 2 — Maestros (Clientes RF-04, Mesas RF-07, Menú RF-08)

| ID Prueba | Requisito / Componente | Datos de Entrada | Resultado Esperado | Resultado Obtenido | Estado | Evidencia |
|---|---|---|---|---|---|---|
| **CP-RF04-01** | Validación formato de cédula (RF-04) | Documento con menos de 10 dígitos (ej: `12345`) | Rechazo con mensaje: debe contener exactamente 10 dígitos numéricos | Error de validación reportado | APROBADA | `docs/evidencias/RF-04_cedula_invalida.png` |
| **CP-RF04-02** | Unicidad de cédula en clientes (RF-04) | Cédula ya existente (ej: `0928374650`) | Rechazo con mensaje de documento duplicado | Bloqueo con mensaje claro | APROBADA | `docs/evidencias/RF-04_cedula_duplicada.png` |
| **CP-RF04-03** | Búsqueda simple de clientes (RF-04) | Término: `Juan` o `0928` | Listado filtra clientes coincidentes en nombre o cédula | Búsqueda filtrada correctamente | APROBADA | `docs/evidencias/RF-04_busqueda_cliente.png` |
| **CP-RF04-04** | CRUD Clientes (RF-04) | Datos válidos con cédula de 10 dígitos | Inserción en transacción `persona` + `cliente` y visualización inmediata | Registro creado y editable en BD | APROBADA | `docs/evidencias/RF-04_crud_cliente.png` |
| **CP-RF04-05** | Control referencial clientes (RF-04) | Intentar eliminar cliente con reservas previas | Bloqueo de borrado con mensaje informativo | Bloqueado por integridad referencial | APROBADA | `docs/evidencias/RF-04_fk_cliente.png` |
| **CP-RF07-01** | Validación capacidad y número único mesa (RF-07) | Capacidad = 0 o número de mesa ya registrado | Rechazo en servidor con mensaje de validación | Detectado número duplicado y capacidad <= 0 | APROBADA | `docs/evidencias/RF-07_validacion_mesa.png` |
| **CP-RF07-02** | Cambio rápido de estado de mesa (RF-07) | Cambio de `libre` a `ocupada` desde selector de tabla | Estado guardado en BD y reflejado de inmediato en la interfaz | Cambio inmediato guardado | APROBADA | `docs/evidencias/RF-07_cambio_estado_mesa.png` |
| **CP-RF07-03** | Integridad referencial mesas (RF-07) | Intentar eliminar mesa con pedidos históricos (Mesa #1) | Bloqueo por restricción `ON DELETE RESTRICT` | Bloqueado con mensaje explicativo | APROBADA | `docs/evidencias/RF-07_fk_mesa.png` |
| **CP-RF07-04** | Permisos por rol en mesas (RF-07) | Usuario `mesero1` accediendo a `/mesas/crear` | HTTP 403 Forbidden (solo puede ver y cambiar estado) | HTTP 403 Acceso Denegado | APROBADA | `docs/evidencias/RF-07_permisos_mesas.png` |
| **CP-RF08-01** | Validación precio de plato > 0 (RF-08) | Precio = `0.00` o negativo | Rechazo por validación de servidor (`CHECK precio > 0`) | Error reportado: precio debe ser mayor a 0 | APROBADA | `docs/evidencias/RF-08_precio_invalido.png` |
| **CP-RF08-02** | Creación de plato en menú (RF-08) | Categoría, nombre, precio `$4.50`, activo | Plato registrado y visible en catálogo | Plato creado con formato $4.50 USD | APROBADA | `docs/evidencias/RF-08_crear_plato.png` |
| **CP-RF08-03** | Baja lógica de plato (RF-08) | Desactivar plato (`activo = false`) | El plato se oculta para nuevos pedidos pero se mantiene en el histórico | Plato desactivado correctamente | APROBADA | `docs/evidencias/RF-08_baja_logica_plato.png` |
| **CP-RF08-04** | Bloqueo de eliminación con pedidos (RF-08) | Intentar eliminar plato con ventas históricas | Bloqueo por integridad y sugerencia de desactivar | Bloqueado ofreciendo "Desactivar" | APROBADA | `docs/evidencias/RF-08_fk_plato.png` |
| **CP-RF08-05** | Permisos por rol en menú (RF-08) | `mesero1` o `cocinero1` intentando crear plato | HTTP 403 Forbidden (solo consulta permitida) | HTTP 403 Acceso Denegado | APROBADA | `docs/evidencias/RF-08_permisos_menu.png` |

---

## Pruebas de la Fase 3 — Pedidos (RF-09, RF-10, RF-11)

| ID Prueba | Requisito / Componente | Datos de Entrada | Resultado Esperado | Resultado Obtenido | Estado | Evidencia |
|---|---|---|---|---|---|---|
| **CP-RF09-01** | Creación de pedido para llevar (RF-09) | Tipo `llevar`, 2 comensales, 2 platos activos | Pedido insertado en transacción atómica; total = Σ(cantidad × precio) calculado en servidor | Pedido creado; total coincide con cálculo servidor | APROBADA | `tests/fase3_pedidos_test.php` |
| **CP-RF09-02** | Creación de pedido por mesa (RF-09) | Tipo `mesa`, mesa libre, 1 plato | Pedido insertado; mesa cambia a `ocupada` inmediatamente | Mesa en `ocupada` tras crear pedido | APROBADA | `tests/fase3_pedidos_test.php` |
| **CP-RF09-03** | Rechazo de plato inactivo en pedido (RF-09, RF-08) | Plato con `activo = false` en detalle | Excepción con mensaje `desactivado`; transacción revertida | RuntimeException lanzada; BD intacta | APROBADA | `tests/fase3_pedidos_test.php` |
| **CP-RF09-04** | Historial inicial del pedido (RF-09, RF-10) | Crear pedido nuevo | `historial_estado_pedido` tiene exactamente 1 registro con estado `pendiente` | 1 registro `pendiente` con `cambiado_en` | APROBADA | `tests/fase3_pedidos_test.php` |
| **CP-RF10-01** | Transición válida completa (RF-10) | `pendiente → en_preparacion → listo → entregado` | Cada transición retorna `ok = true`; historial tiene 4 registros | 4 registros con hora exacta en `historial_estado_pedido` | APROBADA | `tests/fase3_pedidos_test.php` |
| **CP-RF10-02** | Rechazo de salto de estado (RF-10) | `en_preparacion → entregado` (omitiendo `listo`) | `ok = false`; estado no cambia en BD | Transición rechazada con mensaje descriptivo | APROBADA | `tests/fase3_pedidos_test.php` |
| **CP-RF10-03** | Rechazo de retroceso de estado (RF-10) | `en_preparacion → pendiente` | `ok = false`; estado no retrocede | Retroceso bloqueado | APROBADA | `tests/fase3_pedidos_test.php` |
| **CP-RF10-04** | Estado terminal sin más transiciones (RF-10) | `entregado → cancelado` | `ok = false`; estado `entregado` es terminal | Ninguna transición aceptada desde `entregado` | APROBADA | `tests/fase3_pedidos_test.php` |
| **CP-RF10-05** | Endpoint JSON de cocina (RF-10) | `listarParaCocina()` | Array con campos `id`, `estado`, `tipo`, `items` (array) | Estructura válida; `cancelado` no incluido | APROBADA | `tests/fase3_pedidos_test.php` |
| **CP-RF11-01** | Bloqueo de modificación post-pendiente (RF-11) | Modificar pedido en `en_preparacion` | `ok = false`; mensaje menciona estado bloqueante | Modificación rechazada | APROBADA | `tests/fase3_pedidos_test.php` |
| **CP-RF11-02** | Bloqueo de cancelación post-pendiente (RF-11) | Cancelar pedido en `en_preparacion` | `ok = false`; cancelación rechazada | Cancelación bloqueada | APROBADA | `tests/fase3_pedidos_test.php` |
| **CP-RF11-03** | Cancelación exitosa en estado pendiente (RF-11) | Cancelar pedido en `pendiente` | `ok = true`; estado pasa a `cancelado` en BD | Estado `cancelado` confirmado | APROBADA | `tests/fase3_pedidos_test.php` |

---

## Pruebas de la Fase 4 — Predicciones (P1, P4, P6)

| ID Prueba | Requisito / Componente | Datos de Entrada | Resultado Esperado | Resultado Obtenido | Estado | Evidencia |
|---|---|---|---|---|---|---|
| **CP-P1-01** | P1: Pedidos por hora — datos suficientes | Fecha objetivo con 4 semanas del mismo DOW | Gráfico de barras con 13 franjas horarias (10–22); picos verdes en 12–14 y 19–21 | Gráfico renderizado; promedios coherentes con histórico | APROBADA | `tests/fase4_predicciones_test.php` |
| **CP-P1-02** | P1: Advertencia datos insuficientes | Fecha con menos de 4 semanas disponibles | Alerta amarilla visible: "Solo se encontraron N semana(s)" | Alerta mostrada y gráfico con datos parciales | APROBADA | `docs/evidencias/P1_advertencia.png` |
| **CP-P4-01** | P4: Platos más demandados — datos suficientes | Misma fecha objetivo con 4 semanas del DOW | Top 10 platos activos en gráfico horizontal + tabla con barras de progreso | Platos ordenados por promedio descendente; tabla y gráfico coherentes | APROBADA | `tests/fase4_predicciones_test.php` |
| **CP-P4-02** | P4: Solo platos activos | Existencia de platos inactivos en la BD | Platos con `activo = false` NO aparecen en la predicción | Ningún plato inactivo en la lista | APROBADA | `tests/fase4_predicciones_test.php` |
| **CP-P6-01** | P6: Venta proyectada del día | Fecha objetivo con histórico completo (4 sem.) | Tarjeta con `$X.XX` USD; sparkline de 7 días con línea de proyección punteada | Proyección visible; sparkline muestra datos reales vs. proyección | APROBADA | `tests/fase4_predicciones_test.php` |
| **CP-P6-02** | P6: Acceso restringido por rol (RF-03) | Sesión con rol `mesero` o `cocinero` → GET `/predicciones` | HTTP 403 Acceso Denegado | 403 mostrado; módulo no accesible | APROBADA | `docs/evidencias/P6_acceso_403.png` |
| **CP-DEMO-01** | Etiqueta "Datos de demostración" | Acceso a `/predicciones` con cualquier fecha | La etiqueta **📂 Datos de demostración** es visible en el encabezado y en cada sección | Badge amarillo presente en 4 ubicaciones de la página | APROBADA | `docs/evidencias/DEMO_badge.png` |

---

## Pruebas de la Fase 5 — Verificación General e Integridad de BD

| ID Prueba | Requisito / Componente | Datos de Entrada | Resultado Esperado | Resultado Obtenido | Estado | Evidencia |
|---|---|---|---|---|---|---|
| **CP-F5-01** | Script de comprobación SQL integral | `tests/verificacion.sql` en PostgreSQL | Ejecución de las 30 consultas sin errores de sintaxis ni de schema | Ejecutado 100% exitoso (0 huérfanos, 0 saltos de estado) | APROBADA | `tests/verificacion.sql` |
| **CP-F5-02** | Consistencia de totales de pedido | Comparación total cabecera vs suma de ítems | 0 discrepancias contables en todos los pedidos | 0 filas con diferencias (`ROUND(total - sum) = 0`) | APROBADA | `tests/verificacion.sql` (V-RF09-02) |
| **CP-F5-03** | Integridad referencial y estado de facturación | Facturas emitidas vs pedidos entregados | Todas las facturas asociadas a pedidos en estado 'entregado' | 0 facturas huérfanas o con estado erróneo | APROBADA | `tests/verificacion.sql` (V-INT-03) |
| **CP-F5-04** | Suite completa de pruebas automatizadas | Ejecución de smoke, fase 1, 2, 3 y 4 | 100% de aserciones en estado PASS | Todas las suites pasaron sin fallos | APROBADA | Terminal / Tests CLI |
