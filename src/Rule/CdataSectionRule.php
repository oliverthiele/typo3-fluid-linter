<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

use OliverThiele\FluidLinter\Result\FixResult;
use OliverThiele\FluidLinter\Result\FixStatus;

final class CdataSectionRule implements FileRuleInterface, FixableFileRuleInterface
{
    // CDATA sections inside <f:comment> were the old way to safely comment out Fluid syntax:
    // CDATA told the parser not to interpret the block at all, so invalid Fluid or a stray
    // ViewHelper call inside a comment could not break rendering.
    // CDATA itself is NOT removed in Fluid 5 — what ended is Fluid stripping it from the
    // template (#108148). The construct therefore no longer comments anything out, and it
    // writes a deprecation entry on every render from TYPO3 13.4.21 on — in a template set
    // that used the idiom consistently, that can be a very large number of log entries.
    // The replacement is a plain <f:comment>, which ignores Fluid syntax errors by itself
    // since TYPO3 13.3 (#104904).
    // Legitimate CDATA in XML/RSS templates (e.g. <title><![CDATA[...]]></title>) is not flagged.
    // Fluid 5 provides {{{expression}}} as the explicit CDATA-output syntax.

    private const CDATA_OPEN = '<![CDATA[';
    private const CDATA_CLOSE = ']]>';

    public function getName(): string
    {
        return 'cdata-section';
    }

    public function checkFile(string $content, string $filePath): array
    {
        $violations = [];

        $commentRanges = $this->collectCommentRanges($content);
        if ($commentRanges === []) {
            return [];
        }

        preg_match_all('/' . preg_quote(self::CDATA_OPEN, '/') . '/', $content, $cdataMatches, PREG_OFFSET_CAPTURE);

        foreach ($cdataMatches[0] as $cdataMatch) {
            $cdataOffset = $cdataMatch[1];

            foreach ($commentRanges as [$rangeStart, $rangeEnd]) {
                if ($cdataOffset >= $rangeStart && $cdataOffset <= $rangeEnd) {
                    $lineNumber = substr_count(substr($content, 0, $cdataOffset), "\n") + 1;
                    $violations[] = [
                        'line' => $lineNumber,
                        'message' => 'CDATA section inside <f:comment> — Fluid 5 no longer strips CDATA, so this no longer comments anything out, and it logs a deprecation on every render from TYPO3 13.4.21 on. Use plain <f:comment> without CDATA.',
                        'severity' => 'error',
                    ];
                    break;
                }
            }
        }

        return $violations;
    }

    /**
     * Removes the CDATA delimiters inside every <f:comment> block while keeping the commented
     * text — including any surrounding HTML comment markers, which grey the text out in the IDE.
     * CDATA outside <f:comment> (XML/RSS templates, script bodies) is never touched.
     */
    public function fix(string $filePath, bool $allowRisky): FixResult
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return new FixResult(FixStatus::None, '');
        }

        $commentRanges = $this->collectCommentRanges($content);
        if ($commentRanges === []) {
            return new FixResult(FixStatus::None, '');
        }

        $removed = 0;
        // Rewrite the ranges back to front so the offsets of the untouched ranges stay valid
        foreach (array_reverse($commentRanges) as [$rangeStart, $rangeEnd]) {
            $segment = substr($content, $rangeStart, $rangeEnd - $rangeStart);
            $removed += substr_count($segment, self::CDATA_OPEN);
            $cleanedSegment = $this->removeCdataDelimiters($segment);
            if ($cleanedSegment !== $segment) {
                $content = substr_replace($content, $cleanedSegment, $rangeStart, $rangeEnd - $rangeStart);
            }
        }

        if ($removed === 0) {
            return new FixResult(FixStatus::None, '');
        }

        file_put_contents($filePath, $content);

        return new FixResult(
            FixStatus::Applied,
            sprintf(
                'Removed %d CDATA section(s) from <f:comment> in %s',
                $removed,
                basename($filePath),
            ),
        );
    }

    /**
     * Collects the byte ranges of all top-level <f:comment>...</f:comment> blocks.
     *
     * Nested comments are reported as one outer range: a CDATA section inside an inner comment
     * lies within the outer one as well, so no occurrence is lost. Self-closing <f:comment />
     * tags carry no content and are skipped — counting them as an opening tag would pair the
     * next closing tag with the wrong comment.
     *
     * @return list<array{int, int}>
     */
    private function collectCommentRanges(string $content): array
    {
        preg_match_all('/<f:comment\b[^>]*>|<\/f:comment>/i', $content, $matches, PREG_OFFSET_CAPTURE);

        $ranges = [];
        $depth = 0;
        $rangeStart = 0;

        foreach ($matches[0] as [$tag, $offset]) {
            if (str_ends_with($tag, '/>')) {
                continue;
            }

            if ($tag[1] !== '/') {
                if ($depth === 0) {
                    $rangeStart = $offset;
                }
                $depth++;
                continue;
            }

            if ($depth === 0) {
                continue;
            }

            $depth--;
            if ($depth === 0) {
                $ranges[] = [$rangeStart, $offset + strlen($tag)];
            }
        }

        return $ranges;
    }

    /**
     * Drops the CDATA delimiters line by line. A line that held nothing but delimiters and
     * whitespace is removed entirely instead of being left behind as a blank line; on every
     * other touched line the whitespace the removal left at the end of the line is trimmed.
     */
    private function removeCdataDelimiters(string $segment): string
    {
        $lines = explode("\n", $segment);
        $cleanedLines = [];

        foreach ($lines as $line) {
            $cleanedLine = str_replace([self::CDATA_OPEN, self::CDATA_CLOSE], '', $line);

            if ($cleanedLine === $line) {
                $cleanedLines[] = $line;
                continue;
            }

            if (trim($cleanedLine) === '') {
                continue;
            }

            $cleanedLines[] = preg_replace('/[ \t]+$/', '', $cleanedLine) ?? $cleanedLine;
        }

        return implode("\n", $cleanedLines);
    }
}
