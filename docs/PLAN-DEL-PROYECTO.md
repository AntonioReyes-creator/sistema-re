# Plan del proyecto: plataforma de revisión y corrección de exámenes

Documento de referencia para el desarrollo. Se actualiza al cerrar cada fase o al tomar una decisión nueva.

- **Última actualización:** 2026-10-13
- **Estado actual:** Fase 0 casi terminada (falta verificar GitHub Actions).

---

# Parte 1. Resumen y conclusiones

## 1.1 Qué vamos a construir

Una plataforma web donde un docente:

1. crea una evaluación con sus preguntas, clave de respuestas y rúbrica,
2. imprime las hojas de examen que genera la plataforma,
3. sube un lote de unos 30 exámenes escaneados o fotografiados,
4. recibe notas propuestas con justificación,
5. revisa **solo los casos dudosos** y aprueba las notas antes de publicarlas.

Está pensada para estudiantes de 1.º a 5.º de secundaria.

## 1.2 Principios acordados

| Principio | Qué significa en la práctica |
|---|---|
| **La IA propone y el docente aprueba** | Ninguna nota se publica sin aprobación humana. |
| **Revisión por excepciones** | El docente revisa solo lo que el sistema marca como dudoso, no los 30 exámenes. |
| **Nota final con reglas fijas** | La IA propone puntajes por criterio; la suma, los límites y la nota final los calcula el código. |
| **Medir antes de construir** | Antes de la plataforma, se mide con exámenes reales si la IA lee y califica bien. |
| **Avance incremental** | Cada fase entrega algo que funciona y se prueba antes de pasar a la siguiente. |
| **Reutilizar open source** | En cada módulo se busca primero una librería o repositorio existente, revisando su licencia. |
| **Monolito modular** | Una aplicación Laravel y un solo servicio Python. Sin microservicios hasta que haga falta. |

## 1.3 Ya existen soluciones parecidas

- **Comerciales:** Gradescope (Turnitin), PowerGrader, IntelGrader, Examino.
- **Open source:** llm-grader, ScribeCheck, ai-answer-pre-grader, gradeops, ScriptTrust-AI.

**Conclusión:** no partimos de uno de esos proyectos como base, porque casi todos son personales o académicos, no usan PHP y no resuelven lo difícil. Sí tomamos sus ideas y librerías concretas.

**Dónde nos diferenciamos:**
- funcionar bien en español,
- adaptarnos a la escala de notas y los informes locales,
- cumplir la ley de protección de datos de menores,
- ser fácil de usar para un docente individual.

## 1.4 Decisiones clave

### A. La plataforma genera la hoja de examen
Cada hoja impresa lleva un **código QR** (evaluación, alumno y página), **marcas en las esquinas** para enderezarla y **zonas de respuesta** definidas.

Con esto se resuelven:
- la identificación del alumno,
- el orden de las páginas,
- la ubicación de cada pregunta,
- la lectura fiable de la opción múltiple.

Los exámenes en hoja libre quedan para más adelante y siempre con más revisión humana.

### B. El docente define el tipo de cada pregunta
El sistema no adivina si una pregunta es de opción múltiple o de desarrollo: se configura al crear la evaluación.

### C. Alcance del MVP por tipo de pregunta

| Tipo | Dificultad | En el MVP | Cómo |
|---|---|---|---|
| A. Opción múltiple | Baja con plantilla | Sí | Lectura de marcas con OpenCV, sin IA. |
| B. Desarrollo | Media | Sí | Modelo de visión con la rúbrica. |
| C. Inglés (reading, writing, gramática) | Baja a media | Sí | Modelo de visión con la rúbrica. |
| D. Relacionar con líneas | Alta | No | Fase posterior; mientras tanto, revisión manual asistida. |
| E. Colorear y dibujos | Muy alta | No | Por defecto, revisión del docente. |

### D. Lectura de la letra manuscrita
Se usará un **modelo de visión por API** que lea y evalúe en el mismo paso. El OCR clásico (Tesseract, PaddleOCR) funciona mal con letra manuscrita de escolares; solo sirve para texto impreso.

### E. Cómo decidir qué va a revisión
No basta con la "confianza" que declara el modelo. Se usarán:
- **dos lecturas** de la misma respuesta (o dos modelos distintos): si no coinciden, va a revisión;
- **reglas fijas:** puntaje dentro del máximo, respuesta no vacía, QR válido, alumno existente.

### F. Aprender de las correcciones sin entrenar modelos
- **Dentro del lote:** cuando el docente corrige una respuesta, el sistema propone aplicar la misma corrección a respuestas parecidas, mostrando antes cuáles cambiarían.
- **Entre evaluaciones:** las correcciones aprobadas se guardan como ejemplos que se añaden a las instrucciones del modelo para esa pregunta.

