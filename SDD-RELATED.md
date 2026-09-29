# SCI Editorial Blocks — Feature SDD: SCI Related

**Documento:** Feature Software Design Document  
**Versión de la feature:** SCI Editorial Blocks 0.3.0  
**Block name:** sci-editorial/related  
**Estado:** APPROVED FOR IMPLEMENTATION — IMPLEMENTED IN 0.3.0  
**Product Owner:** Martín García  
**Technical Director:** ChatGPT  
**Technical Writer:** Codex  
**Text domain:** sci-editorial-blocks

Este documento registra el contrato aprobado para SCI Related 0.3.0. Amplía el alcance para 0.3.0; no modifica ni reemplaza SDD.md, SDD-READING-TIME.md ni las decisiones aprobadas para releases anteriores.

### Convenciones de evidencia

- **Hecho verificado:** dato observado en el repositorio o documentado por una API oficial.
- **Recomendación:** enfoque técnico preferido para satisfacer el requisito.
- **Decisión aprobada:** contrato concreto aprobado por el Technical Director.
- **Pendiente de validación:** comprobación que requiere implementación/QA en WordPress mínimo o entorno real; no representa una decisión de arquitectura abierta.

## 1. Propósito

SCI Related agrega una sola recomendación editorial relacionada con el post que contiene la instancia del bloque. Busca facilitar la continuidad de lectura y los enlaces internos, mediante señales simples y explicables de categorías, tags y título.

## 2. Fuentes y autoridad

**Hecho verificado — estado local:** package.json y la cabecera de sci-editorial-blocks.php declaran la versión 0.2.0. El bootstrap registra los bloques existentes desde metadata. README.md describe TOC, Callout, Reading Time y el patrón Summary; los documentos de fase/patch detallan sus contratos y QA.

**Hecho verificado — autoridad:** SDD.md y SDD-READING-TIME.md son los contratos aprobados existentes. SCI Related es una ampliación separada para 0.3.0 y mantiene reuse-first, Gutenberg-native, WordPress-native, seguridad por defecto, render server-side, portabilidad, dependencias mínimas y ausencia de infraestructura persistente propia.

**Alcance documental:** este archivo define únicamente SCI Related. La compatibilidad global permanece WordPress >= 7.0 y PHP >= 8.2, salvo cambio aprobado en el SDD principal. Nada en este documento cambia el código ni el release 0.2.0.

## 3. Alcance funcional 0.3.0

Un block type sci-editorial/related, automático y con una recomendación por instancia. El frontend muestra solamente:

1. la etiqueta traducible “Relacionado”;
2. el título real del post candidato;
3. un enlace al permalink de ese post.

No incluye imagen, excerpt, autor, fecha, categorías visibles, reading time ni múltiples resultados. En 0.3.0 la instancia siempre resuelve el candidato automáticamente.

## 4. Non-goals

Fuera de 0.3.0: override manual por artículo, IA, embeddings, vector DB, personalización, analytics/CTR, scoring configurable, pesos editables, dashboard SEO, consultas a APIs externas, cache persistente, cron, tablas/options/transients propios, endpoint REST/AJAX propio, JavaScript frontend, thumbnails, excerpts y varios posts relacionados. Una versión futura podrá evaluar un override per-post basado en post meta registrada y una UX específica del Post Editor; esta propuesta no diseña esa arquitectura y 0.3.0 no crea post meta.

## 5. Identidad y registro del bloque

**Decisión aprobada:** registrar sci-editorial/related como bloque dinámico mediante block.json, apiVersion: 3, dentro del namespace sci-editorial; registrarlo server-side desde metadata siguiendo el patrón existente. La metadata declara el contexto de post que consuma el bloque y render PHP. No declara atributos de selección manual en 0.3.0.

El plugin conserva el text domain sci-editorial-blocks, el bootstrap y la arquitectura existente. No se propone crear un paquete, servicio, framework, endpoint ni dependencia adicional.

