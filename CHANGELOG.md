# Changelog

## 0.4.0

### Added

- SCI — Fuentes static Gutenberg block using Core List and List Item blocks.
- Static serialization keeps the label, source titles, and links visible after plugin deactivation.

## 0.3.0

### Added

- SCI — Relacionado dynamic Gutenberg block.
- Automatic related-post selection using categories, tags and lightweight title similarity.

## 0.2.0

### Added

- SCI — Tiempo de lectura dynamic Gutenberg block.
- Automatic Unicode-safe reading time calculation at 220 WPM.
- Gutenberg-native presentation controls.

## 0.1.5

### Fixed

- Fixed the TOC editor crash by declaring `wp-components` and using Core's available `__experimentalUnitControl` export for the approved item-gap control.

## 0.1.4

### Changed

- TOC links are no longer underlined by default.
- TOC typography, colors, margin and padding use Gutenberg Core controls.
- Added editable item spacing and accent-line color controls.

## 0.1.3

### Changed

- “SCI — En resumen automático” now includes a bundled, safe decorative summary icon using Gutenberg Core blocks.

## 0.1.2

### Changed

- Gutenberg and Global Styles now control TOC and Callout presentation through Core Block Supports.
- Removed default visual presentation rules that could override theme/container presentation.

## 0.1.1

### Changed

- TOC no longer imposes a default background and inherits its visual context from Gutenberg/theme containers.

### Added

- Gutenberg-native “SCI — En resumen automático” pattern using WordPress Core Post Excerpt.

## 0.1.0

### Added

- SCI — En este artículo.
- SCI — Callout.
- Cinco variantes de Callout.
- TOC basado en headings H2.
- Anchors runtime deterministas y resolución de colisiones.
- Estilos compatibles con Global Styles y block themes.
