# SCI Editorial Blocks — Software Design Document

**Versión:** 0.1.0  
**Estado:** APPROVED FOR IMPLEMENTATION  
**Product Owner:** Martín García  
**Technical Director:** ChatGPT  
**Implementation Developer:** Codex  
**Plugin slug:** `sci-editorial-blocks`  
**Text domain:** `sci-editorial-blocks`

Este documento es el contrato técnico de SCI Editorial Blocks v0.1. Las fases de implementación deben respetar sus contratos, compatibilidad, seguridad, dependencias y límites. Toda fase termina con revisión del Technical Director; ninguna fase comienza automáticamente.

## 1. Propósito e identidad

SCI Editorial Blocks es un plugin reutilizable para WordPress que añade componentes editoriales nativos de Gutenberg para artículos técnicos, publicaciones editoriales y contenido largo. Debe sentirse como una extensión del Block Editor, no como un page builder independiente.

La versión 0.1 contiene exactamente dos bloques: `sci-editorial/toc` y `sci-editorial/callout`.

## 2. Principios

En orden de prioridad:

1. Reuse first.
2. Gutenberg-native first.
3. WordPress-native first.
4. Security by default.
5. Simplicity.
6. Maintainability.
7. Content portability.
8. Minimal dependencies.
9. Progressive enhancement.
10. No speculative architecture.

Si Core o Gutenberg tienen una API estable y suficiente, debe preferirse a una alternativa propia. No crear infraestructura para necesidades hipotéticas.

## 3. Compatibilidad

- WordPress **7.0 o superior**.
- PHP **8.2 o superior**.
- Block Editor/Gutenberg y block themes.
- Twenty Twenty-Five es el entorno inicial de referencia y QA, no una dependencia del plugin.
- Usar Block API `apiVersion: 3` en ambos bloques.

## 4. Alcance v0.1

1. **SCI — En este artículo**, block name `sci-editorial/toc`.
2. **SCI — Callout**, block name `sci-editorial/callout`.

No se añaden otros bloques o block types para las variantes de Callout.

## 5. Non-goals

Fuera de v0.1: IA, analytics, telemetría, REST propia, AJAX, tablas propias, CPT, cron, transients como infraestructura, shortcodes, page builders, sliders, acordeones, pricing tables, sistema externo de iconos, bloques Related/Sources/Summary, H3/H4 en TOC, scroll spy, sticky automático, JavaScript frontend, configuración global compleja, dashboard e integraciones comerciales.

## 6. Dependencias y distribución

### Runtime

Ninguna dependencia externa. Permitidos: WordPress Core, APIs Gutenberg disponibles con la versión mínima y PHP estándar. No Composer sin aprobación futura explícita. No frameworks CSS o librerías UI externas.

### Desarrollo/build

- Usar `@wordpress/scripts` exclusivamente como dependencia de desarrollo.
- `@wordpress/*` se externaliza durante el build y se obtiene desde WordPress en runtime.
- No crear configuración Webpack/Vite propia mientras `@wordpress/scripts` cubra el caso.
- Versionar `package-lock.json`, fuentes, configuración, documentación y tests; excluir `node_modules`, logs, temporales y metadata de IDE.

## 7. Identificadores y estructura

- Namespace PHP: `SCI\EditorialBlocks`.
- Prefijo de funciones globales solo si fueran inevitables: `sci_editorial_blocks_`.
- Block namespace: `sci-editorial`.
- Text domain: `sci-editorial-blocks`.
- `block.json` es la fuente canónica de metadata.
- Registrar los bloques server-side desde sus directorios `block.json`.

Estructura objetivo, simplificable para evitar archivos vacíos:

```text
sci-editorial-blocks/
├── sci-editorial-blocks.php
├── package.json
├── package-lock.json
├── README.md
├── SDD.md
├── .gitignore
├── src/
│   ├── toc/
│   │   ├── block.json
│   │   ├── index.js
│   │   ├── edit.js
│   │   └── style.scss
│   └── callout/
│       ├── block.json
│       ├── index.js
│       ├── edit.js
│       ├── save.js
│       └── style.scss
├── includes/
├── build/
└── tests/
```

