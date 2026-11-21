# Matriz de Estado de Requisitos del Sistema

Materia: **Administración de Bases de Datos (UTEQ)**  
Proyecto: **Sistema Web de Gestión de Restaurante**

---

| Código | Requisito / Descripción | Prioridad | Estado | Módulo / Archivo | Prueba Asociada |
|---|---|---|---|---|---|
| **RF-01** | Iniciar y cerrar sesión según credenciales y rol | Alta | **Completado (Fase 1)** | `src/controllers/AuthController.php`, `src/core/Auth.php` | CP-RF01-01 a CP-RF01-03 |
| **RF-02** | Gestión de usuarios (CRUD) con rol y baja lógica | Alta | **Completado (Fase 1)** | `src/controllers/UsuarioController.php`, `src/models/UsuarioModel.php` | CP-RF02-01 a CP-RF02-05 |
| **RF-03** | Control de acceso por rol (HTTP 403 y menú adaptativo) | Alta | **Completado (Fase 1)** | `src/core/Auth.php`, `src/views/layouts/main.php` | CP-RF03-01 a CP-RF03-02, CP-CSRF-01 |
| **RF-04** | Gestión de clientes (cédula 10 dígitos única, CRUD) | Alta | **Completado (Fase 2)** | `src/controllers/ClienteController.php`, `src/models/ClienteModel.php` | CP-RF04-01 a CP-RF04-05 |
| **RF-07** | Gestión de mesas (número único, capacidad > 0, estado) | Alta | **Completado (Fase 2)** | `src/controllers/MesaController.php`, `src/models/MesaModel.php` | CP-RF07-01 a CP-RF07-04 |
| **RF-08** | Menú y precios (categoría, precio > 0, baja lógica) | Alta | **Completado (Fase 2)** | `src/controllers/MenuController.php`, `src/models/MenuModel.php` | CP-RF08-01 a CP-RF08-05 |
| **RF-09** | Creación de pedido (mesa/llevar, transacción, cálculo servidor) | Alta | **Completado (Fase 3)** | `src/controllers/PedidoController.php`, `src/models/PedidoModel.php`, `src/views/pedidos/crear.php` | CP-RF09-01 a CP-RF09-04 |
| **RF-10** | Máquina de estados del pedido (sin saltos e historial) | Alta | **Completado (Fase 3)** | `src/controllers/CocinaController.php`, `src/views/cocina/index.php` | CP-RF10-01 a CP-RF10-05 |
| **RF-11** | Modificar/cancelar pedido solo en estado pendiente | Media | **Completado (Fase 3)** | `src/controllers/PedidoController.php`, `src/views/pedidos/editar.php` | CP-RF11-01 a CP-RF11-03 |
| **P1** | Predicción: Pedidos por hora (franjas horarias) | Alta | Pendiente (Fase 4) | `src/controllers/PrediccionController.php` | CP-P1 |
| **P4** | Predicción: Platos más vendidos (unidades estimadas) | Alta | Pendiente (Fase 4) | `src/controllers/PrediccionController.php` | CP-P4 |
| **P6** | Predicción: Ventas esperadas del día (total facturado) | Alta | Pendiente (Fase 4) | `src/controllers/PrediccionController.php` | CP-P6 |

*Leyenda de estados: `Completado`, `En desarrollo`, `Pendiente`.*
