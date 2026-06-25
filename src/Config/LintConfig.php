<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Config;

final class LintConfig
{
    private ?int $typo3Version = null;

    /** @var array<string, array{enabled: bool, severity: string}> */
    private array $ruleConfigurations = [];

    public function setTypo3Version(int $version): static
    {
        $this->typo3Version = $version;
        return $this;
    }

    public function rule(string $ruleName, string $severity = 'error', bool $enabled = true): static
    {
        $this->ruleConfigurations[$ruleName] = ['enabled' => $enabled, 'severity' => $severity];
        return $this;
    }

    public function disableRule(string $ruleName): static
    {
        $this->ruleConfigurations[$ruleName] = ['enabled' => false, 'severity' => 'error'];
        return $this;
    }

    public function getTypo3Version(): ?int
    {
        return $this->typo3Version;
    }

    public function isRuleEnabled(string $ruleName): bool
    {
        return $this->ruleConfigurations[$ruleName]['enabled'] ?? true;
    }

    public function getOverrideSeverity(string $ruleName): ?string
    {
        return isset($this->ruleConfigurations[$ruleName])
            ? $this->ruleConfigurations[$ruleName]['severity']
            : null;
    }
}
