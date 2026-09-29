# SCI Editorial Blocks — Feature SDD: Reading Time

**Feature:** SCI — Tiempo de lectura  
**Block name:** `sci-editorial/reading-time`  
**Release objetivo:** SCI Editorial Blocks 0.2.0  
**Estado:** APPROVED FOR IMPLEMENTATION — 0.2.0
**Product Owner:** Martín García  
**Technical Director:** ChatGPT  
**Developer / Technical Writer:** Codex  
**Versión inspeccionada del plugin:** 0.1.5

Este SDD es un addendum de arquitectura para SCI Editorial Blocks 0.2.0. Amplía exclusivamente el alcance del SDD base para añadir `sci-editorial/reading-time`.

La restricción del SDD original de “exactamente dos bloques en v0.1” continúa siendo válida históricamente para la serie 0.1.

Todos los demás principios, contratos de seguridad, Gutenberg-native, compatibilidad, dependencias, Global Styles y autoridad de implementación del SDD base continúan vigentes.

Este addendum no autoriza por sí mismo el inicio de implementación. Las decisiones de la tabla final se presentan para aprobación formal. Los hechos y recomendaciones técnicas se distinguen expresamente.

## 1. Propósito

Añadir un bloque Gutenberg que muestre el tiempo de lectura estimado del post actual, por ejemplo “7 min de lectura”. El uso principal es colocar una sola instancia en la plantilla Single para que funcione en artículos existentes y futuros sin editar cada post.

## 2. Gobernanza y precedencia

`SDD.md` sigue siendo el contrato base. Este addendum extiende únicamente el alcance de la versión 0.2.0 mediante el nuevo block type Reading Time. El alcance histórico de v0.1 permanece limitado a TOC y Callout; no hay contradicción entre esas versiones.

## 3. Fuentes locales revisadas

Se leyeron `SDD.md`, `README.md`, `CHANGELOG.md`, `PHASE-1-FOUNDATION.md`, `PHASE-2-CALLOUT.md`, `PHASE-3-TOC-CORE.md`, `PHASE-4-ANCHORS.md`, `PHASE-5-RELEASE.md` y los informes de patches `PHASE-5A-0.1.1.md`, `PHASE-5B-0.1.2.md`, `PHASE-PATCH-ICON-0.1.3.md`, `PHASE-PATCH-TOC-CONTROLS-0.1.4.md` y `PHASE-HOTFIX-TOC-EDITOR-0.1.5.md`.

**Hecho local verificado:** la cabecera de `sci-editorial-blocks.php` y `package.json` indican versión `0.1.5`; README y changelog también reflejan el hotfix 0.1.5. El plugin usa `block.json`, registro server-side, PHP namespace `SCI\\EditorialBlocks`, `@wordpress/scripts` como tooling de desarrollo y no declara dependencias runtime externas. Los informes describen TOC dinámico, Callout estático con InnerBlocks, un pattern Summary basado en bloques Core y cero frontend JavaScript.

**Hecho local verificado:** `includes/toc.php` usa `parse_blocks()` para recorrer recursivamente bloques anidados. Phase 3 verificó que `WP_Block_Processor` no ofrece de forma conveniente el árbol recursivo `innerBlocks` necesario para su recorrido. Reading Time también requiere una allowlist textual sobre `innerBlocks`; para un único `post_content`, el árbol de Core es la opción de menor complejidad.

## 4. Identidad propuesta

| Propiedad | Propuesta |
|---|---|
| Nombre visible | SCI — Tiempo de lectura |
| Block name | `sci-editorial/reading-time` |
| Block API | v3 |
| Metadata | `block.json` |
| Registro | Server-side desde metadata |
| Render | Dinámico en PHP |
| Text domain | `sci-editorial-blocks` |
| Contexto consumido | `postId`, `postType` |

## 5. Contrato funcional

Calcular el tiempo usando únicamente el texto editorial extraído del `post_content` del post identificado por el contexto Core. La fórmula propuesta es `ceil(total_words / 220)`.

No mostrar resultado cuando no haya palabras relevantes. Con 1–220 palabras mostrar 1 minuto; con 221–440, 2 minutos; y así sucesivamente. Nunca se mostrará “0 min de lectura”. La velocidad inicial es fija: no habrá preferencia por sitio ni por usuario.

Todas las strings serán traducibles con text domain `sci-editorial-blocks`; el formato traducible deberá aceptar el entero como argumento para respetar el orden gramatical de otros idiomas. La redacción en español por defecto es “%d min de lectura”.

