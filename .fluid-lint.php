<?php

declare(strict_types=1);

use OliverThiele\FluidLinter\Config\LintConfig;

// Development config — f:debug is allowed (warning only), version-specific rules disabled.
// Copy to your project root and adjust as needed.
return (new LintConfig())
    ->rule('debug-viewhelper', severity: 'warning');
