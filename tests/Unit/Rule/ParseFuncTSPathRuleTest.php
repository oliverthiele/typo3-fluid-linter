<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Result\FixStatus;
use OliverThiele\FluidLinter\Rule\ParseFuncTSPathRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParseFuncTSPathRuleTest extends TestCase
{
    private ParseFuncTSPathRule $rule;
    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $this->rule = new ParseFuncTSPathRule();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    private function writeTempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fluid-linter-test-') . '.html';
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;
        return $path;
    }

    // --- Detection: tag syntax ---

    #[Test]
    #[DataProvider('tagLinesWithViolation')]
    public function checkDetectsEmptyParseFuncTSPathInTagSyntax(string $line): void
    {
        self::assertCount(1, $this->rule->check($line, 1));
    }

    public static function tagLinesWithViolation(): array
    {
        return [
            'empty double-quoted value' => ['<f:format.html parseFuncTSPath="">'],
            'empty single-quoted value' => ["<f:format.html parseFuncTSPath=''>"],
            'empty value with spaces around =' => ['<f:format.html parseFuncTSPath = "">'],
            'empty value with spaces inside quotes' => ['<f:format.html parseFuncTSPath="   ">'],
        ];
    }

    // --- Detection: inline syntax ---

    #[Test]
    #[DataProvider('inlineLinesWithViolation')]
    public function checkDetectsEmptyParseFuncTSPathInInlineSyntax(string $line): void
    {
        self::assertCount(1, $this->rule->check($line, 1));
    }

    public static function inlineLinesWithViolation(): array
    {
        return [
            'empty double-quoted inline argument' => ["{bodytext -> f:format.html(parseFuncTSPath: '')}"],
            'empty single-quoted inline argument' => ['{bodytext -> f:format.html(parseFuncTSPath: "")}'],
            'empty inline argument with spaces' => ["{bodytext -> f:format.html(parseFuncTSPath :  '')}"],
        ];
    }

    // --- Detection: no violation ---

    #[Test]
    #[DataProvider('linesWithNoViolation')]
    public function checkIgnoresNonEmptyParseFuncTSPath(string $line): void
    {
        self::assertSame([], $this->rule->check($line, 1));
    }

    public static function linesWithNoViolation(): array
    {
        return [
            'valid TS path in tag syntax' => ['<f:format.html parseFuncTSPath="lib.parseFunc_RTE">'],
            'valid TS path in inline syntax' => ["{bodytext -> f:format.html(parseFuncTSPath: 'lib.parseFunc_RTE')}"],
            'inline notation without parseFuncTSPath' => ['{field -> f:format.html()}'],
            'unrelated attribute named similar' => ['<tag parseFuncPath="">'],
        ];
    }

    // --- Fix: tag syntax ---

    #[Test]
    public function fixRemovesEmptyParseFuncTSPathAttributeInTagSyntax(): void
    {
        $content = '<f:format.html parseFuncTSPath="" value="{bodytext}" />';
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::Applied, $result->status);
        $fixed = file_get_contents($filePath);
        self::assertStringNotContainsString('parseFuncTSPath', $fixed);
        self::assertStringContainsString('value="{bodytext}"', $fixed);
    }

    #[Test]
    public function fixRemovesTagAttributeWithSingleQuotes(): void
    {
        $content = "<f:format.html parseFuncTSPath='' />";
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::Applied, $result->status);
        self::assertStringNotContainsString('parseFuncTSPath', file_get_contents($filePath));
    }

    #[Test]
    public function fixDoesNotTouchNonEmptyTagAttribute(): void
    {
        $content = '<f:format.html parseFuncTSPath="lib.parseFunc_RTE" />';
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::None, $result->status);
        self::assertSame($content, file_get_contents($filePath));
    }

    // --- Fix: inline syntax, comma handling ---

    #[Test]
    public function fixRemovesOnlyInlineArgument(): void
    {
        $content = "{bodytext -> f:format.html(parseFuncTSPath: '')}";
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::Applied, $result->status);
        $fixed = file_get_contents($filePath);
        self::assertStringNotContainsString('parseFuncTSPath', $fixed);
        self::assertStringContainsString('{bodytext -> f:format.html()}', $fixed);
    }

    #[Test]
    public function fixRemovesFirstInlineArgumentWithTrailingComma(): void
    {
        $content = "{bodytext -> f:format.html(parseFuncTSPath: '', class: 'foo')}";
        $filePath = $this->writeTempFile($content);

        $this->rule->fix($filePath, false);

        $fixed = file_get_contents($filePath);
        self::assertStringNotContainsString('parseFuncTSPath', $fixed);
        self::assertStringContainsString("class: 'foo'", $fixed);
        self::assertStringNotContainsString('(,', $fixed);
    }

    #[Test]
    public function fixRemovesLastInlineArgumentWithLeadingComma(): void
    {
        $content = "{bodytext -> f:format.html(class: 'foo', parseFuncTSPath: '')}";
        $filePath = $this->writeTempFile($content);

        $this->rule->fix($filePath, false);

        $fixed = file_get_contents($filePath);
        self::assertStringNotContainsString('parseFuncTSPath', $fixed);
        self::assertStringContainsString("class: 'foo'", $fixed);
        self::assertStringNotContainsString(',)', $fixed);
    }

    #[Test]
    public function fixRemovesMiddleInlineArgumentLeavingOtherArgsIntact(): void
    {
        $content = "{bodytext -> f:format.html(a: 'x', parseFuncTSPath: '', b: 'y')}";
        $filePath = $this->writeTempFile($content);

        $this->rule->fix($filePath, false);

        $fixed = file_get_contents($filePath);
        self::assertStringNotContainsString('parseFuncTSPath', $fixed);
        self::assertStringContainsString("a: 'x'", $fixed);
        self::assertStringContainsString("b: 'y'", $fixed);
    }
}