<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

final class UnderscoreVariableRule implements RuleInterface, VersionedRuleInterface
{
    // Fluid 5 (TYPO3 v14) forbids user-defined variable names starting with an underscore.
    // {_all} is a Fluid-internal variable and remains valid.
    private const PATTERN = '/\{(_[a-zA-Z][a-zA-Z0-9]*)/';

    /** @var list<string> */
    private const FLUID_INTERNAL_VARIABLES = ['_all'];

    public function getName(): string
    {
        return 'underscore-variable';
    }

    public function getMinimumTypo3Version(): int
    {
        return 14;
    }

    public function getMaximumTypo3Version(): ?int
    {
        return null;
    }

    public function check(string $line, int $lineNumber): array
    {
        if (preg_match_all(self::PATTERN, $line, $matches) === 0) {
            return [];
        }

        $violations = [];
        foreach ($matches[1] as $variableName) {
            if (!in_array($variableName, self::FLUID_INTERNAL_VARIABLES, true)) {
                $violations[] = [
                    'line' => $lineNumber,
                    'message' => sprintf(
                        'Fluid variable "{%s}" starts with an underscore — forbidden in Fluid 5 (TYPO3 v14).',
                        $variableName,
                    ),
                ];
            }
        }

        return $violations;
    }
}
