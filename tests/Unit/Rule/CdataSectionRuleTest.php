<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Result\FixStatus;
use OliverThiele\FluidLinter\Rule\CdataSectionRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CdataSectionRuleTest extends TestCase
{
    private CdataSectionRule $rule;
    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $this->rule = new CdataSectionRule();
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
        $content = "<f:comment><![CDATA[first]]></f:comment>\n"
            . "<p>some text</p>\n"
            . '<f:comment><![CDATA[second]]></f:comment>';

        $violations = $this->rule->checkFile($content, 'Test.html');

        self::assertCount(2, $violations);
    }

    #[Test]
    public function cdataOutsideCommentNotFlaggedEvenWithCommentPresent(): void
    {
        // The CDATA in <title> must not be flagged even when an <f:comment> exists in the file
        $content = "<f:comment>regular comment</f:comment>\n"
            . '<title><![CDATA[News]]></title>';

        self::assertSame([], $this->rule->checkFile($content, 'Test.html'));
    }

    #[Test]
    public function fixRemovesCdataDelimitersAndKeepsCommentText(): void
    {
        $filePath = $this->writeTempFile('<f:comment><![CDATA[no output here]]></f:comment>');

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::Applied, $result->status);
        self::assertSame('<f:comment>no output here</f:comment>', file_get_contents($filePath));
    }

    #[Test]
    public function fixKeepsSurroundingHtmlCommentMarkers(): void
    {
        // The HTML comment markers are kept on purpose — they grey the text out in the IDE
        $filePath = $this->writeTempFile('<f:comment><!-- <![CDATA[###### CATALOG ######]]> --></f:comment>');

        $this->rule->fix($filePath, false);

        self::assertSame(
            '<f:comment><!-- ###### CATALOG ###### --></f:comment>',
            file_get_contents($filePath),
        );
    }

    #[Test]
    public function fixDropsLinesThatHeldNothingButDelimiters(): void
    {
        $content = "<f:comment>\n"
            . "<![CDATA[\n"
            . "    {myVar}\n"
            . "]]>\n"
            . '</f:comment>';
        $filePath = $this->writeTempFile($content);

        $this->rule->fix($filePath, false);

        self::assertSame("<f:comment>\n    {myVar}\n</f:comment>", file_get_contents($filePath));
    }

    #[Test]
    public function fixTrimsWhitespaceLeftBehindAtTheEndOfALine(): void
    {
        $content = "    <f:comment> <!-- <![CDATA[\n"
            . "        old markup\n"
            . '    ]]> --></f:comment>';
        $filePath = $this->writeTempFile($content);

        $this->rule->fix($filePath, false);

        self::assertSame(
            "    <f:comment> <!--\n        old markup\n     --></f:comment>",
            file_get_contents($filePath),
        );
    }

    #[Test]
    public function fixDoesNotTouchCdataOutsideFComment(): void
    {
        $content = "<f:comment><![CDATA[commented out]]></f:comment>\n"
            . "<title><![CDATA[News & Updates]]></title>\n"
            . '<script>/* <![CDATA[ */ var x = 1; /* ]]> */</script>';
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::Applied, $result->status);
        self::assertSame(
            "<f:comment>commented out</f:comment>\n"
            . "<title><![CDATA[News & Updates]]></title>\n"
            . '<script>/* <![CDATA[ */ var x = 1; /* ]]> */</script>',
            file_get_contents($filePath),
        );
    }

    #[Test]
    public function fixHandlesMultipleCommentsAndReportsTheCount(): void
    {
        $content = "<f:comment><![CDATA[first]]></f:comment>\n"
            . "<p>untouched {variable}</p>\n"
            . '<f:comment><![CDATA[second]]><![CDATA[third]]></f:comment>';
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertStringContainsString('3', $result->description);
        self::assertSame(
            "<f:comment>first</f:comment>\n"
            . "<p>untouched {variable}</p>\n"
            . '<f:comment>secondthird</f:comment>',
            file_get_contents($filePath),
        );
    }

    #[Test]
    public function fixLeavesNoViolationBehind(): void
    {
        $content = "<f:comment> <!-- <![CDATA[\n"
            . "    <f:nonexistent />\n"
            . "]]> --></f:comment>\n"
            . '<f:comment><![CDATA[second]]></f:comment>';
        $filePath = $this->writeTempFile($content);

        $this->rule->fix($filePath, false);

        $fixed = file_get_contents($filePath);
        self::assertIsString($fixed);
        self::assertSame([], $this->rule->checkFile($fixed, $filePath));
    }

    #[Test]
    public function fixReturnsNoneWhenNoCdataInsideAComment(): void
    {
        $content = "<f:comment>a regular comment</f:comment>\n"
            . '<title><![CDATA[News]]></title>';
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::None, $result->status);
        self::assertSame($content, file_get_contents($filePath));
    }

    #[Test]
    public function fixIsNotConfusedBySelfClosingCommentTag(): void
    {
        // <f:comment /> carries no content — treating it as an opening tag would pair the
        // closing tag below with the wrong comment and swallow the CDATA in <title>
        $content = "<f:comment />\n"
            . "<f:comment>commented out</f:comment>\n"
            . '<title><![CDATA[News]]></title>';
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::None, $result->status);
        self::assertSame($content, file_get_contents($filePath));
    }
}
