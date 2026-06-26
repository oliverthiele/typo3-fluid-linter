<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

use OliverThiele\FluidLinter\Result\FixResult;
use OliverThiele\FluidLinter\Result\FixStatus;

final class XmlDeclarationRule implements FileRuleInterface, FixableFileRuleInterface
{
    // The XML processing instruction (starting with "<?xml") is unnecessary in Fluid templates.
    // HTML5 does not require it, and it triggers Quirks Mode in older browsers when placed
    // before the DOCTYPE declaration.
    private const PATTERN = '/^<\?xml\s/';

    public function getName(): string
    {
        return 'xml-declaration';
    }

    public function checkFile(string $content, string $filePath): array
    {
        if (preg_match(self::PATTERN, $content) !== 1) {
            return [];
        }

        return [[
            'line' => 1,
            'message' => 'XML processing instruction is unnecessary in Fluid templates and may trigger Quirks Mode in older browsers.',
            'severity' => 'warning',
        ]];
    }

    public function fix(string $filePath, bool $allowRisky): FixResult
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return new FixResult(FixStatus::None, '');
        }

        if (preg_match(self::PATTERN, $content) !== 1) {
            return new FixResult(FixStatus::None, '');
        }

        $newContent = preg_replace('/^<\?xml[^\n]*\n?/', '', $content);
        if ($newContent === null || $newContent === $content) {
            return new FixResult(FixStatus::None, '');
        }

        file_put_contents($filePath, $newContent);
        return new FixResult(
            FixStatus::Applied,
            sprintf('Removed XML processing instruction from %s', basename($filePath)),
        );
    }
}