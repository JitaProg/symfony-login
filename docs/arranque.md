# Arranque en Docker

## Requisitos

* **Docker y Docker Compose:** Instalados y en ejecución en el sistema.
* **Git:** Para el control de versiones.
* **Carpeta del proyecto Symfony:** Estructurada en local (Windows) con los ficheros base de la aplicación.

## Dockerfile

Ubicación: `symfony/Dockerfile`

```dockerfile
FROM php:8.4.1-cli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN apt-get update && apt-get install git unzip -y && rm -rf /var/lib/apt/lists/*
```

**Qué hace cada línea:**
* `FROM php:8.4.1-cli`: Utiliza la imagen oficial de PHP 8.4 en modo consola. Symfony 8.1 exige PHP 8.4 o superior.
* `COPY --from=composer:2`: Copia el ejecutable de Composer desde su imagen oficial. Así no dependes de que Composer esté instalado en el host.
* `WORKDIR`: Define el directorio de trabajo dentro del contenedor.
* `RUN apt-get ...`: Instala `git` y `unzip`, que Composer necesita para descargar paquetes.

## compose.yaml

Ubicación: `symfony/compose.yaml`

```yaml
services:
  web:
    build: .
    container_name: symfony_app
    ports:
      - "8080:8080"
    volumes:
      - .:/var/www/html
    command: php -S 0.0.0.0:8080 -t public/
```

**Qué significa cada parámetro:**
* `build: .`: Construye la imagen con el Dockerfile de esta carpeta.
* `container_name`: Nombre fijo del contenedor (`symfony_app`).
* `ports`: Publica el puerto 8080 del contenedor en el 8080 de tu máquina.
* `volumes`: Monta tu carpeta del proyecto dentro del contenedor. Los cambios que hagas en Windows se ven al instante dentro de Docker.
* `command`: Arranca el servidor integrado de PHP apuntando a `public/`, que es el *document root* de Symfony.

## Primer arranque

Comandos ejecutados y para qué sirve cada uno:

1. **Configuración del secreto local (`APP_SECRET`):**
   * Genera un valor aleatorio seguro:
     ```bash
     php -r "echo bin2hex(random_bytes(32));"
     ```
   * Crea el fichero `symfony/.env.local` con ese valor para mantenerlo fuera de Git:
     ```env
     APP_SECRET=el_valor_que_has_generado
     ```
   * Edita `symfony/.env` para dejar un valor de relleno y la URL por defecto:
     ```env
     APP_SECRET=dev-only-insecure-placeholder
     DEFAULT_URI=http://localhost:8080
     ```

2. **Construcción y ejecución (desde la carpeta `symfony/`):**
   * Construye la imagen de Docker:
     ```bash
     docker compose build
     ```
   * Arranca el contenedor en segundo plano:
     ```bash
     docker compose up -d
     ```
   * Comprueba que el contenedor está activo:
     ```bash
     docker compose ps
     ```

3. **Instalación de dependencias dentro del contenedor:**
   * Instala los paquetes mediante el PHP del contenedor:
     ```bash
     docker compose exec web composer install
     ```
   * Verifica la versión de PHP y el estado de Symfony:
     ```bash
     docker compose exec web php -v
     docker compose exec web php bin/console about
     ```
   * Comprueba la disponibilidad de SQLite para la persistencia:
     ```bash
     docker compose exec web php -m | Select-String -Pattern "pdo_sqlite|sqlite3"
     ```

## URL local

* [http://localhost:8080](http://localhost:8080)  
*(Nota: Es normal que devuelva un código 404 en este punto, indicando que el servidor funciona correctamente pero aún no hay rutas definidas).*

## Comandos útiles

| Descripción | Comando |
| --- | --- |
| Ver estado de los contenedores | `docker compose ps` |
| Ver los logs del servidor web | `docker compose logs -f web` |
| Detener los contenedores | `docker compose stop` |
| Volver a arrancar los contenedores | `docker compose up -d` |
| Apagar y eliminar contenedores | `docker compose down` |
| Ejecutar comandos de consola Symfony | `docker compose exec web php bin/console <comando>` |

## Problemas encontrados

Durante el arranque inicial no se han registrado incidencias. El montaje del volumen, la compilación con PHP 8.4 y la ejecución del servidor integrado se han completado de manera fluida y óptima.