<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

final class DebugViewHelperRule implements RuleInterface
{
    // Matches both tag-based (<f:debug ...>) and inline ({var -> f:debug()}) syntax.
    // Default severity is warning so it does not block development builds.
    // Set severity to error in the live config to prevent debug output in production.
    private const PATTERN = '/<f:debug[\s>\/]|-> f:debug\(\)/';

    public function getName(): string
    {
        return 'debug-viewhelper';
    }

    public function check(string $line, int $lineNumber): array
    {
        if (preg_match(self::PATTERN, $line) !== 1) {
            return [];
        }

        return [['line' => $lineNumber, 'message' => 'f:debug ViewHelper found — remove before going live.']];
    }
}
