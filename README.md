# Symfony Login

Aplicación de práctica desarrollada con **Symfony 8.1** y **PHP 8.4** que implementa un sistema de autenticación completo: páginas públicas, login por nombre de usuario, dashboard protegido por roles, logout con CSRF y persistencia en SQLite. Todo el entorno se ejecuta con Docker.

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4)
![Symfony](https://img.shields.io/badge/Symfony-8.1-000000)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED)

> Proyecto de aprendizaje. No está pensado para producción.

---

## Características

- Páginas públicas `/` y `/acerca-de`.
- Login por `username` con contraseña hasheada.
- Dashboard privado accesible solo con `ROLE_USER`.
- Logout mediante `POST` y token CSRF.
- Protección CSRF stateless en login y logout.
- Persistencia con Doctrine ORM sobre SQLite.
- Migraciones versionadas.
- Comando de consola para crear o actualizar usuarios de prueba.
- Twig, AssetMapper y Stimulus.
- Entorno reproducible con un único contenedor Docker.
- Documentación del proceso en `docs/`.

---

## Stack

| Tecnología | Uso |
|---|---|
| PHP 8.4 | Lenguaje de la aplicación |
| Symfony 8.1 | Framework |
| Doctrine ORM | Persistencia |
| SQLite | Base de datos |
| Twig | Plantillas |
| AssetMapper | CSS y JavaScript sin Node |
| Stimulus | Controlador JS para el CSRF |
| Docker Compose | Entorno de desarrollo |

> La práctica original pedía Symfony 7.4 LTS. El proyecto se ha desarrollado con Symfony 8.1 porque el entorno base ya utilizaba esa versión. La decisión está documentada en `docs/1-investigacion.md`.

---

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) en ejecución.
- Git.
- No necesitas PHP ni Composer instalados en el host: se usan dentro del contenedor.

---

## Puesta en marcha

### 1. Clonar el repositorio

```bash
git clone https://github.com/JitaProg/symfony-login.git
cd symfony-login
```

### 2. Levantar el contenedor

```bash
docker compose up -d --build
```

Comprueba que está activo:

```bash
docker compose ps
```

La aplicación queda disponible en:

```text
http://localhost:8080
```

### 3. Instalar dependencias

```bash
docker compose exec web composer install
```

### 4. Crear la base de datos

```bash
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction
```

Esto crea `var/data_dev.db` con la tabla `app_user`.

> No uses `doctrine:database:create` con SQLite: el archivo se crea al aplicar la migración.

### 5. Crear un usuario de prueba

```bash
docker compose exec web php bin/console app:create-user alumno alumno@example.test
```

El comando pedirá la contraseña de forma oculta y guardará únicamente su hash. Si el usuario ya existe, actualiza su contraseña y su email.

También puedes pasarla como argumento, aunque no es recomendable porque queda en el historial:

```bash
docker compose exec web php bin/console app:create-user alumno alumno@example.test "mi-password"
```

### 6. Entrar

Abre `http://localhost:8080/login` e inicia sesión con el usuario creado.

---

## Variables de entorno

La configuración por defecto está en `.env` y funciona tal cual para desarrollo.

Si necesitas sobrescribir valores, crea `.env.local`:

```dotenv
APP_SECRET=un-valor-largo-y-aleatorio
```

`.env.local` está ignorado por Git y no debe subirse al repositorio.

La base de datos se configura con:

```dotenv
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
```

En desarrollo se usa `var/data_dev.db`; en pruebas, `var/data_test.db`.

---

## Rutas

| Método | Ruta | Nombre | Acceso |
|---|---|---|---|
| GET | `/` | `app_home` | Público |
| GET | `/acerca-de` | `app_about` | Público |
| GET | `/login` | `app_login` | Público |
| POST | `/login` | `app_login` | Firewall |
| GET | `/dashboard` | `app_dashboard` | `ROLE_USER` |
| POST | `/logout` | `app_logout` | Sesión iniciada + CSRF |

`/dashboard` está protegido en `config/packages/security.yaml`:

```yaml
access_control:
    - { path: ^/dashboard, roles: ROLE_USER }
```

La protección no está en la plantilla ni en el controlador: está en la configuración del firewall.

---

## Flujo de autenticación

```text
GET /dashboard sin sesión
        ↓
access_control exige ROLE_USER
        ↓
Redirección a /login
        ↓
POST /login con credenciales válidas
        ↓
Provider Doctrine + comprobación del hash
        ↓
Token guardado en sesión
        ↓
Redirección a /dashboard
        ↓
POST /logout con token CSRF
        ↓
Sesión invalidada y vuelta a /
```

