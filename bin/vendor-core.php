<?php

/**
 * Vendor the clean-room Directorium Core engine into src/Core/ (issue #3).
 *
 * Ordo ships standalone: the engine is bundled inside the plugin so a site
 * resolves liturgical days with no API and no network. This script is the
 * documented, repeatable build step that copies Core's runtime source and its
 * CC0 corpus into src/Core/, records the exact Core version, and copies Core's
 * own AGPL licence alongside it. The vendored tree is committed to the repo;
 * this script is a maintainer step, never run on the WordPress host.
 *
 * Clean-room constraint: the ONLY source ever copied is the Directorium Core
 * repository. Nothing from any prior engine is admitted; bin/check-no-old-engine.php
 * enforces that in CI.
 *
 * Usage:
 *   php bin/vendor-core.php [--from=/path/to/Core]
 *
 * The source defaults to a sibling ../Core checkout (the platform working tree);
 * pass --from or set CORE_SRC to vendor from anywhere.
 *
 * @package Directorium\Ordo
 */

declare(strict_types=1);

$pluginRoot = dirname(__DIR__);

// Resolve the Core source: --from=… wins, then $CORE_SRC, then the sibling ../Core.
$source = null;
$args = $_SERVER['argv'] ?? array();
foreach ($args as $arg) {
    if (strncmp($arg, '--from=', 7) === 0) {
        $source = substr($arg, 7);
    }
}
if ($source === null || $source === '') {
    $source = getenv('CORE_SRC') ?: dirname($pluginRoot) . '/Core';
}
$source = rtrim(str_replace('\\', '/', $source), '/');

$fail = static function (string $message): void {
    fwrite(STDERR, 'vendor-core: ' . $message . PHP_EOL);
    exit(1);
};

// Sanity-check that $source really is a Core checkout before touching anything.
foreach (['src/Directorium.php', 'src/functions.php', 'data/corpus', 'LICENSE'] as $required) {
    if (!file_exists($source . '/' . $required)) {
        $fail("source '$source' is not an Directorium Core checkout (missing $required).");
    }
}

// Read the engine version straight from the source (no autoload needed).
$directoriumPhp = (string) file_get_contents($source . '/src/Directorium.php');
if (preg_match("/const\s+VERSION\s*=\s*'([^']+)'/", $directoriumPhp, $m) !== 1) {
    $fail('could not read Directorium::VERSION from the source.');
}
$version = $m[1];

// Best-effort git ref of the source, so the pin is exact and auditable.
$ref = 'unknown';
$out = [];
$status = 0;
$devNull = stripos(PHP_OS, 'WIN') === 0 ? '2>NUL' : '2>/dev/null';
@exec('git -C ' . escapeshellarg($source) . ' rev-parse HEAD ' . $devNull, $out, $status);
if ($status === 0 && isset($out[0]) && $out[0] !== '') {
    $ref = trim($out[0]);
}

$target = $pluginRoot . '/src/Core';

// Recursively remove a path (clean the target so no stale file survives a re-vendor).
$rmTree = static function (string $path) use (&$rmTree): void {
    if (is_dir($path) && !is_link($path)) {
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                $rmTree($path . '/' . $entry);
            }
        }
        @rmdir($path);
        return;
    }
    if (file_exists($path) || is_link($path)) {
        @unlink($path);
    }
};

// Recursively copy a directory tree.
$copyTree = static function (string $from, string $to) use (&$copyTree, $fail): void {
    if (!is_dir($to) && !mkdir($to, 0755, true) && !is_dir($to)) {
        $fail("could not create '$to'.");
    }
    foreach (scandir($from) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $src = $from . '/' . $entry;
        $dst = $to . '/' . $entry;
        if (is_dir($src)) {
            $copyTree($src, $dst);
        } elseif (!copy($src, $dst)) {
            $fail("could not copy '$src'.");
        }
    }
};

$rmTree($target);
if (!mkdir($target, 0755, true) && !is_dir($target)) {
    $fail("could not create '$target'.");
}

// The runtime engine and its CC0 corpus — everything Core needs to resolve a day.
$copyTree($source . '/src', $target . '/src');
$copyTree($source . '/data/corpus', $target . '/data/corpus');

// Core's own AGPL licence travels with the bundled component.
if (!copy($source . '/LICENSE', $target . '/LICENSE')) {
    $fail('could not copy Core LICENSE.');
}

// Pin the exact version, ref and provenance so the bundle is auditable.
$stamp = date('Y-m-d');
$pin = <<<TXT
Directorium Core — vendored into this plugin (do not edit these files by hand).

version: $version
ref:     $ref
source:  https://github.com/Directorium/Core
vendored: $stamp

Regenerate with:  php bin/vendor-core.php
Core is licensed AGPL-3.0-or-later (see LICENSE in this directory); the plugin
itself is GPL-2.0-or-later. The corpus under data/corpus/ is CC0.
TXT;
file_put_contents($target . '/CORE_VERSION', $pin . PHP_EOL);

$readme = <<<MD
# Vendored Directorium Core

This directory is a **generated, committed copy** of the clean-room Directorium Core
engine and its CC0 corpus, bundled so the plugin resolves liturgical days offline.

**Do not edit anything here by hand.** Regenerate with `php bin/vendor-core.php`
(see [`../../bin/vendor-core.php`](../../bin/vendor-core.php)). The pinned version
and source ref are in [`CORE_VERSION`](CORE_VERSION).

Core is licensed **AGPL-3.0-or-later** (see [`LICENSE`](LICENSE)); the plugin that
bundles it is GPL-2.0-or-later — compatible because GPL-2.0-**or-later** reaches
GPLv3, with which AGPL-3.0 combines. The corpus under `data/corpus/` is CC0.
MD;
file_put_contents($target . '/README.md', $readme . PHP_EOL);

$phpCount = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target . '/src', FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if ($file->getExtension() === 'php') {
        $phpCount++;
    }
}

fwrite(STDOUT, "vendor-core: Core $version ($ref) vendored into src/Core/ — $phpCount PHP files.\n");
exit(0);
