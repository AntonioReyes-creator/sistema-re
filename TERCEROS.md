# Componentes de terceros

Registro de librerías, imágenes y repositorios de terceros que usa el proyecto.
Antes de añadir uno nuevo, revisar su licencia (ver reglas en `docs/PLAN-DEL-PROYECTO.md`, sección 1.6):

- MIT, Apache 2.0, BSD: se pueden usar.
- GPL: solo aislado en el servicio Python y tras evaluarlo.
- AGPL: no se usa.

| Componente | Versión | Licencia | Dónde | Uso |
|---|---|---|---|---|
| Laravel (framework) | 13.x | MIT | `web/` | Plataforma web |
| Pest | 4.x | MIT | `web/` (desarrollo) | Pruebas de PHP |
| FastAPI | 0.115.x | MIT | `procesador/` | API del servicio Python |
| Uvicorn | 0.34.x | BSD-3-Clause | `procesador/` | Servidor de FastAPI |
| pydantic-settings | 2.9.x | MIT | `procesador/` | Configuración por variables de entorno |
| pytest | 8.x | MIT | `procesador/` (desarrollo) | Pruebas de Python |
| HTTPX | 0.28.x | BSD-3-Clause | `procesador/` (desarrollo) | Cliente de pruebas de FastAPI |
| php-extension-installer | 2.x | MIT | `docker/php/Dockerfile` | Instalar extensiones de PHP |
| Imagen `php` | 8.4-fpm | PHP License | Docker | Ejecutar Laravel |
| Imagen `python` | 3.13-slim | PSF | Docker | Ejecutar el procesador |
| Imagen `nginx` | 1.27 | BSD-2-Clause | Docker | Servidor web |
| Imagen `mysql` | 8.4 | GPL-2.0 (servidor independiente, sin modificar) | Docker | Base de datos |
| Imagen `redis` | 7 | BSD-3-Clause | Docker | Colas y caché |
