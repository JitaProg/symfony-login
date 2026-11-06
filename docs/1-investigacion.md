# Investigación y decisiones iniciales

## 1. Qué voy a construir

El objetivo principal de este proyecto es desarrollar una aplicación web básica utilizando el framework Symfony. La estructura de la aplicación se divide en dos áreas principales:

* **Zona pública:** Compuesta por las rutas `/` (página de inicio) y `/acerca-de` (información del proyecto/aplicación), accesibles para cualquier visitante sin necesidad de autenticación.
* **Zona privada y gestión de acceso:** Un sistema completo de autenticación compuesto por un formulario de login real, un dashboard privado accesible únicamente tras iniciar sesión y una funcionalidad de logout para cerrar la sesión de forma segura.

## 2. Requisitos técnicos

Para poder desarrollar, ejecutar y desplegar la aplicación correctamente, el entorno de desarrollo requiere las siguientes herramientas y dependencias:

* **PHP 8.4:** Lenguaje base necesario para ejecutar el framework y aprovechar las últimas mejoras de rendimiento y sintaxis.
* **Extensiones de PHP requeridas:** Módulos esenciales para el correcto funcionamiento de Symfony y la base de datos (como `pdo_sqlite`, `mbstring`, `iconv`, `ctype`, entre otras).
* **Composer:** Gestor de dependencias de PHP utilizado para instalar Symfony, paquetes de terceros y gestionar el autoloader.
* **Docker / Docker Compose:** Para la contenerización del entorno de ejecución de la aplicación web.
* **Git:** Sistema de control de versiones para gestionar los cambios del código fuente.

## 3. Versión de Symfony elegida

Para este proyecto se ha seleccionado **Symfony 8.1**.

Aunque en la documentación de la práctica (ficheros Markdown) se solicitaba el uso de la versión **7.4 LTS**, se ha tomado la decisión de trabajar con Symfony 8.1 debido a que tanto el entorno base como el proyecto de referencia utilizan esta versión. 

Es importante señalar que Symfony 8.1 no cuenta con soporte a largo plazo (no es LTS). Sin embargo, la estructura básica, los componentes de enrutado, los controladores, la persistencia y los conceptos fundamentales que se evalúan en esta práctica son exactamente los mismos entre ambas versiones, garantizando la validez de las implementaciones realizadas.

## 4. Herramientas del entorno

A continuación se detallan las herramientas utilizadas en el entorno de desarrollo, su propósito y el comando correspondiente para verificar su instalación y versión:

| Herramienta | Para qué sirve | Comando de comprobación |
| --- | --- | --- |
| **PHP** | Entorno de ejecución del lenguaje backend | `php -v` |
| **Composer** | Gestor de paquetes y dependencias de PHP | `composer --version` |
| **Symfony CLI** | Herramienta de línea de comandos para gestionar proyectos Symfony | `symfony version` |
| **Docker** | Motor de contenerización para aislar el entorno | `docker --version` |
| **Docker Compose** | Orquestador de contenedores para gestionar los servicios | `docker compose version` |
| **Git** | Control de versiones del código fuente | `git --version` |

## 5. Cómo funciona una petición en Symfony

El flujo del ciclo de vida de una petición HTTP en Symfony sigue una arquitectura claramente definida:

1. **`public/index.php` (Front Controller):** Todas las peticiones entrantes al servidor web son dirigidas a este punto de entrada único.
2. **Kernel:** El Front Controller instancia e inicializa el Kernel de Symfony, que carga la configuración del contenedor de servicios y los bundles del proyecto.
3. **Router:** El componente de enrutamiento analiza la URI y el método HTTP de la petición para matchear y determinar qué controlador debe procesar la solicitud.
4. **Controlador:** Se ejecuta la lógica de negocio correspondiente a la ruta elegida. Si la respuesta requiere renderizar vistas, el controlador interactúa con las plantillas.
5. **Twig:** El motor de plantillas procesa el archivo `.html.twig`, combina los datos proporcionados por el controlador y genera la estructura HTML final.
6. **Response:** El controlador devuelve un objeto `Response` de Symfony (que contiene el HTML, cabeceras HTTP y código de estado), enviándolo de vuelta al cliente.

## 6. Autenticación y autorización

Es fundamental diferenciar ambos conceptos en la arquitectura de seguridad de la aplicación:

* **Autenticación (¿Quién eres?):** Es el proceso de verificar la identidad de un usuario. En la aplicación, esto ocurre cuando el usuario introduce sus credenciales (username y password) en el formulario de login y el sistema comprueba que coinciden con los registros guardados.
* **Autorización (¿Qué puedes hacer?):** Es el proceso de verificar si un usuario autenticado tiene permiso para acceder a un recurso específico o realizar una acción determinada. Por ejemplo, permitir que solo los usuarios autenticados puedan entrar a la ruta del dashboard privado.

## 7. Persistencia: SQLite y Doctrine

Para la persistencia de datos se ha optado por utilizar **SQLite** administrado a través del ORM **Doctrine**.

* **Base de datos:** El archivo de la base de datos SQLite se almacena de forma local en la carpeta `var/` del proyecto.
* **Gestión de esquema:** Se utilizan las migraciones de Doctrine (`doctrine:migrations`) para versionar la estructura de la base de datos y aplicar cambios en el esquema.
* **Modelo de datos:** Se define la entidad `User` para almacenar el identificador, el nombre de usuario y la contraseña (siempre de forma hasheada).

**¿Por qué SQLite en lugar de MySQL/PostgreSQL?**  
Se ha elegido SQLite por su simplicidad y bajo consumo de recursos en entornos de desarrollo o prácticas locales. Al ser una base de datos basada en un único archivo local, no requiere desplegar ni configurar un servicio o contenedor adicional para un gestor de base de datos relacional completo (como MySQL o PostgreSQL), facilitando la portabilidad y ejecución del proyecto sin añadir complejidad a la infraestructura.

## 8. Decisiones de seguridad

Con el objetivo de garantizar un nivel básico pero sólido de seguridad en la aplicación, se han aplicado las siguientes prácticas:

* **Almacenamiento seguro de contraseñas:** Las contraseñas nunca se guardan en texto plano en la base de datos. Se utiliza el servicio hasher nativo de Symfony (`UserPasswordHasherInterface`), que aplica algoritmos de hashing seguros (como Auto/BCrypt/Argon2id).
* **Protección CSRF:** El formulario de inicio de sesión incluye un token CSRF (Cross-Site Request Forgery) gestionado por Symfony para evitar peticiones maliciosas desde otros sitios.
* **Gestión de variables de entorno:** La clave secreta de la aplicación (`APP_SECRET`) y otros parámetros sensibles se configuran mediante variables de entorno en archivos `.env.local` y se excluyen explícitamente del control de versiones (`.gitignore`).
* **Datos de prueba reproducibles:** Para poder probar el sistema, el usuario de prueba no se crea manualmente mediante consultas SQL directas, sino a través de un comando CLI o fixture reproducible para mantener la integridad de las pruebas.

## 9. Plan de trabajo

Las decisiones finales de diseño e infraestructura adoptadas para la realización de la práctica son:

* **Credencial de acceso:** El inicio de sesión se realiza mediante el campo `username` (nombre de usuario) en lugar de correo electrónico.
* **Infraestructura simplificada:** Desplieg

## 10. Comprobaciones que puedes ejecutar ahora

Son comandos de lectura, no modifican el proyecto:

```bash
php -v
composer --version
symfony version
docker --version
docker compose version
git --version
php -m