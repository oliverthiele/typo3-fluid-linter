<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter;

use OliverThiele\FluidLinter\Config\LintConfig;
use OliverThiele\FluidLinter\Result\LintResult;
use OliverThiele\FluidLinter\Result\Violation;
use OliverThiele\FluidLinter\Rule\FileRuleInterface;
use OliverThiele\FluidLinter\Rule\RuleInterface;
use OliverThiele\FluidLinter\Rule\VersionedRuleInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class Linter
{
    /** @var list<RuleInterface|FileRuleInterface> */
    private array $rules = [];

    public function __construct(private readonly LintConfig $config = new LintConfig())
    {
    }

    public function addRule(RuleInterface|FileRuleInterface $rule): void
    {
        $this->rules[] = $rule;
    }

    /** @return list<RuleInterface|FileRuleInterface> */
    public function getRules(): array
    {
        return $this->rules;
    }

    public function lintFile(string $filePath): LintResult
    {
        $result = new LintResult();
        $content = file_get_contents($filePath);

        if ($content === false) {
            return $result;
        }

        foreach ($this->rules as $rule) {
            if (!$this->isRuleApplicable($rule)) {
                continue;
            }

            if ($rule instanceof FileRuleInterface) {
                foreach ($rule->checkFile($content, $filePath) as $ruleViolation) {
                    $result->addViolation(new Violation(
                        file: $filePath,
                        line: $ruleViolation['line'],
                        rule: $rule->getName(),
                        message: $ruleViolation['message'],
                        severity: $this->resolveSeverity($rule->getName(), $ruleViolation['severity']),
                    ));
                }
                continue;
            }

            $lines = explode("\n", $content);
            foreach ($lines as $lineIndex => $line) {
                foreach ($rule->check($line, $lineIndex + 1) as $ruleViolation) {
                    $result->addViolation(new Violation(
                        file: $filePath,
                        line: $ruleViolation['line'],
                        rule: $rule->getName(),
                        message: $ruleViolation['message'],
                        severity: $this->resolveSeverity($rule->getName(), 'error'),
                    ));
                }
            }
        }

        return $result;
    }

    /**
     * @return array<string, LintResult>
     */
    public function lintDirectory(string $directory, string $extension = 'html'): array
    {
        $results = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === $extension) {
                $results[$file->getPathname()] = $this->lintFile($file->getPathname());
            }
        }

        return $results;
    }

    private function isRuleApplicable(RuleInterface|FileRuleInterface $rule): bool
    {
        if (!$this->config->isRuleEnabled($rule->getName())) {
            return false;
        }

        if (!$rule instanceof VersionedRuleInterface) {
            return true;
        }

        $typo3Version = $this->config->getTypo3Version();
        if ($typo3Version === null) {
            return false;
        }

        $minimum = $rule->getMinimumTypo3Version();
        $maximum = $rule->getMaximumTypo3Version();

        if ($minimum !== null && $typo3Version < $minimum) {
            return false;
        }

        if ($maximum !== null && $typo3Version > $maximum) {
            return false;
        }

        return true;
    }

    private function resolveSeverity(string $ruleName, string $defaultSeverity): string
    {
        return $this->config->getOverrideSeverity($ruleName) ?? $defaultSeverity;
    }
}
