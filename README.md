# Sistema Web de Gestión de Restaurante

Proyecto de la asignatura **Administración de Bases de Datos**, Universidad Técnica Estatal de Quevedo (**UTEQ**).

Este sistema centraliza la gestión operativa de un restaurante: seguridad por roles, catálogo de clientes, control de mesas, menú, pedidos en tiempo real con pantalla de cocina y predicciones analíticas sobre PostgreSQL 16.

---

## Estado del Proyecto

| Fase | Módulo | Requisitos | Estado |
|---|---|---|---|
| Fase 0 | Entorno y conexión | Conexión PDO, schema, seed | ✅ Completado |
| Fase 1 | Seguridad y accesos | RF-01, RF-02, RF-03 | ✅ Completado |
| Fase 2 | Maestros | RF-04, RF-07, RF-08 | ✅ Completado |
| Fase 3 | Pedidos y cocina | RF-09, RF-10, RF-11 | ✅ Completado |
| Fase 4 | Predicciones | P1, P4, P6 | ✅ Completado |
| Fase 5 | Pruebas y cierre | verificacion.sql, README, docs | ✅ Completado |

---

## 1. Tecnologías y Versiones

- **Base de Datos:** PostgreSQL 16 (desplegado en contenedor Docker).
- **Backend:** PHP 8.2+ (CLI y servidor integrado) sin frameworks.
- **Acceso a Datos:** PDO nativo (`pdo_pgsql`) con consultas preparadas y parametrizadas obligatorias.
- **Frontend:** HTML5, CSS3, JavaScript Vanilla y Bootstrap 5.3 (CDN).
- **Entorno de Trabajo:** Windows con Git Bash / Docker Desktop.

---

## 2. Requisitos Previos

1. **Docker Desktop** instalado y en ejecución.
2. **PHP 8.2 o superior** con la extensión `pdo_pgsql` habilitada en `php.ini`.
3. **Git** y terminal **Git Bash**.

---

## 3. Guía de Instalación y Ejecución Paso a Paso (Git Bash)

Sigue estos comandos uno por uno desde la terminal Git Bash:

### Paso 1: Ingresar al directorio del proyecto
```bash
git clone https://github.com/jmaciasherr4/SGR-5A_GRUPO_6.git
cd SGR-5A_GRUPO_6
```

### Paso 2: Verificar que la extensión `pdo_pgsql` esté activa en PHP
```bash
php -m | grep pdo_pgsql
```
*(Debe retornar `pdo_pgsql`. Si no aparece, asegúrate de descomentar `extension=pdo_pgsql` en tu `php.ini`).*

### Paso 3: Configurar las variables de entorno
```bash
cp .env.example .env
```

### Paso 4: Iniciar el contenedor de PostgreSQL con Docker Compose
```bash
docker compose up -d
```
*(Si usas la versión con guión: `docker-compose up -d`).*

### Paso 5: Verificar que el contenedor esté saludable (`healthy`)
```bash
docker compose ps
```
*(El contenedor `restaurante_db` debe indicar `healthy` en el puerto `5434->5432/tcp`).*

### Paso 6: Ejecutar las pruebas de humo iniciales (Smoke Test)
```bash
php tests/smoke.php
```
*(Todas las comprobaciones deben mostrar `[OK]`).*

### Paso 7: Iniciar el servidor web de desarrollo
```bash
php -S localhost:8000 -t public
```

#### Paso 8: Abrir en el navegador

Aplicación:

```
http://localhost:8000
```

Verificar la conexión con la base de datos:

```
http://localhost:8000/salud
```

Inicia sesión con `admin` / `admin123`.


## 4. Usuarios de Prueba del Sistema

Todos los usuarios de demostración han sido creados en `database/02_datos_base.sql` con la siguiente contraseña de desarrollo:

- **Contraseña universal de desarrollo:** `admin123`

| Usuario | Rol | Módulos accesibles |
|---|---|---|
| `admin` | Administrador | Todos: Usuarios, Clientes, Mesas, Menú, Pedidos, Cocina, **Predicciones** |
| `mesero1` | Mesero | Clientes, Mesas (ver/estado), Menú (consulta), Pedidos (crear/modificar) |
| `cajero1` | Cajero | — (acceso base; módulo de facturación fuera de alcance en esta etapa) |
| `cocinero1` | Cocinero | Menú (consulta), **Pantalla de Cocina** (ver y avanzar estados) |

---

## 5. Módulos Implementados