## 1.5 Tecnologías

| Capa | Tecnología |
|---|---|
| Backend web | PHP con Laravel |
| Interfaz | Inertia con Vue (pantalla de revisión) y Filament (panel de administración) |
| Base de datos | MySQL 8 (pendiente decidir si se cambia a PostgreSQL) |
| Colas | Redis con Laravel Horizon |
| Archivos | Volumen privado compartido entre Laravel y Python en desarrollo; almacenamiento compatible con S3 en producción (se decide en la Fase 13) |
| Procesamiento | Python con FastAPI y Pydantic |
| Imágenes | OpenCV, Pillow, pypdfium2, zxing-cpp |
| IA | Modelo de visión por API, con structured outputs, Batch API y prompt caching |
| Despliegue | Docker Compose en un VPS |
| Pruebas | Pest (PHP), pytest (Python), GitHub Actions |

**Comunicación Laravel ↔ Python:** un trabajo de cola por página. El trabajo llama por HTTP a FastAPI, enviando una referencia al archivo (no la imagen en Base64), con un tiempo límite de unos 2 minutos.

**Lo que no usaremos por ahora:** microservicios, Kubernetes, Kafka, entrenar modelos propios, base de datos vectorial ni GPU propia.

## 1.6 Componentes open source a reutilizar

| Módulo | Componente | Licencia | Uso |
|---|---|---|---|
| Administración | Filament | MIT | Pantallas de cursos, alumnos y evaluaciones. |
| Login y permisos | Laravel Breeze, spatie/laravel-permission | MIT | Login, roles e instituciones. |
| Auditoría | spatie/laravel-activitylog | MIT | Historial de cambios de notas. |
| Informes | maatwebsite/excel, DomPDF | MIT / LGPL | Excel y PDF. |
| QR en las hojas | simplesoftwareio/simple-qrcode | MIT | Generar el QR de cada hoja. |
| Opción múltiple | OMRChecker | **Por verificar** (GPLv3 o MIT según la fuente) | Lectura de burbujas. |
| QR y alineación | zxing-cpp, ArUco de OpenCV | Apache 2.0 | Leer el QR y enderezar la hoja. |
| PDF a imagen | pypdfium2 | Apache / BSD | Convertir PDF a imágenes. **No usar PyMuPDF (AGPL).** |
| Visor de revisión | OpenSeadragon, Annotorious | BSD | Zoom y anotaciones sobre la imagen. |
| Datos de prueba | Label Studio | Apache 2.0 | Etiquetar exámenes reales con la nota correcta. |
| Ideas de evaluación con IA | llm-grader, ScribeCheck | Por verificar | Instrucciones al modelo, rúbricas, doble lectura. |

**Reglas para integrar código de terceros:**
1. **Revisar la licencia antes de copiar código.** MIT, Apache y BSD se pueden usar. GPL se aísla en el servicio Python. AGPL se evita.
2. **Preferir librerías mantenidas** antes que proyectos completos de una sola persona.
3. **Encapsular cada componente** detrás de una interfaz propia, para poder cambiarlo sin tocar el resto.
4. **Registrar cada componente** con su versión y licencia en `TERCEROS.md`.

## 1.7 Costos de IA (estimación preliminar)

Precios por millón de tokens (octubre de 2026):

| Modelo | Entrada | Salida |
|---|---|---|
| Claude Opus 5.5 | $4 | $20 |
| Claude Sonnet 5.5 | $2 | $10 |
| Claude Haiku 5.5 | $0.10 | $0.50 |

**Estimación** (se confirmará en la Fase 2), suponiendo unos 3.000 tokens de entrada y 1.000 de salida por página:

| Modelo | Por página | Lote de 30 exámenes × 4 páginas | Con Batch API (−50%) |
|---|---|---|---|
| Opus 5.5 | ~$0.03 | ~$4 | ~$2 |
| Sonnet 5.5 | ~$0.015 | ~$2 | ~$1 |

## 1.8 Riesgos principales

| Riesgo | Mitigación |
|---|---|
| La IA no lee bien la letra de los alumnos | Fase 2: medir con exámenes reales antes de construir la plataforma. |
| Notas equivocadas que nadie revisa | Doble lectura, reglas fijas, umbrales calibrados y aprobación obligatoria del docente. |
| Datos personales de menores enviados a un proveedor externo | Revisar términos del proveedor (entrenamiento, retención, región), consentimiento y la ley local antes del piloto. |
| Licencias incompatibles | Revisión de licencias en la Fase 1 y registro en `TERCEROS.md`. |
| Alcance demasiado grande | MVP limitado a los tipos A, B y C, y avance por fases. |
| Costos de IA mayores a lo previsto | Medir el costo real en la Fase 2; usar Batch API, prompt caching y modelos más baratos si la calidad se mantiene. |

