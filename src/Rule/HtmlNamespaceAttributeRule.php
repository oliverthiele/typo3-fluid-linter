<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

final class HtmlNamespaceAttributeRule implements FileRuleInterface
{
    // Detects <html> tags with a Fluid xmlns declaration that are missing data-namespace-typo3-fluid="true".
    // Without that attribute, the <html> tag from this Fluid file appears in the output in addition to the
    // <html> tag rendered by TYPO3 itself (via the PAGE TypoScript object), resulting in duplicate <html> tags.
    // Exception: templates that use <f:layout> are never rendered directly — their <html> is stripped by Fluid
    // before rendering, so the attribute is not required there.
    private const HTML_WITH_FLUID_NAMESPACE = '/<html[^>]+xmlns:[a-zA-Z0-9.]+\s*=\s*["\']http:\/\/typo3\.org\/ns\//s';
    private const DATA_ATTRIBUTE = 'data-namespace-typo3-fluid="true"';
    private const FLUID_LAYOUT_TAG = '<f:layout';

    public function getName(): string
    {
        return 'html-namespace-attribute';
    }

    public function checkFile(string $content, string $filePath): array
    {
        if (preg_match(self::HTML_WITH_FLUID_NAMESPACE, $content, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            return [];
        }

        if (str_contains($content, self::DATA_ATTRIBUTE)) {
            return [];
        }

        if (str_contains($content, self::FLUID_LAYOUT_TAG)) {
            return [];
        }

        $lineNumber = substr_count(substr($content, 0, (int)$matches[0][1]), "\n") + 1;

        return [[
            'line' => $lineNumber,
            'message' => 'Missing data-namespace-typo3-fluid="true" on <html> tag — without it, this tag appears in addition to the <html> rendered by TYPO3\'s PAGE object, resulting in duplicate <html> tags.',
            'severity' => 'error',
        ]];
    }
}
