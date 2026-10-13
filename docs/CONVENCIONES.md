# Convenciones del proyecto

## Idioma

| Elemento | Idioma | Motivo |
|---|---|---|
| Código: clases, tablas, columnas, variables, endpoints | Inglés | Laravel y Eloquent asumen nombres en inglés (plurales de tablas, relaciones). Mezclar idiomas en el código genera errores. |
| Interfaz para el docente | Español | Usuarios hispanohablantes. |
| Documentación, commits, issues y comentarios | Español | Equipo hispanohablante. |

## Ramas

- `main`: siempre estable; solo recibe cambios por pull request con las pruebas en verde.
- Ramas de trabajo: `fase-<n>/<descripcion-corta>`, por ejemplo `fase-3/login`.

## Commits

Formato [Conventional Commits](https://www.conventionalcommits.org/es/v1.0.0/) en español:

```
<tipo>(<ámbito>): <descripción en imperativo>
```

- **Tipos:** `feat`, `fix`, `test`, `docs`, `refactor`, `chore`, `ci`.
- **Ámbitos:** `web`, `procesador`, `poc`, `docker`, `docs`.

Ejemplos:

```
feat(web): agregar login con recuperación de contraseña
test(procesador): cubrir lectura de QR en hojas giradas
docs: cerrar fase 0 en el plan
```

## Pruebas

- Toda funcionalidad nueva llega con sus pruebas.
- Una fase no se cierra si las pruebas no pasan en GitHub Actions.
- PHP: Pest (`web/tests`). Python: pytest (`procesador/tests`).

## Datos sensibles

- Nunca subir exámenes reales, datos de alumnos ni archivos `.env` al repositorio.
- Los exámenes de la prueba de concepto van en `poc/datos/`, que está en `.gitignore`.
