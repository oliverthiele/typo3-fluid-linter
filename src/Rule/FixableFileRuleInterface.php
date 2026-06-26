<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

use OliverThiele\FluidLinter\Result\FixResult;

interface FixableFileRuleInterface
{
    /**
     * Apply an automatic fix for violations found in the given file.
     *
     * @param bool $allowRisky Allow destructive operations (delete, overwrite). Requires explicit user opt-in.
     */
    public function fix(string $filePath, bool $allowRisky): FixResult;
}