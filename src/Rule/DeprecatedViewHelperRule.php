<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Rule;

final class DeprecatedViewHelperRule implements FileRuleInterface, VersionedRuleInterface
{
    /**
     * Each entry describes one deprecated or removed ViewHelper/argument:
     *
     *   pattern  — regex matched line-by-line
     *   since    — first TYPO3 major version where this is an issue (removed or deprecated)
     *   severity — 'error' for removed ViewHelpers, 'warning' for deprecated-but-still-working
     *   message  — human-readable description with migration hint
     *   source   — TYPO3 changelog URL
     */
    private const ENTRIES = [
        // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.0/Breaking-92529-AllFluidWidgetFunctionalityRemoved.html
        [
            'pattern' => '/<f:widget\./',
            'since' => 11,
            'severity' => 'error',
            'message' => '<f:widget.*> ViewHelpers were completely removed in TYPO3 v11 — for pagination use the PHP PaginationInterface API; for other widgets implement a custom controller action.',
        ],

        // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.0/Deprecation-92132-DeprecatedViewHelperFbebuttonsshortcut.html
        [
            'pattern' => '/\bgetVars\s*=/',
            'since' => 11,
            'severity' => 'warning',
            'message' => 'The "getVars" argument on <be:moduleLayout.button.shortcutButton> is deprecated since TYPO3 v11 — use the "arguments" parameter instead.',
        ],

        // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.0/Deprecation-92132-DeprecatedViewHelperFbebuttonsshortcut.html
        [
            'pattern' => '/<f:be\.buttons\.shortcut(?:[\s\/>]|$)/',
            'since' => 12,
            'severity' => 'error',
            'message' => '<f:be.buttons.shortcut> was removed in TYPO3 v12 (deprecated in v11) — use <be:moduleLayout.button.shortcutButton arguments="..."> instead.',
        ],

        // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.3/Deprecation-94225-FbecontainerViewHelper.html
        [
            'pattern' => '/<f:be\.container(?:[\s\/>]|$)/',
            'since' => 11,
            'severity' => 'warning',
            'message' => '<f:be.container> is deprecated since TYPO3 v11.3 — use <f:be.pageRenderer> to register backend resources; render ModuleTemplate from the controller instead.',
        ],

        // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.3/Deprecation-94227-FbaseViewHelper.html
        [
            'pattern' => '/<f:base(?:[\s\/>]|$)/',
            'since' => 12,
            'severity' => 'error',
            'message' => '<f:base> was removed in TYPO3 v12 — use TypoScript config.baseURL or copy the ViewHelper class into your extension if you still need it.',
        ],

        // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.0/Breaking-100963-DeprecatedFunctionalityRemoved.html
        [
            'pattern' => '/<f:be\.buttons\.csh(?:[\s\/>]|$)/',
            'since' => 13,
            'severity' => 'error',
            'message' => '<f:be.buttons.csh> was removed in TYPO3 v13 — context-sensitive help (CSH) has been removed from the backend entirely.',
        ],
        // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.0/Breaking-100963-DeprecatedFunctionalityRemoved.html
        [
            'pattern' => '/<f:be\.labels\.csh(?:[\s\/>]|$)/',
            'since' => 13,
            'severity' => 'error',
            'message' => '<f:be.labels.csh> was removed in TYPO3 v13 — context-sensitive help (CSH) has been removed from the backend entirely.',
        ],

        // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.2/Deprecation-107208-FdebugrenderViewHelper.html
        [
            'pattern' => '/<f:debug\.render(?:[\s\/>]|$)/',
            'since' => 14,
            'severity' => 'warning',
            'message' => '<f:debug.render> is deprecated since TYPO3 v14.2 and will be removed in v15 — create a custom ViewHelper to replace the functionality if needed.',
        ],

        // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.2/Deprecation-100887-UseNonceArgumentInFAssetViewHelpers.html
        [
            'pattern' => '/\buseNonce\s*=/',
            'since' => 14,
            'severity' => 'warning',
            'message' => 'The useNonce argument is deprecated since TYPO3 v14.2 and will be removed in v15 — use csp="1" instead (applies to f:asset.script and f:asset.css).',
        ],
    ];

    public function __construct(private readonly int $typo3Version = 0) {}

    public function getName(): string
    {
        return 'deprecated-viewhelper';
    }

    public function getMinimumTypo3Version(): int
    {
        return 11;
    }

    public function getMaximumTypo3Version(): ?int
    {
        return null;
    }

    public function checkFile(string $content, string $filePath): array
    {
        $violations = [];
        $lines = explode("\n", $content);

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;
            foreach (self::ENTRIES as $entry) {
                if ($this->typo3Version < $entry['since']) {
                    continue;
                }
                if (preg_match($entry['pattern'], $line) === 1) {
                    $violations[] = [
                        'line' => $lineNumber,
                        'message' => $entry['message'],
                        'severity' => $entry['severity'],
                    ];
                }
            }
        }

        return $violations;
    }
}