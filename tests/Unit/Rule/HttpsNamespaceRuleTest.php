<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Result\FixStatus;
use OliverThiele\FluidLinter\Rule\HttpsNamespaceRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HttpsNamespaceRuleTest extends TestCase
{
    private HttpsNamespaceRule $rule;
    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $this->rule = new HttpsNamespaceRule();
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
    public function noViolationForCorrectHttpPrefix(): void
    {
        self::assertSame([], $this->rule->check('xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"', 1));
    }

    #[Test]
    public function violationForHttpsPrefix(): void
    {
        $violations = $this->rule->check('xmlns:f="https://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"', 5);

        self::assertCount(1, $violations);
        self::assertSame(5, $violations[0]['line']);
    }

    #[Test]
    public function noViolationForUnrelatedHttpsUrl(): void
    {
        // A link href using https must not trigger the rule
        self::assertSame([], $this->rule->check('<a href="https://example.com">link</a>', 1));
    }

    #[Test]
    public function fixReplacesHttpsWithHttp(): void
    {
        $content = '<html xmlns:f="https://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">';
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::Applied, $result->status);
        $fixed = file_get_contents($filePath);
        self::assertStringContainsString('http://typo3.org/ns/', $fixed);
        self::assertStringNotContainsString('https://typo3.org/ns/', $fixed);
    }

    #[Test]
    public function fixReturnsNoneWhenNoHttpsNamespaceFound(): void
    {
        $content = '<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">';
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::None, $result->status);
        self::assertSame($content, file_get_contents($filePath));
    }

    #[Test]
    public function fixReplacesAllOccurrencesAndReportsCount(): void
    {
        $content = '<html' . "\n" .
            '    xmlns:f="https://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"' . "\n" .
            '    xmlns:be="https://typo3.org/ns/TYPO3/CMS/Backend/ViewHelpers">';
        $filePath = $this->writeTempFile($content);

        $result = $this->rule->fix($filePath, false);

        self::assertSame(FixStatus::Applied, $result->status);
        self::assertStringContainsString('2', $result->description);
        $fixed = file_get_contents($filePath);
        self::assertSame(0, substr_count($fixed, 'https://typo3.org/ns/'));
        self::assertSame(2, substr_count($fixed, 'http://typo3.org/ns/'));
    }

    #[Test]
    public function fixDoesNotAffectUnrelatedHttpsUrls(): void
    {
        $content = '<html xmlns:f="https://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">' . "\n" .
            '<a href="https://example.com">link</a>';
        $filePath = $this->writeTempFile($content);

        $this->rule->fix($filePath, false);

        $fixed = file_get_contents($filePath);
        self::assertStringContainsString('https://example.com', $fixed);
    }
}