## 1.9 Metas de éxito (propuestas, a confirmar en la Fase 2)

Con un lote de 30 exámenes:

- **≥ 70 %** de las respuestas evaluadas automáticamente.
- **≤ 2 %** de error entre las respuestas evaluadas automáticamente, comparando con la nota del docente.
- **≤ 20 minutos** para que el docente revise las excepciones del lote.
- **0** notas publicadas sin aprobación del docente.

## 1.10 Decisiones pendientes

| # | Pregunta | Afecta a |
|---|---|---|
| 1 | ¿Quiénes usarán la plataforma al inicio: un docente, varios o instituciones? | Login, base de datos, permisos |
| 2 | ¿Registro público, por invitación o uso interno? | Login |
| 3 | ¿MySQL o PostgreSQL? | Base de datos |
| 4 | ¿Qué proveedor de IA y qué modelo? | Fase 2 y costos |
| 5 | ¿País y ley de protección de datos aplicable? (se supone Perú, Ley 29733) | Seguridad y contratos |
| 6 | ¿Qué asignaturas y tipos de examen se usarán en el piloto? | Fases 2 y 10 |
| 7 | ¿Escala de notas (por ejemplo 0–20 o letras AD, A, B, C)? | Cálculo de notas e informes |

---

# Parte 2. Fases de desarrollo

## 2.1 Cómo trabajamos

- **Una fase a la vez.** No se empieza una fase sin cerrar sus dependencias.
- **Cada fase entrega algo que funciona** y se puede mostrar.
- **Nada se da por terminado sin pruebas.** Cada fase tiene sus pruebas y sus criterios de aceptación.
- **Todo se anota en este documento:** al cerrar una fase se actualiza la tabla de seguimiento y la bitácora de decisiones.

### Definición de "terminado" (aplica a todas las fases)

- [ ] Las tareas de la fase están completas.
- [ ] Las pruebas automáticas de la fase pasan en local y en GitHub Actions.
- [ ] Las pruebas manuales están hechas y anotadas.
- [ ] Se cumplen los criterios de aceptación.
- [ ] Los componentes de terceros nuevos están en `TERCEROS.md`.
- [ ] Este documento está actualizado (seguimiento y bitácora).

### Tipos de prueba que usaremos

| Tipo | Herramienta | Qué comprueba |
|---|---|---|
| Unitarias | Pest (PHP), pytest (Python) | Funciones y clases por separado. |
| Integración | Pest, pytest | Laravel con base de datos, colas y servicio Python. |
| Contrato | pytest con esquemas Pydantic | Que Laravel y Python se envían el formato acordado. |
| Interfaz | Pest con Laravel Dusk o pruebas de navegador | Flujos completos desde la web. |
| Calidad de IA | Conjunto de exámenes de referencia | Porcentaje de acierto contra la nota del docente. |
| Manuales | Lista de verificación en este documento | Lo que no se puede automatizar. |

**Conjunto de exámenes de referencia:** exámenes reales, anonimizados y ya corregidos por docentes. Se crea en la Fase 2 y se usa como prueba de regresión en todas las fases siguientes. Si un cambio empeora el acierto, no se integra.

## 2.2 Seguimiento

| Fase | Nombre | Depende de | Estado |
|---|---|---|---|
| 0 | Organización y entorno | — | En curso |
| 1 | Evaluación de componentes open source | 0 | Pendiente |
| 2 | Prueba de concepto de IA | 0, 1 | Pendiente |
| 3 | Plataforma base y login | 0 | Pendiente |
| 4 | Evaluaciones y hojas con QR | 3 | Pendiente |
| 5 | Carga de lotes y preparación de documentos | 4 | Pendiente |
| 6 | Opción múltiple | 5 | Pendiente |
| 7 | Desarrollo e inglés con IA | 2, 5 | Pendiente |
| 8 | Revisión por excepciones | 6, 7 | Pendiente |
| 9 | Notas, aprobación e informes | 8 | Pendiente |
| 10 | Piloto con un docente real | 9 | Pendiente |
| 11 | Aprendizaje de correcciones | 10 | Pendiente |
| 12 | Preguntas de relación y gráficas | 10 | Pendiente |
| 13 | Producción y servicio comercial | 10 | Pendiente |

