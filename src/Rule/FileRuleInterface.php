<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

interface FileRuleInterface
{
    public function getName(): string;

    /**
     * @return list<array{line: int, message: string, severity: string}>
     */
    public function checkFile(string $content, string $filePath): array;
}