## 6. Fuente de contenido

**Decisión propuesta:** usar `$block->context['postId']` y obtener el `WP_Post` correspondiente con Core (`get_post()` si el renderer requiere un objeto) para leer `post_content` sin filtros de página.

El bloque no calculará desde `the_content`, HTML final, DOM, URL, HTTP, excerpt ni contenido completo del template. Así no incorpora TOC, Summary, widgets, footer, comentarios ni bloques que solo están en la plantilla Single. Una instancia insertada en una plantilla Single contará el contenido del post cuyo `postId` entrega el contexto Core.

**Contexto ausente o inválido:** retornar cadena vacía antes de recorrer o contar. No producir warnings ni output de placeholder en frontend.

**Query Loop:** no crear soporte especial. Si el contexto Core entrega un `postId` válido, calcular el post de esa instancia. Casos de Query Loop multi-post que no funcionen naturalmente con ese contexto quedan fuera de la primera implementación y deben documentarse en QA.

## 7. Extracción estructurada y allowlist

**Decisión aprobada para este addendum:** obtener el `post_content`, recorrer `parse_blocks()` recursivamente y recolectar texto editorial de una allowlist cerrada. No usar regex para interpretar estructura Gutenberg.

Algoritmo de contenido:

```text
get post_content
→ parse_blocks()
→ recursive traversal of innerBlocks
→ collect editorial text from allowlist
→ normalize
→ Unicode word count
→ ceil(words / 220)
```

Motivo: la feature procesa el contenido de un solo post y necesita recorrer `innerBlocks`. Phase 3 ya documenta la limitación práctica de `WP_Block_Processor` para obtener una estructura recursiva cómoda. El costo de materializar un único documento se considera proporcionado; no se justifica añadir lógica streaming propia.

### 7.1 Bloques cuyo texto cuenta

En 0.2.0 solo se contará texto editorial de:

- `core/paragraph`;
- `core/heading`;
- `core/list`;
- `core/quote`;
- contenido textual permitido dentro de `sci-editorial/callout`.

`core/list-item` puede aparecer como nodo estructural interno de `core/list`; su texto se cuenta como parte de la lista, no como una excepción independiente a la allowlist. Para `sci-editorial/callout`, recorrer sus InnerBlocks permitidos (`core/paragraph` y `core/list`) y no contar su etiqueta de variante.

Recorrer `innerBlocks` recursivamente para alcanzar bloques permitidos bajo contenedores. Extraer fragmentos textuales de markup interno ya parseado sin duplicar texto entre wrapper y hijos.

### 7.2 Bloques y contenido excluidos

No contar en 0.2.0:

- captions de `core/image`;
- buttons, forms, embeds y `core/html`/custom HTML;
- code blocks;
- bloques dinámicos de template;
- `sci-editorial/toc`;
- Post Excerpt situado en template;
- navigation, related content, footer, sidebar ni UI del template;
- atributos, comentarios de bloques, markup HTML o contenido de bloques desconocidos.

No intentar reconocer todos los tipos posibles. Los nodos desconocidos/dinámicos no aportan texto. No ejecutar `render_block()`, `do_blocks()`, shortcodes ni callbacks; no hacer requests HTTP.

### 7.3 Extracción de texto

Usar `innerHTML`/`innerContent` del bloque ya parseado y APIs Core apropiadas. `wp_strip_all_tags()` puede convertir markup inline a texto; solo se aplicará a HTML de tipos permitidos. La implementación debe recorrer fragmentos directos sin volver a contar el texto de `innerBlocks`. En Callout se omite el HTML directo del wrapper/label y se visitan sus hijos permitidos.

## 8. Conteo de palabras y Unicode

**Hecho verificado local:** en la instalación Core WordPress 7.1.2 inspeccionada no aparece una función `wp_count_words()` en `wp-admin` ni `wp-includes`; tampoco se localizó una API documentada de frontend para conteo Unicode. `str_word_count()` no debe usarse porque su conjunto de caracteres por defecto no representa adecuadamente palabras españolas/Unicode.

**Decisión propuesta cerrada:** usar tokenización PCRE con propiedades Unicode y flag `/u`; no usar `str_word_count()`. Un patrón local puede reconocer letras, marcas combinantes y números, con guion/apóstrofo internos. Como referencia de implementación:

