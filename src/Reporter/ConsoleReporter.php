<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Reporter;

use OliverThiele\FluidLinter\Result\LintResult;

final class ConsoleReporter implements ReporterInterface
{
    private const SEVERITY_LABELS = [
        'error' => 'ERROR',
        'warning' => 'WARN ',
        'info' => 'INFO ',
    ];

    /**
     * @param array<string, LintResult> $results
     */
    public function report(array $results): void
    {
        $counts = ['error' => 0, 'warning' => 0, 'info' => 0];

        foreach ($results as $filePath => $result) {
            if (!$result->hasViolations()) {
                continue;
            }

            echo PHP_EOL . $filePath . PHP_EOL;

            foreach ($result->getViolations() as $violation) {
                $label = self::SEVERITY_LABELS[$violation->severity] ?? 'ERROR';
                echo sprintf(
                    "  Line %d  [%s]  %s  %s\n",
                    $violation->line,
                    $label,
                    $violation->rule,
                    $violation->message,
                );
                $counts[$violation->severity] = ($counts[$violation->severity] ?? 0) + 1;
            }
        }

        $total = array_sum($counts);
        if ($total > 0) {
            $summary = [];
            if ($counts['error'] > 0) {
                $summary[] = $counts['error'] . ' error(s)';
            }
            if ($counts['warning'] > 0) {
                $summary[] = $counts['warning'] . ' warning(s)';
            }
            if ($counts['info'] > 0) {
                $summary[] = $counts['info'] . ' info(s)';
            }
            echo PHP_EOL . 'Found ' . implode(', ', $summary) . '.' . PHP_EOL;
        } else {
            echo 'No violations found.' . PHP_EOL;
        }
    }
}
