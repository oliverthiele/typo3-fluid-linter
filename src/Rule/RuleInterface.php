<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

interface RuleInterface
{
    public function getName(): string;

    /**
     * @return list<array{line: int, message: string}>
     */
    public function check(string $line, int $lineNumber): array;
}
