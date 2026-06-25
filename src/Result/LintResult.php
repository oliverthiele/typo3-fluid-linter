<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Result;

final class LintResult
{
    /** @var Violation[] */
    private array $violations = [];

    public function addViolation(Violation $violation): void
    {
        $this->violations[] = $violation;
    }

    /** @return Violation[] */
    public function getViolations(): array
    {
        return $this->violations;
    }

    public function hasViolations(): bool
    {
        return $this->violations !== [];
    }

    public function getViolationCount(): int
    {
        return count($this->violations);
    }
}
