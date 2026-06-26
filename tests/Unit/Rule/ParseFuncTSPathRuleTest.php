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

    #[Test]
    public function fixRemovesEmptyParseFuncTSPathAttribute(): void
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
    public function fixRemovesAttributeWithSingleQuotes(): void
    {
        $content = "<f:format.html parseFuncTSPath='' />";
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::Applied, $result->status);
        self::assertStringNotContainsString('parseFuncTSPath', file_get_contents($filePath));
    }

    #[Test]
    public function fixDoesNotTouchNonEmptyParseFuncTSPath(): void
    {
        $content = '<f:format.html parseFuncTSPath="lib.parseFunc_RTE" />';
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::None, $result->status);
        self::assertSame($content, file_get_contents($filePath));
    }
}