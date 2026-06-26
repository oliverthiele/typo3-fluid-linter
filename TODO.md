# TODO — TYPO3 Fluid Linter

## In progress / next

- [x] `fluid-file-extension`: `info` when `.html` exists without counterpart AND same directory already has `.fluid.html` files (project is actively migrating)
- [x] `--format=json` reporter — structured output for IDE integrations and external tooling
- [x] `--fix` / `--allow-risky` — rename (safe) and delete (risky) for `fluid-file-extension`

## Config API

- [ ] `skipPaths(['path/to/Legacy/'])` — exclude directories or glob patterns from linting
- [ ] `withoutRules(['rule-id', ...])` — batch disable; convenience wrapper around multiple `disableRule()` calls
- [ ] Per-file rule overrides via inline comments: `{# fluid-lint-disable typographic-quotes #}` (like eslint-disable)

## New rules

- [ ] **unused-namespace** — detect `{namespace v=...}` or `xmlns:v="..."` declarations where no `v:` ViewHelper is used in the file; reduces parse overhead and noise
- [ ] **inline-debug** — catch `{variable -> f:debug()}` inline syntax; the current `debug-viewhelper` rule only catches `<f:debug>` tag form
- [ ] **missing-fluid-namespace** — warn if the file uses `f:` ViewHelpers but has no `xmlns:f` or `{namespace f=...}` declaration (only relevant for standalone partials/layouts, not EXT: paths)
- [ ] **core-viewhelper-required-args** — flag `<f:form.textbox />` without `property` or `name`, `<f:image />` without `src`, etc.; these throw runtime exceptions; needs a maintained list of required arguments per core ViewHelper
- [ ] **deprecated-viewhelper**: extend entries as new TYPO3 deprecations are published in each minor/major release

## Output formats

- [ ] `--format=json` — structured JSON for IDE plugins, pre-commit hooks, and custom tooling *(in progress)*
- [ ] `--format=checkstyle` — XML format compatible with Jenkins, SonarQube, and PhpStorm's external tool inspection import
- [ ] `--format=junit` — JUnit XML for CI systems that expect test result format (e.g. GitLab CI artifacts)

## Ideas from compared linters

Compared against `fluid-components-linter`, `fluid-lint`, and `Fluid.Lint` (see `temp/`):

- **AST-based syntax check** (`Fluid.Lint` / `fluid-lint` approach): actual Fluid template parsing via `typo3fluid/fluid` to catch unclosed tags, malformed expressions, invalid nesting. Would require adding `typo3fluid/fluid` as an optional dependency or a separate `fluid-lint-ast` package. Breaking change for "zero dependency" promise.
- **Component slot validation** (`fluid-components-linter` approach): verify that `<fc:component>` slots are correctly used. Only relevant for projects using `sitegeist/fluid-components` — candidate for an optional plugin rule.

## Ideas for 1.0

- [ ] VS Code extension / PhpStorm plugin using `--format=json`
- [ ] Pre-commit hook example (`lint-staged` or `.pre-commit-config.yaml`)
- [ ] Watch mode (`--watch`) for continuous linting during template editing
- [ ] Exit code `2` for warnings (separate from `1` for errors) — opt-in via `--strict`

## DeprecatedViewHelperRule — entries to add (upcoming TYPO3 versions)

Track new deprecations at https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog-13-combined.html
and https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog-14-combined.html

- [ ] Review v13 combined changelog for any missed deprecations/removals
- [ ] Review v14 combined changelog for any missed deprecations/removals
- [ ] Add a CONTRIBUTING note: every new TYPO3 deprecation that affects Fluid templates → new ENTRIES entry with source URL