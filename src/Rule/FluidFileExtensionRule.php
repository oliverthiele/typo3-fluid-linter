<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

final class FluidFileExtensionRule implements FileRuleInterface, VersionedRuleInterface
{
    // In TYPO3 v14+, .fluid.html is the preferred extension.
    // If a .fluid.html counterpart already exists for this .html file, both are loaded and may conflict.
    public function getName(): string
    {
        return 'fluid-file-extension';
    }

    public function getMinimumTypo3Version(): int
    {
        return 14;
    }

    public function getMaximumTypo3Version(): ?int
    {
        return null;
    }

    public function checkFile(string $content, string $filePath): array
    {
        if (!str_ends_with($filePath, '.html') || str_ends_with($filePath, '.fluid.html')) {
            return [];
        }

        $fluidCounterpart = substr($filePath, 0, -5) . '.fluid.html';

        if (!file_exists($fluidCounterpart)) {
            return [];
        }

        return [[
            'line' => 1,
            'message' => sprintf(
                'Both %s and its .fluid.html counterpart exist — TYPO3 v14 prefers .fluid.html and may load the wrong file.',
                basename($filePath),
            ),
            'severity' => 'warning',
        ]];
    }
}
