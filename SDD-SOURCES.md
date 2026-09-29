# SCI Editorial Blocks — Feature SDD: SCI Sources

**Documento:** Feature Software Design Document  
**Versión objetivo:** SCI Editorial Blocks 0.4.0  
**Versión del plugin inspeccionada:** 0.3.0  
**Block name propuesto:** `sci-editorial/sources`  
**Estado:** APPROVED FOR IMPLEMENTATION — 0.4.0
**Product Owner:** Martín García  
**Technical Director:** ChatGPT  
**Developer / Technical Writer:** Codex  
**Text domain:** `sci-editorial-blocks`

Este documento registra el contrato aprobado para SCI Sources 0.4.0. `SDD.md`, `SDD-READING-TIME.md` y `SDD-RELATED.md` mantienen sus contratos y precedencia para las versiones que definen; este addendum agrega Sources al alcance 0.4.0 sin cambiar el alcance histórico de releases anteriores.

## 1. Convenciones de evidencia

- **Hecho verificado:** observación del repositorio/WordPress local o comportamiento descrito por documentación oficial.
- **Recomendación:** solución concreta preferida para Sources.
- **Decisión propuesta:** contrato para aprobación del Technical Director; todavía no autoriza implementación.
- **Pendiente de validación:** comprobación funcional durante implementación. No deja abierta la elección arquitectónica.
- **Inferencia:** conclusión basada en la composición documentada de APIs Core, que debe comprobarse en la matriz de WordPress indicada.

## 2. Fuentes y versión inspeccionadas

**Hecho local verificado:** la cabecera de `sci-editorial-blocks.php` y `package.json` declaran **0.3.0**. README y CHANGELOG también describen 0.3.0 y SCI Related.

Se revisaron `SDD.md`, `SDD-READING-TIME.md`, `SDD-RELATED.md`, `README.md`, `CHANGELOG.md` y la documentación vigente de TOC, Callout, Reading Time, Related y Automatic Summary: `PHASE-3-TOC-CORE.md`, `PHASE-2-CALLOUT.md`, `PHASE-READING-TIME-0.2.0.md`, `PHASE-RELATED-0.3.0.md`, `PHASE-5-RELEASE.md` y `PHASE-PATCH-ICON-0.1.3.md`.

**Hecho verificado:** el proyecto prioriza reuse-first, Gutenberg-native, WordPress-native, contenido portable, seguridad por defecto, dependencias runtime mínimas y ausencia de JavaScript frontend cuando no hace falta. Callout ya emplea InnerBlocks estáticos restringidos y su informe registra que el markup editorial sobrevivió a la desactivación. El Summary es un pattern compuesto por bloques Core. Estos son precedentes locales para una solución Sources estática.

**Evidencia de comandos/entorno:** `rg -n 'Version:|"version"' sci-editorial-blocks.php package.json package-lock.json`; lectura de los documentos enumerados; `wp --path=<temporary-test-root> core version` devolvió `7.0.4`; `wp eval` consultó `WP_Block_Type_Registry` y encontró `core/list`, `core/list-item` y `core/paragraph` registrados, con `api_version=3`. Esta comprobación fue de solo lectura; el placeholder sustituye la ruta local del entorno desechable.

## 3. Propósito y posición

SCI — Fuentes presenta una lista editorial manual de enlaces que respaldan un artículo técnico. Cada referencia consta de un texto descriptivo y, opcionalmente mientras se edita, un enlace Core aplicado a ese texto. La fuente de autoridad y documentación permanece en el contenido de Gutenberg; el plugin aporta un wrapper y un label traducible.

**Recomendación editorial, no comportamiento del plugin:** colocar Fuentes y documentación después del contenido principal y antes de Relacionado, newsletter o comentarios. El plugin no mueve ni inserta el bloque automáticamente.

## 4. Identidad y alcance propuestos

