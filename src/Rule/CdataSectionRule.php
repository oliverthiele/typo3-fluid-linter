<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

final class CdataSectionRule implements FileRuleInterface
{
    // CDATA sections inside <f:comment> were the old way to safely comment out Fluid syntax.
    // Deprecated in Fluid 4 (typo3fluid/fluid < 5.0), removed in Fluid 5 / TYPO3 v14.
    // Legitimate CDATA in XML/RSS templates (e.g. <title><![CDATA[...]]></title>) is not flagged.
    // Fluid 5 provides {{{expression}}} as the explicit CDATA-output syntax.

    public function getName(): string
    {
        return 'cdata-section';
    }

    public function checkFile(string $content, string $filePath): array
    {
        $violations = [];

        // Collect all <f:comment>...</f:comment> byte ranges
        preg_match_all('/<f:comment\b[^>]*>/i', $content, $openMatches, PREG_OFFSET_CAPTURE);
        preg_match_all('/<\/f:comment>/i', $content, $closeMatches, PREG_OFFSET_CAPTURE);

        $commentRanges = [];
        foreach ($openMatches[0] as $openMatch) {
            $openOffset = $openMatch[1];
            foreach ($closeMatches[0] as $closeMatch) {
                $closeOffset = $closeMatch[1];
                if ($closeOffset > $openOffset) {
                    $commentRanges[] = [$openOffset, $closeOffset + strlen($closeMatch[0])];
                    break;
                }
            }
        }

        if ($commentRanges === []) {
            return [];
        }

        preg_match_all('/<!\[CDATA\[/', $content, $cdataMatches, PREG_OFFSET_CAPTURE);

        foreach ($cdataMatches[0] as $cdataMatch) {
            $cdataOffset = $cdataMatch[1];

            foreach ($commentRanges as [$rangeStart, $rangeEnd]) {
                if ($cdataOffset >= $rangeStart && $cdataOffset <= $rangeEnd) {
                    $lineNumber = substr_count(substr($content, 0, $cdataOffset), "\n") + 1;
                    $violations[] = [
                        'line' => $lineNumber,
                        'message' => 'CDATA section inside <f:comment> — deprecated in Fluid 4 and removed in Fluid 5. Use plain <f:comment> without CDATA.',
                        'severity' => 'error',
                    ];
                    break;
                }
            }
        }

        return $violations;
    }
}
