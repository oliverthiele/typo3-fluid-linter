<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Result\FixStatus;
use OliverThiele\FluidLinter\Rule\XmlDeclarationRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class XmlDeclarationRuleTest extends TestCase
{
    private XmlDeclarationRule $rule;
    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $this->rule = new XmlDeclarationRule();
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

    #[Test]
    public function fixRemovesXmlDeclarationLine(): void
    {
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<html lang=\"en\"></html>";
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::Applied, $result->status);
        $fixed = file_get_contents($filePath);
        self::assertStringNotContainsString('<?xml', $fixed);
        self::assertStringContainsString('<html lang="en">', $fixed);
    }

    #[Test]
    public function fixRemovesDeclarationWithoutLeavingLeadingNewline(): void
    {
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<html>";
        $filePath = $this->writeTempFile($content);

        $this->rule->fix($filePath, false);

        $fixed = file_get_contents($filePath);
        self::assertSame('<html>', $fixed);
    }

    #[Test]
    public function fixReturnsNoneWhenNoXmlDeclarationPresent(): void
    {
        $content = "<!DOCTYPE html>\n<html lang=\"en\">";
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::None, $result->status);
        self::assertSame($content, file_get_contents($filePath));
    }
}