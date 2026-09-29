# SCI Editorial Blocks

**Gutenberg-native editorial blocks for technical publishing.**

SCI Editorial Blocks is a lightweight WordPress plugin for long-form and technical content. It adds editorial structure while staying native to the Gutenberg Block Editor.

![Release version](https://img.shields.io/badge/release-v0.4.0-2f6feb)
![WordPress requirement](https://img.shields.io/badge/WordPress-7.0%2B-21759b?logo=wordpress&logoColor=white)
![PHP requirement](https://img.shields.io/badge/PHP-8.2%2B-777bb4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)

**Requirements:** WordPress 7.0 or later, PHP 8.2 or later, and the Block Editor. The plugin adds no frontend JavaScript and has no external runtime dependencies.

<!-- Add docs/images/sci-editorial-blocks-overview.png here after an approved product screenshot is available. -->

## Why SCI Editorial Blocks?

Gutenberg already provides the editing and layout foundation. This plugin adds a small set of editorial components for technical articles without turning the editor into a separate page builder. It reuses WordPress and Gutenberg APIs, keeps authored content portable, and adds frontend behavior only where a component needs server-side output.

## Included editorial components

### SCI — En este artículo

An automatic, server-rendered table of contents based on the post’s H2 blocks. It preserves manual anchors and generates deterministic runtime anchors for headings without one. It does not modify `post_content`.

### SCI — Callout

A static Gutenberg block with five variants: **En contexto**, **Clave**, **En la práctica**, **Para decidir**, and **Advertencia**. Its content is restricted to Core paragraphs and lists and is serialized with the block.

### Automatic Summary

A Gutenberg pattern, not a custom block. It combines Core Group, Image, Paragraph, and Post Excerpt blocks with a local decorative SVG icon. The excerpt comes from WordPress.

### SCI — Tiempo de lectura

Calculates reading time on the server at 220 words per minute using Unicode-aware word counting. It is designed for use in a Single template.

### SCI — Relacionado

Selects one published post from candidates that share categories or tags. A lightweight title-token match helps rank those candidates. Selection is deterministic; there is no AI or manual override.

### SCI — Fuentes

A manual sources and documentation list built with Core List and List Item blocks. Titles and optional links are statically serialized with the block content.

## Design principles

- Gutenberg-native and WordPress-native first; reuse Core capabilities.
- Server-side rendering for dynamic components; no frontend JavaScript.
- No external runtime dependencies or telemetry.
- Portable editorial content and compatibility with Global Styles and `theme.json`.

## Installation

### GitHub Release ZIP

A `v0.4.0` Git tag is available, but a GitHub Release and downloadable release asset have not been published yet. When the release ZIP is available from GitHub Releases:

1. Download `sci-editorial-blocks-0.4.0.zip` from the project’s GitHub Releases page.
2. In WordPress Admin, go to **Plugins → Add New → Upload Plugin** and select the ZIP.
3. Install and activate **SCI Editorial Blocks**.

### Development checkout

```sh
git clone https://github.com/nettixperu/SCI-Editorial-Blocks.git
cd SCI-Editorial-Blocks
npm ci
npm run build
```

Copy the plugin directory into `wp-content/plugins/` and activate it in WordPress. Node.js and npm are needed for development builds, not for runtime.

## Recommended usage

These placements are editorial recommendations; the plugin does not enforce them.

- **Single template:** SCI — Tiempo de lectura, SCI — En este artículo, and SCI — Relacionado.
- **Single template:** insert the Automatic Summary pattern where the excerpt should appear.
- **Individual post content:** add SCI — Callout and SCI — Fuentes where they support the article.

## Suggested article structure

```text
Title
Metadata and reading time
Automatic Summary
Post content, with Callouts where useful
Sources and documentation
Related article
Newsletter or comments
```

This is an example editorial flow, not a plugin requirement.

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 7.0 or later |
| PHP | 8.2 or later |
| Block Editor | Required |
| Frontend JavaScript | None |
| External runtime dependencies | None |

## Performance and privacy

The plugin does not add frontend JavaScript, telemetry, external API calls, analytics, or custom database tables. The Automatic Summary icon is bundled locally. TOC, Reading Time, and Related generate output server-side when their blocks are rendered. Related uses a bounded WordPress Core candidate query; database work is not zero.

## Content portability

Callout and Sources serialize their labels and authored content into Gutenberg markup, so that content remains readable if the plugin is disabled. Dynamic components such as the table of contents, reading time, and related-post recommendation may stop displaying when the plugin is disabled; they do not replace or rewrite the original post content. The Automatic Summary pattern uses Core blocks, while its decorative icon is a plugin asset.

## Development

From the repository root:

```sh
npm ci
npm run build
npm run lint:js
npm run lint:css
```

The available PHP fixtures are in `tests/` and run with WP-CLI against a disposable WordPress installation with the plugin active. There is no `npm test` script.

```text
src/       Block source and metadata
build/     Compiled assets and runtime metadata
includes/  Shared PHP logic
tests/     WordPress integration fixtures
```

## Quality

Release QA for 0.4.0 included WordPress 7.0.4 and 7.1.2 on PHP 8.5.4, with Twenty Twenty-Five and Twenty Twenty-Four checks. TOC and Related have automated WP-CLI fixtures, and the release ZIP was smoke-tested. `npm audit --omit=dev` reported zero runtime vulnerabilities for 0.4.0. These are project QA results, not a compatibility certification for every WordPress site or theme.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup, coding guidance, and pull request expectations.

## Security

See [SECURITY.md](SECURITY.md) for supported versions and vulnerability reporting. Please do not report suspected vulnerabilities in public issues.

## License

SCI Editorial Blocks is licensed under the GNU General Public License v2.0 or later (GPL-2.0-or-later).

See [LICENSE](LICENSE) for the full license text.

## About SCI WebHosting

[SCI WebHosting](https://www.sciwebhosting.com/) is a technical editorial publication focused on infrastructure, hosting, cybersecurity, email, and web technologies for businesses in Latin America.

---

Repository contracts and feature specifications: [SDD.md](SDD.md), [Reading Time](SDD-READING-TIME.md), [Related](SDD-RELATED.md), and [Sources](SDD-SOURCES.md).