```regex
~[\p{L}\p{N}][\p{L}\p{M}\p{N}]*(?:['’\-][\p{L}\p{N}][\p{L}\p{M}\p{N}]*)*~u
```

Cada coincidencia es un token; puntuación sola no cuenta. Letras Unicode, incluyendo tildes y ñ, cuentan correctamente. Los tokens técnicos y numéricos razonables cuentan como una palabra. Los enlaces aportan su texto visible, no el valor de `href`.

Fixtures mínimos aprobados:

| Texto | Conteo |
|---|---:|
| `ZFS e iSCSI en Perú` | 5 |
| `SPF, DKIM y DMARC` | 4 |
| `virtualización` | 1 |

La implementación debe comprobar el resultado de PCRE y tratar UTF-8 inválido como cero, sin warnings/fatals.

## 9. Algoritmo

1. Resolver `postId` del contexto y confirmar un post válido.
2. Leer únicamente el `post_content` fuente.
3. Ejecutar `parse_blocks()` una vez y recorrer el resultado recursivamente.
4. Recolectar solo los tipos permitidos en la sección 7, sin ejecutar bloques.
5. Normalizar/limpiar el texto extraído y contar tokens Unicode con la regla de la sección 8.
6. Si `words === 0`, devolver output vacío.
7. Calcular `ceil(words / 220)` y emitir la string traducible con escape contextual.

La operación será pura respecto al post: no escribir meta ni cambiar `post_content`.

## 10. Render y markup

Render server-side con un wrapper semántico mínimo, preferentemente `<span>` o `<p>` según la validación de contexto/flujo de contenido; clase raíz `wp-block-sci-editorial-reading-time` y atributos de wrapper Core.

Ejemplo conceptual:

```html
<span class="wp-block-sci-editorial-reading-time">7 min de lectura</span>
```

El texto debe estar presente en HTML server-side. Escapar la string traducida con la API contextual Core (`esc_html()`); usar `get_block_wrapper_attributes()` si es compatible con el wrapper escogido. No emitir markup cuando el conteo es cero o no hay contexto válido.

## 11. Experiencia de edición

El bloque debe seguir el inserter y editor Gutenberg nativos. Preferir mostrar el mismo cálculo en preview del editor solo si Core entrega contenido/contexto ya disponible sin endpoint, API REST personalizada ni duplicar el algoritmo en JavaScript.

La recomendación inicial es placeholder Gutenberg sencillo —“El tiempo de lectura se calcula automáticamente al mostrar la entrada”— si mostrar el valor requeriría lógica cliente adicional. No construir controles ni panel de configuración; WPM es fijo. `save` no serializará el resultado.

## 12. Presentación y Block Supports

Aplicar “PLUGIN CONTROLS STRUCTURE. GUTENBERG CONTROLS PRESENTATION.” El bloque no impondrá font family, tamaños, peso, color, fondo, alineación ni spacing.

**Recomendación:** comenzar sin CSS de presentación y evaluar supports Core selectivos: texto/color y background si semánticamente útil; typography (solo controles que no cambien el rol del indicador); margin/padding si el producto necesita separarlo como unidad en template. No activar supports por defecto sin validar la experiencia en WordPress 7.0.4 y theme.json.

**Hecho Core verificado:** Block Supports registra atributos/UI de Core y los añade al wrapper con `useBlockProps()` en editor o `get_block_wrapper_attributes()` en renderer PHP para bloques dinámicos. `theme.json`/Global Styles puede gobernar presets y settings habilitados por tema. El bloque debe heredar del tema cuando no hay valor explícito.

## 13. Performance y caché

- Cero frontend JavaScript.
- Cero llamadas HTTP o APIs externas.
- Cero queries SQL creadas para contar palabras; usar el post/contexto de la renderización actual.
- Una pasada por el contenido fuente.
- Sin persistencia, opciones, transients ni post meta.
- Se permite memoización estática/request-local por `post_id` si se confirma que el cálculo se repite en varias instancias del bloque; la clave y el lifetime son solo el request PHP actual.
- El cálculo solo se ejecuta al renderizar la instancia Reading Time.

## 14. Seguridad

- No endpoints, AJAX, opciones, cron, base propia ni código ejecutable configurable.
- No renderizar callbacks o shortcodes del contenido para contar palabras.
- Limitar la extracción a spans de texto de bloques reconocidos; limpiar tags antes del conteo.
- Escapar el output como texto HTML.
- Fallar cerrado a salida vacía ante contexto ausente, `post_content` no textual, error de extracción o UTF-8 inválido; sin warning/fatal.