**Hitos:**
- **Hito 1, viabilidad (fin de la Fase 2):** decidir si se continúa, con qué modelo y con qué metas.
- **Hito 2, MVP (fin de la Fase 9):** flujo completo de un lote, de la carga a los informes.
- **Hito 3, validación (fin de la Fase 10):** el MVP funciona con un docente real.

```
Fase 0 ─┬─ Fase 1 ── Fase 2 ─────────────────┐
        │                                     ▼
        └─ Fase 3 ── Fase 4 ── Fase 5 ─┬─ Fase 6 ─┐
                                       └─ Fase 7 ─┴─ Fase 8 ── Fase 9 ── Fase 10 ─┬─ Fase 11
                                                                                  ├─ Fase 12
                                                                                  └─ Fase 13
```

Las Fases 1–2 y 3–5 pueden avanzar en paralelo si hay más de una persona.

---

## Fase 0. Organización y entorno

**Objetivo:** dejar el repositorio y el entorno listos para que cualquier persona pueda levantar el proyecto con un solo comando.

**Tareas:**
- [x] Definir la estructura del repositorio:
  ```
  sistema-re/
  ├── web/          # Laravel 13 (PHP 8.4)
  ├── procesador/   # Python 3.13 + FastAPI
  ├── docker/       # Dockerfile de PHP y configuración de Nginx
  ├── poc/          # Prueba de concepto de IA (se crea en la Fase 2)
  ├── docs/         # Este plan y documentación
  ├── docker-compose.yml
  └── TERCEROS.md
  ```
- [x] Crear `docker-compose.yml` con: Laravel (PHP-FPM y Nginx), MySQL, Redis y el servicio Python. Los documentos se guardan en un volumen compartido (MinIO se descartó; ver bitácora).
- [x] Crear `.env.example` para cada parte, sin secretos reales.
- [x] Crear `TERCEROS.md` con su formato (componente, versión, licencia, uso).
- [x] Configurar GitHub Actions para correr las pruebas de PHP y Python (`.github/workflows/ci.yml`).
- [x] Definir convenciones: idioma del código, nombres de ramas y formato de commits (`docs/CONVENCIONES.md`).

**Pruebas:**
- [x] `docker compose up` levanta todos los servicios sin errores (2026-10-13).
- [x] Endpoint de salud de Laravel (`/up`) responde 200.
- [x] Endpoint de salud de FastAPI (`/health`) responde 200.
- [x] Pruebas de Pest (3) y pytest (1) pasan en local.
- [ ] Las mismas pruebas pasan en GitHub Actions (pendiente: subir el repositorio a GitHub).
- [ ] Una persona nueva levanta el proyecto siguiendo solo el README (pendiente: práctica guiada con el usuario).

**Criterios de aceptación:**
- Una persona nueva clona el repositorio y tiene todo funcionando en menos de 15 minutos siguiendo el README.

**Entregable:** repositorio con la estructura, Docker y CI funcionando.

---

## Fase 1. Evaluación de componentes open source

**Objetivo:** decidir con datos qué repositorios y librerías vamos a integrar y cuáles solo usaremos como referencia.

**Tareas:**
- [ ] Revisar **OMRChecker**: licencia real (archivo LICENSE), actividad reciente, calidad del código y si funciona con nuestras hojas.
- [ ] Revisar **llm-grader** y **ScribeCheck**: licencia, cómo estructuran la rúbrica, las instrucciones al modelo y la doble lectura.
- [ ] Confirmar licencias de las librerías de la tabla 1.6.
- [ ] Probar OMRChecker con 5 hojas de burbujas de prueba.
- [ ] Llenar `TERCEROS.md` con lo que se decida integrar.

**Pruebas:**
- [ ] OMRChecker lee correctamente al menos 95 % de las burbujas en las 5 hojas de prueba (marcas claras).
- [ ] Cada componente elegido tiene su licencia verificada y anotada.

**Criterios de aceptación:**
- Hay una tabla de decisión: componente, licencia, ¿se integra o solo es referencia?, motivo.
- Ningún componente AGPL en el proyecto.

**Entregable:** sección "Resultado de la Fase 1" en la bitácora de este documento y `TERCEROS.md` actualizado.

---

## Fase 2. Prueba de concepto de IA (viabilidad)

**Objetivo:** medir, antes de construir la plataforma, si la IA lee y califica correctamente exámenes reales y cuánto cuesta.

