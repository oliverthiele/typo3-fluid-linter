<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Reporter;

use OliverThiele\FluidLinter\Result\LintResult;

final class GithubActionsReporter implements ReporterInterface
{
    // GitHub Actions annotation levels: error, warning, notice
    private const SEVERITY_MAP = [
        'error' => 'error',
        'warning' => 'warning',
        'info' => 'notice',
    ];

    /**
     * @param array<string, LintResult> $results
     */
    public function report(array $results): void
    {
        foreach ($results as $result) {
            foreach ($result->getViolations() as $violation) {
                $level = self::SEVERITY_MAP[$violation->severity] ?? 'error';
                echo sprintf(
                    "::%s file=%s,line=%d,title=%s::%s\n",
                    $level,
                    $violation->file,
                    $violation->line,
                    $violation->rule,
                    $violation->message,
                );
            }
        }
    }
}
