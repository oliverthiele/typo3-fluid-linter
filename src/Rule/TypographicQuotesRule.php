<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

final class TypographicQuotesRule implements FileRuleInterface
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
    // The bodies of <script> and <style> elements are excluded, because both languages use the
    // matched characters legitimately: a JavaScript template literal is delimited by backticks
    // (`url("${poster}")`), and an object literal or CSS declaration puts a value right after a
    // colon. Checking them turned every template with inline JS into a permanent false positive.
    // The opening tags themselves stay in scope — <script src=“x“> is still a real error.
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

    // Body of a <script> or <style> element, captured separately from its delimiting tags.
    private const EMBEDDED_CODE_PATTERN = '#(<(script|style)\b[^>]*>)(.*?)(</\2\s*>)#is';

    public function getName(): string
    {
        return 'typographic-quotes';
    }

    public function checkFile(string $content, string $filePath): array
    {
        $violations = [];

        foreach (explode("\n", self::maskEmbeddedCode($content)) as $lineIndex => $line) {
            foreach ($this->check($line, $lineIndex + 1) as $violation) {
                $violations[] = [
                    'line' => $violation['line'],
                    'message' => $violation['message'],
                    'severity' => 'error',
                ];
            }
        }

        return $violations;
    }

    /**
     * Checks a single line, without any knowledge of the surrounding <script>/<style> context.
     *
     * Kept public as the line-level primitive behind checkFile() — the rule needs the whole file
     * to recognise embedded code, but every pattern decision is still made one line at a time.
     *
     * @return list<array{line: int, message: string}>
     */
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

    /**
     * Blanks out the body of every <script> and <style> element.
     *
     * Only newlines survive, so all following line numbers stay correct. Replacement happens
     * byte-wise on purpose: a multibyte character becomes several spaces, which is irrelevant
     * because nothing but the line breaks is read from the masked content afterwards.
     */
    private static function maskEmbeddedCode(string $content): string
    {
        return (string)preg_replace_callback(
            self::EMBEDDED_CODE_PATTERN,
            static fn (array $matches): string => $matches[1]
                . (string)preg_replace('/[^\n]/', ' ', $matches[3])
                . $matches[4],
            $content,
        );
    }
}
