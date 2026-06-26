<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Result\FixStatus;
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

    // --- checkFile: no violation ---

    #[Test]
    public function noViolationWhenOnlyHtmlExistsAndNoFluidHtmlInDirectory(): void
    {
        $filePath = $this->tempDirectory . '/Template.html';
        touch($filePath);

        self::assertSame([], $this->rule->checkFile('', $filePath));
    }

    #[Test]
    public function noViolationForFluidHtmlFileItself(): void
    {
        $fluidHtmlFile = $this->tempDirectory . '/Template.fluid.html';
        $htmlFile = $this->tempDirectory . '/Template.html';
        touch($fluidHtmlFile);
        touch($htmlFile);

        self::assertSame([], $this->rule->checkFile('', $fluidHtmlFile));
    }

    #[Test]
    public function noViolationForNonHtmlFile(): void
    {
        $filePath = $this->tempDirectory . '/Template.xml';
        touch($filePath);

        self::assertSame([], $this->rule->checkFile('', $filePath));
    }

    // --- checkFile: info (migration hint) ---

    #[Test]
    public function infoWhenHtmlExistsAndOtherFluidHtmlFilesAreInSameDirectory(): void
    {
        // Another file in the same directory is already migrated → project is actively migrating
        touch($this->tempDirectory . '/AlreadyMigrated.fluid.html');
        $filePath = $this->tempDirectory . '/NotYetMigrated.html';
        touch($filePath);

        $violations = $this->rule->checkFile('', $filePath);

        self::assertCount(1, $violations);
        self::assertSame('info', $violations[0]['severity']);
        self::assertStringContainsString('NotYetMigrated.html', $violations[0]['message']);
        self::assertStringContainsString('.fluid.html', $violations[0]['message']);
    }

    #[Test]
    public function noViolationWhenHtmlExistsButNoFluidHtmlAnywhereInDirectory(): void
    {
        // Project has not started migrating — stay silent
        touch($this->tempDirectory . '/Template.html');
        touch($this->tempDirectory . '/Other.html');

        $violations = $this->rule->checkFile('', $this->tempDirectory . '/Template.html');

        self::assertSame([], $violations);
    }

    // --- checkFile: warning (conflict) ---

    #[Test]
    public function warningWhenBothHtmlAndFluidHtmlCounterpartExist(): void
    {
        $htmlFile = $this->tempDirectory . '/Template.html';
        $fluidHtmlFile = $this->tempDirectory . '/Template.fluid.html';
        touch($htmlFile);
        touch($fluidHtmlFile);

        $violations = $this->rule->checkFile('', $htmlFile);

        self::assertCount(1, $violations);
        self::assertSame('warning', $violations[0]['severity']);
        self::assertStringContainsString('Template.html', $violations[0]['message']);
        self::assertStringContainsString('Template.fluid.html', $violations[0]['message']);
    }

    // --- fix: rename (safe) ---

    #[Test]
    public function fixRenamesHtmlToFluidHtmlWhenNoCounterpartExists(): void
    {
        $htmlFile = $this->tempDirectory . '/Template.html';
        $fluidHtmlFile = $this->tempDirectory . '/Template.fluid.html';
        touch($htmlFile);

        $fixResult = $this->rule->fix($htmlFile, allowRisky: false);

        self::assertSame(FixStatus::Applied, $fixResult->status);
        self::assertStringContainsString('Template.html', $fixResult->description);
        self::assertStringContainsString('Template.fluid.html', $fixResult->description);
        self::assertFileDoesNotExist($htmlFile);
        self::assertFileExists($fluidHtmlFile);
    }

    // --- fix: delete (risky) ---

    #[Test]
    public function fixSkipsDeleteWhenCounterpartExistsAndAllowRiskyIsFalse(): void
    {
        $htmlFile = $this->tempDirectory . '/Template.html';
        $fluidHtmlFile = $this->tempDirectory . '/Template.fluid.html';
        touch($htmlFile);
        touch($fluidHtmlFile);

        $fixResult = $this->rule->fix($htmlFile, allowRisky: false);

        self::assertSame(FixStatus::Skipped, $fixResult->status);
        self::assertStringContainsString('--allow-risky', $fixResult->description);
        self::assertFileExists($htmlFile, 'HTML file must not be deleted without --allow-risky');
    }

    #[Test]
    public function fixDeletesHtmlWhenCounterpartExistsAndAllowRiskyIsTrue(): void
    {
        $htmlFile = $this->tempDirectory . '/Template.html';
        $fluidHtmlFile = $this->tempDirectory . '/Template.fluid.html';
        touch($htmlFile);
        touch($fluidHtmlFile);

        $fixResult = $this->rule->fix($htmlFile, allowRisky: true);

        self::assertSame(FixStatus::Applied, $fixResult->status);
        self::assertFileDoesNotExist($htmlFile, 'HTML file must be deleted with --allow-risky');
        self::assertFileExists($fluidHtmlFile, 'fluid.html counterpart must be kept');
    }

    // --- fix: non-applicable files ---

    #[Test]
    public function fixReturnsNoneForFluidHtmlFileItself(): void
    {
        $fluidHtmlFile = $this->tempDirectory . '/Template.fluid.html';
        touch($fluidHtmlFile);

        $fixResult = $this->rule->fix($fluidHtmlFile, allowRisky: false);

        self::assertSame(FixStatus::None, $fixResult->status);
    }

    // --- metadata ---

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