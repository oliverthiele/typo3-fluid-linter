<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

final class TypographicQuotesRule implements RuleInterface
{
    // Matches any non-ASCII quote character immediately after = (HTML/Fluid tag attribute)
    // or : (Fluid inline ViewHelper argument, e.g. {var -> f:format.html(class: «container»)}).
    // Only these two positions are checked — the same characters in text content between tags
    // (e.g. «quoted text» in a <p>) are intentional prose and never flagged.
    //
    // Known limitation: prose text containing `word: «quote»` directly in a template would be
    // flagged as a false positive. In practice this is rare — Fluid templates keep translated
    // text in .xlf files, not inline.
    //
    // Characters covered:
    //   U+0060  `   GRAVE ACCENT (backtick)
    //   U+00AB  «   LEFT-POINTING DOUBLE ANGLE QUOTATION MARK
    //   U+00BB  »   RIGHT-POINTING DOUBLE ANGLE QUOTATION MARK
    //   U+2018  '   LEFT SINGLE QUOTATION MARK
    //   U+2019  '   RIGHT SINGLE QUOTATION MARK
    //   U+201A  ‚   SINGLE LOW-9 QUOTATION MARK
    //   U+201C  "   LEFT DOUBLE QUOTATION MARK
    //   U+201D  "   RIGHT DOUBLE QUOTATION MARK
    //   U+201E  „   DOUBLE LOW-9 QUOTATION MARK
    //   U+2032  ′   PRIME (foot mark)
    //   U+2033  ″   DOUBLE PRIME (inch mark)
    //   U+2039  ‹   SINGLE LEFT-POINTING ANGLE QUOTATION MARK
    //   U+203A  ›   SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
    private const PATTERN = '/[=:]\s*([\x{0060}\x{00AB}\x{00BB}\x{2018}\x{2019}\x{201A}\x{201C}\x{201D}\x{201E}\x{2032}\x{2033}\x{2039}\x{203A}])/u';

    public function getName(): string
    {
        return 'typographic-quotes';
    }

    public function check(string $line, int $lineNumber): array
    {
        if (!preg_match_all(self::PATTERN, $line, $allMatches, PREG_SET_ORDER)) {
            return [];
        }

        $violations = [];
        foreach ($allMatches as $matches) {
            $character = $matches[1];
            $violations[] = ['line' => $lineNumber, 'message' => sprintf(
                'Invalid quote character "%s" (U+%04X) used as value delimiter — only straight ASCII quotes (" or \') are valid in HTML attributes and Fluid ViewHelper arguments.',
                $character,
                mb_ord($character),
            )];
        }
        return $violations;
    }
}
