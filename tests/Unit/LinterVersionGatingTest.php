<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit;

use OliverThiele\FluidLinter\Config\LintConfig;
use OliverThiele\FluidLinter\Linter;
use OliverThiele\FluidLinter\Rule\FluidFileExtensionRule;
use OliverThiele\FluidLinter\Rule\UnderscoreVariableRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests that VersionedRuleInterface rules are only applied for the correct TYPO3 versions.
 *
 * These tests cover the bug where UnderscoreVariableRule was firing on TYPO3 v13 projects
 * because VersionedRuleInterface was not implemented — causing false positives for
 * underscore-prefixed variables that are valid in Fluid 4 / TYPO3 v13.
 */
final class LinterVersionGatingTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/fluid-linter-test-' . uniqid('', true) . '.html';
        file_put_contents($this->tempFile, '<p>{_myVariable}</p>');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    #[Test]
    public function underscoreVariableRuleIsSkippedWithoutTypo3Version(): void
    {
        $linter = new Linter(new LintConfig());
        $linter->addRule(new UnderscoreVariableRule());

        $result = $linter->lintFile($this->tempFile);

        self::assertFalse($result->hasViolations(), 'Rule must be inactive when no TYPO3 version is set');
    }

    #[Test]
    public function underscoreVariableRuleIsSkippedForTypo3Version13(): void
    {
        $config = (new LintConfig())->setTypo3Version(13);
        $linter = new Linter($config);
        $linter->addRule(new UnderscoreVariableRule());

        $result = $linter->lintFile($this->tempFile);

        self::assertFalse($result->hasViolations(), 'Rule must be inactive for TYPO3 v13 — underscore variables are valid in Fluid 4');
    }

    #[Test]
    public function underscoreVariableRuleFiresForTypo3Version14(): void
    {
        $config = (new LintConfig())->setTypo3Version(14);
        $linter = new Linter($config);
        $linter->addRule(new UnderscoreVariableRule());

        $result = $linter->lintFile($this->tempFile);

        self::assertTrue($result->hasViolations(), 'Rule must fire for TYPO3 v14 — underscore variables are forbidden in Fluid 5');
    }

    #[Test]
    public function fluidFileExtensionRuleIsSkippedWithoutTypo3Version(): void
    {
        $linter = new Linter(new LintConfig());
        $linter->addRule(new FluidFileExtensionRule());

        $result = $linter->lintFile($this->tempFile);

        self::assertFalse($result->hasViolations(), 'FluidFileExtensionRule must be inactive without a TYPO3 version');
    }

    #[Test]
    public function configSeverityOverrideIsApplied(): void
    {
        file_put_contents($this->tempFile, '<f:debug />');
        $config = (new LintConfig())->rule('debug-viewhelper', severity: 'warning');

        $linter = new Linter($config);

        // Add a non-versioned rule whose default would be error
        $linter->addRule(new \OliverThiele\FluidLinter\Rule\DebugViewHelperRule());

        $result = $linter->lintFile($this->tempFile);

        self::assertTrue($result->hasViolations());
        self::assertSame('warning', $result->getViolations()[0]->severity);
    }

    #[Test]
    public function disabledRuleProducesNoViolations(): void
    {
        file_put_contents($this->tempFile, '<f:debug />');
        $config = (new LintConfig())->disableRule('debug-viewhelper');

        $linter = new Linter($config);
        $linter->addRule(new \OliverThiele\FluidLinter\Rule\DebugViewHelperRule());

        $result = $linter->lintFile($this->tempFile);

        self::assertFalse($result->hasViolations());
    }
}