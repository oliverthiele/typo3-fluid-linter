<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

use OliverThiele\FluidLinter\Result\FixResult;
use OliverThiele\FluidLinter\Result\FixStatus;

final class FluidFileExtensionRule implements FileRuleInterface, FixableFileRuleInterface, VersionedRuleInterface
{
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

        if (file_exists($fluidCounterpart)) {
            return [[
                'line' => 1,
                'message' => sprintf(
                    'Both %s and %s exist — TYPO3 v14 prefers .fluid.html and may load the wrong file. Remove the .html version.',
                    basename($filePath),
                    basename($fluidCounterpart),
                ),
                'severity' => 'warning',
            ]];
        }

        // Only emit info when the project is actively migrating: at least one .fluid.html
        // file already exists in the same directory, meaning this .html file was simply
        // not yet renamed.
        $fluidFilesInDirectory = glob(dirname($filePath) . '/*.fluid.html') ?: [];
        if ($fluidFilesInDirectory === []) {
            return [];
        }

        return [[
            'line' => 1,
            'message' => sprintf(
                '%s has no .fluid.html counterpart — rename to %s for TYPO3 v14 compatibility.',
                basename($filePath),
                basename($fluidCounterpart),
            ),
            'severity' => 'info',
        ]];
    }

    public function fix(string $filePath, bool $allowRisky): FixResult
    {
        if (!str_ends_with($filePath, '.html') || str_ends_with($filePath, '.fluid.html')) {
            return new FixResult(FixStatus::None, '');
        }

        $fluidCounterpart = substr($filePath, 0, -5) . '.fluid.html';

        if (file_exists($fluidCounterpart)) {
            if (!$allowRisky) {
                return new FixResult(
                    FixStatus::Skipped,
                    sprintf(
                        'Delete %s (keeping %s) — add --allow-risky to apply',
                        basename($filePath),
                        basename($fluidCounterpart),
                    ),
                );
            }
            unlink($filePath);
            return new FixResult(
                FixStatus::Applied,
                sprintf('Deleted %s (kept %s)', basename($filePath), basename($fluidCounterpart)),
            );
        }

        if (!file_exists($filePath)) {
            return new FixResult(FixStatus::None, '');
        }

        rename($filePath, $fluidCounterpart);
        return new FixResult(
            FixStatus::Applied,
            sprintf('Renamed %s → %s', basename($filePath), basename($fluidCounterpart)),
        );
    }
}