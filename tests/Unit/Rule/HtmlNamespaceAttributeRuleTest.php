<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Rule\HtmlNamespaceAttributeRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HtmlNamespaceAttributeRuleTest extends TestCase
{
    private HtmlNamespaceAttributeRule $rule;

    protected function setUp(): void
    {
        $this->rule = new HtmlNamespaceAttributeRule();
    }

    #[Test]
    public function noViolationWhenDataAttributeIsPresent(): void
    {
        $content = '<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers" data-namespace-typo3-fluid="true">';

        self::assertSame([], $this->rule->checkFile($content, 'Test.html'));
    }

    #[Test]
    public function noViolationForLayoutTemplate(): void
    {
        // Layout templates are never rendered directly — <html> is stripped by Fluid
        $content = '<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">' . "\n" .
            '<f:layout name="Default" />';

        self::assertSame([], $this->rule->checkFile($content, 'Default.html'));
    }

    #[Test]
    public function noViolationWhenNoFluidNamespace(): void
    {
        $content = '<html lang="en"><body><p>No Fluid here</p></body></html>';

        self::assertSame([], $this->rule->checkFile($content, 'Test.html'));
    }

    #[Test]
    public function violationWhenFluidNamespacePresentButDataAttributeMissing(): void
    {
        $content = '<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">' . "\n" .
            '<body><f:render partial="Foo" /></body></html>';

        $violations = $this->rule->checkFile($content, 'Test.html');

        self::assertCount(1, $violations);
        self::assertSame('error', $violations[0]['severity']);
        self::assertSame(1, $violations[0]['line']);
    }

    #[Test]
    public function violationReportsCorrectLineNumber(): void
    {
        $content = "\n\n" . '<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">';

        $violations = $this->rule->checkFile($content, 'Test.html');

        self::assertSame(3, $violations[0]['line']);
    }
}