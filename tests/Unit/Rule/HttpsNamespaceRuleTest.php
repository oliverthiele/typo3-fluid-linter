<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Tests\Unit\Rule;

use OliverThiele\FluidLinter\Rule\HttpsNamespaceRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HttpsNamespaceRuleTest extends TestCase
{
    private HttpsNamespaceRule $rule;

    protected function setUp(): void
    {
        $this->rule = new HttpsNamespaceRule();
    }

    #[Test]
    public function noViolationForCorrectHttpPrefix(): void
    {
        self::assertSame([], $this->rule->check('xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"', 1));
    }

    #[Test]
    public function violationForHttpsPrefix(): void
    {
        $violations = $this->rule->check('xmlns:f="https://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"', 5);

        self::assertCount(1, $violations);
        self::assertSame(5, $violations[0]['line']);
    }

    #[Test]
    public function noViolationForUnrelatedHttpsUrl(): void
    {
        // A link href using https must not trigger the rule
        self::assertSame([], $this->rule->check('<a href="https://example.com">link</a>', 1));
    }
}