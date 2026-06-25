<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Rule\XmlDeclarationRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class XmlDeclarationRuleTest extends TestCase
{
    private XmlDeclarationRule $rule;

    protected function setUp(): void
    {
        $this->rule = new XmlDeclarationRule();
    }

    #[Test]
    public function violationForXmlDeclarationAtStart(): void
    {
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<html>";

        $violations = $this->rule->checkFile($content, 'Test.html');

        self::assertCount(1, $violations);
        self::assertSame('warning', $violations[0]['severity']);
        self::assertSame(1, $violations[0]['line']);
    }

    #[Test]
    public function noViolationForHtmlWithoutXmlDeclaration(): void
    {
        $content = "<!DOCTYPE html>\n<html lang=\"en\">";

        self::assertSame([], $this->rule->checkFile($content, 'Test.html'));
    }

    #[Test]
    public function noViolationWhenXmlAppearsInContent(): void
    {
        // <?xml in the middle of content (not at file start) must not trigger the rule
        $content = "<p>Use <?xml ...?> for RSS feeds</p>";

        self::assertSame([], $this->rule->checkFile($content, 'Test.html'));
    }
}