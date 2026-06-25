<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

final class XmlDeclarationRule implements FileRuleInterface
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
}
