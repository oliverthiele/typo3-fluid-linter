<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Rule\ParseFuncTSPathRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParseFuncTSPathRuleTest extends TestCase
{
    private ParseFuncTSPathRule $rule;

    protected function setUp(): void
    {
        $this->rule = new ParseFuncTSPathRule();
    }

    #[Test]
    #[DataProvider('linesWithViolation')]
    public function checkDetectsEmptyParseFuncTSPath(string $line): void
    {
        self::assertCount(1, $this->rule->check($line, 1));
    }

    public static function linesWithViolation(): array
    {
        return [
            'empty double-quoted value' => ['<f:format.html parseFuncTSPath="">'],
            'empty single-quoted value' => ["<f:format.html parseFuncTSPath=''>"],
            'empty value with spaces around =' => ['<f:format.html parseFuncTSPath = "">'],
            'empty value with spaces inside quotes' => ['<f:format.html parseFuncTSPath="   ">'],
        ];
    }

    #[Test]
    #[DataProvider('linesWithNoViolation')]
    public function checkIgnoresNonEmptyParseFuncTSPath(string $line): void
    {
        self::assertSame([], $this->rule->check($line, 1));
    }

    public static function linesWithNoViolation(): array
    {
        return [
            'valid TS path' => ['<f:format.html parseFuncTSPath="lib.parseFunc_RTE">'],
            'inline notation without parseFuncTSPath' => ['{field -> f:format.html()}'],
            'unrelated attribute named similar' => ['<tag parseFuncPath="">'],
        ];
    }
}