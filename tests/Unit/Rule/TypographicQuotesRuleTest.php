<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Rule\TypographicQuotesRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TypographicQuotesRule.
 *
 * The rule was created because LLMs occasionally produce non-ASCII quote characters as
 * HTML/Fluid attribute delimiters — characters that editors display visually similar to
 * straight quotes but cause silent parse failures or unexpected behavior at runtime.
 *
 * Every character in the PATTERN constant must have a dedicated violation test case here.
 * If a character is removed from the pattern, the corresponding test will fail — which is
 * intentional: removals must be deliberate and documented, not silent.
 */
final class TypographicQuotesRuleTest extends TestCase
{
    private TypographicQuotesRule $rule;

    protected function setUp(): void
    {
        $this->rule = new TypographicQuotesRule();
    }

    // --- No violation expected ---

    #[Test]
    #[DataProvider('linesWithNoViolation')]
    public function checkReturnsNoViolationFor(string $description, string $line): void
    {
        self::assertSame([], $this->rule->check($line, 1), $description);
    }

    public static function linesWithNoViolation(): array
    {
        return [
            // Correct delimiters — must never be flagged
            'straight double quote as attribute delimiter' => [
                'straight double quote as attribute delimiter',
                '<tag class="value">',
            ],
            'straight single quote as attribute delimiter' => [
                'straight single quote as attribute delimiter',
                "<tag class='value'>",
            ],
            'straight quotes in inline ViewHelper argument' => [
                'straight quotes in inline ViewHelper argument — must not be flagged',
                "{bodytext -> f:format.html(parseFuncTSPath: 'lib.parseFunc_RTE')}",
            ],

            // The key false-positive cases: same non-ASCII characters appearing in TEXT CONTENT
            // (between tags), not as attribute delimiters. These are the cases that motivated
            // the [=:]\s*[chars] approach — text content between tags is never directly preceded
            // by = or :.
            'inch mark in text content' => [
                'inch mark (U+2033) in text content — must not be flagged',
                "<f:case value=\"1\">1/2\u{2033}</f:case>",
            ],
            'inch mark inside correctly-quoted attribute value' => [
                'inch mark (U+2033) inside a straight-quoted attribute value — must not be flagged',
                "<img alt=\"5 ft 2\u{2033}\">",
            ],
            'prime inside correctly-quoted attribute value' => [
                'prime (U+2032) inside a straight-quoted attribute value — must not be flagged',
                "<span title=\"6\u{2032} tall\">",
            ],
            'angle quotes in paragraph text without preceding colon' => [
                'angle quotes (U+00AB, U+00BB) in paragraph text without colon before — must not be flagged',
                "<p>\u{00AB}quoted text\u{00BB}</p>",
            ],
            'typographic quotes in paragraph text without preceding colon' => [
                'typographic quotes (U+201C, U+201D) in paragraph text — must not be flagged',
                "<p>\u{201C}He said so.\u{201D}</p>",
            ],
            'german low-9 quotes in paragraph text without preceding colon' => [
                'German low-9 quotes (U+201E, U+201C) in paragraph text — must not be flagged',
                "<p>\u{201E}Hallo\u{201C}</p>",
            ],

            // Edge cases
            'empty line' => ['empty line', ''],
            'no attributes' => ['tag with no attributes', '<p>Hello world</p>'],
            'comment line' => ['HTML comment', '<!-- class="foo" -->'],
        ];
    }

    // --- Violation expected (tag-based attribute syntax) ---

    #[Test]
    #[DataProvider('linesWithViolation')]
    public function checkReturnsViolationFor(
        string $description,
        string $line,
        string $expectedCharacter,
        int $expectedCodePoint,
    ): void {
        $violations = $this->rule->check($line, 3);

        self::assertCount(1, $violations, $description);
        self::assertSame(3, $violations[0]['line'], $description);
        self::assertStringContainsString(
            $expectedCharacter,
            $violations[0]['message'],
            $description . ' — message must contain the offending character',
        );
        self::assertStringContainsString(
            sprintf('U+%04X', $expectedCodePoint),
            $violations[0]['message'],
            $description . ' — message must contain the Unicode code point',
        );
    }

