<?php

/**
 * Clean-room guard (issue #3): fail if anything resembling the old 3M-I engine is
 * present.
 *
 * The substantive check is a positive invariant — every namespaced PHP file in the
 * bundle must belong to Introibo\Ordo (the plugin) or Introibo\Core (the vendored
 * engine). A stray class from any prior engine would declare some other namespace
 * and fail here. A short, extensible denylist of old-engine identifiers backs it up.
 *
 * Run in CI on every push/PR. Exits non-zero (printing every offending file) on any
 * violation, zero when the tree is clean.
 *
 * @package Introibo\Ordo
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$self = __FILE__;

// Extend this as old-engine symbols surface. Clean-room Ordo/Core never contain these.
$denylist = array(
    '/\bThree[_\- ]?M[_\- ]?I\b/i',
    '/\bclass\s+MMM_?Ordo\b/i',
    '/\bfunction\s+mmm_ordo_/i',
);

$errors = array();

$walk = static function (string $dir) use (&$walk, $root, $self, $denylist, &$errors): void {
    foreach (scandir($dir) ?: array() as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . '/' . $entry;

        // Never descend into third-party or VCS directories.
        if (is_dir($path)) {
            if ($entry === 'vendor' || $entry === '.git' || $entry === 'node_modules') {
                continue;
            }
            $walk($path);
            continue;
        }

        if (substr($entry, -4) !== '.php' || $path === $self) {
            continue;
        }

        $rel = ltrim(str_replace('\\', '/', substr($path, strlen($root))), '/');
        $code = (string) file_get_contents($path);

        // Positive invariant: any declared namespace must be an Introibo namespace,
        // and code vendored under src/Core/ must be Introibo\Core specifically.
        if (preg_match('/^\s*namespace\s+([^;]+);/m', $code, $m) === 1) {
            $namespace = trim($m[1]);
            $expected = (strpos($rel, 'src/Core/') === 0) ? 'Introibo\\Core' : 'Introibo\\Ordo';
            if (strpos($namespace, $expected) !== 0) {
                $errors[] = "$rel: namespace '$namespace' is not under '$expected' (old-engine code?)";
            }
        }

        // Negative denylist: no old-engine identifiers anywhere.
        foreach ($denylist as $pattern) {
            if (preg_match($pattern, $code) === 1) {
                $errors[] = "$rel: matches forbidden old-engine pattern $pattern";
            }
        }
    }
};

$walk($root);

if ($errors !== array()) {
    fwrite(STDERR, "check-no-old-engine: FAILED\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '  - ' . $error . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "check-no-old-engine: clean — only Introibo\\Ordo and Introibo\\Core namespaces present.\n");
exit(0);
