<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Rule\FluidFileExtensionRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FluidFileExtensionRuleTest extends TestCase
{
    private string $tempDirectory;
    private FluidFileExtensionRule $rule;

    protected function setUp(): void
    {
        $this->tempDirectory = sys_get_temp_dir() . '/fluid-linter-test-' . uniqid('', true);
        mkdir($this->tempDirectory);
        $this->rule = new FluidFileExtensionRule();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDirectory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->tempDirectory);
    }

    #[Test]
    public function noViolationWhenOnlyHtmlExists(): void
    {
        $filePath = $this->tempDirectory . '/Template.html';
        touch($filePath);

        self::assertSame([], $this->rule->checkFile('', $filePath));
    }

    #[Test]
    public function violationWhenBothHtmlAndFluidHtmlExist(): void
    {
        $htmlFile = $this->tempDirectory . '/Template.html';
        $fluidHtmlFile = $this->tempDirectory . '/Template.fluid.html';
        touch($htmlFile);
        touch($fluidHtmlFile);

        $violations = $this->rule->checkFile('', $htmlFile);

        self::assertCount(1, $violations);
        self::assertSame('warning', $violations[0]['severity']);
        self::assertSame(1, $violations[0]['line']);
    }

    #[Test]
    public function noViolationForFluidHtmlFileItself(): void
    {
        // Linting a .fluid.html file must not trigger the rule for its own .html counterpart
        $fluidHtmlFile = $this->tempDirectory . '/Template.fluid.html';
        $htmlFile = $this->tempDirectory . '/Template.html';
        touch($fluidHtmlFile);
        touch($htmlFile);

        self::assertSame([], $this->rule->checkFile('', $fluidHtmlFile));
    }

    #[Test]
    public function minimumTypo3VersionIsFourteen(): void
    {
        self::assertSame(14, $this->rule->getMinimumTypo3Version());
    }

    #[Test]
    public function maximumTypo3VersionIsNull(): void
    {
        self::assertNull($this->rule->getMaximumTypo3Version());
    }
}