    public static function linesWithViolation(): array
    {
        // One entry per character in the PATTERN constant.
        // If you add a character to PATTERN, add a test case here.
        // If a test case fails after a PATTERN change, the removal was not intentional.
        return [
            'U+0060 GRAVE ACCENT / backtick' => [
                'backtick as attribute delimiter — common in shell scripts, LLMs sometimes confuse contexts',
                '<tag class=`value`>',
                '`',
                0x0060,
            ],
            'U+00AB LEFT-POINTING DOUBLE ANGLE QUOTATION MARK' => [
                "left angle quote \u{00AB} — LLMs generate this in French/German text contexts",
                "<tag class=\u{00AB}value\u{00BB}>",
                "\u{00AB}",
                0x00AB,
            ],
            'U+00BB RIGHT-POINTING DOUBLE ANGLE QUOTATION MARK' => [
                "right angle quote \u{00BB} as opening delimiter",
                "<tag class=\u{00BB}value>",
                "\u{00BB}",
                0x00BB,
            ],
            'U+2018 LEFT SINGLE QUOTATION MARK' => [
                "left single typographic quote \u{2018} — the classic LLM quote confusion",
                "<tag class=\u{2018}value\u{2019}>",
                "\u{2018}",
                0x2018,
            ],
            'U+2019 RIGHT SINGLE QUOTATION MARK' => [
                "right single typographic quote \u{2019} as opening delimiter",
                "<tag class=\u{2019}value>",
                "\u{2019}",
                0x2019,
            ],
            'U+201A SINGLE LOW-9 QUOTATION MARK' => [
                "single low-9 quote \u{201A} — German opening single quote",
                "<tag class=\u{201A}value\u{2018}>",
                "\u{201A}",
                0x201A,
            ],
            'U+201C LEFT DOUBLE QUOTATION MARK' => [
                "left double typographic quote \u{201C} — the original motivation for this rule",
                "<tag class=\u{201C}value\u{201D}>",
                "\u{201C}",
                0x201C,
            ],
            'U+201D RIGHT DOUBLE QUOTATION MARK' => [
                "right double typographic quote \u{201D} as opening delimiter",
                "<tag class=\u{201D}value>",
                "\u{201D}",
                0x201D,
            ],
            'U+201E DOUBLE LOW-9 QUOTATION MARK' => [
                "double low-9 quote \u{201E} — German opening double quote",
                "<tag class=\u{201E}value\u{201C}>",
                "\u{201E}",
                0x201E,
            ],
            'U+2032 PRIME (foot mark)' => [
                'prime as delimiter — appears when LLM generates measurement-heavy content',
                "<tag alt=\u{2032}6ft\u{2032}>",
                "\u{2032}",
                0x2032,
            ],
            'U+2033 DOUBLE PRIME (inch mark)' => [
                'double prime as delimiter — appears when LLM generates measurement data',
                "<tag alt=\u{2033}5in\u{2033}>",
                "\u{2033}",
                0x2033,
            ],
            'U+2039 SINGLE LEFT-POINTING ANGLE QUOTATION MARK' => [
                "single left angle quote \u{2039}",
                "<tag class=\u{2039}value\u{203A}>",
                "\u{2039}",
                0x2039,
            ],
            'U+203A SINGLE RIGHT-POINTING ANGLE QUOTATION MARK' => [
                "single right angle quote \u{203A} as opening delimiter",
                "<tag class=\u{203A}value>",
                "\u{203A}",
                0x203A,
            ],
        ];
    }

    // --- Violation expected (Fluid inline ViewHelper argument syntax) ---

    #[Test]
    #[DataProvider('inlineLinesWithViolation')]
    public function checkReturnsViolationForInlineSyntax(
        string $description,
        string $line,
        string $expectedCharacter,
    ): void {
        $violations = $this->rule->check($line, 1);

        self::assertCount(1, $violations, $description);
        self::assertStringContainsString(
            $expectedCharacter,
            $violations[0]['message'],
            $description . ' — message must contain the offending character',
        );
    }

    public static function inlineLinesWithViolation(): array
    {
        return [
            'left double quote after colon in inline argument' => [
                "LLM generates {var -> f:format.html(class: \u{201C}value\u{201D})} with typographic opening quote",
                "{bodytext -> f:format.html(class: \u{201C}container\u{201D})}",
                "\u{201C}",
            ],
            'angle quote after colon in inline argument' => [
                "LLM generates angle quote \u{00AB} in inline ViewHelper argument",
                "{var -> f:someVh(attr: \u{00AB}value\u{00BB})}",
                "\u{00AB}",
            ],
            'backtick after colon in inline argument' => [
                'backtick as inline ViewHelper argument value delimiter',
                "{var -> f:format.html(parseFuncTSPath: `lib.parseFunc_RTE`)}",
                '`',
            ],
            'german low-9 quote after colon in inline argument' => [
                "German opening double quote \u{201E} in inline argument",
                "{var -> f:format.html(class: \u{201E}container\u{201C})}",
                "\u{201E}",
            ],
        ];
    }

    #[Test]
    public function lineNumberIsPassedThroughToViolation(): void
    {
        $violations = $this->rule->check("<tag class=\u{201C}test\">", 42);

        self::assertSame(42, $violations[0]['line']);
    }
}