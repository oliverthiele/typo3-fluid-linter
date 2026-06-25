<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

interface VersionedRuleInterface
{
    /**
     * Minimum TYPO3 major version this rule applies to (inclusive), or null for no lower bound.
     */
    public function getMinimumTypo3Version(): ?int;

    /**
     * Maximum TYPO3 major version this rule applies to (inclusive), or null for no upper bound.
     */
    public function getMaximumTypo3Version(): ?int;
}
