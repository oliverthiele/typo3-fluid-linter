# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.6.1] — 2026-07-02

### Fixed

- `bin/fluid-lint --fix`: fix-shadowing — when a file had violations from two different rules, the second rule's `fix()` operated on already-modified file content; now only one fix is applied per file per run (break after first `Applied`); re-run fluid-lint to apply further fixes
- `bin/fluid-lint --fix`: exit-code granularity — a file with one fixed and one unfixed error previously exited `0` because the whole file was marked as fixed; `$fixedRules` now tracks `(filePath, ruleName)` pairs so unfixed errors in the same file still contribute to exit code `1`
- `bin/fluid-lint --fix`: stdout pollution — `FIXED`/`SKIP` progress lines were written to stdout, corrupting `--format=json` output and confusing `--format=github` annotations; all fix progress output now goes to stderr
- `bin/fluid-lint --fix`: replaced `O(N²)` `in_array($fixedFiles)` check with `O(1)` `isset()` on the `$fixedRules` map
- `TypographicQuotesRule`: only the first invalid quote delimiter per line was reported (`preg_match`); now all of them are (`preg_match_all`) — e.g. a tag with both `src` and `alt` using bad delimiters previously hid the second violation

### Changed

- README/TODO: documented the complementary relationship with TYPO3 core's `typo3 fluid:analyze` command (AST-based syntax/deprecation check, requires a bootable TYPO3 instance and `*.fluid.*` files) — this tool remains necessary for standalone extension repos and lightweight CI without a full TYPO3 install

## [0.6.0] — 2026-06-26

### Added

- `TypographicQuotesRule`: extended to also detect typographic quotes after `:` in Fluid inline ViewHelper argument syntax (e.g. `{var -> f:format.html(class: «value»)}`); previously only `=` was checked, missing the inline argument separator entirely; violation message updated from "attribute delimiter" to "value delimiter"
- `XmlDeclarationRule`: implements `FixableFileRuleInterface` — `--fix` removes the `<?xml ...?>` processing instruction line from the file (safe, no `--allow-risky` needed)
- `ParseFuncTSPathRule`: extended to detect empty `parseFuncTSPath` in both tag syntax (`parseFuncTSPath=""`) and Fluid inline syntax (`parseFuncTSPath: ''`); `--fix` removes the attribute/argument in both forms, with correct comma handling for inline calls that have other arguments alongside it (safe, no `--allow-risky` needed)
- `HtmlNamespaceAttributeRule`: implements `FixableFileRuleInterface` — `--fix` inserts `data-namespace-typo3-fluid="true"` before the closing `>` of the `<html>` opening tag (safe, no `--allow-risky` needed)
- `HttpsNamespaceRule`: implements `FixableFileRuleInterface` — `--fix` replaces all occurrences of `https://typo3.org/ns/` with `http://typo3.org/ns/` in-place (safe, no `--allow-risky` needed)
- `FluidFileExtensionRule`: `info` migration hint now only fires when the same directory already contains at least one `.fluid.html` file — signals an actively migrating project; previously always-silent directories stay silent
- `FluidFileExtensionRule`: implements `FixableFileRuleInterface` — `--fix` renames `.html` → `.fluid.html` (safe); `--fix --allow-risky` additionally deletes orphaned `.html` files when a `.fluid.html` counterpart already exists (destructive, requires explicit opt-in)
- `FixableFileRuleInterface` — new interface for rules that can automatically correct their own violations
- `FixResult` / `FixStatus` — value objects returned by `fix()` (Applied, Skipped, None)
- `Linter::getRules()` — returns the registered rule list for use by fix orchestration in `bin/fluid-lint`
- `JsonReporter` — `--format=json` emits structured JSON to stdout; shape: `{ "violations": [...], "summary": { "errors", "warnings", "infos", "files_checked", "files_with_violations" } }`; useful for IDE integrations and pre-commit hooks
- `TODO.md` — captures planned features, new rule ideas, ideas from compared linters, and future format/config extensions

### Changed

- README: corrected the "Why" section — existing AST-based Fluid linters (Fluid.Lint, fluid-lint) do exist; this tool takes a different, zero-dependency approach targeting encoding artifacts, deprecated ViewHelpers, and Fluid 5 changes

## [0.5.0] — 2026-06-26

## [0.5.0] — 2026-06-26

### Added

- Add `DeprecatedViewHelperRule` — detects ViewHelpers and arguments that were deprecated or removed in a specific TYPO3 version; requires `--typo3-version=<major>`; each entry references the TYPO3 changelog URL as a source comment:
  - `<f:widget.*>` — all Fluid widget ViewHelpers were completely removed in TYPO3 v11; for pagination use the PHP `PaginationInterface` API (error)
  - `getVars` argument on `<be:moduleLayout.button.shortcutButton>` — deprecated in TYPO3 v11; use `arguments` instead (warning)
  - `<f:be.container>` — deprecated in TYPO3 v11.3; use `<f:be.pageRenderer>` (warning)
  - `<f:be.buttons.shortcut>` — removed in TYPO3 v12 (deprecated in v11); use `<be:moduleLayout.button.shortcutButton arguments="...">` (error)
  - `<f:base>` — removed in TYPO3 v12 (deprecated in v11.3); use TypoScript `config.baseURL` (error)
  - `<f:be.buttons.csh>` — removed in TYPO3 v13 (error)
  - `<f:be.labels.csh>` — removed in TYPO3 v13 (error)
  - `<f:debug.render>` — deprecated in TYPO3 v14.2, removal planned for v15; create a custom ViewHelper if needed (warning)
  - `useNonce` argument on `<f:asset.*>` — deprecated in TYPO3 v14.2; use `csp="1"` instead (warning)