**Tareas:**
- [ ] Reunir entre 30 y 100 exámenes reales ya corregidos, **anonimizados**, de 2 o 3 asignaturas (incluida inglés).
- [ ] Etiquetar con Label Studio: transcripción correcta y nota por pregunta y criterio.
- [ ] Escribir las rúbricas en un formato estructurado (JSON o YAML).
- [ ] Script en `poc/` que:
  - envíe cada respuesta (imagen) y su rúbrica al modelo de visión,
  - pida la respuesta con structured outputs (transcripción, puntaje por criterio, justificación, motivo de duda),
  - haga doble lectura y marque desacuerdos,
  - registre los tokens usados y el costo.
- [ ] Comparar al menos dos modelos (por ejemplo, Opus 5.5 y Sonnet 5.5).
- [ ] Probar como referencia un OCR administrado con soporte de manuscritos (Google Document AI o Azure Document Intelligence).
- [ ] Calcular las métricas y escribir el informe de resultados.

**Pruebas (métricas a obtener):**
- [ ] Precisión de la transcripción (porcentaje de palabras correctas).
- [ ] Coincidencia de la nota de la IA con la del docente, por pregunta y por examen.
- [ ] Porcentaje de respuestas que se podrían evaluar automáticamente.
- [ ] Error entre las respuestas evaluadas automáticamente.
- [ ] Costo real por página y por lote.
- [ ] Tiempo de procesamiento por página.

**Criterios de aceptación:**
- Informe con las métricas de cada modelo.
- Decisión documentada: **continuar, ajustar el alcance o detener**.
- Metas de la sección 1.9 confirmadas o corregidas con datos reales.
- Umbrales iniciales para mandar respuestas a revisión.

**Entregable:** carpeta `poc/` con el script, las rúbricas de ejemplo, los resultados y el informe. Los exámenes reales **no** se suben al repositorio.

---

## Fase 3. Plataforma base y login

**Objetivo:** una web segura donde el docente entra y organiza sus cursos y alumnos.

**Tareas:**
- [ ] Instalar Laravel en `web/`.
- [ ] Login, cierre de sesión y recuperación de contraseña (Breeze).
- [ ] Modelo de datos inicial: instituciones, usuarios, roles, cursos, alumnos.
- [ ] Separación por institución: columna `institution_id` y filtro global en los modelos.
- [ ] Roles con spatie/laravel-permission: administrador de institución y docente.
- [ ] Panel de administración con Filament: cursos, alumnos e importación de alumnos desde Excel.
- [ ] Auditoría básica con spatie/laravel-activitylog.

**Pruebas:**
- [ ] Unitarias: un docente solo ve sus cursos y alumnos.
- [ ] Integración: un docente de la institución A **no** puede ver ni editar datos de la institución B (prueba obligatoria).
- [ ] Interfaz: login, recuperación de contraseña y cierre de sesión.
- [ ] Importar 30 alumnos desde un Excel de ejemplo.

**Criterios de aceptación:**
- Un docente entra, crea un curso e importa a sus alumnos.
- Las pruebas de separación entre instituciones pasan.

**Entregable:** plataforma con login, cursos y alumnos.

---

## Fase 4. Evaluaciones y hojas de examen con QR

**Objetivo:** el docente crea una evaluación y descarga las hojas listas para imprimir.

**Tareas:**
- [ ] Modelo de datos: evaluaciones, preguntas, tipo de pregunta, clave de respuestas, rúbricas, criterios y puntaje máximo.
- [ ] Pantalla para crear la evaluación y sus preguntas (opción múltiple, desarrollo, inglés).
- [ ] Editor de rúbricas por criterio.
- [ ] Plantilla de hoja de examen: marcas en las esquinas, QR (evaluación, alumno, página) y zonas de respuesta.
- [ ] Generar un PDF por alumno con su QR, o un PDF con todas las hojas del curso.
- [ ] Guardar la posición de cada zona de respuesta (coordenadas) para usarla en el procesamiento.

**Pruebas:**
- [ ] Unitarias: la suma de puntajes máximos coincide con el total de la evaluación.
- [ ] Unitarias: no se puede guardar una pregunta sin clave o sin rúbrica.
- [ ] Integración: el PDF generado tiene una página por hoja y el QR decodifica los datos correctos.
- [ ] Manual: imprimir, escanear y comprobar que el QR se lee desde el escaneo.

**Criterios de aceptación:**
- El docente crea una evaluación con 10 preguntas mixtas y descarga las hojas de un curso de 30 alumnos.
- El QR de cada hoja escaneada se lee correctamente.

**Entregable:** creación de evaluaciones y generación de hojas imprimibles.

---

## Fase 5. Carga de lotes y preparación de documentos

**Objetivo:** el docente sube un lote y el sistema lo organiza por alumno, página y pregunta.