- Nombre en inserter: **SCI — Fuentes**.
- Block name: `sci-editorial/sources`.
- API: Block API v3, metadata `block.json`, registro server-side desde metadata, siguiendo la estructura existente.
- Target: 0.4.0; versión actual inspeccionada: 0.3.0.
- Un solo nuevo block type. No crear un bloque SCI hijo para cada fuente.
- Una instancia contiene un label fijo y como máximo una lista raíz `core/list`. Cada `core/list-item` de primer nivel representa una fuente; el contenido RichText del item es su título y puede incluir un formato Core `core/link` aplicado al título.
- No almacenar fuentes en atributos estructurados propios `{title,url}` ni crear `sci-editorial/source-item` en 0.4.0.
- Orden manual. Duplicados permitidos.

## 5. Modelo de contenido: comparación obligatoria

| Criterio | A. InnerBlocks / Core | B. Atributos propios serializados |
|---|---|---|
| UX Gutenberg | Cada título se edita inline como List Item; el autor selecciona el título y aplica el enlace con la toolbar Core. | Requiere un editor repetidor propio con un campo de título y un control de URL por fila; aunque use componentes Core, composición y estado los gestiona el plugin. |
| Reordenamiento | Mover `core/list-item` con los movers y las acciones de lista Core. | Implementar acciones de mover y actualizar el array; puede reutilizar botones Core pero no los movers de bloques. |
| Supervivencia al desactivar | Label, HTML de lista, títulos y enlaces serializados están en `post_content` mediante un bloque estático y bloques Core. | Es viable solo si `save()` genera markup completo de todas las filas; los atributos por sí solos no aparecen al desactivar el plugin. |
| Validación / contrato | Usa el formato RichText de enlaces y la estructura list/list-item de Core. No garantiza que cada item tenga un solo link, ni separa título/URL en campos independientes. | Podría representar `{ title, url }` en atributos propios, pero ese modelo no se adopta ni se usará como almacenamiento de fuentes en 0.4.0. |
| Complejidad y mantenimiento | Baja: InnerBlocks, `allowedBlocks`, `InnerBlocks.Content` y controles existentes. | Mayor: modelo array, campos, inserción, eliminación, reordenamiento, recuperación y validación de URLs. |
| Compatibilidad futura | El contenido usa bloques Core portables; los editores conocen listas, movers y enlaces. | Contrato de atributos y editor repetidor quedan bajo mantenimiento SCI. |

### Decisión recomendada

**Decisión propuesta cerrada para aprobación: A. InnerBlocks/Core.** La relación título-enlace se expresa como un List Item Core cuyo RichText contiene el título y cuyo formato opcional `core/link` envuelve ese texto. `core/list-item` ya permite RichText; `core/list` ya modela colección y orden; Gutenberg proporciona edición inline, link UI, inserción, eliminación y reordenamiento. Cada fuente se guarda como `core/list-item`; no existe schema SCI `{title,url}` ni un bloque `sci-editorial/source-item`.

Un enlace es el flujo editorial normal, pero no es estructuralmente obligatorio. Si el autor quita el link, el título permanece como texto normal. No se implementa un formulario/array propietario para imponer una pareja estructurada título-URL ni se filtran/eliminan filas incompletas.

## 6. Composición y allowlist

Contrato del bloque SCI:

```text
sci-editorial/sources
└── core/list
    ├── core/list-item   (una fuente por item de primer nivel)
    ├── core/list-item
    └── core/list-item
```

Debe haber como máximo un `core/list` raíz dentro de SCI Sources. El anidamiento adicional que Core permita dentro de `core/list-item` sigue el comportamiento Core y no se presenta como otra fuente.

