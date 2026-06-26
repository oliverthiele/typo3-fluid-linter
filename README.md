# TYPO3 Fluid Linter — Static analysis for Fluid templates

A CLI linter for TYPO3 Fluid templates that catches encoding errors, deprecated syntax, and Fluid 5 breaking changes before they reach production.

[![Packagist Version](https://img.shields.io/packagist/v/oliver-thiele/typo3-fluid-linter.svg)](https://packagist.org/packages/oliver-thiele/typo3-fluid-linter)
[![PHP](https://img.shields.io/packagist/dependency-v/oliver-thiele/typo3-fluid-linter/php.svg)](https://php.net/)
[![License](https://img.shields.io/packagist/l/oliver-thiele/typo3-fluid-linter.svg)](LICENSE)
[![Changelog](https://img.shields.io/badge/Changelog-CHANGELOG.md-blue.svg)](CHANGELOG.md)

---

## Why TYPO3 Fluid Linter?

No dedicated linter for TYPO3 Fluid templates exists. Generic HTML linters do not understand Fluid syntax, and TYPO3's own rendering engine only catches errors at runtime — often in production. This tool runs as part of your CI/CD pipeline and catches entire classes of mistakes that are otherwise invisible until a page breaks.

### Designed for AI-assisted development

This tool was created to catch a specific class of errors that LLMs introduce when generating Fluid templates. Language models produce correct Fluid logic reliably, but they occasionally introduce Unicode encoding artifacts that are nearly invisible in most editors: a `"` (U+201C, left double quotation mark) instead of a straight `"`, a `″` (U+2033, double prime / inch mark) as an attribute delimiter, or `«value»` (angle quotation marks) as an attribute value. A human developer would never write `class=«container»` — but a language model can, especially when the surrounding context contains measurement data, quotation-heavy text, or content in languages that use these characters as standard punctuation.

The alternative — asking a second LLM to review every generated template for encoding errors — is slower, costs tokens, and is non-deterministic. A static linter is faster, deterministic, and integrates into CI with a single command.

---

## Features

- **Invalid attribute quote detection** — flags non-ASCII quote characters used as attribute value delimiters: typographic quotes (U+201C `"`, U+201D `"`, U+2018 `'`, U+2019 `'`, U+201A `‚`, U+201E `„`), backtick (U+0060 `` ` ``), prime/inch marks (U+2032 `′`, U+2033 `″`), angle quotes (U+00AB `«`, U+00BB `»`, U+2039 `‹`, U+203A `›`); text content between tags (e.g. `1/2″` inside `<f:case>`) is not flagged
- **Missing namespace attribute** — detects `<html>` tags with a Fluid xmlns declaration that are missing `data-namespace-typo3-fluid="true"`, which causes duplicate `<html>` elements in the rendered output
- **Invalid namespace URI** — `https://typo3.org/ns/` throws a runtime exception; the correct prefix is `http://typo3.org/ns/`
- **Fluid 5 compatibility** — detects variable names starting with an underscore, which are forbidden in Fluid 5 (TYPO3 v14)
- **Deprecated syntax** — flags empty `parseFuncTSPath=""`, which causes a runtime error; use `{field -> f:format.html()}` instead
- **CDATA section detection** — flags `<![CDATA[...]]>` inside `<f:comment>` blocks, the old pattern for safely commenting out Fluid code; deprecated in Fluid 4 and removed in Fluid 5; legitimate CDATA in XML/RSS templates and `{{{expression}}}` output syntax are not flagged
- **XML declaration warning** — flags the `<?xml ...?>` processing instruction, which is unnecessary in Fluid templates and may trigger Quirks Mode
- **Debug ViewHelper detection** — flags `<f:debug>` usage to prevent debug output from reaching production; configurable as `warning` (development) or `error` (live gate)
- **Deprecated ViewHelper detection** — flags ViewHelpers and arguments that were deprecated or removed in a specific TYPO3 version: all `<f:widget.*>` (removed v11), `getVars` on `<be:moduleLayout.button.shortcutButton>` (deprecated v11), `<f:be.container>` (deprecated v11.3), `<f:be.buttons.shortcut>` and `<f:base>` (removed v12), `<f:be.buttons.csh>` and `<f:be.labels.csh>` (removed v13), `<f:debug.render>` and `useNonce` on `<f:asset.*>` (deprecated v14.2); requires `--typo3-version=<major>`
- **Config file support** — per-project `.fluid-lint.php` with rule severity overrides; separate live config for CI release gates
- **Version-aware rules** — pass `--typo3-version=14` to activate rules specific to a TYPO3 major version
- **Three severity levels** — `error` (exit code 1), `warning`, and `info` (both exit code 0)
- **GitHub Actions integration** — `--format=github` emits inline annotations with correct severity levels directly in pull request diffs
- **Zero TYPO3 dependency** — runs standalone in any PHP 8.2+ environment; no TYPO3 core required

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP         | >=8.2   |

---

## Installation

```bash
composer require --dev oliver-thiele/typo3-fluid-linter
```

---

## Usage

Lint a directory recursively:

```bash
vendor/bin/fluid-lint packages/my_extension/Resources/Private/
```

Lint a single file:

```bash
vendor/bin/fluid-lint path/to/Template.html
```

Lint multiple paths:

```bash
vendor/bin/fluid-lint templates/ partials/ layouts/
```

Lint with TYPO3 version-specific rules:

```bash
vendor/bin/fluid-lint --typo3-version=14 packages/
```

### Config file

Copy `.fluid-lint.php` to your project root. The linter auto-detects it on every run:

```bash
vendor/bin/fluid-lint packages/
```

For a stricter live/release gate (e.g. block on `f:debug`):

```bash
vendor/bin/fluid-lint --config=.fluid-lint.live.php packages/
```

Example `.fluid-lint.php` (development — `f:debug` is a warning):

```php
<?php
use OliverThiele\FluidLinter\Config\LintConfig;

return (new LintConfig())
    ->rule('debug-viewhelper', severity: 'warning');
```

Example `.fluid-lint.live.php` (production — `f:debug` blocks the pipeline):

```php
<?php
use OliverThiele\FluidLinter\Config\LintConfig;

return (new LintConfig())
    ->rule('debug-viewhelper', severity: 'error');
```

### GitHub Actions

```yaml
- name: Lint Fluid templates
  run: vendor/bin/fluid-lint --format=github --typo3-version=13 packages/
```

The `--format=github` flag emits `::error`, `::warning`, and `::notice` annotations that GitHub Actions renders as inline comments in pull requests.

For a release gate that also blocks on `f:debug`:

```yaml
- name: Lint Fluid templates (live gate)
  run: vendor/bin/fluid-lint --format=github --config=.fluid-lint.live.php packages/
```

---

## CLI

```
Usage: fluid-lint [--format=github] [--config=<file>] [--typo3-version=<major>] <path> [<path>...]

Options:
  --format=github          Emit GitHub Actions annotation format instead of console output
  --config=<file>          Load rule configuration from a PHP file returning a LintConfig instance
  --typo3-version=<major>  Activate version-specific rules (e.g. --typo3-version=14)

Config file resolution (first match wins):
  1. --config=<file> (explicit path)
  2. .fluid-lint.php in the current working directory
  3. Built-in defaults (all rules enabled, default severities)

Exit codes:
  0    No errors (warnings and infos do not affect the exit code)
  1    One or more errors found, or path not found
```

---

## Rules

| Rule | ID | Severity | Version |
|------|----|----------|---------|
| Invalid quote character as attribute delimiter (typographic, backtick, prime/inch, angle) | `typographic-quotes` | error | all |
| Missing `data-namespace-typo3-fluid="true"` on `<html>` tag | `html-namespace-attribute` | error | all |
| `https://` in Fluid namespace URI throws an exception | `https-namespace` | error | all |
| Fluid variable name starts with underscore (forbidden in Fluid 5) | `underscore-variable` | error | 14+ |
| Empty `parseFuncTSPath=""` causes a runtime error | `parsefunc-tspath` | error | all |
| `<![CDATA[` inside `<f:comment>` — deprecated in Fluid 4, removed in Fluid 5 | `cdata-section` | error | all |
| `<?xml ...?>` processing instruction is unnecessary in Fluid templates | `xml-declaration` | warning | all |
| `f:debug` ViewHelper found — remove before going live | `debug-viewhelper` | warning | all |
| Both `.html` and `.fluid.html` counterpart exist | `fluid-file-extension` | warning | 14+ |
| Deprecated or removed ViewHelper/argument — `f:widget.*` (removed v11), `getVars` arg (deprecated v11), `f:be.container` (deprecated v11.3), `f:be.buttons.shortcut` + `f:base` (removed v12), `f:be.buttons.csh` + `f:be.labels.csh` (removed v13), `f:debug.render` + `useNonce` (deprecated v14.2) | `deprecated-viewhelper` | error / warning | 11+ |

---

## License

GPL-2.0-or-later — see [LICENSE](LICENSE)

---

## Author

Oliver Thiele — [oliver-thiele.de](https://www.oliver-thiele.de)