**Tareas:**
- [ ] Laravel: subida de PDF e imágenes a almacenamiento privado, con validación de tipo y tamaño.
- [ ] Registro del lote y de cada página con estados: `pendiente`, `procesando`, `requiere_revision`, `completado`, `fallido`.
- [ ] Cola con Redis y Horizon: un trabajo por página, con reintentos.
- [ ] Prevenir trabajos duplicados (clave única por página y versión).
- [ ] Servicio Python en `procesador/`:
  - PDF a imágenes (pypdfium2),
  - lectura del QR (zxing-cpp),
  - enderezar la hoja con las marcas de las esquinas (OpenCV),
  - recortar cada zona de respuesta,
  - detectar páginas en blanco, borrosas o duplicadas.
- [ ] Contrato Laravel ↔ Python definido con Pydantic y documentado.
- [ ] Autenticación entre Laravel y Python (token compartido).
- [ ] Pantalla de progreso del lote (sin tener que dejar la página abierta).

**Pruebas:**
- [ ] Unitarias (pytest): enderezado con hojas giradas hasta 15°, lectura de QR, recorte de zonas.
- [ ] Contrato: Laravel y Python aceptan y devuelven el formato acordado.
- [ ] Integración: un lote de 30 exámenes de prueba queda organizado por alumno y página.
- [ ] Casos de error: página sin QR, página repetida, página faltante, archivo dañado.
- [ ] Reintento: si el servicio Python se cae, el trabajo se reintenta y no se duplica.

**Criterios de aceptación:**
- Un lote de 30 exámenes escaneados queda separado por alumno, página y pregunta.
- Las páginas con problemas aparecen marcadas con su motivo.

**Entregable:** carga de lotes con procesamiento en segundo plano y recortes por pregunta.

---

## Fase 6. Preguntas de opción múltiple

**Objetivo:** calificar automáticamente la opción múltiple sin usar IA.

**Tareas:**
- [ ] Integrar OMRChecker (o lectura propia con OpenCV, según la Fase 1) detrás del endpoint `/omr/read`.
- [ ] Detectar: marca única, ninguna marca, varias marcas, marca corregida, marca tenue.
- [ ] Comparar con la clave y calcular el puntaje.
- [ ] Marcar como `requiere_revision` los casos ambiguos, con su motivo.

**Pruebas:**
- [ ] Conjunto de prueba con marcas: equis, círculo, visto, relleno parcial, borrones y corrección.
- [ ] Unitarias: cálculo de puntaje con la clave.
- [ ] Calidad: ≥ 99 % de acierto en marcas claras; 100 % de las marcas ambiguas van a revisión.

**Criterios de aceptación:**
- La opción múltiple de un lote de 30 exámenes se califica sin intervención, salvo los casos ambiguos marcados.

**Entregable:** calificación automática de opción múltiple.

---

## Fase 7. Preguntas de desarrollo e inglés con IA

**Objetivo:** leer y evaluar respuestas manuscritas con la rúbrica, usando lo aprendido en la Fase 2.

**Tareas:**
- [ ] Módulo de IA en Python detrás del endpoint `/evaluate`, con el proveedor encapsulado para poder cambiarlo.
- [ ] Instrucciones al modelo y esquema de salida tomados de la Fase 2.
- [ ] Structured outputs: transcripción, puntaje por criterio, justificación y motivo de duda.
- [ ] Doble lectura y comparación; si no coinciden, `requiere_revision`.
- [ ] Reglas fijas: puntaje dentro del máximo, respuesta vacía, texto ilegible.
- [ ] Prompt caching para la rúbrica y Batch API para lotes que no son urgentes.
- [ ] Registro de tokens y costo por página y por lote.
- [ ] Criterios distintos por objetivo de la pregunta (por ejemplo, la gramática pesa distinto en reading que en un ejercicio de gramática).

**Pruebas:**
- [ ] Unitarias: validación del JSON de salida y de los límites de puntaje.
- [ ] Integración con el proveedor de IA simulado (sin costo) para pruebas automáticas.
- [ ] Calidad: correr el conjunto de exámenes de referencia y comparar con los resultados de la Fase 2. El acierto no debe bajar.
- [ ] Costo: el costo real por lote está dentro de lo estimado en la Fase 2.

**Criterios de aceptación:**
- Se cumplen las metas de acierto definidas en la Fase 2 sobre el conjunto de referencia.
- Toda respuesta tiene un estado y una justificación.

**Entregable:** evaluación automática de desarrollo e inglés.

---

## Fase 8. Revisión por excepciones

**Objetivo:** que el docente revise solo los casos dudosos, rápido y sin reprocesar el lote.

