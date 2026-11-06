# SGR-Sistema de Gestión de Restaurante

Aplicación de gestión para restaurantes pequeños y medianos que centraliza la información operativa (reservas, mesas, menú, pedidos, inventario, facturación y caja) en una base de datos relacional **PostgreSQL**, para reducir errores operativos y contar con información confiable.

> **Estado del proyecto:** etapa de especificación de requisitos (semana 2). Aún no hay código implementado.

## Actores del sistema

| Actor | Responsabilidad |
|---|---|
| Administrador | Configura y supervisa el sistema: usuarios, menú, inventario, facturas y caja. |
| Mesero | Atiende en el salón: reservas, estado de mesas y pedidos. |
| Cajero | Cobra pedidos, registra la forma de pago y controla la caja diaria. |
| Cocinero | Prepara los pedidos y consulta el inventario. |

## Módulos y funcionalidades previstas

| # | Módulo | Funcionalidades | Requisitos |
|---|---|---|---|
| 1 | Seguridad y accesos | Inicio y cierre de sesión, gestión de usuarios y roles, restricción de opciones por rol | RF-01, RF-02, RF-03 |
| 2 | Personas y clientes | Registrar, consultar y editar clientes | RF-04 |
| 3 | Reservas | Registrar, consultar, modificar y cancelar reservas; asignar mesa | RF-05, RF-06 |
| 4 | Mesas | Registrar y editar mesas; controlar su estado (libre, ocupada, reservada) | RF-07 |
| 5 | Menú | Registrar, editar y desactivar platos con categoría y precio | RF-08 |
| 6 | Pedidos | Crear pedidos por mesa o para llevar; modificar, cancelar y cambiar estado | RF-09, RF-10, RF-11 |
| 7 | Inventario | Registrar ingredientes, entradas y salidas de stock; alertas de stock bajo | RF-12, RF-13 |
| 8 | Facturación | Generar facturas con forma de pago; consultar facturas emitidas | RF-14, RF-15 |
| 9 | Control de caja | Abrir y cerrar caja con cuadre diario; historial de cierres | RF-16, RF-17 |

## Plan de desarrollo (orden preliminar)

1. **Fase 1 – Base:** seguridad y accesos, mesas, menú y clientes.
2. **Fase 2 – Operación del salón:** reservas y pedidos.
3. **Fase 3 – Cobro:** facturación (primera versión funcional).
4. **Fase 4 – Control:** modificación/cancelación de reservas y pedidos, inventario, consulta de facturas y caja.
5. **Fase 5 – Complementos:** historial de cierres de caja.

## Fuera del alcance de esta versión

Recetas con cálculo de costos, proveedores y órdenes de compra, control de turnos y asistencia de empleados, y reportes estadísticos avanzados.

## Tecnologías

- Base de datos: **PostgreSQL**
- Interfaz y lógica de negocio: por definir por el equipo

## Documentación

El informe maestro en LaTeX y el PDF actualizado se encuentran en la carpeta compartida del equipo.
