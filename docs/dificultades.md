# Registro de Dificultades Técnicas y Soluciones

Materia: **Administración de Bases de Datos (UTEQ)**  
Proyecto: **Sistema Web de Gestión de Restaurante**

---

### Dificultad 1: Disponibilidad del puerto estándar 5432 de PostgreSQL
- **Descripción:** En entornos Windows y estaciones de trabajo de estudiantes universitarios, el puerto `5432` suele estar en uso por servicios locales de PostgreSQL o contenedores previos.
- **Impacto:** Falla al intentar levantar el contenedor Docker con mensaje `bind: address already in use`.
- **Solución implementada:** Se configuró el puerto del host en `5434` mediante la variable `${DB_PORT:-5434}:5432` en `docker-compose.yml` y `.env` para evitar cualquier colisión con el contenedor `postgres_abd` preexistente en el puerto 5433.

### Dificultad 2: Compatibilidad de hashing entre `pgcrypto` y PHP
- **Descripción:** Las contraseñas debían ser sembradas directamente en el script SQL para los usuarios iniciales y luego ser validadas en PHP con `password_verify()`.
- **Impacto:** Si el formato del algoritmo o costo salino difiere, la autenticación fallaría.
- **Solución implementada:** Se utilizó `crypt('clave', gen_salt('bf', 10))` en `database/02_datos_base.sql`, lo cual genera un hash compatible con el algoritmo Blowfish (`$2a$10$...`) reconocido nativamente por PHP.

### Dificultad 3: Mapeo de tipos booleanos en PostgreSQL con PDO no emulado
- **Descripción:** Al usar `PDO::ATTR_EMULATE_PREPARES => false`, pasar valores booleanos `false` de PHP en arrays de parámetros asociativos de `$stmt->execute(['activo' => false])` se convertía en una cadena vacía `""`, produciendo el error de PostgreSQL `SQLSTATE[22P02]: invalid input syntax for type boolean: ""`.
- **Impacto:** Falla en la inserción o actualización de usuarios cuando el campo `activo` se establecía en falso.
- **Solución implementada:** Se normalizó el parámetro para enviar explícitamente los literales booleanos reconocidos por PostgreSQL (`'true'` y `'false'`) en `UsuarioModel.php`.

---

### Dificultad 4: Aparente bypass de protección CSRF en DevTools de Opera
- **Descripción:** Al probar el formulario de creación de usuarios, se eliminó desde las herramientas de desarrollador el campo `input[name="csrf_token"]` del DOM. El formulario se envió y el usuario se creó, sugiriendo que la protección CSRF no funcionaba.
- **Causa real:** El DOM contenía **dos** formularios con `name="csrf_token"`: el formulario de usuario y el de logout en la barra de navegación. La búsqueda en DevTools seleccionó y eliminó el input del formulario de **logout**, dejando intacto el token del formulario de usuario. El servidor sí validó el token correctamente.
- **Solución implementada:** Se asignaron IDs únicos a cada input CSRF — `id="csrf_token_usuario"` en formularios de datos e `id="csrf_token_logout"` en el navbar — para evitar ambigüedades en DevTools. Adicionalmente, se verificó que `Router::dispatch()` rechace con HTTP 403 cualquier POST sin token válido antes de llamar al controlador.

---

### Dificultad 5: Método `Csrf::token()` inexistente en la vista de cocina
- **Descripción:** La vista `src/views/cocina/index.php` llamaba a `Csrf::token()` para obtener el token como string para inyectarlo en el bloque JavaScript de AJAX, pero ese método no existe en la clase `App\Core\Csrf`.
- **Impacto:** `Fatal error: Call to undefined method App\Core\Csrf::token()` al acceder a `/cocina`.
- **Solución implementada:** Se reemplazó `Csrf::token()` por el método correcto `Csrf::getToken()`, que es el mismo que usan todos los demás módulos del sistema. No se crearon métodos adicionales ni se modificó la clase `Csrf`.

---

### Dificultad 6: Bucle de bloqueo por redirección 403 y token CSRF vacío en pantalla de acceso denegado
- **Descripción:** Al iniciar sesión como usuario con rol Cajero, la aplicación lo redirigía a `/pedidos` (ruta configurada inicialmente en `getHomeRoute()`), módulo restringido exclusivamente a Administrador y Mesero, disparando la pantalla HTTP 403 «Acceso denegado». En dicha pantalla, el botón de cierre de sesión contenía un input oculto con token CSRF vacío (`value=""`), por lo que al intentar cerrar sesión el servidor rechazaba la petición POST con «Solicitud no válida o sesión expirada», dejando al usuario atrapado sin poder salir ni volver al login.
- **Impacto:** Imposibilidad de continuar operando o cerrar sesión con el rol Cajero tras autenticarse.
- **Solución implementada:** Se ajustó `Auth::getHomeRoute()` para dirigir al cajero a su ruta autorizada `/panel`, se creó su vista respectiva `src/views/panel/cajero.php`, se corrigió la pantalla de error 403 para generar tokens CSRF válidos en el formulario de logout, se añadió soporte limpio de salida sin atrapar al usuario en `/logout`, y se incorporaron cabeceras HTTP de no-caché (`Cache-Control: no-store`) en `Auth::requireLogin()` para impedir volver a pantallas protegidas con el botón «Atrás» del navegador tras cerrar sesión.