**Tareas:**
- [ ] Pantalla de revisión (Inertia + Vue) con OpenSeadragon para el zoom.
- [ ] Para cada excepción, mostrar: imagen original, transcripción, pregunta, respuesta interpretada, puntaje propuesto y motivo.
- [ ] Acciones: aceptar, corregir transcripción, corregir puntaje por criterio, marcar como no procesable.
- [ ] Atajos de teclado para pasar de un caso al siguiente.
- [ ] Estado `validada_por_docente` y registro de quién cambió qué.
- [ ] Filtros: por pregunta, por alumno, por motivo.
- [ ] Revisión manual asistida para las preguntas de tipo D y E: recorte de la imagen y el docente asigna el puntaje.

**Pruebas:**
- [ ] Integración: una corrección del docente actualiza solo esa respuesta y queda en la auditoría.
- [ ] Interfaz: revisar 20 excepciones seguidas solo con el teclado.
- [ ] Manual: medir el tiempo de revisión de un lote de prueba.

**Criterios de aceptación:**
- Un docente revisa todas las excepciones de un lote de 30 exámenes en el tiempo meta (≤ 20 minutos, o la meta corregida en la Fase 2).

**Entregable:** pantalla de revisión por excepciones.

---

## Fase 9. Notas, aprobación e informes

**Objetivo:** calcular las notas con reglas fijas, aprobarlas y exportarlas.

**Tareas:**
- [ ] Cálculo de la nota final según la escala configurada (pendiente: decisión 7).
- [ ] Aprobación del lote por el docente; sin aprobación no se publica nada.
- [ ] Informes: Excel con las notas del curso y PDF por alumno con observaciones.
- [ ] Bloquear cambios después de aprobar (o exigir motivo y dejar registro).

**Pruebas:**
- [ ] Unitarias: cálculo de notas con casos límite (cero, máximo, decimales, redondeo).
- [ ] Integración: no se puede exportar ni publicar un lote sin aprobar.
- [ ] Integración: el Excel y el PDF coinciden con las notas en la base de datos.

**Criterios de aceptación:**
- Flujo completo funcionando: crear evaluación → imprimir → subir lote → procesar → revisar → aprobar → exportar.

**Entregable:** **MVP completo (Hito 2).**

---

## Fase 10. Piloto con un docente real

**Objetivo:** validar el MVP con exámenes y un docente reales.

**Tareas:**
- [ ] Revisar la protección de datos antes del piloto: consentimiento, términos del proveedor de IA, retención.
- [ ] Desplegar en un servidor de pruebas.
- [ ] Acompañar al docente en al menos 2 lotes reales.
- [ ] Medir las metas de la sección 1.9.
- [ ] Recoger comentarios y priorizar mejoras.

**Pruebas:**
- [ ] Las métricas reales del piloto comparadas con las de la Fase 2.
- [ ] Encuesta breve al docente sobre facilidad de uso y tiempo ahorrado.

**Criterios de aceptación:**
- Se cumplen las metas de éxito, o hay un plan concreto para cerrar la diferencia.

**Entregable:** informe del piloto y lista priorizada de mejoras **(Hito 3)**.

---

## Fase 11. Aprendizaje de correcciones

**Objetivo:** que las correcciones del docente se reutilicen de forma controlada.

**Tareas:**
- [ ] Registrar la interpretación inicial y la corrección, con el tipo de error.
- [ ] Buscar respuestas parecidas en el mismo lote y pregunta.
- [ ] Proponer aplicar la misma corrección, mostrando antes qué casos cambiarían.
- [ ] Reprocesar solo los casos aceptados.
- [ ] Guardar las correcciones aprobadas como ejemplos para futuras evaluaciones de esa pregunta.

**Pruebas:**
- [ ] Integración: aplicar una corrección a casos similares cambia solo los seleccionados.
- [ ] Calidad: con los ejemplos aprobados, el acierto sobre el conjunto de referencia sube o se mantiene.

**Criterios de aceptación:**
- Ninguna corrección se aplica sin aprobación del docente.
- Se puede demostrar con datos si la corrección mejoró los resultados.

**Entregable:** propagación de correcciones dentro del lote y ejemplos reutilizables.

---

## Fase 12. Preguntas de relación y gráficas

**Objetivo:** automatizar lo que sea fiable de los tipos D y E.

**Tareas:**
- [ ] Preguntas de relación: plantilla con puntos de origen y destino definidos; detección de trazos con OpenCV y apoyo del modelo de visión.
- [ ] Coloreado: solo casos acotados con plantilla (por ejemplo, "colorea la región X").
- [ ] Todo lo que no sea fiable sigue en revisión manual asistida.