Puntos clave:

- El login lo procesa `form_login`; el controlador solo muestra el formulario.
- El usuario se carga con `UserRepository::loadUserByIdentifier()`.
- `User::getRoles()` añade siempre `ROLE_USER`.
- El logout es un formulario `POST`, no un enlace.
- El CSRF se valida mediante `Origin`/`Sec-Fetch-Site` y doble envío.

---

## Estructura del proyecto

```text
.
├── assets/                 # CSS y controladores Stimulus
├── config/                 # Configuración de Symfony
│   ├── packages/           # security, doctrine, twig, csrf...
│   └── routes.yaml         # Rutas explícitas, incluido app_logout
├── docs/                   # Documentación del proyecto
├── migrations/             # Migraciones de Doctrine
├── public/                 # Document root
├── src/
│   ├── Command/            # app:create-user
│   ├── Controller/         # Main, Security y Dashboard
│   ├── Entity/             # User
│   └── Repository/         # UserRepository
├── templates/
│   ├── dashboard/
│   ├── main/
│   ├── security/
│   └── base.html.twig
├── compose.yaml
├── Dockerfile
└── composer.json
```

---

## Documentación

| Documento | Contenido |
|---|---|
| [`docs/1-investigacion.md`](docs/1-investigacion.md) | Investigación y decisiones iniciales |
| [`docs/2-arranque.md`](docs/2-arranque.md) | Docker y primer arranque |
| [`docs/3-flujo_peticion.md`](docs/3-flujo_peticion.md) | Recorrido de una petición en Symfony |
| [`docs/4-persistencia.md`](docs/4-persistencia.md) | Doctrine, SQLite y migraciones |
| [`docs/5a-autenticacion.md`](docs/5a-autenticacion.md) | Login, provider y usuario de prueba |
| [`docs/5b-dashboard.md`](docs/5b-dashboard.md) | Dashboard protegido, logout y CSRF |

---

## Comandos útiles

```bash
# Ver el estado del contenedor
docker compose ps

# Ver logs de la aplicación
docker compose logs -f web

# Información de Symfony
docker compose exec web php bin/console about

# Rutas registradas
docker compose exec web php bin/console debug:router

# Configuración del firewall
docker compose exec web php bin/console debug:firewall main

# Estado de las migraciones
docker compose exec web php bin/console doctrine:migrations:status

# Validar el mapeo de Doctrine
docker compose exec web php bin/console doctrine:schema:validate

# Validar plantillas Twig
docker compose exec web php bin/console lint:twig templates/

# Parar el entorno
docker compose down
```

### Reiniciar la base de datos

```bash
docker compose exec web rm -f var/data_dev.db
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction
```

Después tendrás que volver a crear el usuario de prueba.

---

## Verificación funcional

| Código | Acción | Resultado esperado |
|---|---|---|
| P03 | Abrir `/login` | Se muestra el formulario |
| P04 | Entrar con credenciales válidas | Se accede a `/dashboard` |
| P05 | Entrar con contraseña incorrecta | Vuelve a `/login` con error |
| P06 | Abrir `/dashboard` sin sesión | Redirige a `/login` |
| P07 | Cerrar sesión | Vuelve a `/` y desaparece el usuario |
| P08 | Abrir `/dashboard` tras el logout | Redirige a `/login` |

Comprobación rápida:

```bash
curl.exe -I http://localhost:8080/
curl.exe -I http://localhost:8080/dashboard
```

Resultado esperado: `200` en `/` y `302` hacia `/login` en `/dashboard` sin sesión.

---

## Seguridad

- Las contraseñas se guardan como hash, nunca en texto plano.
- El login y el logout están protegidos con CSRF.
- `.env.local`, `vendor/` y `var/` están ignorados por Git.
- La base de datos de desarrollo no se versiona.
- El logout solo acepta `POST`.
- `always_use_default_target_path` evita redirecciones manipulables tras el login.

---

## Estado y siguientes pasos

- [x] Entorno Docker
- [x] Páginas públicas con Twig
- [x] Persistencia con Doctrine y SQLite
- [x] Login con usuario y contraseña
- [x] Dashboard protegido con `ROLE_USER`
- [x] Logout con CSRF
- [x] Documentación por fases

---

## Autor

**Borja Lozano**  
GitHub: [@JitaProg](https://github.com/JitaProg)

Proyecto desarrollado como práctica de onboarding de Symfony.
