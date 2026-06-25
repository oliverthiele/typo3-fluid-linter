<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

final class TypographicQuotesRule implements RuleInterface
{
    // Matches any non-ASCII quote character immediately after an = sign in an HTML/Fluid attribute.
    // Only the position right after = is checked — the same characters in text content between
    // tags (e.g. 1/2″ inside <f:case>) are intentional and never flagged.
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
    private const PATTERN = '/=\s*([\x{0060}\x{00AB}\x{00BB}\x{2018}\x{2019}\x{201A}\x{201C}\x{201D}\x{201E}\x{2032}\x{2033}\x{2039}\x{203A}])/u';

    public function getName(): string
    {
        return 'typographic-quotes';
    }

    public function check(string $line, int $lineNumber): array
    {
        if (preg_match(self::PATTERN, $line, $matches) !== 1) {
            return [];
        }

        $character = $matches[1];
        return [['line' => $lineNumber, 'message' => sprintf(
            'Invalid quote character "%s" (U+%04X) used as attribute delimiter — only straight ASCII quotes (" or \') are valid in HTML attributes and Fluid ViewHelpers.',
            $character,
            mb_ord($character),
        )]];
    }
}