**Pruebas:**
- [ ] Conjunto de prueba con líneas rectas, curvas, cruzadas y superpuestas.
- [ ] Calidad: medir el acierto y decidir qué casos se automatizan.

**Criterios de aceptación:**
- Solo se automatizan los casos que superan la meta de acierto; el resto va a revisión.

**Entregable:** soporte parcial de preguntas de relación y gráficas.

---

## Fase 13. Producción y servicio comercial

**Objetivo:** dejar la plataforma lista para ofrecerse a otros docentes o instituciones.

**Tareas:**
- [ ] Despliegue seguro: HTTPS, copias de seguridad, monitoreo de errores.
- [ ] Límites de uso y seguimiento de costos por institución.
- [ ] Política de retención y borrado de exámenes.
- [ ] Pruebas de carga con varios lotes en paralelo.
- [ ] Planes de suscripción y facturación, si se decide vender.

**Pruebas:**
- [ ] Carga: 10 lotes simultáneos sin errores ni duplicados.
- [ ] Recuperación: restaurar una copia de seguridad en un servidor limpio.
- [ ] Revisión de seguridad del código y de la configuración.

**Criterios de aceptación:**
- La plataforma soporta el volumen esperado y cumple la política de datos.

**Entregable:** plataforma en producción.

---

# Parte 3. Bitácora de decisiones

Registrar aquí cada decisión importante, con fecha y motivo.

| Fecha | Decisión | Motivo |
|---|---|---|
| 2026-10-12 | Monolito modular en Laravel y un servicio Python | Desarrollo más rápido; sin complejidad de microservicios. |
| 2026-10-12 | La plataforma genera las hojas con QR y zonas de respuesta | Resuelve identificación, orden de páginas y ubicación de preguntas. |
| 2026-10-12 | MVP con preguntas de tipo A, B y C | D y E son mucho más difíciles; quedan para la Fase 12. |
| 2026-10-12 | Modelo de visión en lugar de OCR clásico para letra manuscrita | El OCR clásico funciona mal con letra de escolares. |
| 2026-10-12 | Prueba de concepto de IA antes de la plataforma | Es el mayor riesgo del proyecto; se mide primero. |
| 2026-10-12 | Reutilizar open source revisando licencias | Acelerar el desarrollo sin riesgos legales. |
| 2026-10-12 | No usar PyMuPDF (AGPL); usar pypdfium2 | Evitar la obligación de publicar el código. |
| 2026-10-13 | Laravel 13 con PHP 8.4, ejecutado solo dentro de Docker | El PHP local es 7.4 y Laravel 13 exige 8.3 o más; Docker evita instalar nada en el equipo. |
| 2026-10-13 | Nombres de código y endpoints en inglés; interfaz y documentación en español | Laravel y Eloquent asumen nombres en inglés (ver `docs/CONVENCIONES.md`). |
| 2026-10-13 | Sin MinIO; volumen Docker compartido para los documentos en desarrollo | MinIO usa licencia AGPL y no hace falta para el MVP; el almacenamiento S3 se decide en la Fase 13. |
| 2026-10-13 | En desarrollo, `storage` y `bootstrap/cache` reciben permisos 777 al arrancar `web` | En Windows los archivos montados aparecen como de root y PHP no podría escribir. No aplica a producción. |

---

# Referencias

**Repositorios similares:**
- [llm-grader](https://github.com/fujisawa-max/llm-grader)
- [ScribeCheck](https://github.com/Shiva-a1/ScribeCheck)
- [ai-answer-pre-grader](https://github.com/SAANTHOSH-M-G/ai-answer-pre-grader)
- [gradeops](https://github.com/tuhinnss/gradeops)
- [ScriptTrust-AI](https://github.com/shreyas-robotics/ScriptTrust-AI)
- [Handwritten-evaluator-answer-sheet](https://github.com/AaryanPartap/Handwritten-evaluator-answer-sheet)
- [Automated-Exam-Scoring-LLM](https://github.com/akturkumut/Automated-Exam-Scoring-LLM)
- [OMRChecker](https://github.com/Udayraj123/OMRChecker)

**Investigación:**
- [Automated Grading of Handwritten Mathematics Using Vision-Capable LLMs (arXiv, 2026)](https://arxiv.org/html/2605.19043)

**Productos comerciales:**
- [Alternativas a Gradescope (Apporto)](https://www.apporto.com/gradescope-alternatives)
- [Reseña de Gradescope 2026 (AI Indigo)](https://aiindigo.com/blog/gradescope-review-2026-is-ai-driven-grading-actually-saving-educators-time)
