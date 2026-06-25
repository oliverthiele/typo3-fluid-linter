<?php

declare(strict_types=1);

use OliverThiele\FluidLinter\Config\LintConfig;

// Live/production config — f:debug is an error and blocks the pipeline.
// Use this in CI for the main branch or release gate.
// Example: vendor/bin/fluid-lint --config=.fluid-lint.live.php packages/
return (new LintConfig())
    ->rule('debug-viewhelper', severity: 'error');