| Módulo | URL | Rol(es) | Descripción |
|---|---|---|---|
| Login / Logout | `/login` | Todos | Autenticación con bcrypt y control de sesión |
| Panel | `/panel` | Todos | Redirección según rol |
| Usuarios | `/usuarios` | Administrador | CRUD con baja lógica |
| Clientes | `/clientes` | Admin, Mesero | CRUD con validación de cédula 10 dígitos |
| Mesas | `/mesas` | Admin (CRUD), Mesero (ver/estado) | Control de zonas, capacidad y estado |
| Menú | `/menu` | Admin (CRUD), Mesero/Cocinero (consulta) | Platos y categorías con baja lógica |
| Pedidos | `/pedidos` | Admin, Mesero | Crear, ver, editar (solo pendiente), cancelar |
| Pantalla de Cocina | `/cocina` | Admin, Cocinero | Kanban con auto-refresh JSON cada 3 s |
| **Predicciones** | `/predicciones` | **Administrador** | P1 (horas), P4 (platos), P6 (ventas) con Chart.js |
| Verificación | `/salud` | Todos | Estado de conexión a BD |

---

## 5. Cómo Probar en Tableta u Otro Dispositivo de la Misma Red

Para evaluar el diseño responsivo táctil en una tableta (768–1024 px) o teléfono:

### Paso 1: Conocer la dirección IP local de tu laptop
En Git Bash ejecuta:
```bash
ipconfig | grep -i "IPv4"
```
*(Ejemplo de IP obtenida: `192.168.100.25`).*

### Paso 2: Iniciar el servidor escuchando en todas las interfaces de red
```bash
php -S 0.0.0.0:8000 -t public
```

### Paso 3: Acceder desde la tableta
En el navegador de la tableta conectada a la misma red Wi-Fi, abre:
```
http://192.168.100.25:8000/salud
```

---

## 6. Cómo Reiniciar la Base de Datos desde Cero

Si necesitas limpiar completamente la base de datos y volver a ejecutar todos los scripts de inicialización (`01_schema.sql`, `02_datos_base.sql`, `03_datos_historicos.sql`):

```bash
docker compose down -v
```

```bash
docker compose up -d
```

*(El parámetro `-v` destruye el volumen `restaurante_pgdata`, permitiendo que `/docker-entrypoint-initdb.d` vuelva a inicializar los datos).*

---

## 7. Estructura de Carpetas

```
/
├─ docker-compose.yml          # Definición del contenedor PostgreSQL 16 (puerto 5434)
├─ Dockerfile.web              # Imagen PHP opcional para contenedor web
├─ .env.example                # Plantilla de variables de entorno
├─ .env                        # Variables locales activas (ignorado en git)
├─ .gitignore                  # Exclusiones de Git
├─ .gitattributes              # Normalización de saltos de línea LF
├─ README.md                   # Documentación principal e instrucciones
├─ database/
│   ├─ 01_schema.sql           # Tablas, claves primarias, foráneas (RESTRICT), checks e índices
│   ├─ 02_datos_base.sql       # Roles, usuarios de prueba (bcrypt), menú, mesas, ingredientes
│   ├─ 03_datos_historicos.sql # 6 semanas de pedidos y facturas deterministas para predicciones
│   └─ cambios/                # Migraciones y alteraciones futuras numeradas
├─ public/
│   ├─ index.php               # Front controller principal del sistema
│   └─ assets/
│       ├─ css/app.css         # Estilos base, áreas táctiles >=44px, responsividad
│       └─ js/                 # Scripts JS vanilla
├─ src/
│   ├─ bootstrap.php           # Autocargador PSR-4, zona horaria y carga de entorno
│   ├─ config/
│   │   └─ database.php        # Configuración de conexión PDO
│   ├─ core/
│   │   ├─ Database.php        # Singleton PDO con ERRMODE_EXCEPTION y logs de error
│   │   ├─ Router.php          # Enrutador simple GET/POST
│   │   └─ Env.php             # Lector simple de archivo .env
│   ├─ controllers/            # Controladores del sistema
│   ├─ models/                 # Modelos / Repositorios con consultas preparadas
│   └─ views/                  # Vistas PHP limpias con Bootstrap
├─ storage/
│   └─ logs/                   # Registro de errores del sistema (database.log)
├─ tests/
│   ├─ smoke.php               # Prueba de humo CLI (conexión, conteo y prevención SQLi)
│   └─ verificacion.sql        # Consultas de verificación para DBeaver
└─ docs/
    ├─ decisiones.md           # Registro de decisiones de arquitectura (ADR)
    ├─ estado_requisitos.md    # Matriz de seguimiento de requisitos RF-01 a RF-11 y P1, P4, P6
    ├─ pruebas.md              # Casos de prueba detallados y registro de evidencias
    ├─ dificultades.md         # Registro de problemas técnicos y soluciones
    ├─ predicciones.md         # Documentación de metodología de predicciones
    └─ evidencias/             # Carpeta para capturas de pantalla de pruebas
```
# Sistema Web de Gestión de Restaurante

