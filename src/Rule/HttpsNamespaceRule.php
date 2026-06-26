<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

use OliverThiele\FluidLinter\Result\FixResult;
use OliverThiele\FluidLinter\Result\FixStatus;

final class HttpsNamespaceRule implements RuleInterface, FixableFileRuleInterface
{
    // typo3fluid/fluid throws an Exception when a namespace URI starts with https://.
    // See: NamespaceDetectionTemplateProcessor — Patterns::NAMESPACEPREFIX_INVALID = 'https://typo3.org/ns/'
    private const PATTERN = '/xmlns:[a-zA-Z0-9.]+\s*=\s*["\']https:\/\/typo3\.org\/ns\//';
    private const WRONG_PREFIX = 'https://typo3.org/ns/';
    private const CORRECT_PREFIX = 'http://typo3.org/ns/';

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

    public function fix(string $filePath, bool $allowRisky): FixResult
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return new FixResult(FixStatus::None, '');
        }

        if (!str_contains($content, self::WRONG_PREFIX)) {
            return new FixResult(FixStatus::None, '');
        }

        $newContent = str_replace(self::WRONG_PREFIX, self::CORRECT_PREFIX, $content);
        $count = substr_count($content, self::WRONG_PREFIX);

        file_put_contents($filePath, $newContent);
        return new FixResult(
            FixStatus::Applied,
            sprintf(
                'Replaced %d occurrence(s) of https://typo3.org/ns/ with http://typo3.org/ns/ in %s',
                $count,
                basename($filePath),
            ),
        );
    }
}
