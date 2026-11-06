# Dashboard protegido y logout

## 1. Objetivo de la fase

En esta fase se cierra el flujo de autenticación:

- Proteger `/dashboard` para que solo accedan usuarios autenticados.
- Redirigir al dashboard después de un login correcto.
- Implementar el cierre de sesión.
- Impedir que `/dashboard` siga siendo accesible después del logout.
- Proteger el logout con CSRF.

La fase anterior (`5a-autenticacion.md`) cubre el formulario de login, el provider y la creación del usuario de prueba. Esta fase cubre todo lo que ocurre después de autenticarse.

---

## 2. Resumen del flujo

```text
GET /dashboard sin sesión
        ↓
access_control exige ROLE_USER
        ↓
El firewall redirige a /login
        ↓
POST /login con credenciales correctas
        ↓
Se crea la sesión y se guarda el token
        ↓
Redirección a /dashboard
        ↓
POST /logout con token CSRF
        ↓
Se invalida la sesión y se vuelve a /
        ↓
GET /dashboard vuelve a redirigir a /login
```

---

## 3. Protección de `/dashboard`

La protección está en `config/packages/security.yaml`, no en la plantilla ni en el controlador:

```yaml
    access_control:
        - { path: ^/dashboard, roles: ROLE_USER }
```

Qué significa:

- Cualquier ruta que empiece por `/dashboard` exige `ROLE_USER`.
- Si el usuario no está autenticado, el firewall lo redirige a `/login`.
- Si está autenticado pero no tiene el rol, Symfony devuelve `403 Access Denied`.
- La comprobación se hace antes de ejecutar el controlador.

Por eso no sirve con ocultar el enlace en Twig: aunque alguien escriba la URL a mano, `access_control` lo bloquea.

### El controlador

`src/Controller/DashboardController.php`:

```php
#[Route('/dashboard', name: 'app_dashboard')]
public function index(#[CurrentUser] User $user): Response
{
    return $this->render('dashboard/index.html.twig', [
        'user' => $user,
    ]);
}
```

`#[CurrentUser]` inyecta el usuario autenticado. El controlador asume que el firewall ya ha dejado pasar; la seguridad real sigue estando en `access_control`.

### El rol `ROLE_USER`

Symfony **no añade `ROLE_USER` automáticamente**. Si la columna `roles` de la base de datos está vacía, el token se crea sin roles y `access_control` devuelve:

```text
Access Denied. The user doesn't have ROLE_USER.
```

La solución está en la entidad `User`:

```php
public function getRoles(): array
{
    $roles = $this->roles;
    $roles[] = 'ROLE_USER';

    return array_values(array_unique($roles));
}
```

Así, todo usuario tiene al menos `ROLE_USER` aunque la columna `roles` contenga `[]`.

---

## 4. Redirección después del login

En `security.yaml`:

```yaml
            form_login:
                login_path: app_login
                check_path: app_login
                enable_csrf: true
                default_target_path: app_dashboard
                always_use_default_target_path: true
                failure_path: app_login
```

- `default_target_path: app_dashboard` hace que tras un login correcto se vaya al dashboard.
- `always_use_default_target_path: true` evita que se pueda manipular la redirección con un parámetro `_target_path`.
- Si las credenciales fallan, se vuelve a `app_login`.

Además, `SecurityController::login()` redirige al dashboard si el usuario ya tiene sesión:

```php
if ($this->getUser()) {
    return $this->redirectToRoute('app_dashboard');
}
```

---

## 5. Logout

### Configuración

```yaml
            logout:
                path: app_logout
                target: app_home
                enable_csrf: true
```

- `path: app_logout`: ruta que dispara el cierre de sesión.
- `target: app_home`: a dónde se redirige después.
- `enable_csrf: true`: exige un token CSRF válido.

### La ruta

En `config/routes.yaml`:

```yaml
app_logout:
    path: /logout
    methods: POST
```

No existe un `LogoutController`. El firewall intercepta la petición antes de que llegue a un controlador y se encarga de todo.

Se usa `POST` a propósito: un `GET /logout` no debe cerrar sesión. Con `methods: POST`, un GET devuelve `405 Method Not Allowed`.

### El formulario

En `templates/base.html.twig`:

```twig
<form method="post" action="{{ path('app_logout') }}" class="logout-form">
    <input
        type="hidden"
        data-controller="csrf-protection"
        name="_csrf_token"
        value="{{ csrf_token('logout') }}"
    >
    <button type="submit">Cerrar sesión</button>
</form>
```

