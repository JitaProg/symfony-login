# Autenticación y gestión de usuarios (Fase 5a)

## 1. Decisión: Login por nombre de usuario (`username`)

Se ha tomado la decisión de implementar el proceso de inicio de sesión utilizando el nombre de usuario (`username`) como identificador principal en lugar de la dirección de correo electrónico (`email`). 

Esta decisión simplifica el flujo de trabajo en entornos de desarrollo y desarrollo local, garantizando que el método `getUserIdentifier()` de la entidad `User` devuelva el valor normalizado del `username`.

## 2. Archivos implicados

| Archivo | Papel en la autenticación |
| --- | --- |
| `src/Entity/User.php` | Entidad de usuario adaptada para implementar `UserInterface` y `PasswordAuthenticatedUserInterface`. |
| `src/Repository/UserRepository.php` | Repositorio personalizado con soporte para `UserLoaderInterface` y `PasswordUpgraderInterface`. |
| `src/Command/CreateUserCommand.php` | Comando CLI interactivo para dar de alta usuarios o actualizar contraseñas hasheadas. |
| `src/Controller/SecurityController.php` | Controlador encargo de la vista GET del formulario de login y captura de errores de autenticación. |
| `templates/security/login.html.twig` | Plantilla Twig que renderiza el formulario de inicio de sesión y los mensajes de error. |
| `config/packages/security.yaml` | Fichero de configuración principal para el firewall, proveedores de usuarios y algoritmos de hashing. |
| `config/packages/csrf.yaml` | Configuración para la protección CSRF en formularios de autenticación. |
| `config/packages/translation.yaml` | Ajuste del idioma por defecto (`es`) para mostrar los mensajes de error en español. |

## 3. Configuración de `security.yaml` (Fase 5a)

```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User

    firewalls:
        dev:
            pattern: ^/(_profiler(/|$)|(_wdt|assets|build)/)
            security: false

        main:
            lazy: true
            provider: app_user_provider

            form_login:
                login_path: app_login
                check_path: app_login
                enable_csrf: true
                default_target_path: app_home
                always_use_default_target_path: true
                failure_path: app_login

    access_control: []

when@test:
    security:
        password_hashers:
            Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface:
                algorithm: auto
                cost: 4
                time_cost: 3
                memory_cost: 10
```

## 4. Recorrido de una petición de login

El ciclo de autenticación mediante el formulario de login sigue los siguientes pasos:

1. **Petición GET `/login`:** El navegador solicita la página de inicio de sesión. El método `SecurityController::login()` comprueba si el usuario ya está autenticado (redirigiendo a `/` si es así) o muestra la plantilla `login.html.twig` con el último nombre de usuario introducido y el último error producido.
2. **Envío del formulario (POST `/login`):** El usuario escribe sus datos y envía el formulario conteniendo los campos `_username`, `_password` y `_csrf_token`.
3. **Intercepción por el Firewall `main`:** El componente de seguridad intercepta la petición en la ruta `check_path` (`app_login`) antes de que se vuelva a ejecutar la lógica del controlador.
4. **Carga y verificación de credenciales:**
   * El proveedor de usuarios (`app_user_provider`) consulta el repositorio `UserRepository::loadUserByIdentifier()` buscando el nombre de usuario de forma insensible a mayúsculas/minúsculas.
   * El sistema comprueba el token CSRF para evitar ataques entre sitios.
   * El servicio hasher de Symfony compara la contraseña introducida en texto plano contra el hash almacenado en la base de datos usando la interfaz `PasswordAuthenticatedUserInterface`.
5. **Establecimiento de sesión y redirección:**
   * **Credenciales válidas:** Se crea la sesión persistente del usuario y el firewall redirige automáticamente a la ruta por defecto (`app_home` / `/`).
   * **Credenciales inválidas:** Se guarda la excepción en la sesión y el firewall redirige de nuevo a `failure_path` (`/login`), mostrando el mensaje de error traducido al español.

## 5. El comando `app:create-user` y el hashing seguro

Para garantizar la seguridad de la información y evitar el almacenamiento de claves en texto plano, la inserción de usuarios de prueba se realiza mediante un comando de línea de comandos personalizado registrado como `app:create-user`.

### Características del comando:
* **Uso de dependencias:** Inyecta `UserPasswordHasherInterface` para procesar el cifrado seguro.
* **Algoritmos modernos:** Utiliza la configuración automática de Symfony (`auto`), aplicando algoritmos como **bcrypt** o **argon2id**.
* **Modo interactivo seguro:** Permite solicitar la contraseña de forma oculta en la terminal (`askHidden`) evitando que quede registrada en el historial del terminal bash.

### Ejemplo de ejecución:
```bash
docker compose exec web php bin/console app:create-user alumno alumno@example.test
```

## 6. Pendiente para la Fase 5b

En la siguiente etapa se completará la gestión de la seguridad con los siguientes elementos:

* Protección de zonas privadas como la ruta `/dashboard` utilizando reglas de control de acceso (`access_control`).
* Implementación de la ruta y controlador de cierre de sesión (`logout`).
* Personalización de los enlaces de navegación según el estado de la sesión activa del usuario.