- La allowlist de hijos directos de SCI Sources es exactamente `['core/list']`.
- `core/list` contiene sus `core/list-item` nativos. No permitir `core/paragraph`, `core/heading`, `core/image`, `core/video`, `core/columns`, `core/buttons`, `core/query`, `core/html` ni otros bloques SCI como hijos directos del wrapper.
- Usar un template Gutenberg con un único `core/list` como estructura inicial. Aplicar el mecanismo Core más simple (template/locking de bloque) que impida añadir o dejar una segunda lista raíz, manteniendo disponibles la inserción, edición, eliminación y reordenamiento de `core/list-item`. Validar en el baseline que el lock del wrapper no se herede de forma que bloquee esas operaciones. No crear UI propietaria. Si ningún lock/template preserva la edición normal de items, mantener el template de una lista y resolver la inserción de una segunda raíz con la capacidad nativa mínima de `InnerBlocks`; no ampliar el alcance a lógica propietaria de filas.
- Solo los list items de primer nivel son fuentes. El anidamiento de lista que Core permite dentro de un item no constituye otra fuente en el contrato; se deja como capacidad nativa disponible, sin lógica SCI para recorrerlo o transformarlo.
- Usar un único `InnerBlocks` en `edit` y un `InnerBlocks.Content` en `save`.

**Hecho Core:** `InnerBlocks` admite `allowedBlocks`, templates y lock settings; el bloque core/list acepta core/list-item, y core/list-item declara como padre core/list. El template de Sources debe iniciar exactamente una lista. La documentación de locking indica que el template lock puede heredarse por los descendientes y que un lock individual de bloque puede limitar movimiento/eliminación; la elección concreta se validará en editor para no bloquear operaciones de list-item. La instalación local WordPress 7.0.4 comprobó que ambos tipos están registrados con API v3.

## 7. Render y markup estático

**Decisión propuesta:** bloque estático; sin `render.php`, callback dinámico ni consulta.

Markup semántico recomendado:

```html
<aside class="wp-block-sci-editorial-sources">
    <p class="sci-sources-label">Fuentes y documentación</p>
    <ul class="wp-block-list">
        <li><a href="https://example.org/rfc">RFC — título descriptivo</a></li>
    </ul>
</aside>
```

- El label es un `<p>`, no un heading obligatorio. No crear jerarquía H2 automática.
- `save()` construye elementos React normales con `useBlockProps.save()` y `InnerBlocks.Content`; no devuelve HTML crudo ni ejecuta contenido.
- El wrapper, label y listas/títulos/enlaces quedan serializados en el markup guardado. No se añade una copia en PHP ni se calcula output en frontend.
- Puede usarse CSS pequeño para separación y estructura del label/lista, sin bordes, fondos, espaciado o paleta obligatorios.

**Hecho Core:** un bloque estático guarda el HTML de `save()` en el contenido y ese HTML constituye su salida frontend; InnerBlocks se guarda usando `InnerBlocks.Content`.  
**Inferencia corroborada por precedente local:** al desactivar el plugin WordPress conserva/renderiza el HTML ya guardado aunque no pueda editarse como Sources. El informe Callout Phase 2 ya verificó que la lista, el link, el label y el contenido de su bloque estático siguieron visibles tras desactivar SCI. Para Sources, esa degradación es un release gate: se confirmará explícitamente después de guardar varias fuentes.

## 8. Degradación y portabilidad

Al desactivar SCI Editorial Blocks:

- Los títulos y links guardados como HTML permanecen en `post_content` y continúan visibles.
- La lista HTML sigue siendo legible y navegable.
- Se pierde la identidad/editabilidad SCI del wrapper; WordPress puede mostrar el bloque como no disponible en el editor.
- El label traducible “Fuentes y documentación”, si está serializado, también permanece visible.
- CSS exclusivo del plugin puede dejar de estar disponible; el marcado Core conserva estructura y contenido.
- No reescribir el contenido en desactivación ni durante render.

**Release gate obligatorio:** crear Sources, añadir varias fuentes, guardar el post y desactivar SCI Editorial Blocks. El frontend debe conservar de forma legible el label serializado, la lista, los títulos y los enlaces. Puede perderse el estilo SCI y los controles de edición; no puede perderse el contenido editorial. La reactivación debe dejar el bloque válido o recuperable sin pérdida editorial.