Proyecto de la asignatura **Administración de Bases de Datos**, Universidad Técnica Estatal de Quevedo (**UTEQ**).

Este sistema centraliza la gestión operativa de un restaurante: seguridad por roles, catálogo de clientes, control de mesas, menú, pedidos en tiempo real con pantalla de cocina y predicciones analíticas sobre PostgreSQL 16.

---

## Estado del Proyecto

| Fase | Módulo | Requisitos | Estado |
|---|---|---|---|
| Fase 0 | Entorno y conexión | Conexión PDO, schema, seed | ✅ Completado |
| Fase 1 | Seguridad y accesos | RF-01, RF-02, RF-03 | ✅ Completado |
| Fase 2 | Maestros | RF-04, RF-07, RF-08 | ✅ Completado |
| Fase 3 | Pedidos y cocina | RF-09, RF-10, RF-11 | ✅ Completado |
| Fase 4 | Predicciones | P1, P4, P6 | ✅ Completado |
| Fase 5 | Pruebas y cierre | verificacion.sql, README, docs | ✅ Completado |

---

## 1. Tecnologías y Versiones

- **Base de Datos:** PostgreSQL 16 (desplegado en contenedor Docker).
- **Backend:** PHP 8.2+ (CLI y servidor integrado) sin frameworks.
- **Acceso a Datos:** PDO nativo (`pdo_pgsql`) con consultas preparadas y parametrizadas obligatorias.
- **Frontend:** HTML5, CSS3, JavaScript Vanilla y Bootstrap 5.3 (CDN).
- **Entorno de Trabajo:** Windows con Git Bash / Docker Desktop.

---

## 2. Requisitos Previos

1. **Docker Desktop** instalado y en ejecución.
2. **PHP 8.2 o superior** con la extensión `pdo_pgsql` habilitada en `php.ini`.
3. **Git** y terminal **Git Bash**.

---

## 3. Guía de Instalación y Ejecución Paso a Paso (Git Bash)

Sigue estos comandos uno por uno desde la terminal Git Bash:

### Paso 1: Ingresar al directorio del proyecto
```bash
git clone https://github.com/jmaciasherr4/SGR-5A_GRUPO_6.git
cd SGR-5A_GRUPO_6
```

### Paso 2: Verificar que la extensión `pdo_pgsql` esté activa en PHP
```bash
php -m | grep pdo_pgsql
```
*(Debe retornar `pdo_pgsql`. Si no aparece, asegúrate de descomentar `extension=pdo_pgsql` en tu `php.ini`).*

### Paso 3: Configurar las variables de entorno
```bash
cp .env.example .env
```

### Paso 4: Iniciar el contenedor de PostgreSQL con Docker Compose
```bash
docker compose up -d
```
*(Si usas la versión con guión: `docker-compose up -d`).*

### Paso 5: Verificar que el contenedor esté saludable (`healthy`)
```bash
docker compose ps
```
*(El contenedor `restaurante_db` debe indicar `healthy` en el puerto `5434->5432/tcp`).*

### Paso 6: Ejecutar las pruebas de humo iniciales (Smoke Test)
```bash
php tests/smoke.php
```
*(Todas las comprobaciones deben mostrar `[OK]`).*

### Paso 7: Iniciar el servidor web de desarrollo
```bash
php -S localhost:8000 -t public
```

#### Paso 8: Abrir en el navegador

Aplicación:

```
http://localhost:8000
```

Verificar la conexión con la base de datos:

```
http://localhost:8000/salud
```

Inicia sesión con `admin` / `admin123`.


## 4. Usuarios de Prueba del Sistema

Todos los usuarios de demostración han sido creados en `database/02_datos_base.sql` con la siguiente contraseña de desarrollo:

- **Contraseña universal de desarrollo:** `admin123`

| Usuario | Rol | Módulos accesibles |
|---|---|---|
| `admin` | Administrador | Todos: Usuarios, Clientes, Mesas, Menú, Pedidos, Cocina, **Predicciones** |
| `mesero1` | Mesero | Clientes, Mesas (ver/estado), Menú (consulta), Pedidos (crear/modificar) |
| `cajero1` | Cajero | — (acceso base; módulo de facturación fuera de alcance en esta etapa) |
| `cocinero1` | Cocinero | Menú (consulta), **Pantalla de Cocina** (ver y avanzar estados) |

---

## 5. Módulos Implementados

