<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

final class ParseFuncTSPathRule implements RuleInterface
{
    // Only an empty parseFuncTSPath="" / parseFuncTSPath='' causes a runtime error.
    // A non-empty value like parseFuncTSPath="lib.parseFunc_RTE" is still valid.
    private const PATTERN = '/parseFuncTSPath\s*=\s*(["\'])\s*\1/';

    public function getName(): string
    {
        return 'parsefunc-tspath';
    }

    public function check(string $line, int $lineNumber): array
    {
        if (preg_match(self::PATTERN, $line) !== 1) {
            return [];
        }

        return [['line' => $lineNumber, 'message' => 'Empty parseFuncTSPath="" causes a runtime error — use {field -> f:format.html()} inline notation instead.']];
    }
}