Al reactivar el plugin, el block name registrado y la estructura estática original deben reabrirse sin bloque inválido ni pérdida de filas.

## 9. Link behavior y edición

- El título del `core/list-item` es el texto descriptivo del enlace. El autor selecciona el texto y utiliza la toolbar/link UI Core para asignar o editar la URL.
- No crear modal, preview, LinkControl paralelo ni búsqueda remota. No usar `fetch`, `wp_remote_get`, cURL, scraping, OpenGraph o iframe.
- No establecer `target="_blank"`, `rel="nofollow"` ni `rel="sponsored"` automáticamente.
- Si el autor activa “abrir en pestaña nueva” desde la UI Core, respetar el markup y los atributos de seguridad que Core serialice para esa opción; SCI no los altera.
- No imponer una allowlist propia de esquemas/hosts. La edición y normalización del enlace usan el control Core; aplicar el escaping contextual requerido si una fase futura añade render PHP, que no se propone aquí.
- Título/link descriptivos deben seguir operables por teclado. El título es el anchor text cuando existe una URL.

## 10. Items incompletos y empty state

Comportamiento propuesto para que el guardado no borre input editorial ni cree links sin nombre:

| Estado del item | Comportamiento propuesto |
|---|---|
| Título con link | Flujo editorial normal: el texto del título es anchor text del URL Core. |
| Título sin URL o link eliminado | Mantener el título como texto plano en la lista; no descartar el contenido ni mostrar error. |
| Título vacío, con o sin intento de link | Aceptar el comportamiento Core. No validar, advertir, borrar automáticamente ni fabricar un enlace; el autor puede eliminar el item usando Gutenberg. |
| URL vacía | Equivale a quitar el link: mantener el título como texto plano. No inventar `href`. |
| SCI Sources sin lista/items | Mostrar el label serializado; no generar fuente ficticia ni ejecutar consultas. |
| Lista Core con item vacío | Dejarlo al comportamiento Core; no emitir un `href` vacío generado por SCI y no mostrar warnings/admin notices. El editor puede eliminarlo con Gutenberg. |

No implementar validación propietaria. No eliminar filas automáticamente, no convertir una URL en título y no inventar “Fuente 1”. No detectar ni deduplicar URLs. Los items vacíos siguen siendo editables/eliminables con controles Gutenberg; retirar un enlace nunca elimina el texto.

**Pendiente de validación, no decisión abierta:** confirmar en WP 7.0.x cómo serializa/renderiza Core un `core/list-item` vacío y cómo eliminarlo desde controles Gutenberg. SCI no crea ni modifica ese comportamiento; en particular, no fabrica un enlace vacío ni añade warnings/notices.

## 11. Block Supports y Global Styles

Regla: **el plugin controla la estructura; Gutenberg y Global Styles controlan la presentación.**

Supports propuestos para el wrapper SCI, sujeto a disponibilidad en el mínimo:

- `color.text` y `color.link`;
- `typography.fontSize` y `typography.lineHeight`;
- `spacing.margin` y `spacing.padding`.

No habilitar background por defecto, paleta, font-family o valores rígidos. La lista Core puede mantener sus propios supports nativos. Evitar duplicar controles de presentación específicos por cada fuente. No editar `theme.json`; heredar settings/presets del tema. CSS SCI solo estructura y separación mínima, sin `!important`, reset global ni identidad fija.

**Hecho Core:** block supports expone herramientas/atributos al wrapper mediante `useBlockProps()` y `useBlockProps.save()`; `theme.json` limita o habilita la UI y entrega presets/Global Styles.  
**Pendiente de validación:** probar supports escogidos en WordPress mínimo con Twenty Twenty-Five y Twenty Twenty-Four, cuidando que estilos de wrapper no anulen los controles Core de links/lista.

## 12. Accesibilidad e internacionalización