| Módulo | URL | Rol(es) | Descripción |
|---|---|---|---|
| Login / Logout | `/login` | Todos | Autenticación con bcrypt y control de sesión |
| Panel | `/panel` | Todos | Redirección según rol |
| Usuarios | `/usuarios` | Administrador | CRUD con baja lógica |
| Clientes | `/clientes` | Admin, Mesero | CRUD con validación de cédula 10 dígitos |
| Mesas | `/mesas` | Admin (CRUD), Mesero (ver/estado) | Control de zonas, capacidad y estado |
| Menú | `/menu` | Admin (CRUD), Mesero/Cocinero (consulta) | Platos y categorías con baja lógica |
| Pedidos | `/pedidos` | Admin, Mesero | Crear, ver, editar (solo pendiente), cancelar |
| Pantalla de Cocina | `/cocina` | Admin, Cocinero | Kanban con auto-refresh JSON cada 3 s |
| **Predicciones** | `/predicciones` | **Administrador** | P1 (horas), P4 (platos), P6 (ventas) con Chart.js |
| Verificación | `/salud` | Todos | Estado de conexión a BD |

---

## 5. Cómo Probar en Tableta u Otro Dispositivo de la Misma Red

Para evaluar el diseño responsivo táctil en una tableta (768–1024 px) o teléfono:

### Paso 1: Conocer la dirección IP local de tu laptop
En Git Bash ejecuta:
```bash
ipconfig | grep -i "IPv4"
```
*(Ejemplo de IP obtenida: `192.168.100.25`).*

### Paso 2: Iniciar el servidor escuchando en todas las interfaces de red
```bash
php -S 0.0.0.0:8000 -t public
```

### Paso 3: Acceder desde la tableta
En el navegador de la tableta conectada a la misma red Wi-Fi, abre:
```
http://192.168.100.25:8000/salud
```

---

## 6. Cómo Reiniciar la Base de Datos desde Cero

Si necesitas limpiar completamente la base de datos y volver a ejecutar todos los scripts de inicialización (`01_schema.sql`, `02_datos_base.sql`, `03_datos_historicos.sql`):

```bash
docker compose down -v
```

```bash
docker compose up -d
```

*(El parámetro `-v` destruye el volumen `restaurante_pgdata`, permitiendo que `/docker-entrypoint-initdb.d` vuelva a inicializar los datos).*

---

## 7. Estructura de Carpetas

```
/
├─ docker-compose.yml          # Definición del contenedor PostgreSQL 16 (puerto 5434)
├─ Dockerfile.web              # Imagen PHP opcional para contenedor web
├─ .env.example                # Plantilla de variables de entorno
├─ .env                        # Variables locales activas (ignorado en git)
├─ .gitignore                  # Exclusiones de Git
├─ .gitattributes              # Normalización de saltos de línea LF
├─ README.md                   # Documentación principal e instrucciones
├─ database/
│   ├─ 01_schema.sql           # Tablas, claves primarias, foráneas (RESTRICT), checks e índices
│   ├─ 02_datos_base.sql       # Roles, usuarios de prueba (bcrypt), menú, mesas, ingredientes
│   ├─ 03_datos_historicos.sql # 6 semanas de pedidos y facturas deterministas para predicciones
│   └─ cambios/                # Migraciones y alteraciones futuras numeradas
├─ public/
│   ├─ index.php               # Front controller principal del sistema
│   └─ assets/
│       ├─ css/app.css         # Estilos base, áreas táctiles >=44px, responsividad
│       └─ js/                 # Scripts JS vanilla
├─ src/
│   ├─ bootstrap.php           # Autocargador PSR-4, zona horaria y carga de entorno
│   ├─ config/
│   │   └─ database.php        # Configuración de conexión PDO
│   ├─ core/
│   │   ├─ Database.php        # Singleton PDO con ERRMODE_EXCEPTION y logs de error
│   │   ├─ Router.php          # Enrutador simple GET/POST
│   │   └─ Env.php             # Lector simple de archivo .env
│   ├─ controllers/            # Controladores del sistema
│   ├─ models/                 # Modelos / Repositorios con consultas preparadas
│   └─ views/                  # Vistas PHP limpias con Bootstrap
├─ storage/
│   └─ logs/                   # Registro de errores del sistema (database.log)
├─ tests/
│   ├─ smoke.php               # Prueba de humo CLI (conexión, conteo y prevención SQLi)
│   └─ verificacion.sql        # Consultas de verificación para DBeaver
└─ docs/
    ├─ decisiones.md           # Registro de decisiones de arquitectura (ADR)
    ├─ estado_requisitos.md    # Matriz de seguimiento de requisitos RF-01 a RF-11 y P1, P4, P6
    ├─ pruebas.md              # Casos de prueba detallados y registro de evidencias
    ├─ dificultades.md         # Registro de problemas técnicos y soluciones
    ├─ predicciones.md         # Documentación de metodología de predicciones
    └─ evidencias/             # Carpeta para capturas de pantalla de pruebas
```