## 15. SEO y metadatos

Feature informativa de UX. No modificar headings, schema, canonical, robots, metadata SEO ni contenido editorial. No afirmar que Reading Time sea un factor directo de ranking.

## 16. Degradación

Al desactivar/desinstalar el plugin, el bloque dinámico puede dejar de mostrar el indicador. No hay pérdida de contenido editorial y no se guardará una copia estática del resultado únicamente para degradación.

## 17. Non-goals

WPM configurable, velocidad por usuario/idioma, tiempo de escucha, TTS, audio, barra/progreso, porcentaje leído, tracking, analytics, scroll JavaScript, IA, post meta, settings de administración, REST/AJAX, consulta HTTP del propio post y procesamiento de contenido de template.

## 18. Pruebas mínimas

| Grupo | Casos |
|---|---|
| Umbrales | Vacío, 1, 100, 220, 221, 440, 441 y 1,500+ palabras; resultado cero nunca visible. |
| Texto/Unicode | Español con tildes y ñ, marcas combinantes, Unicode, puntuación y términos técnicos (`ZFS`, `iSCSI`, `DKIM`, `DMARC`, `NVMe`, `WordPress`). |
| Bloques | Paragraph, heading, list/list-item, quote, Callout; enlaces; inline formatting; grupos anidados; bloques desconocidos/dinámicos excluidos. |
| Fuente/contexto | Contenido del post solamente; sin contexto, id inválido, post vacío; template con TOC y Summary no duplica texto ni cuenta excerpt/template. |
| Editor/storage | Inserción en Single template, placeholder/cálculo según contrato, save/reload sin resultado serializado ni invalid block. |
| Presentación | Supports heredados de theme.json; Twenty Twenty-Five y Twenty Twenty-Four; reset sin estilo propietario. |
| Rendimiento | Sin render dinámico de bloques, queries adicionales evitables ni cálculo si la instancia no está presente. |
| Regresión | TOC/AnchorPlan/anchors y Callout save/reload, Summary excerpt; frontend sin JS; `post_content` inmutable. |

## 19. Definition of Done propuesto

La implementación quedará lista cuando el bloque dinámico API v3 se registre desde `block.json`; use post context Core válido; calcule exactamente `ceil(words/220)` con WPM fijo; cuente solo texto de bloques acordados mediante extracción estructurada sin ejecutar bloques; sea Unicode-safe y nunca muestre cero; no persista ni mutile contenido; no añada frontend JS, dependencias runtime, endpoints o queries evitables; herede la presentación de Core/tema; traduzca y escape output; pase pruebas de umbrales, estructura, editor, frontend, temas y regresión.

## 20. Validaciones de implementación pendientes

No queda una decisión arquitectónica pendiente para que el Technical Director revise el mini SDD. Durante implementación se deberán verificar:

1. Extracción sin duplicación desde `innerHTML`/`innerContent` en listas, quotes y Callout anidados.
2. Fixtures de los umbrales 0/1/220/221/440/441 y los tres fixtures Unicode acordados.
3. Resolución de `postId` en Single template y comportamiento natural del contexto en Query Loop.
4. Placeholder Gutenberg, save/reload y supports del tema en WordPress >= 7.0.
5. Ausencia de llamadas SQL evitables en el render con el post ya disponible/cached.

## 21. Investigación Core / Reuse first