- Cada referencia enlazada usa el título editorial como texto del enlace; no usar “Fuente 1” ni “Ver enlace” como único nombre accesible.
- Label textual **Fuentes y documentación**; no transmitir información solo mediante estilo/color.
- Usar navegación estándar por teclado del editor y `<a>` nativo en frontend.
- Text domain `sci-editorial-blocks`.
- Traducir en JS el nombre/label SCI que el plugin aporta.
- `core/list` proporciona su propia interfaz e inserter traducible para añadir elementos; no duplicar esa interfaz con otro botón SCI “Añadir fuente”. La suma de una fila usa la acción Core de añadir list item. Si una QA del inserter demuestra que el flujo no es comprensible, escalar UX antes de crear un control propio.

## 13. Seguridad

- No aceptar edición de HTML arbitrario; `supports.html = false` en el wrapper si Core lo permite para este bloque.
- Usar solamente InnerBlocks/Core y RichText/link serialization; `save()` emite nodos, no concatenación de HTML editorial.
- No recuperar URLs ni ejecutar código remoto.
- No generar anchors sin título/link ni attributes de link por inferencia.
- No añadir handlers PHP, endpoints, DB, opciones, meta, transients, cron, telemetry o sanitizadores/validadores complejos.
- Aplicar APIs Core de serialización y sanitización. Cualquier futura salida dinámica requeriría nuevo análisis de escaping contextual y aprobación.

## 14. Rendimiento e infraestructura

- 0 JavaScript frontend.
- 0 consultas SQL adicionales.
- 0 peticiones a APIs externas.
- 0 dependencias runtime nuevas.
- 0 storage propio: no post meta, tablas, options, transients o cron.
- HTML de lista guardado + CSS mínimo. JavaScript de editor solo para registrar/editar el bloque bajo las dependencias Core existentes.

## 15. SEO y metadatos

No generar schema `citation`, `ScholarlyArticle` ni `CreativeWork`; no alterar SEO/link attributes. Las fuentes son enlaces editoriales normales. No verificar enlaces rotos ni analizar outbound links.

## 16. Non-goals de 0.4.0

Autor, fecha, editorial/publisher, DOI, ISBN, favicon, imagen, excerpt, metadata automática, scraping, OpenGraph, previews, APA/MLA/BibTeX, Zotero, fuentes detectadas automáticamente, IA, link health checker, schema, enlaces afiliados, ordenamiento automático, detección de duplicados, selección de fuentes fuera del editor, REST/AJAX, almacenamiento persistente y frontend JavaScript.

## 17. Matriz mínima de pruebas

| ID | Caso | Resultado esperado |
|---|---|---|
| A | Una fuente | Item Core y título enlazado renderizan correctamente. |
| B | Múltiples fuentes | Lista muestra cada fuente en el orden guardado. |
| C | Reordenamiento | Movers Core actualizan orden; save/reload conserva orden. |
| D | Título largo | Texto permanece íntegro y envuelve responsive. |
| E | URL larga | Link editable y serializado sin romper layout. |
| F | Unicode/acentos | Texto/link y serialización conservan Unicode. |
| G | HTTPS | Control Core guarda enlace estándar. |
| H | Editar título | Anchor text actualizado después de guardar/reabrir. |
| I | Editar link | Href actualizado con UI Core. |
| J | Eliminar link | Título permanece como texto plano, sin href. |
| K | Eliminar item | Acción Core elimina solo ese list item. |
| L | Item vacío | Sin link vacío; se registra resultado exacto de Core en editor y frontend. |
| M | Guardar/reabrir | Bloque válido, título/link intactos. |
| N | Desactivar plugin | HTML guardado permanece visible como lista. |
| O | Portabilidad del contenido | Títulos y URLs siguen en `post_content`; no se pierden. |
| P | Reactivar plugin | Sources vuelve a reconocerse, sin invalidación. |
| Q | Bloque no permitido en wrapper | Inserter directo solo ofrece `core/list`. |
| R | Twenty Twenty-Five | Global Styles y layout funcionan. |
| S | Twenty Twenty-Four | Sin dependencia de TT5; Global Styles funcionan. |
| T | Responsive | Sin overflow con títulos y URLs extensos. |
| U | TOC regression | Registro, anchors y render sin cambios. |
| V | Callout regression | Registro, variantes y markup sin cambios. |
| W | Reading Time regression | Registro y salida sin cambios. |
| X | Related regression | Registro y candidato renderizado sin cambios. |
| Y | Automatic Summary | Pattern sigue registrado e insertable. |

