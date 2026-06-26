<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Rule\CdataSectionRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CdataSectionRuleTest extends TestCase
{
    private CdataSectionRule $rule;

    protected function setUp(): void
    {
        $this->rule = new CdataSectionRule();
    }

    #[Test]
    public function violationForCdataInsideFComment(): void
    {
        $content = '<f:comment><![CDATA[{myVar}]]></f:comment>';

        $violations = $this->rule->checkFile($content, 'Test.html');

        self::assertCount(1, $violations);
        self::assertSame('error', $violations[0]['severity']);
    }

    #[Test]
    public function noViolationForCdataOutsideFComment(): void
    {
        // Legitimate CDATA in an XML/RSS feed — must not be flagged
        $content = '<title><![CDATA[News & Updates]]></title>';

        self::assertSame([], $this->rule->checkFile($content, 'Feed.html'));
    }

    #[Test]
    public function noViolationForFCommentWithoutCdata(): void
    {
        $content = '<f:comment>This is a regular Fluid comment</f:comment>';

        self::assertSame([], $this->rule->checkFile($content, 'Test.html'));
    }

    #[Test]
    public function violationReportsCorrectLineNumber(): void
    {
        $content = "<f:comment>\n<![CDATA[\n{myVar}\n]]>\n</f:comment>";

        $violations = $this->rule->checkFile($content, 'Test.html');

        self::assertSame(2, $violations[0]['line']);
    }

    #[Test]
    public function multipleCdataBlocksInMultipleComments(): void
    {
        $content = "<f:comment><![CDATA[first]]></f:comment>\n" .
            "<p>some text</p>\n" .
            '<f:comment><![CDATA[second]]></f:comment>';

        $violations = $this->rule->checkFile($content, 'Test.html');

        self::assertCount(2, $violations);
    }

    #[Test]
    public function cdataOutsideCommentNotFlaggedEvenWithCommentPresent(): void
    {
        // The CDATA in <title> must not be flagged even when an <f:comment> exists in the file
        $content = "<f:comment>regular comment</f:comment>\n" .
            '<title><![CDATA[News]]></title>';

        self::assertSame([], $this->rule->checkFile($content, 'Test.html'));
    }
}
