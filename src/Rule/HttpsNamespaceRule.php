<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

final class HttpsNamespaceRule implements RuleInterface
{
    // typo3fluid/fluid throws an Exception when a namespace URI starts with https://.
    // See: NamespaceDetectionTemplateProcessor — Patterns::NAMESPACEPREFIX_INVALID = 'https://typo3.org/ns/'
    private const PATTERN = '/xmlns:[a-zA-Z0-9.]+\s*=\s*["\']https:\/\/typo3\.org\/ns\//';

    public function getName(): string
    {
        return 'https-namespace';
    }

    public function check(string $line, int $lineNumber): array
    {
        if (preg_match(self::PATTERN, $line) !== 1) {
            return [];
        }

        return [['line' => $lineNumber, 'message' => 'Invalid Fluid namespace URI — use http://typo3.org/ns/ (not https://). TYPO3 Fluid throws an exception at runtime for https:// namespaces.']];
    }
}
