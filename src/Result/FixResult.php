<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Result;

final class FixResult
{
    public function __construct(
        public readonly FixStatus $status,
        public readonly string $description,
    ) {
    }
}
