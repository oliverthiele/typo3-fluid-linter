<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Rule\DeprecatedViewHelperRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DeprecatedViewHelperRule.
 *
 * Covers both removed ViewHelpers (severity: error) and deprecated-but-still-working
 * ViewHelpers/arguments (severity: warning). Version gating (since field) is tested
 * inline here; the Linter-level VersionedRuleInterface gating is tested separately in
 * LinterVersionGatingTest.
 *
 * One no-violation test per entry covers the version boundary (version = since - 1).
 * One violation test per entry covers the version where the entry activates.
 */
final class DeprecatedViewHelperRuleTest extends TestCase
{
    // --- No violation expected ---

    #[Test]
    #[DataProvider('validLines')]
    public function checkFileReturnsNoViolationFor(string $description, string $content, int $typo3Version): void
    {
        $rule = new DeprecatedViewHelperRule($typo3Version);
        $violations = $rule->checkFile($content, 'dummy.html');

        self::assertSame([], $violations, $description);
    }

    public static function validLines(): array
    {
        return [
            // Version boundary: entries must not fire below their since version
            'f:widget.paginate on v10 (removed in v11)' => [
                'f:widget.* was removed in v11 — must not flag on v10',
                '<f:widget.paginate objects="{items}" as="paginatedItems">',
                10,
            ],
            'f:be.buttons.shortcut on v11 (removed in v12)' => [
                'f:be.buttons.shortcut was removed in v12 — must not flag on v11',
                '<f:be.buttons.shortcut />',
                11,
            ],
            'getVars on v10 (deprecated in v11)' => [
                'getVars was deprecated in v11 — must not flag on v10',
                '<be:moduleLayout.button.shortcutButton getVars="tt_content" />',
                10,
            ],
            'f:be.container on v10 (deprecated in v11.3)' => [
                'f:be.container was deprecated in v11.3 — must not flag on v10',
                '<f:be.container>',
                10,
            ],
            'f:base on v11 (removed in v12)' => [
                'f:base was removed in v12 — must not flag on v11',
                '<f:base href="https://example.com/" />',
                11,
            ],
            'f:be.buttons.csh on v12 (removed in v13)' => [
                'f:be.buttons.csh was removed in v13 — must not flag on v12',
                '<f:be.buttons.csh table="tt_content" field="header" />',
                12,
            ],
            'f:be.labels.csh on v12 (removed in v13)' => [
                'f:be.labels.csh was removed in v13 — must not flag on v12',
                '<f:be.labels.csh table="tt_content" field="header" />',
                12,
            ],
            'f:debug.render on v13 (only deprecated in v14)' => [
                'f:debug.render was only deprecated in v14 — must not flag on v13',
                '<f:debug.render value="{foo}" />',
                13,
            ],
            'useNonce on v13 (only deprecated in v14)' => [
                'useNonce was only deprecated in v14 — must not flag on v13',
                '<f:asset.script useNonce="1" src="..." />',
                13,
            ],

            // False positives: superficially similar patterns that must not match
            'f:be.buttons.save (not shortcut)' => [
                'f:be.buttons.save is a different ViewHelper — must not be flagged',
                '<f:be.buttons.save />',
                11,
            ],
            'attribute containing useNonce as a word inside a string value' => [
                'the word "useNonce" inside a comment or string value must not be flagged',
                '<f:comment><!-- legacy: useNonce was removed --></f:comment>',
                14,
            ],
            'f:asset.css with csp instead of useNonce' => [
                'csp="1" is the replacement for useNonce — must not be flagged',
                '<f:asset.css csp="1" href="..." />',
                14,
            ],
        ];
    }

    // --- Violations expected ---

    #[Test]
    #[DataProvider('violatingContent')]
    public function checkFileReturnsViolationFor(
        string $description,
        string $content,
        int $typo3Version,
        string $expectedMessageFragment,
        string $expectedSeverity,
        int $expectedLine,
    ): void {
        $rule = new DeprecatedViewHelperRule($typo3Version);
        $violations = $rule->checkFile($content, 'dummy.html');

        self::assertCount(1, $violations, $description);
        self::assertSame($expectedLine, $violations[0]['line'], $description . ' — line number');
        self::assertStringContainsString(
            $expectedMessageFragment,
            $violations[0]['message'],
            $description . ' — message fragment',
        );
        self::assertSame($expectedSeverity, $violations[0]['severity'], $description . ' — severity');
    }

