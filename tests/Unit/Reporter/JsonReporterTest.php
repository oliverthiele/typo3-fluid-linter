<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Reporter;

use OliverThiele\FluidLinter\Reporter\JsonReporter;
use OliverThiele\FluidLinter\Result\LintResult;
use OliverThiele\FluidLinter\Result\Violation;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JsonReporterTest extends TestCase
{
    private function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();
        return ob_get_clean() ?: '';
    }

    #[Test]
    public function outputIsValidJson(): void
    {
        $reporter = new JsonReporter();
        $output = $this->captureOutput(fn () => $reporter->report([]));

        self::assertJson($output);
    }

    #[Test]
    public function emptyResultsProduceEmptyViolationsArray(): void
    {
        $reporter = new JsonReporter();
        $output = $this->captureOutput(fn () => $reporter->report([]));
        $data = json_decode($output, true);

        self::assertSame([], $data['violations']);
        self::assertSame(0, $data['summary']['errors']);
        self::assertSame(0, $data['summary']['warnings']);
        self::assertSame(0, $data['summary']['infos']);
        self::assertSame(0, $data['summary']['files_checked']);
        self::assertSame(0, $data['summary']['files_with_violations']);
    }

    #[Test]
    public function violationsAreIncludedWithAllFields(): void
    {
        $result = new LintResult();
        $result->addViolation(new Violation(
            file: 'path/to/Template.html',
            line: 5,
            rule: 'typographic-quotes',
            message: 'Invalid quote character',
            severity: 'error',
        ));

        $reporter = new JsonReporter();
        $output = $this->captureOutput(fn () => $reporter->report(['path/to/Template.html' => $result]));
        $data = json_decode($output, true);

        self::assertCount(1, $data['violations']);
        self::assertSame('path/to/Template.html', $data['violations'][0]['file']);
        self::assertSame(5, $data['violations'][0]['line']);
        self::assertSame('typographic-quotes', $data['violations'][0]['rule']);
        self::assertSame('error', $data['violations'][0]['severity']);
        self::assertSame('Invalid quote character', $data['violations'][0]['message']);
    }

    #[Test]
    public function summaryCountsAreCorrect(): void
    {
        $result = new LintResult();
        $result->addViolation(new Violation('a.html', 1, 'rule-a', 'msg', 'error'));
        $result->addViolation(new Violation('a.html', 2, 'rule-b', 'msg', 'error'));
        $result->addViolation(new Violation('a.html', 3, 'rule-c', 'msg', 'warning'));
        $result->addViolation(new Violation('a.html', 4, 'rule-d', 'msg', 'info'));

        $emptyResult = new LintResult();

        $reporter = new JsonReporter();
        $output = $this->captureOutput(
            fn () => $reporter->report(['a.html' => $result, 'b.html' => $emptyResult])
        );
        $data = json_decode($output, true);

        self::assertSame(2, $data['summary']['errors']);
        self::assertSame(1, $data['summary']['warnings']);
        self::assertSame(1, $data['summary']['infos']);
        self::assertSame(2, $data['summary']['files_checked']);
        self::assertSame(1, $data['summary']['files_with_violations']);
    }

    #[Test]
    public function outputEndsWithNewline(): void
    {
        $reporter = new JsonReporter();
        $output = $this->captureOutput(fn () => $reporter->report([]));

        self::assertStringEndsWith(PHP_EOL, $output);
    }

    #[Test]
    public function slashesAreNotEscaped(): void
    {
        $result = new LintResult();
        $result->addViolation(new Violation('path/to/Template.html', 1, 'rule', 'msg', 'error'));

        $reporter = new JsonReporter();
        $output = $this->captureOutput(fn () => $reporter->report(['path/to/Template.html' => $result]));

        self::assertStringContainsString('path/to/Template.html', $output, 'Forward slashes must not be escaped');
    }
}