## [0.4.0] — 2026-06-26

### Fixed

- `TypographicQuotesRule`: extended to cover all non-ASCII quote characters that may appear as attribute delimiters — backtick (U+0060), prime/inch marks (U+2032, U+2033), angle quotes (U+00AB, U+00BB, U+2039, U+203A), all four typographic quote variants (U+2018–U+201E); violation message now includes the specific character and its Unicode code point; text content between tags is not flagged
- `UnderscoreVariableRule`: now correctly implements `VersionedRuleInterface` with minimum TYPO3 version 14 — the rule was previously active for all TYPO3 versions, causing false positives on v13 projects where underscore-prefixed variables are valid
- Deleted stray `src/Rule/fluid-lint` file that was accidentally placed in the wrong directory

### Added

- Add PHPUnit 11 test suite (`composer phpunit`) covering all nine rules with positive, negative, and false-positive cases; every character in `TypographicQuotesRule`'s pattern has a dedicated test case so accidental removals from the character set are caught immediately

## [0.3.0] — 2026-06-25

### Added

- Add `CdataSectionRule` — detects `<![CDATA[...]]>` inside `<f:comment>` blocks, the old pattern for safely commenting out Fluid syntax; deprecated in Fluid 4 and removed in Fluid 5 / TYPO3 v14; legitimate CDATA in XML/RSS templates and Fluid 5's `{{{expression}}}` output syntax are not flagged (`cdata-section`, severity: error)

## [0.2.0] — 2026-06-25

### Added

- Add `XmlDeclarationRule` — warns when `<?xml ...?>` processing instruction is present; unnecessary in Fluid templates and may trigger Quirks Mode in older browsers (`xml-declaration`, severity: warning)
- Add `DebugViewHelperRule` — detects `<f:debug>` and inline `-> f:debug()` syntax to prevent debug output from reaching production (`debug-viewhelper`, severity: warning by default, configurable as error in live config)
- Add `LintConfig` class with fluent builder API for per-project rule configuration — override severity or disable rules via a PHP config file
- Add `--config=<file>` CLI flag to load a project-specific `LintConfig` instance from a PHP file
- Add auto-detection of `.fluid-lint.php` in the current working directory when no `--config` flag is given
- Add example config files: `.fluid-lint.php` (development) and `.fluid-lint.live.php` (production release gate)

## [0.1.0] — 2026-06-25

### Added

- Add `TypographicQuotesRule` — detects U+201C/U+201D/U+2018/U+2019 used as attribute delimiters (straight ASCII quotes required)
- Add `HtmlNamespaceAttributeRule` — detects `<html>` tags with Fluid xmlns declarations missing `data-namespace-typo3-fluid="true"`, which causes duplicate `<html>` output
- Add `HttpsNamespaceRule` — detects `https://typo3.org/ns/` in xmlns declarations; Fluid throws a runtime exception for this prefix (source: `NAMESPACEPREFIX_INVALID` in `typo3fluid/fluid`)
- Add `UnderscoreVariableRule` — detects Fluid variable names starting with underscore, forbidden in Fluid 5 / TYPO3 v14 (`{_all}` is whitelisted as a Fluid-internal variable)
- Add `ParseFuncTSPathRule` — detects empty `parseFuncTSPath=""` which causes a runtime error; non-empty values like `parseFuncTSPath="lib.parseFunc_RTE"` are not flagged
- Add `FluidFileExtensionRule` — detects coexisting `.html` and `.fluid.html` files; TYPO3 v14 prefers `.fluid.html` and may load the wrong file (active only with `--typo3-version=14`)
- Add `RuleInterface` for line-by-line rules
- Add `FileRuleInterface` for rules that require full file content (supports multi-line constructs)
- Add `VersionedRuleInterface` — rules declare minimum and maximum TYPO3 major version
- Add `ConsoleReporter` with severity labels (`ERROR`, `WARN`, `INFO`) and grouped summary
- Add `GithubActionsReporter` — maps severities to `::error`, `::warning`, `::notice` annotations
- Add `bin/fluid-lint` CLI with `--format=github` and `--typo3-version=<major>` flags
- Exit code `1` only on `error` severity; `warning` and `info` exit with `0`

[Unreleased]: https://github.com/oliverthiele/typo3-fluid-linter/compare/0.6.1...HEAD
[0.6.1]: https://github.com/oliverthiele/typo3-fluid-linter/compare/0.6.0...0.6.1
[0.6.0]: https://github.com/oliverthiele/typo3-fluid-linter/compare/0.5.0...0.6.0
[0.5.0]: https://github.com/oliverthiele/typo3-fluid-linter/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/oliverthiele/typo3-fluid-linter/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/oliverthiele/typo3-fluid-linter/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/oliverthiele/typo3-fluid-linter/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/oliverthiele/typo3-fluid-linter/releases/tag/0.1.0