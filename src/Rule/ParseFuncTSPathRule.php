<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

use OliverThiele\FluidLinter\Result\FixResult;
use OliverThiele\FluidLinter\Result\FixStatus;

final class ParseFuncTSPathRule implements RuleInterface, FixableFileRuleInterface
{
    // Tag-based syntax: <f:format.html parseFuncTSPath="" />
    // Only an empty value causes a runtime error; a non-empty value like
    // parseFuncTSPath="lib.parseFunc_RTE" is still valid.
    private const PATTERN_TAG = '/parseFuncTSPath\s*=\s*(["\'])\s*\1/';

    // Inline syntax: {bodytext -> f:format.html(parseFuncTSPath: '')}
    // Same empty-value constraint applies.
    private const PATTERN_INLINE = '/parseFuncTSPath\s*:\s*(["\'])\s*\1/';

    public function getName(): string
    {
        return 'parsefunc-tspath';
    }

    public function check(string $line, int $lineNumber): array
    {
        if (
            preg_match(self::PATTERN_TAG, $line) !== 1
            && preg_match(self::PATTERN_INLINE, $line) !== 1
        ) {
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

        $hasTag = preg_match(self::PATTERN_TAG, $content) === 1;
        $hasInline = preg_match(self::PATTERN_INLINE, $content) === 1;

        if (!$hasTag && !$hasInline) {
            return new FixResult(FixStatus::None, '');
        }

        $newContent = $content;

        if ($hasTag) {
            // Remove the empty attribute including any leading whitespace.
            $newContent = preg_replace('/\s*parseFuncTSPath\s*=\s*(["\'])\s*\1/', '', $newContent) ?? $newContent;
        }

        if ($hasInline) {
            // Two passes to avoid PCRE backreference numbering issues with alternation.
            // Pass 1: last/middle arg — eat the leading comma.
            $newContent = preg_replace('/,\s*parseFuncTSPath\s*:\s*(["\'])\s*\1/', '', $newContent) ?? $newContent;
            // Pass 2: first/only arg — eat trailing comma+space if present.
            $newContent = preg_replace('/parseFuncTSPath\s*:\s*(["\'])\s*\1\s*,?\s*/', '', $newContent) ?? $newContent;
        }

        if ($newContent === $content) {
            return new FixResult(FixStatus::None, '');
        }

        file_put_contents($filePath, $newContent);
        return new FixResult(
            FixStatus::Applied,
            sprintf('Removed empty parseFuncTSPath attribute from %s', basename($filePath)),
        );
    }
}