**Hecho Core:** block.json declara metadata, supports y usesContext; el registro server-side desde metadata es una API Core documentada. [Metadata de bloques](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/) · [registro desde metadata](https://developer.wordpress.org/reference/functions/register_block_type_from_metadata/)

## 6. Modo automático

**Decisión aprobada:** SCI Related es exclusivamente automático en 0.3.0. No tiene selector manual, atributo de selección, botón para reiniciar a automático ni estado manual. El renderer determina el candidato desde el post fuente y no guarda esa decisión en el bloque ni en el post.

La ausencia de controles funcionales no convierte al bloque en una interfaz paralela: se mantiene como bloque Gutenberg-native, con inserción y representación de editor nativas.

## 7. Contexto de post

El bloque necesita un postId y postType resolubles del contexto Gutenberg para determinar el post fuente y excluirlo de la lista. **Recomendación:** consumir postId/postType mediante Block Context (usesContext) y resolver el post fuente explícitamente. En frontend, si no hay contexto válido o el post fuente no es de tipo post, no generar recomendación ni markup visible.

Este contrato no usa el global $post como fallback ambiguo. En plantillas y Query Loop solo opera sobre el contexto concreto que Core provea a la instancia. Si una estructura no proporciona contexto, el resultado es vacío; no se asume el post singular consultado.

**Hecho Core:** Block Context permite a un bloque descendiente heredar valores de bloques ancestros y está soportado en callbacks PHP server-side; la documentación lo identifica como útil en Full Site Editing para contenido dependiente del post. [Block Context](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-context/)

## 8. Posts elegibles

Todos los candidatos deben cumplir estos puntos:

- post_type es exactamente post;
- post_status es exactamente publish;
- el candidato no es el post fuente;
- WordPress lo considera públicamente visible;
- no está protegido por contraseña;
- tiene un permalink no vacío.

Excluir draft, pending, private, trash, revision, attachment, tipos de post distintos y el post actual.

**Recomendación de comprobación Core:** el query solicita post_type = post, post_status = publish y has_password = false; los resultados se revalidan en PHP por existencia, tipo, estado, is_post_publicly_viewable(), contraseña vacía, diferencia con el post fuente y permalink. is_post_publicly_viewable() comprueba visibilidad según post type y status; no sustituye el chequeo explícito de contraseña. [WP_Query](https://developer.wordpress.org/reference/classes/wp_query/) · [is_post_publicly_viewable()](https://developer.wordpress.org/reference/functions/is_post_publicly_viewable/) · [get_permalink()](https://developer.wordpress.org/reference/functions/get_permalink/)

**Límite conocido:** Core permite comprobar visibilidad pública según tipo/estado, pero plugins de membresía, reglas de servidor o filtros de acceso pueden imponer restricciones adicionales. La feature no integrará APIs propietarias de esos sistemas; su contrato de elegibilidad se limita a las comprobaciones Core indicadas y al permalink local. La compatibilidad con sistemas de acceso de terceros requiere validar el sitio concreto.

## 9. Señales y puntuación automática

La candidate query descubre posts únicamente mediante taxonomías compartidas. El title matching no descubre candidatos: su +1 solo ayuda a ordenar candidatos que ya fueron recuperados por compartir categoría o tag. Cada señal se aplica como sigue:

| Señal | Puntos propuestos | Regla |
|---|---:|---|
| Categoría compartida | +3 | Una vez si existe una o más categorías asignadas en común. |
| Tag compartido | +2 por tag | Contar IDs de tags distintos en común, con aporte total limitado a +4 (máximo dos tags). |
| Token normalizado de título compartido | +1 máximo total | Una sola vez aunque coincidan varios tokens; solo entre candidatos descubiertos por taxonomías. |

### 9.1 Categorías

Usar las categorías Core asignadas directamente a ambos posts (category). No hay “categoría principal” propia. Varias categorías compartidas siguen aportando +3, una sola vez, para no sobreponderar asignaciones editoriales extensas.

### 9.2 Tags

Usar IDs únicos de tags Core (post_tag). Cada tag coincidente suma +2 hasta un tope de +4 por post; el tercer tag y los siguientes no elevan score. No hay ajustes de pesos desde admin.

### 9.3 Coincidencia de títulos

**Decisión aprobada:** quitar markup del título con mecanismos Core, extraer secuencias Unicode normalizadas de letras/números mediante PCRE Unicode (\p{L}/\p{N} con modo UTF-8 y case-insensitive), deduplicar tokens y comprobar si ambos títulos comparten al menos uno. Si coinciden uno o varios tokens, se añade exactamente +1 total; varios tokens nunca acumulan puntos. No se calculan porcentajes, similitud difusa ni repetición por varios tokens.

Esta regla es determinista, Unicode-aware, insensible a mayúsculas y no requiere mbstring, NLP, stemming, diccionario de stopwords ni servicio externo. No normaliza acentos entre sí: conexion y conexión no necesariamente coincidirán. No se introduce lista de stopwords; el peso de solo +1 y el umbral de relevancia limitan su efecto. El título nunca crea por sí solo un candidato ni basta por sí solo para superar el umbral.

**Pendiente de validación:** probar el matcher con títulos Unicode y locales soportados en PHP 8.2; verificar con tests que el modo Unicode de PCRE del baseline da comparación case-insensitive esperada para alfabetos relevantes.

## 10. Umbral de relevancia

**Decisión aprobada:** mínimo score = 3. Si no hay candidato con ese score, no emitir markup visible.

Consecuencias concretas:

- una categoría compartida basta (+3);
- un solo tag compartido da score 2 y es insuficiente;
- dos tags compartidos bastan (+4);
- un tag y un token compartido de título suman 3;
- título coincidente por sí solo nunca produce candidato ni alcanza umbral.

El umbral es fijo en 0.3.0, no configurable. Si varios candidatos superan el umbral, gana el score más alto y luego se aplica desempate.

## 11. Desempate y determinismo

Ordenar elegibles por:

1. score descendente;
2. fecha publicada más reciente descendente;
3. ID descendente.

No hay aleatoriedad, orden por ejecución ni elección dependiente del orden de una colección no ordenada. Los términos se comparan como conjuntos de IDs únicos. Mismo post fuente, mismo conjunto candidato y mismos títulos/fechas produce el mismo resultado.

## 12. Query strategy y límite de candidatos

**Decisión aprobada:** recuperar candidatos en una única instancia de WP_Query filtrada por una tax_query con relación OR: categoría compartida y/o tag compartido. La discovery no usa título y no tiene fallback global. Usar asignaciones directas (include_children: false). Si el post fuente no tiene categorías ni tags útiles, o no se pueden resolver sus term IDs, no ejecutar query de candidatos y devolver vacío/no visible output.

Argumentos recomendados:

- post_type = post;
- post_status = publish;
- has_password = false;
- post__not_in = [source_post_id];
- posts_per_page = 50;
- no_found_rows = true;
- ignore_sticky_posts = true;
- orderby = fecha descendente y luego ID descendente;
- update_post_term_cache = true;
- update_post_meta_cache = false;
- tax_query = relation OR, con una cláusula category y, si existen IDs, una post_tag; ambas por term_id e include_children false.

Si solo una de las dos taxonomías tiene términos, incluir solo su cláusula. Los resultados se revalidan en PHP por elegibilidad y luego se puntúan. No ejecutar una query por categoría/tag, no buscar en todos los posts, no usar posts_per_page = -1, ni hacer una consulta separada por cada candidato.

**Límite intencional:** se puntúan como máximo 50 candidatos taxonómicos. Si existen más de 50, los posts antiguos pueden quedar excluidos. Esto se acepta en 0.3.0 por simplicidad/performance; no se implementan paginación ni queries adicionales para evitar el límite. No se garantiza óptimo global sobre todos los posts del sitio.

**Hecho Core:** WP_Query acepta filtros taxonómicos, has_password, estado/tipo, límite, orden y no_found_rows; la documentación recomienda no_found_rows cuando no se necesita paginación. update_post_term_cache está habilitado por defecto para resultados completos. get_the_terms() usa la caché de relaciones de términos; por ello es preferible a wp_get_post_terms() dentro del loop de score para evitar consultas repetidas innecesarias. [WP_Query](https://developer.wordpress.org/reference/classes/wp_query/) · [get_the_terms()](https://developer.wordpress.org/reference/functions/get_the_terms/) · [wp_get_post_terms()](https://developer.wordpress.org/reference/functions/wp_get_post_terms/)

**Presupuesto de queries:** una query Core acotada de candidatos por post fuente en la primera resolución automática; lectura de términos apoyada por caches Core; cero queries por término/candidato creadas por SCI. Cachear la resolución automática en memoria del request por ID de post fuente para que instancias repetidas compartan candidatos/resultado. Sin object cache propio, transients ni cache persistente.

Las consultas internas de priming dependen del estado de object cache y del entorno; no se promete un número SQL absoluto frente a filtros de terceros. [WP_Query](https://developer.wordpress.org/reference/classes/wp_query/)

## 13. Resolución automática

Orden de resolución automática:

1. Resolver el contexto de post fuente válido.
2. Leer/cargar categorías y tags del post fuente; si no hay términos útiles, terminar sin output.
3. Ejecutar o reutilizar la query acotada de candidatos.
4. Revalidar candidatos, calcular score y ordenar por score/fecha/ID.
5. Renderizar el primero con score >= 3; si no existe, devolver string vacío.

La selección automática no se persiste en atributos, post meta ni post_content.

## 14. Datos del post y salida server-side

Obtener el título actual del post seleccionado, el permalink Core del mismo post y escapar al renderizar. No persistir título ni URL. No hay fallback de link externo.

Markup semántico propuesto:

    <aside class="wp-block-sci-editorial-related">
      <span class="sci-related-label">Relacionado</span>
      <a href="[permalink escapado]">[título escapado]</a>
    </aside>

Puede añadirse el wrapper estándar de atributos de bloque requerido por supports/Core. No es obligatorio un heading. Si no hay fuente, candidato, título o permalink válido, no se emite nada visible. Si esc_url() produce URL vacía, devolver string vacío en vez de un enlace roto.

## 15. Editor Gutenberg

El editor no necesita controles funcionales de selección porque el bloque es automático. **Decisión aprobada:** mostrar un placeholder sencillo, por ejemplo: “Relacionado / El artículo relacionado se selecciona automáticamente según el contenido actual.” No añadir búsqueda, selección manual, botón “Volver a automático”, consultas REST ni lógica de editor específica.

Si en el contexto de post ya disponible se puede mostrar una preview real mediante APIs Core sin complejidad significativa, se puede evaluar durante implementación. Si requiere fetching adicional, REST propio o estado/editor logic considerable, conservar el placeholder. No se implementa preview remoto ni fetching complejo solo para mostrar una recomendación en el editor.

**Investigación previa evaluada y rechazada para 0.3.0:** en la propuesta inicial se evaluaron Core Data (getEntityRecords/getEntityRecord), la entidad REST Core de posts y ComboboxControl para una selección manual. Al retirar el override manual por el riesgo de almacenar estado compartido en una plantilla Single, esas APIs dejan de ser requisito y no se usarán para buscar/seleccionar posts en 0.3.0. [Core Data](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-core-data/) · [REST API: Posts](https://developer.wordpress.org/rest-api/reference/posts/) · [ComboboxControl](https://developer.wordpress.org/block-editor/reference-guides/components/combobox-control/)

## 16. Presentación y Block Supports

Principio: **el plugin controla la estructura; Gutenberg y Global Styles controlan la presentación.** No declarar font-family, font-size, color, fondo, padding o margin fijos; no imponer identidad SCI.

**Decisión aprobada:** habilitar solo supports útiles en el wrapper/contenido interno, sujetos a prueba con theme.json:

- color de texto y enlace;
- spacing de margin y padding;
- typography de font size, font family y line height.

No habilitar fondos, gradientes, dimensiones, layout u opciones adicionales en 0.3.0 salvo que la QA demuestre que son necesarias. El CSS propio se limita a relación semántica y estructura; respeta presets del tema. Debe usarse metadata/selectors si es necesario dirigir los supports al label o link sin imponer estilos. La selección final de selectores es implementación local siempre que no cambie este contrato.

**Hecho Core:** Block Supports permite declarar controles de color, spacing y typography y sus valores se integran con la configuración del editor/tema. [Block Supports](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/) · [block.json y supports](https://developer.wordpress.org/block-editor/getting-started/fundamentals/block-json/)

**Pendiente de validación:** confirmar que los supports propuestos se aplican a los nodos semánticos deseados y que Global Styles del tema activo prevalecen en Twenty Twenty-Five y Twenty Twenty-Four.

## 17. Accesibilidad e internacionalización

- El título real del post es el texto del enlace y constituye el nombre accesible del destino; no sustituirlo por “Leer más”.
- El enlace es un elemento `<a>` estándar, operable por teclado.
- “Relacionado” es una etiqueta textual visible; el significado no depende solo del color.
- El placeholder del editor mantendrá un texto visible y traducible.
- Traducir todas las strings con text domain sci-editorial-blocks.

La etiqueta visible del frontend es la string traducible “Relacionado”. El placeholder de editor también es traducible con text domain sci-editorial-blocks.

## 18. Seguridad

- No hay atributo con ID de post en 0.3.0; no se acepta un destino aportado por contenido serializado.
- Revalidar cada candidato en servidor: existe, tipo post, estado publish, públicamente visible, sin contraseña, distinto al post fuente.
- No usar títulos/permalinks guardados en atributos ni confiar en datos del editor.
- Obtener permalink con get_permalink() y comprobar que no sea false/vacío; emitir con esc_url().
- Escapar título y label como texto con esc_html() o variantes de traducción escapadas.
- Usar contenido derivado de APIs Core; no interpretar markup editorial como confiable.
- No ejecutar contenido dinámico del usuario, ni añadir endpoints, AJAX, DB, options, cron, transients o telemetría.

**Hecho Core:** las guías de seguridad indican escapar al emitir y recomiendan esc_url() para URLs y esc_html() para texto dentro de elementos. [Escaping Data](https://developer.wordpress.org/apis/security/escaping/) · [esc_url()](https://developer.wordpress.org/reference/functions/esc_url/) · [esc_html()](https://developer.wordpress.org/reference/functions/esc_html/)

## 19. Degradación

Al desactivar el plugin, el bloque dinámico puede dejar de renderizar. No se pierde texto editorial fuente. El bloque no persiste el candidato automático ni una copia del título/link; no hay fallback estático requerido.

## 20. Presupuesto de rendimiento e infraestructura

- Frontend JavaScript: cero.
- APIs externas y dependencias runtime nuevas: cero.
- Tablas, options, cron, transients/cache persistente: cero.
- Query de candidatos: una WP_Query limitada a 50 para la primera resolución automática por post fuente; no se consulta si falta taxonomía compartida.
- Editor: placeholder sin búsqueda de posts, selector manual ni solicitud REST adicional.
- Repetición en request: memoización en memoria local por ID del post fuente.
- Scoring: PHP sobre máximo 50 posts con caches Core de términos.

Los filtros globales de WordPress y plugins de terceros pueden cambiar tiempos y conteos internos; SCI no agrega trabajo si el bloque no se renderiza.

## 21. Matriz mínima de pruebas

| ID | Caso | Resultado esperado |
|---|---|---|
| A | Categoría compartida | +3; elegible si supera validaciones. |
| B | Varias categorías compartidas | +3 total, no +3 por cada una. |
| C | Un tag compartido | +2; no render si no hay otra señal suficiente. |
| D | Varios tags compartidos | +2 por ID común, con máximo +4. |
| E | Categoría y tags compartidos | Score suma según reglas y cap. |
| F | Token de título compartido | +1 máximo; comparación Unicode/case-insensitive; título solo no basta. |
| G | Scores empatados | Fecha descendente y luego ID descendente. |
| H | Ningún candidato o score < 3 | Sin markup visible. |
| I | Candidato draft/pending/trash | Excluido. |
| J | Candidato private | Excluido. |
| K | Post fuente como candidato | Excluido. |
| L | Múltiples instancias automáticas | Una resolución memoizada por post fuente; cada contexto conserva su propio resultado. |
| M | Sin contexto resoluble | Placeholder editorial; sin query ni markup frontend. |
| N | Post fuente sin categorías ni tags útiles | Sin candidate query ni output. |
| O | Candidate query devuelve más de 50 | Solo se puntúan los 50 candidatos recuperados; no hay paginación ni segunda query. |
| P | Guardar/reabrir editor | Bloque permanece y muestra placeholder automático, sin controles manuales. |
| Q | Frontend | Label, título real y enlace interno correcto; escaping correcto. |
| R | Twenty Twenty-Five | Se inserta y renderiza; Global Styles controla presentación. |
| S | Twenty Twenty-Four | Funciona sin dependencia de TT5 y con Global Styles. |
| T | Regresión TOC | Inserción, render y anchors existentes sin regresión. |
| U | Regresión Callout | Inserción, variantes y contenido conservados. |
| V | Regresión Reading Time | Inserción/editor/frontend sin regresión. |
| W | Automatic Summary | Pattern sigue registrado y funciona. |
| X | Bloqueo de password y estados no públicos | Excluye posts protegidos, draft, pending, private y trash. |
| Y | Título HTML/Unicode/puntuación | Normalización segura y +1 máximo total, sin discovery por título. |

Añadir casos de seguridad/edge: post con permalink vacío; fuente sin contexto, draft o no-post; candidato fuera de los 50; título Unicode en locales soportados. Verificar no hay query de candidatos sin instancia real, sin contexto o sin términos compartidos; y que no se producen consultas N+1 por términos. No probar flujos manuales en 0.3.0 porque no forman parte del contrato.

## 22. Definition of Done de la feature

SCI Related está listo para release cuando:

1. existe exactamente un nuevo block type dinámico sci-editorial/related con metadata y registro server-side;
2. el comportamiento es automático exclusivamente; no existe selección manual, atributo manual ni post meta en 0.3.0;
3. solo se muestran posts post publicados, Core-viewable, no protegidos por password y distintos de la fuente;
4. scoring, tope de tags, matcher ligero, umbral y desempate cumplen las secciones 9–11;
5. query candidata usa taxonomías compartidas, OR, límite 50, sin scan global, N+1 o queries por término;
6. si no hay taxonomía compartida o no se alcanza umbral, no hay salida visible;
7. el editor muestra el placeholder; preview real solo si usa contexto disponible y APIs Core sin complejidad significativa;
8. frontend server-side genera estructura segura y accesible sin JavaScript;
9. supports y estilos respetan Global Styles/theme.json;
10. no hay almacenamiento persistente ni dependencias runtime nuevas;
11. se completa la matriz de prueba de la sección 21 en WP/PHP baseline y temas indicados, sin regresiones ni warnings relevantes.

## 23. Riesgos y pendientes de validación

- La ventana de 50 recientes puede omitir un candidato antiguo con score mayor; es una limitación aceptada por la estrategia acotada propuesta.
- El matching de título es intencionalmente ligero y puede considerar tokens comunes; no garantiza relevancia semántica. Su contribución limitada y el umbral evitan recomendar por título solamente.
- La normalización Unicode/casefold mediante PCRE debe confirmarse en el build de PHP 8.2 del entorno objetivo y con idiomas de contenido reales.
- Plugins de membresía u otras reglas de acceso pueden restringir posts que Core clasifica como viewable; no hay detección universal sin integrar APIs de terceros.
- La preview real del editor podría requerir más complejidad que un placeholder; no debe agregarse fetching remoto solo para mostrarla.
- El formato de supports/metadata y sus selectores necesita comprobación en el WordPress mínimo y ambos temas de prueba.
- Filtros de WP_Query de otros plugins pueden modificar la lista de candidatos; el renderer debe revalidar cada registro devuelto.

**No quedan decisiones críticas de producto/arquitectura sin propuesta concreta.** Los puntos anteriores son límites explícitos o validaciones de implementación, no permiso para ampliar alcance.

## 24. Decisiones aprobadas

Las 15 decisiones REL001–REL015 están **APPROVED** por el Technical Director y reflejan la arquitectura implementada en 0.3.0.

| ID | Decisión | Razón | Estado |
|---|---|---|---|
| REL001 | Bloque dinámico sci-editorial/related, apiVersion 3, metadata y registro server-side. | Reusa arquitectura de bloques existente y render PHP. | APPROVED |
| REL002 | Target de release 0.3.0; sin cambio al release 0.2.0. | Aísla feature futura de los contratos ya aprobados. | APPROVED |
| REL003 | Una recomendación automática por instancia; salida label + título + link. | Alcance editorial explícito y pequeño. | APPROVED |
| REL004 | Automático exclusivamente en 0.3.0, sin selector ni atributo manual. | Evita estado compartido/incorrecto en una plantilla Single. | APPROVED |
| REL005 | Candidate discovery requiere categoría o tag compartido; title matching solo ordena candidatos descubiertos. | Evita escanear globalmente y limita el score de título. | APPROVED |
| REL006 | Categoría compartida +3 como máximo una vez. | Evita sobreponderar múltiples categorías. | APPROVED |
| REL007 | Tags compartidos +2 cada uno, con máximo +4 total. | Controla la influencia de muchos tags. | APPROVED |
| REL008 | Token normalizado de título +1 como máximo total aunque coincidan varios tokens. | Mantiene title matching como señal auxiliar. | APPROVED |
| REL009 | Minimum score = 3; por debajo no hay output visible. | Una categoría o dos tags pueden ser suficientes; un tag no. | APPROVED |
| REL010 | Query Core limitada a 50 candidatos taxonómicos y scoring final en PHP. | Coste acotado y evita consulta global. | APPROVED |
| REL011 | Desempate determinista: score, fecha descendente, post ID descendente. | Reproducible, sin random. | APPROVED |
| REL012 | Sin fallback global si no hay taxonomías compartidas o score suficiente. | No recomendar posts sin señal suficiente. | APPROVED |
| REL013 | Render dinámico server-side, cero JavaScript frontend y placeholder editor mínimo. | No hay interacción funcional que requiera JS/editor controls. | APPROVED |
| REL014 | Override manual per-article deferred to future version; evaluar post meta registrada y UX Post Editor en esa fase, sin diseñarla ahora. | La instancia en Single template no almacena estado distinto por post. | APPROVED |
| REL015 | Gutenberg/Global Styles controlan presentación mediante Block Supports selectivos. | Coherencia con temas y preferencias del sitio. | APPROVED |

## 25. Registro de APIs y referencias investigadas

### Hechos verificados en APIs oficiales

- WP_Query proporciona tax_query, post_type, post_status, post__not_in, has_password, posts_per_page, orderby, no_found_rows y opciones de precarga de caché de términos.
- get_the_terms() usa el object cache de relaciones para leer términos de un post; la referencia de wp_get_post_terms() especifica que delega en wp_get_object_terms().
- is_post_publicly_viewable() combina visibilidad de post type y status; get_permalink() puede devolver false si el post no existe.
- Block Supports expone estilos comunes; Block Context ofrece contexto de post a descendientes y callbacks server-side.
- Guía de seguridad Core define escaping contextual para texto, atributos y URL.

### Investigación histórica evaluada para 0.3.0

La propuesta anterior investigó Core Data (getEntityRecords/getEntityRecord), REST API Posts (search/status/per_page) y ComboboxControl para selección manual. Son APIs Core verificadas, pero dejan de ser necesidades de 0.3.0 al aprobarse el modo automático; no se prescriben para consultar candidatos ni para configurar el bloque. Se conservan estas referencias como registro de investigación, no como requisito de implementación.

### Fuentes oficiales

- [WP_Query](https://developer.wordpress.org/reference/classes/wp_query/)
- [WP_Tax_Query](https://developer.wordpress.org/reference/classes/wp_tax_query/)
- [get_the_terms()](https://developer.wordpress.org/reference/functions/get_the_terms/)
- [get_the_category()](https://developer.wordpress.org/reference/functions/get_the_category/)
- [get_the_tags()](https://developer.wordpress.org/reference/functions/get_the_tags/)
- [wp_get_post_terms()](https://developer.wordpress.org/reference/functions/wp_get_post_terms/)
- [is_post_publicly_viewable()](https://developer.wordpress.org/reference/functions/is_post_publicly_viewable/)
- [get_permalink()](https://developer.wordpress.org/reference/functions/get_permalink/)
- [Core Data — investigado/rechazado para selección manual en 0.3.0](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-core-data/)
- [Core Data selectors — investigados/rechazados para selección manual en 0.3.0](https://developer.wordpress.org/block-editor/reference-guides/data/data-core/)
- [REST API: Posts — investigada/rechazada para búsqueda manual en 0.3.0](https://developer.wordpress.org/rest-api/reference/posts/)
- [ComboboxControl — investigado/rechazado para selección manual en 0.3.0](https://developer.wordpress.org/block-editor/reference-guides/components/combobox-control/)
- [Block Context](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-context/)
- [Block Supports](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/)
- [Block metadata](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/)
- [Escaping Data](https://developer.wordpress.org/apis/security/escaping/)
- [PHP PCRE pattern modifiers](https://www.php.net/manual/en/reference.pcre.pattern.modifiers.php)
- [PCRE2 Unicode case equivalence](https://www.pcre.org/current/doc/html/pcre2unicode.html)

### API fact vs decision

Que Core exponga estas APIs es un hecho documentado; la adopción por el plugin de las reglas concretas de query, scoring, cap, umbral, matcher, elegibilidad y supports de este documento está aprobada por el Technical Director.

## 26. Estado de revisión

**Estado:** IMPLEMENTED IN 0.3.0 — TECHNICALLY APPROVED.  
**Implementación:** autorizada y completada para 0.3.0.  
**Decisiones críticas abiertas:** no; las decisiones REL001–REL015 están aprobadas e implementadas conforme a las secciones 5–16.  
**Siguiente paso:** completar higiene de release 0.3.0 y revisión final.