No crear archivos o directorios vacíos sin uso. No crear service container, DI, repository pattern, factories, clases base abstractas o autoloader propio.

## 8. Registro y APIs de bloques

Ambos bloques usan `apiVersion: 3`, metadata en `block.json`, registro PHP desde metadata y APIs oficiales Gutenberg. Usar `useBlockProps`, `InnerBlocks`, `InspectorControls`, `SelectControl` y Block Supports donde correspondan. No crear interfaces paralelas al editor.

Referencias oficiales de diseño:

- [Block metadata y `block.json`](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/)
- [Registro server-side desde metadata](https://developer.wordpress.org/reference/functions/register_block_type_from_metadata/)
- [WP_Block_Processor](https://developer.wordpress.org/reference/classes/wp_block_processor/)
- [WP_HTML_Tag_Processor](https://developer.wordpress.org/reference/classes/wp_html_tag_processor/)
- [`core/heading`](https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-text/core-block-heading/)
- [InnerBlocks](https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/nested-blocks-inner-blocks/)
- [Block Supports](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/)
- [theme.json / Global Styles](https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/)

`WP_Block_Processor` es la preferencia para escanear estructura de bloques de forma streaming. Solo se permite `parse_blocks()` si implementación demuestra que `WP_Block_Processor` no resuelve limpiamente el recorrido H2 anidado necesario; documentar esa evidencia en la fase correspondiente. Nunca interpretar contenido mediante regex.

`WP_HTML_Tag_Processor` es la API preferida para añadir `id` a la etiqueta de heading renderizada. No usar regex para leer o modificar HTML.

## 9. SCI — En este artículo (TOC)

### Contrato funcional

El bloque genera automáticamente una navegación interna del contenido de la entrada actual. El autor no introduce el índice manualmente. Solo se incluyen headings H2 representados por `core/heading`:

| Nivel | Tratamiento |
|---|---|
| H1 | Ignorar |
| H2 | Incluir |
| H3–H6 | Ignorar |

Soportar H2 en estructuras Gutenberg anidadas cuando Core lo permita. Si no hay H2, no emitir markup visible. Con uno o más H2, renderizar el índice normalmente.

### Render

- Renderizado dinámico server-side a partir del contenido actual.
- No persistir una copia del índice ni estructura derivada en `post_content` o base de datos.
- Usar `<nav aria-label="En este artículo">` con enlaces estándar.
- No insertar un H2 adicional como título del TOC. Si hay rótulo visible, usar un elemento apropiado como `p` o `div`.
- La edición Gutenberg muestra un placeholder sencillo, por ejemplo: “En este artículo / El índice se genera automáticamente con los encabezados H2 de la entrada.” No construir un editor manual ni sincronización compleja en tiempo real.

### Descubrimiento y costo

Preferir `WP_Block_Processor` para detectar/recorrer `core/heading` en el árbol serializado; descender por headings anidados y leer atributos estructurados, no parsear HTML con regex. La documentación Core describe esta clase como escaneo streaming que evita materializar todo el árbol; `parse_blocks()` es alternativa solo bajo la excepción documentada arriba.

La lógica solo se ejecuta en respuesta a una instancia real del bloque `sci-editorial/toc`, no para todos los posts indiscriminadamente. No añadir queries innecesarias ni caché persistente. Se permite memoización/request cache local si se requiere compartir el plan de anchors dentro de la petición.

### Política de anchors

**Anchor manual:** cuando `core/heading` ya tiene anchor, preservarlo exactamente y usarlo como `href` del TOC. No reescribirlo ni normalizarlo. Tiene prioridad absoluta.

**Anchor automático:** si H2 carece de anchor, generar ID únicamente durante render. Formato base `sci-toc-{slug}`. Ejemplo: “Quién debe administrar la conexión” → `sci-toc-quien-debe-administrar-la-conexion`. La generación es determinista.

**Colisiones:** reservar de antemano los anchors manuales existentes al asignar IDs automáticos. Para headings automáticos repetidos, usar sufijos `-2`, `-3`, etc. Si `sci-toc-configuracion` ya está reservado por un anchor manual, el primer automático equivalente recibe `sci-toc-configuracion-2`. No reasignar ni corregir anchors manuales duplicados; diagnósticos/warnings quedan fuera de v0.1.

**AnchorPlan:** TOC y headings deben consumir una misma representación request-local equivalente a:

```text
AnchorPlan[
  { label, id, source: manual|generated }
]
```

Puede ser array/estructura simple; no se exige una clase con ese nombre. Se construye de forma lazy desde el contenido fuente del post y se reutiliza durante el request. Debe ser determinista, vivir solo durante el request y no persistirse. El mismo post/contexto debe producir el mismo AnchorPlan independientemente del orden de render entre `sci-editorial/toc`, `core/post-content` y `core/heading`. No introducir persistencia para resolver el orden. El ID usado en cada enlace debe ser exactamente el ID añadido al heading correspondiente.

Los IDs generados se inyectan solo en el HTML de render. Preferir `WP_HTML_Tag_Processor` para modificar el atributo de la etiqueta, y un filtro específico de `core/heading` como `render_block_core/heading` si es compatible. La implementación local puede elegir hook/orden/estado request-local mientras respete este contrato, no mutile `post_content`, no cambie anchors manuales, comparte AnchorPlan y no use regex ni estado persistente. Cualquier filtro de `core/heading` debe hacer early-return cuando el contexto actual no requiera SCI TOC.

### Contexto del post

`sci-editorial/toc` requiere un contexto de post resoluble. Si no existe contenido/post context válido, no renderizar markup visible ni intentar generar AnchorPlan. El soporte específico para múltiples bloques TOC correspondientes a distintos posts dentro de Query Loop queda fuera de v0.1.

Los slugs deben normalizarse de manera determinista a partir del texto de heading y escaparse contextualmente al emitir atributos/enlaces. No usar `wp_unique_id()` como sustituto del plan, porque el href y el ID del heading deben concordar de forma determinista.

## 10. Degradación del TOC

Al desactivar el plugin:

- contenido de la entrada y headings originales permanecen;
- no se pierde contenido editorial original;
- el TOC puede desaparecer;
- anchors automáticos SCI pueden desaparecer.

Este comportamiento está aprobado. Si el editor requiere anchor permanente, debe usar el soporte nativo de anchor de Gutenberg. No persistir anchors ni markup derivado como mecanismo de degradación.

## 11. SCI — Callout

### Identidad y variantes

Un único block type `sci-editorial/callout`, visible como **SCI — Callout**. El atributo `variant` tiene allowlist cerrada:

| Valor | Etiqueta traducible |
|---|---|
| `context` | En contexto |
| `key` | Clave |
| `practice` | En la práctica |
| `decision` | Para decidir |
| `warning` | Advertencia |

Ante valor inválido, fallback seguro a `context`. La etiqueta se deriva de la variante, es traducible y no se puede editar manualmente por instancia en v0.1.

### Edición y contenido

- Selector de variante con componentes Gutenberg oficiales, preferentemente `InspectorControls` + `SelectControl`.
- Cuerpo mediante `InnerBlocks`.
- `allowedBlocks` exactamente: `core/paragraph`, `core/list`.
- Se admiten párrafos, listas, enlaces dentro de párrafos y formatos inline Core.
- No admitir headings, image, video, cover, columns, buttons, query loop, custom HTML, otro Callout o TOC dentro del Callout.
- No convertir Callout en mini page builder.

### Serialización y degradación

El Callout se guarda como markup estático usando mecanismos Gutenberg estándar y `InnerBlocks` APIs Core. El markup serializado debe incluir el wrapper semántico, la etiqueta textual correspondiente a la variante y los `InnerBlocks`. Al desactivar el plugin sobreviven tanto la etiqueta editorial (“En contexto”, “Clave”, etc.) como el contenido. No depender de render PHP o JavaScript para que la etiqueta básica o el cuerpo existan en el frontend; puede perderse parte del estilo visual.

## 12. Block Supports y Global Styles

Exponer solo supports útiles, evaluando spacing, color y typography por bloque. No activar controles indiscriminadamente ni imponer opciones que el `theme.json` activo haya deshabilitado.

El plugin hereda tipografía del tema, respeta colores y spacing/presets, y funciona con Global Styles. No modifica `theme.json`, no instala paleta/familia tipográfica propia y no depende de variables exclusivas de TT5.

CSS propio limitado a estructura y diferenciación visual mínima de variantes: layout, bordes, indicadores y spacing estructural. Evitar resets, `font-family` fija, tamaño global rígido, paletas rígidas, selectores globales, alta especificidad e `!important` salvo necesidad demostrada.

## 13. JavaScript frontend

No requerir ni implementar JavaScript frontend en v0.1. Sin scroll spy, smooth-scroll propio, animaciones, tracking o navegación dinámica. Los anchors usan navegación estándar del navegador.

## 14. Seguridad

- Allowlist cerrada de variantes y fallback inválido a `context`.
- Escaping contextual con `esc_html`, `esc_attr`, `esc_url` cuando corresponda.
- Renderizar contenido `InnerBlocks` por mecanismos Core; no concatenar HTML editorial como input confiable.
- No ejecutar PHP configurable por usuarios ni código de usuario.
- No endpoints, AJAX, DB propia, options propias o mutación de `post_content` durante render.
- Usar `supports.html = false` en wrappers SCI cuando sea técnicamente apropiado.
- No añadir dependencias runtime externas ni telemetría.

## 15. Accesibilidad e internacionalización

**TOC:** navegación semántica con nombre accesible, enlaces estándar y operable completamente por teclado.  
**Callout:** etiqueta textual obligatoria; no comunicar variante solo mediante color; respetar contraste razonable del tema.  
**I18n:** todas las strings son traducibles usando APIs de traducción de WordPress en PHP y JS con text domain `sci-editorial-blocks`.

## 16. Storage e infraestructura

En v0.1: cero tablas propias, CPT, cron, REST routes propias, handlers admin-ajax, opciones globales necesarias, transients necesarios o telemetría. No persistir AnchorPlan ni índice TOC.

## 17. Pruebas obligatorias

### Plugin

- Activación sin fatal, warning o notice; desactivación limpia.
- Compatibilidad WordPress 7.0+ / PHP 8.2+.
- Registro de ambos bloques y aparición en el inserter.
- Sin dependencias runtime externas.

### TOC

Casos: cero H2; un H2; múltiples H2; H2 en Group; headings anidados; formato inline; headings duplicados; Unicode/acentos/caracteres especiales; anchor manual; colisión manual/automático; anchors manuales duplicados; post sin TOC; TOC insertado desde template.

Verificar además que H1/H3-H6 se ignoran, salida semántica, contexto no resoluble (sin markup y sin AnchorPlan), AnchorPlan compartido e independiente del orden de render TOC/post-content/heading, early-return del filtro heading cuando el TOC no aplica, href/id idénticos, `post_content` sin cambios, sin regex, sin render/scan cuando el TOC no está presente y sin fallback del TOC requerido tras desactivación. El soporte de múltiples TOC de distintos posts en Query Loop no forma parte de v0.1.

### Callout

Probar las cinco variantes con párrafo, varios párrafos, lista y links; verificar bloque no permitido, variant inválido, strings traducibles y supervivencia al desactivar el plugin tanto de la etiqueta textual como de InnerBlocks en el wrapper semántico serializado.

### Temas

Twenty Twenty-Five y al menos otro block theme Core disponible. Confirmar que Global Styles/theme.json del tema gobiernan identidad visual y que el plugin no depende de TT5.

## 18. Definition of Done v0.1

La versión está completa cuando ambos bloques usan block.json y aparecen en Gutenberg; Callout usa InnerBlocks restringidos, soporta las cinco variantes y conserva etiqueta y contenido al desactivar el plugin; TOC detecta solo H2, respeta anchors manuales, genera anchors automáticos deterministas y únicos usando un plan compartido e independiente del orden de render, requiere contexto válido, no cambia `post_content` ni usa regex; no hay dependencias runtime externas ni frontend JS; Global Styles/theme.json siguen gobernando identidad visual; pasan los tests definidos, no hay warnings PHP/JS relevantes y README documenta instalación y comportamiento.

## 19. Fases de implementación

| Fase | Alcance | Estado |
|---|---|---|
| Phase 0 — Preflight | Reconocimiento técnico | CLOSED |
| Phase 1 — Plugin Foundation | Bootstrap/build/registro/placeholders | Pendiente de inicio y revisión |
| Phase 2 — Callout Block | Contrato Callout | Pendiente |
| Phase 3 — TOC Core | Descubrimiento/render TOC | Pendiente |
| Phase 4 — Anchor Integration | AnchorPlan e inyección runtime | Pendiente |
| Phase 5 — Styling / Global Styles | Acabado compatible con temas | Pendiente |
| Phase 6 — QA / Hardening | Matriz de pruebas y seguridad | Pendiente |
| Phase 7 — Release Candidate | Preparación de release | Pendiente |

Ninguna fase continúa automáticamente; todas requieren revisión del Technical Director.

## 20. Autoridad de implementación

Codex puede decidir detalles locales que no cambien comportamiento, contratos, arquitectura, compatibilidad, seguridad, dependencias, persistencia o degradación. Debe escalar al Technical Director cualquier necesidad de nueva dependencia, persistencia, REST/AJAX, variante/bloque nuevo, mutación de `post_content`, cambio de namespace/compatibilidad/anchor contract o cambio de degradación.

## 21. Decision Log

Los estados `APPROVED` reflejan decisiones aprobadas en esta especificación técnica.

| ID | Decisión | Razón | Estado |
|---|---|---|---|
| D001 | Plugin Gutenberg-native. | Integración de edición consistente con Core. | APPROVED |
| D002 | WordPress >= 7.0. | Baseline de compatibilidad. | APPROVED |
| D003 | PHP >= 8.2. | Baseline de runtime. | APPROVED |
| D004 | Dos bloques en v0.1: TOC y Callout. | Alcance explícito y acotado. | APPROVED |
| D005 | TOC solo H2. | H2 representa sección principal editorial. | APPROVED |
| D006 | TOC dinámico server-side. | Siempre deriva del contenido actual sin copia persistente. | APPROVED |
| D007 | TOC no modifica `post_content`. | Protege contenido editorial y evita escritura derivada. | APPROVED |
| D008 | Anchors manuales tienen prioridad absoluta y se conservan exactamente. | Evita romper enlaces definidos por el autor. | APPROVED |
| D009 | Anchors automáticos runtime, deterministas y únicos; TOC/headings comparten AnchorPlan. | Garantiza href e ID concordantes sin persistencia. | APPROVED |
| D010 | Sin regex para parseo de bloques o modificación de HTML. | Reutiliza APIs estructuradas Core. | APPROVED |
| D011 | Un Callout con cinco variantes permitidas. | Mantiene un contrato de bloque único. | APPROVED |
| D012 | Callout usa InnerBlocks restringidos a `core/paragraph` y `core/list`. | Composición Gutenberg suficiente sin crear un builder. | APPROVED |
| D013 | El contenido Callout sobrevive a la desactivación del plugin. | Portabilidad y no pérdida editorial. | APPROVED |
| D014 | TOC puede desaparecer al desactivar el plugin; anchors automáticos también. | Degradación aceptada sin mutar headings persistentemente. | APPROVED |
| D015 | Sin dependencias runtime externas; `@wordpress/scripts` solo para desarrollo. | Reduce superficie y respeta Core como plataforma. | APPROVED |
| D016 | Sin JavaScript frontend en v0.1. | Navegación mediante anchors HTML estándar. | APPROVED |
| D017 | Global Styles/theme.json gobiernan identidad visual. | Compatibilidad entre block themes y no dependencia de TT5. | APPROVED |
| D018 | Sin infraestructura persistente propia. | No hay requisito que justifique tablas, options, transients u otros stores. | APPROVED |
| D019 | AnchorPlan lazy desde contenido fuente, reutilizable en request y no dependiente del orden de render de TOC/post-content/heading. | Asegura IDs y enlaces concordantes sin persistencia aunque cambie el orden de render. | APPROVED |
| D020 | TOC requiere post context válido; Query Loop multi-post queda fuera de v0.1. | Evita generar índices o anchors sin una fuente de post inequívoca. | APPROVED |
| D021 | Callout serializa estáticamente wrapper semántico, etiqueta de variante e InnerBlocks. | Conserva etiqueta y contenido aunque plugin y sus renderers estén desactivados. | APPROVED |

## 22. Open Issues

None blocking implementation.
