# Flujo de una petición

## Resumen

Una petición HTTP en Symfony atraviesa una serie de etapas estandarizadas dentro de la arquitectura MVC del framework. Cuando el usuario solicita un recurso desde su navegador, la petición es canalizada por el servidor web hacia el único punto de entrada de la aplicación (`public/index.php`), donde se inicializa el Kernel. A continuación, el sistema de enrutamiento asigna la URL requerida a una función dentro de un controlador, la cual procesa la lógica de negocio necesaria, renderiza la vista HTML correspondiente mediante el motor de plantillas Twig y retorna una respuesta HTTP estructurada al cliente.

---

## Paso a paso para /acerca-de

1. **El navegador pide `/acerca-de`:** El usuario introduce la dirección `http://localhost:8080/acerca-de` o hace clic en el enlace correspondiente dentro de la navegación.
2. **Docker recibe la petición en el puerto 8080:** El motor de Docker captura la solicitud entrante en la máquina anfitriona y la redirige al servidor web interno corriendo en el contenedor `symfony_app`.
3. **`public/index.php` arranca Symfony:** El servidor pasa el control al *Front Controller* (`public/index.php`), que instancia e inicializa el `Kernel` de la aplicación cargando las configuraciones y servicios.
4. **El router busca una ruta:** El componente de enrutamiento compara la URI `/acerca-de` con la tabla de rutas registrada e identifica la ruta asociada al atributo `#[Route('/acerca-de', name: 'app_about')]`.
5. **Se ejecuta `MainController::about()`:** El Router invoca el método `about()` definido dentro de la clase `src/Controller/MainController.php`.
6. **`render()` carga `main/about.html.twig`:** El método del controlador procesa los datos necesarios e invoca `$this->render()` enviando los argumentos (`detalle_tecnico`) hacia la plantilla indicada.
7. **Twig hereda de `base.html.twig`:** El motor de plantillas lee `main/about.html.twig`, procesa la directiva `{% extends 'base.html.twig' %}`, inyecta los bloques `title` y `body` definidos en la plantilla hija dentro de la plantilla base e interpole las variables pasadas (`{{ detalle_tecnico }}`).
8. **Se devuelve una Response 200:** El controlador envuelve el HTML generado dentro de una instancia del objeto `Symfony\Component\HttpFoundation\Response` con un código de estado HTTP 200 (OK) y la envía de vuelta al navegador del usuario.

---

## Archivos implicados

| Archivo | Papel |
| --- | --- |
| `public/index.php` | **Front Controller:** Punto de entrada único a todas las solicitudes HTTP enviadas al servidor. |
| `src/Kernel.php` | **Núcleo de Symfony:** Carga el entorno, registra los paquetes (*bundles*) y construye el contenedor de servicios. |
| `src/Controller/MainController.php` | **Controlador:** Contiene la lógica del endpoint, vincula las URLs a las funciones e interactúa con la vista. |
| `templates/base.html.twig` | **Plantilla Base:** Define la estructura global del layout HTML (meta etiquetas, navegación común, llamadas a scripts/estilos). |
| `templates/main/about.html.twig` | **Plantilla de Vista:** Extiende del layout base y sobrescribe los bloques para presentar la información específica de la sección "Acerca de". |

---

## Qué hace cada pieza

* **`public/index.php`:** Es el único fichero expuesto públicamente en el servidor web. Se encarga de interceptar todas las peticiones entrantes y derivar su resolución a la lógica interna de Symfony.
* **`src/Kernel.php`:** Centraliza la inicialización de la arquitectura interna de la aplicación, gestionando el contenedor de inyección de dependencias y la configuración de rutas y bundles.
* **`config/routes.yaml`:** Fichero de configuración global de rutas. Aunque en aplicaciones modernas se utilizan atributos en PHP, permite registrar o importar colecciones de rutas externamente.
* **`#[Route]`:** Atributo de código de PHP 8+ utilizado directamente sobre los métodos del controlador para mapear URLs (ej. `/acerca-de`) y asignarles un identificador único (ej. `name: 'app_about'`).
* **`MainController`:** Clase del controlador que agrupa las acciones asociadas a las páginas públicas. Hereda de `AbstractController` para disponer de funciones auxiliares como `render()`.
* **`render()`:** Método facilitador de `AbstractController` que procesa una plantilla Twig pasándole un array asociativo de parámetros y construye automáticamente un objeto `Response` con el HTML generado.
* **Twig (`extends`, `block`, `{{ variable }}`):**
  * `extends`: Declara qué plantilla padre servirá de estructura base.
  * `block`: Define regiones modificables dentro del marcado HTML (como `title` o `body`).
  * `{{ variable }}`: Imprime e interpole el valor de una variable de forma segura (escapando HTML para prevenir XSS).
* **`path()`:** Función propia de Twig que genera dinámicamente la URL relativa correspondiente al nombre interno de una ruta asignada (ej. `path('app_about')`).

---

## El mismo recorrido para /

El recorrido para la página de inicio (`/`) es conceptualmente idéntico al de `/acerca-de`, variando únicamente en los puntos de resolución interna:

1. El navegador realiza la petición a la raíz `http://localhost:8080/`.
2. Docker y `public/index.php` inicializan la ejecución de Symfony.
3. El componente de enrutamiento evalúa `/` y encuentra la ruta coincidente registrada en el atributo `#[Route('/', name: 'app_home')]`.
4. Se ejecuta el método `MainController::index()`.
5. El controlador prepara las variables contextuales (`nombre_alumno`, `descripcion_proyecto`, `fecha_actual`).
6. Se invoca `$this->render('main/index.html.twig', [...])`.
7. Twig procesa `templates/main/index.html.twig`, extendiendo de `base.html.twig` e inyectando las variables en sus marcadores correspondientes.
8. La aplicación retorna un objeto `Response` con el código 200 OK y la representación visual de la página principal.

---

## Errores HTTP

* **404 Not Found:** Ocurre cuando el componente de enrutamiento evalúa la URI solicitada (ej. `/ruta-inexistente`) y no encuentra ningún coincidente registrado en la tabla de rutas de la aplicación. Symfony aborta la ejecución lanzando una excepción `NotFoundHttpException`.
* **403 Forbidden:** Indica que la petición fue reconocida por el sistema pero el usuario autenticado (o anónimo) no dispone de los permisos o roles necesarios concedidos por la capa de autorización para acceder al recurso.
* **500 Internal Server Error:** Ocurre cuando se produce una excepción no controlada o un error no capturado dentro de la lógica del código PHP (en el controlador, servicios o en la compilación de plantillas Twig) durante el procesamiento de la solicitud.

---

## Por qué usar path() y no una URL escrita a mano

El uso de la función `path('nombre_de_ruta')` dentro de las plantillas Twig frente a escribir rutas absolutas o relativas manuales (ej. `<a href="/acerca-de">`) proporciona múltiples ventajas de mantenibilidad y robustez:

1. **Desacoplamiento de las URLs:** Permite modificar el patrón de la dirección pública en la definición del atributo dentro del controlador (por ejemplo, cambiar `/acerca-de` a `/quienes-somos`) sin romper ningún enlace en las vistas de la aplicación.
2. **Generación dinámica segura:** Garantiza que el prefijo del servidor, puerto o subdirectorios donde se encuentre alojado el proyecto se resuelvan de forma automática y consistente.
3. **Detección temprana de errores:** Si el nombre de la ruta pasado a `path()` contiene una errata o no existe, Symfony lanzará una excepción al compilar la plantilla durante el desarrollo, impidiendo que el sitio despliegue enlaces rotos inadvertidamente.