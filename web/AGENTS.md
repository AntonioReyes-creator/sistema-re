# Guía para asistentes de IA: plataforma web (Laravel)

Esta carpeta contiene la plataforma web del proyecto `sistema-re`.
Antes de trabajar, leer `../docs/PLAN-DEL-PROYECTO.md` (fases y decisiones) y `../docs/CONVENCIONES.md`.

## Entorno

- Laravel 13 con PHP 8.4, **solo dentro de Docker**. No instalar PHP ni Composer en el equipo
  (el PHP local es 7.4 y no sirve para este proyecto).
- Ejecutar todos los comandos desde la raíz del repositorio con `docker compose exec web ...`:

```sh
docker compose up -d                                  # encender los servicios
docker compose exec web composer install
docker compose exec web php artisan migrate
docker compose exec web vendor/bin/pest               # pruebas
```

- Servicios: `web` (PHP-FPM), `nginx` (http://localhost:8080), `mysql`, `redis`
  y `procesador` (Python/FastAPI, http://procesador:8001 desde la red de Docker).

## Reglas

- Código (clases, tablas, columnas, rutas) en inglés; textos de la interfaz, comentarios y documentación en español.
- Toda funcionalidad nueva llega con pruebas en Pest (`tests/Feature` o `tests/Unit`).
- Antes de añadir un paquete, revisar su licencia y registrarlo en `../TERCEROS.md` (no se aceptan paquetes AGPL).
- Nunca subir `.env`, exámenes reales ni datos de alumnos.
- Ninguna nota se publica sin aprobación del docente: respetar esta regla en cualquier flujo de calificación.
