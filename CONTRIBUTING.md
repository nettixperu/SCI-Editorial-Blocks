# Contributing to SCI Editorial Blocks

Thank you for considering a contribution.

## Before you start

- Check existing issues and pull requests for related work.
- Open an issue before a significant behavior or architecture change.
- Keep each change focused on one problem.

## Design principles

- Gutenberg-native first; WordPress-native first; reuse Core capabilities.
- Avoid unnecessary runtime dependencies.
- Do not add frontend JavaScript unless the behavior is explicitly justified.
- Preserve content portability and compatibility with Global Styles and `theme.json`.
- Prefer simple, maintainable code over speculative architecture.

## Development setup

Requirements: WordPress 7.0 or later, PHP 8.2 or later, and Node.js/npm for development builds.

```sh
npm ci
npm run build
npm run lint:js
npm run lint:css
```

Targeted WordPress fixtures are in `tests/` and require a disposable WordPress installation with the plugin active.

## Coding expectations

- Follow WordPress and Gutenberg APIs and conventions.
- Validate inputs and escape output for its context.
- Avoid deprecated APIs where a suitable supported API is available.
- Keep implementation focused, small, and maintainable.

## Pull requests

Please include:

- A concise scope and clear description of the change.
- Relevant tests or fixtures when behavior changes, plus the validation performed.
- Only changes needed for the issue; avoid unrelated refactors.
- Required, intentional build output only. Do not include ZIPs, `node_modules`, caches, or other generated files that are not part of the project.

## Commit and repository hygiene

- Do not commit `node_modules/`, ZIP archives, caches, local paths, or secrets.
- Keep documentation changes relevant to the contribution.
- Do not include unrelated file changes.

## License

Contributions are accepted under [GPL-2.0-or-later](LICENSE). No separate contributor license agreement is required.
