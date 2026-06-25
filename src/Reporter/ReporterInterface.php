<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Reporter;

use OliverThiele\FluidLinter\Result\LintResult;

interface ReporterInterface
{
    /**
     * @param array<string, LintResult> $results Keyed by file path
     */
    public function report(array $results): void;
}
