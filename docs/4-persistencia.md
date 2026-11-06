# Persistencia de usuarios

## 1. Decisión: SQLite + Doctrine

Para la persistencia de datos del proyecto se ha seleccionado la combinación de **SQLite** como motor de base de datos y **Doctrine** como ORM (Object-Relational Mapping). 

Esta decisión responde a un criterio de simplicidad y bajo consumo de recursos en entornos de desarrollo:
* **SQLite** no requiere un servicio de servidor en ejecución permanente ni un contenedor dedicado (como MySQL o PostgreSQL), ya que la base de datos es un único fichero plano.
* **Doctrine** abstrae las consultas SQL nativas a través del lenguaje DQL y permite gestionar el esquema mediante objetos PHP (entidades).

---

## 2. Por qué Doctrine ORM

Doctrine actúa como una capa intermedia entre las clases de PHP y la base de datos relacional:
* **Mapeo objeto-relacional:** Transforma filas de tablas en objetos PHP y viceversa, facilitando el trabajo orientado a objetos.
* **Abstracción de la base de datos (DBAL):** Permite cambiar de motor de base de datos (por ejemplo, pasar de SQLite a PostgreSQL en producción) sin modificar la lógica del código fuente.
* **Gestión del esquema:** Garantiza un control de versiones de la estructura de la base de datos a través de migraciones PHP reproducibles.

---

## 3. Esquema de app_user

La tabla principal de usuarios se ha nombrado explícitamente `app_user` para evitar colisiones con palabras reservadas en diversos gestores de bases de datos (como `user` en PostgreSQL/SQL Server).

| Columna | Tipo de dato | Restricciones | Descripción |
| --- | --- | --- | --- |
| `id` | `INTEGER` | `PRIMARY KEY`, `AUTOINCREMENT` | Identificador único del usuario. |
| `email` | `VARCHAR(180)` | `NOT NULL`, `UNIQUE` | Correo electrónico del usuario (normalizado a minúsculas). |
| `username` | `VARCHAR(50)` | `NOT NULL`, `UNIQUE` | Nombre de usuario para iniciar sesión (normalizado a minúsculas). |
| `roles` | `CLOB` / `JSON` | `NOT NULL` | Array en formato JSON con los roles asignados (ej. `["ROLE_USER"]`). |
| `password` | `VARCHAR(255)` | `NOT NULL` | Hash de la contraseña del usuario (nunca en texto plano). |

---

## 4. Migración

La migración encargada de crear el esquema se generó automáticamente comparando las entidades definidas en PHP contra el estado actual de la base de datos:

* **Fichero de migración:** `src/Migrations/Version20261106XXXXXX.php`

**Contenido de la migración (método `up`):**
Crea la tabla `app_user` con los campos `id`, `email`, `username`, `roles` y `password`, junto con los índices únicos para `email` y `username`.

---

## 5. Dónde vive la base de datos en Docker

La base de datos SQLite se almacena en un fichero dentro del volumen montado del contenedor:

* **Ruta relativa en el proyecto:** `var/data_dev.db` (en entorno de desarrollo) y `var/data_test.db` (en entorno de pruebas).
* **Ruta dentro del contenedor:** `/var/www/html/var/data_dev.db`
* **Persistencia:** Gracias al volumen sincronizado en `compose.yaml` (`.:/var/www/html`), la base de datos sobrevive al detener (`docker compose stop`), reiniciar o destruir (`docker compose down`) los contenedores de Docker.

---

## 6. Comandos

A continuación se detallan los comandos ejecutados durante el proceso de configuración y verificación de la capa de persistencia:

```bash
# 1. Instalación de paquetes de Doctrine y MakerBundle
docker compose exec web composer require symfony/orm-pack
docker compose exec web composer require --dev symfony/maker-bundle

# 2. Comprobación del soporte SQLite en PHP
docker compose exec web php -m | Select-String -Pattern "pdo_sqlite|sqlite3"

# 3. Generación del fichero de migración tras definir la entidad User
docker compose exec web php bin/console make:migration

# 4. Aplicación de la migración para crear el fichero var/data_dev.db y la tabla app_user
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction

# 5. Verificación del estado de las migraciones y del esquema
docker compose exec web php bin/console doctrine:migrations:status
docker compose exec web php bin/console doctrine:schema:validate
docker compose exec web php bin/console doctrine:mapping:info

# 6. Consulta de prueba (DQL) sobre la entidad User
docker compose exec web php bin/console doctrine:query:dql "SELECT u.id, u.username, u.email FROM App\Entity\User u"
```

---

## 7. Por qué migraciones y no schema:update

Se descarta el uso del comando `doctrine:schema:update --force` en favor del sistema de migraciones (`doctrine:migrations`) por los siguientes motivos:
* **Trazabilidad y control de versiones:** Las migraciones son ficheros de código PHP que se suben a Git, permitiendo auditorías de los cambios de esquema a lo largo del tiempo.
* **Seguridad en entornos reales:** `schema:update` puede ejecutar operaciones destructivas sin confirmación previa o perder datos por modificaciones no deseadas.
* **Reproducibilidad:** Garantiza que todos los miembros del equipo y los entornos de CI/CD ejecuten exactamente la misma secuencia de transformaciones en la base de datos.

---

## 8. Seguridad

* **Hash de contraseñas:** El atributo `password` de la entidad `User` está diseñado para almacenar exclusivamente hashes criptográficos seguros generados por el hasher de Symfony, evitando el almacenamiento de credenciales en texto plano.
* **Aislamiento de ficheros de datos:** El directorio `var/` donde reside `data_dev.db` está incluido en el fichero `.gitignore` del proyecto, previniendo la subida accidental de información confidencial o de pruebas al repositorio central.

---

## 9. Nota sobre Messenger

En este proyecto no se utiliza el componente Symfony Messenger. Por esta razón, el fichero de migración generado se limita a construir únicamente la tabla `app_user` sin incluir la tabla por defecto `messenger_messages`.

---

## 10. Enlaces

* [Documentación oficial de Symfony: Doctrine ORM](https://symfony.com/doc/current/doctrine.html)
* [Documentación de Symfony: Migraciones de datos](https://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html)
* [Documentación oficial de SQLite](https://www.sqlite.org/docs.html)