Gates explícitos de estructura y portabilidad:

| ID | Caso | Resultado esperado |
|---|---|---|
| Z | Una lista raíz | Sources contiene exactamente un `core/list` raíz en el flujo normal; no se puede añadir una segunda lista raíz mediante el flujo de inserción normal. |
| AA | Tres items | Añadir tres `core/list-item` con controles Gutenberg; todos editables. |
| AB | Reordenar items | Movers Core reordenan items; guardar/reabrir conserva el orden. |
| AC | Link → quitar link | El texto del título permanece como texto plano y el bloque no muestra error. |
| AD | Item vacío | Core mantiene su comportamiento; SCI no genera `href` vacío, validación, warning ni admin notice. |
| AE | Lista anidada | Anidamiento Core no invalida Sources ni rompe el frontend; no se añade bloqueo propietario. |
| AF | Desactivar plugin | Tras guardar varias fuentes, frontend conserva label serializado, lista, títulos y enlaces legibles. Gate de release. |
| AG | Reactivar plugin | El bloque vuelve a ser válido o recuperable sin pérdida editorial. |
| AH | Guardar/reabrir | No aparece bloque inválido; items, texto, links y orden persisten. |

La evidencia para Z–AH debe registrar las operaciones Gutenberg y el resultado observado en el WordPress mínimo. La presentación de listas anidadas sigue Core.

Validar instalación/activación sin warnings, inserter, teclado, save/reload y frontend en la versión mínima (WordPress 7.0.x/PHP >= 8.2) y en una versión actual durante QA. Verificar que no hay PHP renderer/queries/requests frontend, y que la desactivación conserva el HTML guardado.

## 18. Riesgos y validaciones de implementación

- **Link al título completo:** el Core List Item permite RichText y `core/link`, pero no impone que el link abarque todo el texto. Aceptado como convención editorial; el editor debe facilitar revisar/editar ese link.
- **Items vacíos:** Core determina su serialización. SCI no valida ni elimina filas; la comprobación asegura que SCI no fabrique `href` vacío ni warning.
- **Single root list:** un template y locking Core deben impedir una segunda lista raíz sin impedir añadir, editar, eliminar y reordenar `core/list-item`. Si el locking heredado de Core interfiere, seleccionar la opción Core mínima en implementación y registrar evidencia; no crear UI propietaria.
- **Allowlist anidada:** el wrapper limita hijos directos a un único `core/list`, mientras List Item Core mantiene sus relaciones nativas (incluido anidamiento de listas). Sources solo trata items superiores como referencias; no debe escanear ni transformar listas anidadas. Su presentación sigue Gutenberg Core.
- **Label traducido guardado:** como markup estático, el texto se traduce al guardarse en el idioma del editor y no se relocaliza dinámicamente para cada visitante. Esto conserva contenido offline/después de desactivar.
- **HTML fallback:** supervivencia general de bloque desconocido se infiere del markup serializado; ya existe evidencia local equivalente en Callout, pero Sources requiere su propia comprobación.
- **Block Supports:** comprobar que color/link/spacing/typography del wrapper respetan estilos del tema y no sustituyen los supports propios de Core List.

No hay un riesgo que requiera persistencia, dependencias, render dinámico o una arquitectura distinta. La validación de implementación de esta estructura es comprobar el mecanismo Core de lista raíz única sin degradar las acciones nativas de `core/list-item`; no deja abierto el modelo de contenido. Ninguna de las decisiones propuestas está aprobada hasta el visto bueno final del Technical Director.