| Capacidad | Hecho verificado | Recomendación / límite |
|---|---|---|
| Word count | En Core 7.1.2 inspeccionado no se encontró `wp_count_words()` ni una API frontend pública con semántica Unicode apta. | Contar localmente tokens Unicode con PCRE `/u`; validar fixtures. |
| Árbol de bloques | `parse_blocks()` retorna `blockName`, `attrs`, `innerBlocks`, `innerHTML` e `innerContent`; procesa el documento completo y puede consumir más memoria que el processor streaming. | **Decisión:** usarlo para el post único y la recursión simple de la allowlist. |
| Parseo streaming | `WP_Block_Processor` analiza tokens y spans sin construir el árbol completo; la documentación local Phase 3 registra que no ofrece cómodamente el `innerBlocks` recursivo necesario. | No usar para esta feature: el ahorro no justifica complejidad adicional. |
| Texto seguro | `wp_strip_all_tags()` elimina tags y contenido de `script`/`style`; no ejecuta bloques. | Aplicarlo únicamente a HTML interno parseado de bloques permitidos. |
| Contexto | `usesContext` permite heredar `postId`/`postType` y el renderer PHP accede al contexto del bloque; `get_post()` resuelve un ID/objeto a `WP_Post` o `null`. | Usar el `postId` heredado explícitamente; salida vacía si falta/no resuelve. |
| Render dinámico | El renderer PHP produce markup en cada render; bloques dinámicos pueden guardar un fallback, pero esta feature no debe serializar el tiempo. | `render: file:./render.php` y bloque editor con preview simple/placeholder. |
| Supports y wrapper | `block.json` declara supports; Core los aplica a `useBlockProps()` y `get_block_wrapper_attributes()`. | Dejar tipografía/color/spacing bajo control Core, de forma selectiva. |
| Ejecución de bloques | `render_block()`/`do_blocks()` renderizan contenido y callbacks; `parse_blocks()` analiza la serialización sin requerir ejecutar bloques. | No llamar renderers; contar solo los tipos de la allowlist. |

Referencias oficiales:

- [WP_Block_Processor](https://developer.wordpress.org/reference/classes/wp_block_processor/)
- [parse_blocks()](https://developer.wordpress.org/reference/functions/parse_blocks/)
- [wp_strip_all_tags()](https://developer.wordpress.org/reference/functions/wp_strip_all_tags/)
- [Block Context](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-context/)
- [get_post()](https://developer.wordpress.org/reference/functions/get_post/)
- [Dynamic block rendering](https://developer.wordpress.org/block-editor/getting-started/fundamentals/static-dynamic-rendering/)
- [Block Supports](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/)
- [Style Engine con Block Supports](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-style-engine/using-the-style-engine-with-block-supports/)
- [render_block()](https://developer.wordpress.org/reference/functions/render_block/)

## 22. Decision Log

Las decisiones de esta tabla están aprobadas por el Technical Director para la implementación 0.2.0.

| ID | Decisión propuesta | Evidencia / razón | Estado |
|---|---|---|---|
| RT001 | Nuevo bloque Gutenberg dinámico `sci-editorial/reading-time`. | La salida deriva del post actual y se sirve desde PHP. | APPROVED |
| RT002 | Reading Time pertenece a SCI Editorial Blocks 0.2.0. | Añade un block type fuera del alcance histórico 0.1.x. | APPROVED |
| RT003 | Velocidad fija 220 WPM y fórmula `ceil(words/220)`. | Cálculo determinista sin preferencias/configuración. | APPROVED |
| RT004 | Fuente exclusiva: `post_content` del post actual. | Excluye template, UI, TOC, excerpt y contenido periférico. | APPROVED |
| RT005 | Recorrido recursivo mediante `parse_blocks()`. | Core entrega `innerBlocks` estructurados y Phase 3 registra su conveniencia para este caso. | APPROVED |
| RT006 | Allowlist cerrada: paragraph, heading, list, quote y cuerpo textual permitido de Callout. | Mantiene el scope simple y evita reconocer bloques arbitrarios. | APPROVED |
| RT007 | Tokenización PCRE Unicode-safe con `/u`; prohibido `str_word_count()`. | Letras Unicode y tokens técnicos cuentan según fixtures documentados. | APPROVED |
| RT008 | Sin frontend JavaScript. | El valor se genera server-side. | APPROVED |
| RT009 | Sin persistencia, post meta ni cache persistente. | Reading Time es un dato derivado del contenido fuente. | APPROVED |
| RT010 | Sin configuración global; WPM fijo. | No hay caso de producto aprobado para settings. | APPROVED |
| RT011 | Gutenberg y Global Styles controlan presentación. | Conserva theme-native y Block Supports selectivos. | APPROVED |
| RT012 | Al desactivar el plugin puede desaparecer el indicador sin pérdida editorial. | El bloque no almacena contenido ni resultado. | APPROVED |

## 23. Recomendación

**READING TIME SDD APPROVED FOR IMPLEMENTATION — 0.2.0**. El alcance está fijado como addendum para 0.2.0; la limitación histórica de dos bloques permanece vigente para 0.1.x. El contrato de parsing usa `parse_blocks()` con recorrido recursivo y sin regex de estructura Gutenberg; la allowlist está cerrada; el conteo Unicode usa PCRE `/u` y fixtures explícitos.