    public static function violatingContent(): array
    {
        return [
            // Removed in v11 — severity: error
            'f:widget.paginate removed in v11' => [
                'f:widget.paginate must be reported as error on v11+',
                '<f:widget.paginate objects="{items}" as="paginatedItems">',
                11,
                'f:widget.*',
                'error',
                1,
            ],
            'f:widget.autocomplete removed in v11' => [
                'f:widget.autocomplete must be reported as error on v11+ (was already deprecated in v10.4)',
                '<f:widget.autocomplete for="search" searchProperty="title" />',
                11,
                'f:widget.*',
                'error',
                1,
            ],

            // Deprecated in v11 — severity: warning
            'getVars deprecated in v11 — on be:moduleLayout.button.shortcutButton' => [
                'getVars argument must be reported as warning on v11+',
                '<be:moduleLayout.button.shortcutButton getVars="tt_content" />',
                11,
                'getVars',
                'warning',
                1,
            ],

            // Removed in v12 — severity: error
            'f:be.buttons.shortcut removed in v12' => [
                'f:be.buttons.shortcut must be reported as error on v12+',
                '<f:be.buttons.shortcut />',
                12,
                'f:be.buttons.shortcut',
                'error',
                1,
            ],
            'f:be.container deprecated in v11.3' => [
                'f:be.container must be reported as warning on v11+',
                '<f:be.container>',
                11,
                'f:be.container',
                'warning',
                1,
            ],

            // Removed in v12 — severity: error
            'f:base removed in v12 — self-closing' => [
                'f:base must be reported as error on v12+',
                '<f:base href="https://example.com/" />',
                12,
                'f:base',
                'error',
                1,
            ],
            'f:base removed in v12 — with attributes on next line' => [
                'f:base with attributes on next line must be caught',
                "<f:base\n    href=\"https://example.com/\"\n/>",
                12,
                'f:base',
                'error',
                1,
            ],

            // Removed in v13 — severity: error
            'f:be.buttons.csh removed in v13 — self-closing' => [
                'f:be.buttons.csh must be reported as error on v13+',
                '<f:be.buttons.csh table="tt_content" field="header" />',
                13,
                'f:be.buttons.csh',
                'error',
                1,
            ],
            'f:be.buttons.csh removed in v13 — with whitespace after tag name' => [
                'f:be.buttons.csh with attributes on next line must be caught',
                "<f:be.buttons.csh\n    table=\"tt_content\"\n/>",
                13,
                'f:be.buttons.csh',
                'error',
                1,
            ],
            'f:be.labels.csh removed in v13' => [
                'f:be.labels.csh must be reported as error on v13+',
                '<f:be.labels.csh table="tt_content" field="header" />',
                13,
                'f:be.labels.csh',
                'error',
                1,
            ],

            // Deprecated in v14 — severity: warning
            'f:debug.render deprecated in v14' => [
                'f:debug.render must be reported as warning on v14+',
                '<f:debug.render value="{myVar}" />',
                14,
                'f:debug.render',
                'warning',
                1,
            ],
            'useNonce deprecated in v14 — on f:asset.script' => [
                'useNonce on f:asset.script must be reported as warning on v14+',
                '<f:asset.script useNonce="1" src="..." />',
                14,
                'useNonce',
                'warning',
                1,
            ],
            'useNonce deprecated in v14 — on f:asset.css' => [
                'useNonce on f:asset.css must be reported as warning on v14+',
                '<f:asset.css useNonce="1" href="..." />',
                14,
                'useNonce',
                'warning',
                1,
            ],

            // Line number is preserved for a multi-line file
            'violation on line 3 is reported with correct line number' => [
                'line number must match the actual line in the file, not always 1',
                "<html>\n<body>\n<f:debug.render value=\"{foo}\" />\n</body>",
                14,
                'f:debug.render',
                'warning',
                3,
            ],
        ];
    }

    #[Test]
    public function allNineEntriesProduceViolations(): void
    {
        $rule = new DeprecatedViewHelperRule(14);
        $content = implode("\n", [
            '<f:widget.paginate objects="{items}" as="paginatedItems">',
            '<be:moduleLayout.button.shortcutButton getVars="tt_content" />',
            '<f:be.buttons.shortcut />',
            '<f:be.container>',
            '<f:base href="https://example.com/" />',
            '<f:be.buttons.csh />',
            '<f:be.labels.csh />',
            '<f:debug.render />',
            '<f:asset.script useNonce="1" src="..." />',
        ]);

        $violations = $rule->checkFile($content, 'dummy.html');

        self::assertCount(9, $violations, 'All nine ENTRIES must produce a violation');
    }
}
