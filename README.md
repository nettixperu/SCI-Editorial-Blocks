# SCI Editorial Blocks

Bloques editoriales nativos de Gutenberg para contenido técnico y publicaciones largas.

**Versión:** 0.4.0 release candidate  
**Requisitos:** WordPress >= 7.0, PHP >= 8.2, Block Editor activo.

## Bloques

### SCI — En este artículo

- Genera navegación automáticamente desde headings H2.
- Conserva anchors manuales.
- Genera anchors deterministas durante el render, sin modificar `post_content`.
- Los anchors desaparecen al desactivar el plugin; el contenido original permanece.

### SCI — Callout

Variantes permitidas:

- En contexto
- Clave
- En la práctica
- Para decidir
- Advertencia

Contenido permitido: bloques Core `Paragraph` y `List`, incluidos enlaces y formatos inline. La etiqueta y el contenido se serializan estáticamente para sobrevivir a la desactivación.

### SCI — Tiempo de lectura

Calcula automáticamente el tiempo estimado a 220 palabras por minuto. Se recomienda insertarlo una sola vez en la plantilla Single; no requiere configuración.

### SCI — Fuentes

Lista manual de fuentes y documentación con `core/list` y `core/list-item`. Los títulos se editan con Gutenberg; los enlaces son opcionales y usan la herramienta Core de enlaces. El label y las referencias se serializan estáticamente para permanecer visibles al desactivar el plugin.

### SCI — Relacionado

Selecciona automáticamente un artículo publicado que comparta categorías o etiquetas con la entrada actual. Las categorías y etiquetas determinan los candidatos; una coincidencia de título Unicode aporta solo una señal secundaria de ranking. El bloque no ofrece selección manual y no muestra nada cuando el contexto o los candidatos no cumplen los requisitos de relevancia.

### SCI — En resumen automático

Es un Block Pattern Gutenberg-native, no un bloque personalizado. Está compuesto por `core/group`, una fila Core con `core/image` para el icono local y una etiqueta Core traducible “En resumen”, además de `core/post-excerpt`. El SVG es un asset confiable del plugin y la imagen usa `alt` vacío por ser decorativa.

Insértalo una sola vez en la plantilla Single desde el Site Editor. Cada entrada actual o futura mostrará automáticamente su propio excerpt manual o generado por WordPress. El plugin no modifica artículos individuales ni duplica excerpts; el diseño del Group se puede ajustar con Gutenberg.

## Compatibilidad visual

Los bloques funcionan con block themes y Global Styles. El tema gobierna tipografía, escala, colores y ancho de contenido. El plugin aporta composición, bordes y espaciado estructural usando `currentcolor` y presets WordPress cuando están disponibles; no instala una paleta ni una familia tipográfica propia. El TOC no impone ningún fondo por defecto; puede heredar el fondo del contenedor o recibir una elección explícita mediante supports Core.

## Desarrollo

Desde la raíz del plugin:

```sh
npm ci
npm run build
```

Usa `npm run start` para compilar en modo watch, `npm run lint:js` para revisar JavaScript y `npm run lint:css` para revisar estilos.

## Arquitectura

Los bloques se describen mediante `src/*/block.json`, se compilan con `@wordpress/scripts` y se registran en PHP desde la metadata generada en `build/`. Los paquetes `@wordpress/*` se proporcionan desde WordPress; no hay dependencias runtime externas.

Node/npm solo son necesarios para compilar y revisar el código. La instalación runtime usa los artefactos de `build/`.

Consulta [SDD.md](SDD.md) para el contrato base, [SDD-READING-TIME.md](SDD-READING-TIME.md) para Reading Time, [SDD-RELATED.md](SDD-RELATED.md) para SCI Related, [SDD-SOURCES.md](SDD-SOURCES.md) para SCI Sources y [PHASE-READING-TIME-0.2.0.md](PHASE-READING-TIME-0.2.0.md) para la evidencia de Reading Time.