## 19. Reuse-first: APIs y evidencia

| Capacidad | Hecho verificado | Propuesta Sources |
|---|---|---|
| `InnerBlocks` / `allowedBlocks` | API Gutenberg admite restringir hijos directos; `InnerBlocks.Content` serializa hijos en save. | Un `InnerBlocks`, allowlist `core/list`; `InnerBlocks.Content` en save. |
| `core/list` | Core lo define como lista ordenada/no ordenada con `core/list-item` internos. Está registrado en WordPress 7.0.4 del entorno preparado. | Reutilizar lista no ordenada para colección y orden nativos. |
| `core/list-item` | API v3, hijo de `core/list`, estático, atributo de contenido RichText; WordPress 7.0.4 local lo registra con API v3. | Un item top-level representa una fuente; título en el contenido del item. |
| RichText / link controls | RichText admite texto con formatos; documentación del paquete identifica `core/link`; List Item Core usa contenido `rich-text`. | Aplicar Link UI Core al título; sin control duplicado. |
| Static save | `save()` serializa el markup a post content; Core explica markup guardado como output estático. | `aside` + label + InnerBlocks estáticos, sin render PHP. |
| Deactivación | Core documenta que el HTML guardado en static blocks es output guardado; el reporte local Callout confirmó contenido al desactivar. La conservación del bloque desconocido es inferencia que Sources validará. | Mantener título, href y lista dentro del markup almacenado. |
| Block Supports / theme.json | Supports se aplican mediante block props; `theme.json` gobierna opciones/presets. | Declarar solo supports útiles del wrapper, sin identidad propia. |
| Template/locking | InnerBlocks ofrece `template`, `templateLock` y bloqueo individual de bloques. El lock del template puede heredarse a descendientes y los locks individuales restringen movimiento/eliminación. | Template Core con un único `core/list`; elegir lock Core que impida múltiples raíces y preserve edición/reordenamiento de items; validarlo durante implementación. |

Referencias oficiales investigadas:

