<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Result;

final class Violation
{
    public function __construct(
        public readonly string $file,
        public readonly int $line,
        public readonly string $rule,
        public readonly string $message,
        public readonly string $severity = 'error',
    ) {
    }
}
