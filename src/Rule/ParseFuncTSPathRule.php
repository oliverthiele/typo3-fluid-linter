<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

use OliverThiele\FluidLinter\Result\FixResult;
use OliverThiele\FluidLinter\Result\FixStatus;

final class ParseFuncTSPathRule implements RuleInterface, FixableFileRuleInterface
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

    public function fix(string $filePath, bool $allowRisky): FixResult
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return new FixResult(FixStatus::None, '');
        }

        if (preg_match(self::PATTERN, $content) !== 1) {
            return new FixResult(FixStatus::None, '');
        }

        // Remove the empty parseFuncTSPath attribute including any leading whitespace.
        // The inline syntax recommendation remains in the violation message since the
        // variable name cannot be determined statically.
        $newContent = preg_replace('/\s*parseFuncTSPath\s*=\s*(["\'])\s*\1/', '', $content);
        if ($newContent === null || $newContent === $content) {
            return new FixResult(FixStatus::None, '');
        }

        file_put_contents($filePath, $newContent);
        return new FixResult(
            FixStatus::Applied,
            sprintf('Removed empty parseFuncTSPath="" attribute from %s', basename($filePath)),
        );
    }
}