<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Rule\UnderscoreVariableRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UnderscoreVariableRuleTest extends TestCase
{
    private UnderscoreVariableRule $rule;

    protected function setUp(): void
    {
        $this->rule = new UnderscoreVariableRule();
    }

    #[Test]
    public function violationForVariableWithUnderscorePrefix(): void
    {
        $violations = $this->rule->check('<p>{_myVariable}</p>', 7);

        self::assertCount(1, $violations);
        self::assertSame(7, $violations[0]['line']);
        self::assertStringContainsString('_myVariable', $violations[0]['message']);
    }

    #[Test]
    public function noViolationForFluidInternalVariableAll(): void
    {
        // {_all} is a Fluid-internal variable and must remain valid
        self::assertSame([], $this->rule->check('{_all}', 1));
    }

    #[Test]
    public function noViolationForNormalVariable(): void
    {
        self::assertSame([], $this->rule->check('{myVariable}', 1));
    }

    #[Test]
    public function noViolationForSingleUnderscore(): void
    {
        // {_} has no letter after underscore — the pattern requires _[a-zA-Z]
        self::assertSame([], $this->rule->check('{_}', 1));
    }

    #[Test]
    public function multipleViolationsOnOneLine(): void
    {
        $violations = $this->rule->check('{_first} and {_second}', 1);

        self::assertCount(2, $violations);
    }

    #[Test]
    public function minimumTypo3VersionIsFourteen(): void
    {
        self::assertSame(14, $this->rule->getMinimumTypo3Version());
    }

    #[Test]
    public function maximumTypo3VersionIsNull(): void
    {
        self::assertNull($this->rule->getMaximumTypo3Version());
    }
}