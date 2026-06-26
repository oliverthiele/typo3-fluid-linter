<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Rule\DebugViewHelperRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DebugViewHelperRuleTest extends TestCase
{
    private DebugViewHelperRule $rule;

    protected function setUp(): void
    {
        $this->rule = new DebugViewHelperRule();
    }

    #[Test]
    #[DataProvider('linesWithViolation')]
    public function checkDetectsDebugViewHelper(string $line): void
    {
        self::assertCount(1, $this->rule->check($line, 1));
    }

    public static function linesWithViolation(): array
    {
        return [
            'self-closing tag' => ['<f:debug />'],
            'opening tag' => ['<f:debug>'],
            'tag with attributes' => ['<f:debug title="vars" />'],
            'inline notation' => ['{myVar -> f:debug()}'],
        ];
    }

    #[Test]
    #[DataProvider('linesWithNoViolation')]
    public function checkIgnoresNonDebugViewHelpers(string $line): void
    {
        self::assertSame([], $this->rule->check($line, 1));
    }

    public static function linesWithNoViolation(): array
    {
        return [
            // f:debugBar is a different ViewHelper — must not be flagged
            'debugBar ViewHelper' => ['<f:debugBar />'],
            // Text content mentioning f:debug
            'comment about f:debug' => ['<!-- use f:debug to inspect variables -->'],
            'plain text' => ['No debug output here'],
        ];
    }
}
