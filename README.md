# sistema-re
Sistema de revision de examenes

Plataforma web para la revisión y corrección asistida por IA de exámenes escolares.

- Plan del proyecto, decisiones y fases: [docs/PLAN-DEL-PROYECTO.md](docs/PLAN-DEL-PROYECTO.md)
- Convenciones (idioma, ramas, commits): [docs/CONVENCIONES.md](docs/CONVENCIONES.md)
- Componentes de terceros y licencias: [TERCEROS.md](TERCEROS.md)

## Estructura

```
sistema-re/
├── web/          # Plataforma web (Laravel 13, PHP 8.4)
├── procesador/   # Servicio de procesamiento (Python 3.13, FastAPI)
├── docker/       # Dockerfile de PHP y configuración de Nginx
├── docs/         # Plan y documentación
├── docker-compose.yml
└── TERCEROS.md
```

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) en ejecución.
- Git.

No hace falta instalar PHP, Composer ni Python en el equipo: todo corre dentro de Docker.

## Puesta en marcha

```bash
# 1. Archivos de configuración
cp .env.example .env
cp web/.env.example web/.env
cp procesador/.env.example procesador/.env

# 2. Levantar los servicios (la primera vez tarda varios minutos)
docker compose up -d --build

# 3. Instalar dependencias de Laravel, generar la clave y crear las tablas
docker compose exec web composer install
docker compose exec web php artisan key:generate
docker compose exec web php artisan migrate
```

## Comprobar que todo funciona

| Servicio | Dirección | Respuesta esperada |
|---|---|---|
| Web (Laravel) | http://localhost:8080 | Página de inicio de Laravel |
| Salud de la web | http://localhost:8080/up | 200 |
| Procesador (FastAPI) | http://localhost:8001/health | `{"status": "ok"}` |
| Documentación de la API | http://localhost:8001/docs | Swagger de FastAPI |

## Pruebas

```bash
docker compose exec web vendor/bin/pest
docker compose exec procesador pytest
```

Las mismas pruebas corren en GitHub Actions en cada push y pull request.

## Servicios de Docker

| Servicio | Imagen | Puerto local |
|---|---|---|
| `web` | PHP 8.4-FPM | — |
| `nginx` | nginx 1.27 | 8080 |
| `procesador` | Python 3.13 | 8001 |
| `mysql` | MySQL 8.4 | 3307 |
| `redis` | Redis 7 | — |

Los documentos de los exámenes se guardan en el volumen `documentos`, compartido entre `web` y `procesador`.