El logout es un formulario POST, no un enlace. El campo debe llamarse `_csrf_token`.

### Qué hace Symfony al cerrar sesión

1. Comprueba que la petición sea del mismo origen y que el token CSRF sea válido.
2. Elimina el token de seguridad del almacenamiento.
3. Invalida la sesión.
4. Redirige a `app_home`.

A partir de ese momento, `/dashboard` vuelve a estar protegido.

---

## 6. CSRF stateless

`config/packages/csrf.yaml`:

```yaml
framework:
    csrf_protection:
        stateless_token_ids:
            - authenticate
            - logout
```

- El token no se guarda en la sesión.
- Symfony valida las cabeceras `Sec-Fetch-Site`, `Origin` o `Referer`.
- Si el navegador no las envía, el controlador JavaScript `csrf_protection_controller.js` añade el token de doble envío mediante la cookie.
- El token se usa tanto en el login (`authenticate`) como en el logout (`logout`).

Por eso el logout funciona en el navegador con el formulario POST y `data-controller="csrf-protection"`.

---

## 7. Archivos implicados

| Archivo | Papel |
|---|---|
| `config/packages/security.yaml` | Provider, `form_login`, `logout`, `access_control` |
| `config/packages/csrf.yaml` | Tokens stateless `authenticate` y `logout` |
| `config/routes.yaml` | Ruta `app_logout` (`POST /logout`) |
| `src/Controller/SecurityController.php` | Muestra el login y redirige si ya hay sesión |
| `src/Controller/DashboardController.php` | Renderiza el dashboard del usuario autenticado |
| `src/Entity/User.php` | Implementa `UserInterface` y añade `ROLE_USER` |
| `src/Repository/UserRepository.php` | Carga el usuario por `username` |
| `templates/base.html.twig` | Navegación condicional y formulario de logout |
| `templates/security/login.html.twig` | Formulario de login con CSRF |
| `templates/dashboard/index.html.twig` | Datos del usuario autenticado |

---

## 8. Recorridos completos

### 8.1. Abrir `/dashboard` sin sesión

1. El navegador pide `GET /dashboard`.
2. El firewall carga el firewall `main`.
3. `access_control` exige `ROLE_USER`.
4. No hay token en la sesión.
5. El entry point del `form_login` redirige a `/login` con `302`.

### 8.2. Login correcto

1. `POST /login` con `_username`, `_password` y `_csrf_token`.
2. El firewall valida el CSRF.
3. El provider busca al usuario con `UserRepository::loadUserByIdentifier()`.
4. Se comprueba el hash con el password hasher.
5. Se crea un `UsernamePasswordToken` con los roles de `getRoles()`.
6. El token se guarda en la sesión.
7. Se redirige a `app_dashboard`.
8. `access_control` comprueba `ROLE_USER` y deja pasar.
9. `DashboardController` recibe el usuario con `#[CurrentUser]`.

### 8.3. Logout y reintento

1. `POST /logout` con `_csrf_token`.
2. El firewall valida el token.
3. Se invalida la sesión.
4. Se redirige a `/`.
5. `GET /dashboard` vuelve a redirigir a `/login`.

---

## 9. Pruebas P03–P08

| Código | Acción | Resultado esperado |
|---|---|---|
| P03 | Abrir `/login` | Se muestra el formulario |
| P04 | Entrar con credenciales válidas | Se accede a `/dashboard` |
| P05 | Entrar con contraseña incorrecta | Vuelve a `/login` y muestra el error |
| P06 | Abrir `/dashboard` sin sesión | Redirige a `/login` |
| P07 | Pulsar «Cerrar sesión» | Vuelve a `/` y desaparece el usuario |
| P08 | Abrir `/dashboard` después del logout | Redirige otra vez a `/login` |

Comprobación rápida sin sesión:

```powershell
curl.exe -I http://localhost:8080/dashboard
```

Resultado esperado:

```text
HTTP/1.1 302 Found
Location: http://localhost:8080/login
```

---

## 10. Comandos de verificación

```powershell
docker compose exec web php bin/console debug:router
docker compose exec web php bin/console debug:firewall main
docker compose exec web php bin/console cache:clear
```

`debug:router` debe mostrar:

```text
app_dashboard   ANY    /dashboard
app_logout      POST   /logout
```

Revisar el log durante las pruebas:

```powershell
docker compose exec web tail -n 40 var/log/dev.log
```

Tras un login correcto debe aparecer un token con `roles="ROLE_USER"`:

```text
UsernamePasswordToken(user="borja", roles="ROLE_USER")
```

---