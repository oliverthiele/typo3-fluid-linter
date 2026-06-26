<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Reporter;

use OliverThiele\FluidLinter\Result\LintResult;

final class JsonReporter implements ReporterInterface
{
    /**
     * @param array<string, LintResult> $results
     */
    public function report(array $results): void
    {
        $violations = [];
        $counts = ['errors' => 0, 'warnings' => 0, 'infos' => 0];
        $filesWithViolations = 0;

        foreach ($results as $filePath => $result) {
            if (!$result->hasViolations()) {
                continue;
            }

            $filesWithViolations++;

            foreach ($result->getViolations() as $violation) {
                $violations[] = [
                    'file' => $filePath,
                    'line' => $violation->line,
                    'rule' => $violation->rule,
                    'severity' => $violation->severity,
                    'message' => $violation->message,
                ];

                match ($violation->severity) {
                    'error' => $counts['errors']++,
                    'warning' => $counts['warnings']++,
                    default => $counts['infos']++,
                };
            }
        }

        echo json_encode(
            [
                'violations' => $violations,
                'summary' => [
                    'errors' => $counts['errors'],
                    'warnings' => $counts['warnings'],
                    'infos' => $counts['infos'],
                    'files_checked' => count($results),
                    'files_with_violations' => $filesWithViolations,
                ],
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ) . PHP_EOL;
    }
}