- [InnerBlocks y allowedBlocks](https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/nested-blocks-inner-blocks/)
- [Block templates y template locking](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-templates/)
- [Block locking](https://developer.wordpress.org/block-editor/how-to-guides/curating-the-editor-experience/block-locking/)
- [Core List](https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-text/core-block-list/)
- [Core List Item](https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-text/core-block-list-item/)
- [RichText Reference](https://developer.wordpress.org/block-editor/reference-guides/richtext/)
- [@wordpress/rich-text y formato core/link](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-rich-text/)
- [@wordpress/block-editor — LinkControl e InnerBlocks](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/)
- [Static or Dynamic rendering](https://developer.wordpress.org/block-editor/getting-started/fundamentals/static-dynamic-rendering/)
- [Edit and Save](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/)
- [Block Supports](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/)
- [Global Settings & Styles / theme.json](https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/)
- [Block metadata](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/)
- Local implementation/deactivation evidence: `PHASE-2-CALLOUT.md`, sections E, G, L and M; local environment check described in section 2.

## 20. Decision Log

Las decisiones `SRC001`–`SRC015` fueron aprobadas por el Technical Director para implementación en 0.4.0.

| ID | Decisión propuesta | Evidencia / razón | Alternativas | Recomendación | Estado |
|---|---|---|---|---|---|
| SRC001 | Añadir el block type `sci-editorial/sources`. | Alcance propuesto para Sources; naming sigue block namespace registrado por metadata. | No crear bloque SCI o usar contenido libre. | Un único bloque SCI Sources. | APPROVED |
| SRC002 | Target release 0.4.0. | La versión inspeccionada es 0.3.0. | Incluir en 0.3.x o aplazar. | Entregar como 0.4.0 sujeto a aprobación. | APPROVED |
| SRC003 | Fuentes se introducen manualmente en Gutenberg. | El contenido es editorial y el objetivo excluye scraping/consultas externas. | Descubrimiento automático. | Solo ingreso manual. | APPROVED |
| SRC004 | Sources es un bloque estático. | `save()` serializa el wrapper y contenido a `post_content`. | Render dinámico PHP. | Static `save()`; no PHP renderer. | APPROVED |
| SRC005 | Componer el bloque con `InnerBlocks` y Core List. | `core/list`/`core/list-item` están registrados en WP 7.0.4; Core aporta edición y serialización. | Atributos fuente propios. | InnerBlocks/Core, sin schema `{title,url}` ni `sci-editorial/source-item`. | APPROVED |
| SRC006 | Permitir como máximo un `core/list` raíz. | Contrato de composición y necesidad de mantener fuentes dentro de una lista. | Varias listas raíz. | Template/locking Core que preserve edición, borrado y orden de list-items; no UI propietaria. | APPROVED |
| SRC007 | Cada fuente es un `core/list-item` de primer nivel. | Core List Item proporciona contenido RichText y orden nativo. | Bloque hijo propio o fila de atributos. | Reutilizar item Core. | APPROVED |
| SRC008 | Los links se editan con RichText/Core link UI. | Core proporciona formato/enlace sobre el texto de List Item. | Control de URL SCI paralelo. | El título es el anchor text cuando el autor aplica link. | APPROVED |
| SRC009 | El link no es estructuralmente obligatorio. | Core permite texto RichText sin formato `core/link`; eliminarlo deja el título. | Rechazar items sin URL. | Conservar texto sin link; sin error ni autodelete. | APPROVED |
| SRC010 | Label/lista/títulos/enlaces deben sobrevivir a desactivar el plugin. | Static save conserva markup en contenido; verificación de Sources es release gate. | Aceptar desaparición del contenido. | Probar desactivación después de guardar varias fuentes. | APPROVED |
| SRC011 | No implementar JavaScript frontend. | Markup estático y enlaces estándar no requieren JS cliente. | Interacción frontend personalizada. | Cero JS frontend. | APPROVED |
| SRC012 | No recuperar metadata remota ni scraping. | No existe requerimiento; evitar requests y parsing remoto. | HTTP/OpenGraph/enrichment. | Sin requests externos ni validación remota. | APPROVED |
| SRC013 | Gutenberg/Core controlan la presentación y las acciones de edición. | Core List, link UI, templates/locking y Global Styles ofrecen capacidades existentes. | UI de repetidor/lista SCI. | Reutilizar controles y estilos Core; sin duplicar UI. | APPROVED |
| SRC014 | No forzar `target`, `nofollow` ni `sponsored`. | Los enlaces son editoriales; la UI Core deja la decisión al autor. | Atributos forzados. | Respetar atributos que serialice Core. | APPROVED |
| SRC015 | Las listas anidadas siguen el comportamiento Gutenberg Core. | `core/list-item` admite composición nativa; bloquearla añadiría lógica propietaria. | Bloquear anidamiento con SourceItem/custom UI. | No bloquear ni transformar; lista editorial plana es recomendación, no validación. | APPROVED |

## 21. Definition of Done del SDD

El contrato propuesto cierra identidad `sci-editorial/sources`, target 0.4.0, modelo InnerBlocks/Core, una lista raíz, relación list/list-item, static save, supervivencia, links, seguridad, supports, vacíos, performance, SEO y matriz de pruebas. Las 15 decisiones SRC001–SRC015 están aprobadas; las validaciones pendientes son pruebas de compatibilidad Core en implementación y no reabren el modelo de contenido.

## 22. Recomendación

**SOURCES SDD APPROVED FOR IMPLEMENTATION — 0.4.0.** El modelo está cerrado en InnerBlocks/Core con una única lista raíz estática; cada fuente es un `core/list-item`, el link es opcional y el contenido editorial debe sobrevivir a la desactivación. Las 15 decisiones SRC001–SRC015 están aprobadas. La evidencia local WP 7.0.4 confirma que `core/list` y `core/list-item` están disponibles como API v3. No se implementó código.
