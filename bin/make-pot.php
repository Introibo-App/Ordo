<?php

/**
 * Generate languages/ordo.pot from the plugin's translation calls (issue #22).
 *
 * Scans every first-party PHP and block script for the WordPress translation
 * functions and emits a gettext template covering each translatable string. The
 * output is deterministic — no creation-date stamp, entries and file references
 * sorted — so `check-i18n.php` can regenerate it and fail on any drift, making a
 * stale .pot a CI error rather than a silent omission.
 *
 * Latin liturgical tokens (feast names, feriae, season names) are never wrapped in
 * these functions — they are content emitted verbatim from the engine and the
 * LatinCalendar, and so are correctly excluded from the template.
 *
 * Usage: php bin/make-pot.php            (writes languages/ordo.pot)
 *        php bin/make-pot.php --print    (writes to stdout instead)
 *
 * @package Introibo\Ordo
 */

declare(strict_types=1);

const TEXT_DOMAIN = 'ordo';

/** Translation functions whose first string argument is the msgid. */
const FN_SIMPLE = ['__', '_e', 'esc_html__', 'esc_attr__', 'esc_html_e', 'esc_attr_e'];
/** Functions taking (msgid, context, domain). */
const FN_CONTEXT = ['_x', '_ex', 'esc_html_x', 'esc_attr_x'];
/** Functions taking (msgid, msgid_plural, number, domain). */
const FN_PLURAL = ['_n'];
/** Functions taking (msgid, msgid_plural, number, context, domain). */
const FN_PLURAL_CONTEXT = ['_nx'];

$root = dirname(__DIR__);

/**
 * @return list<string>
 */
function target_files(string $root): array
{
    $files = [];

    foreach (['ordo.php', 'uninstall.php'] as $name) {
        $path = $root . '/' . $name;
        if (is_file($path)) {
            $files[] = $path;
        }
    }

    // First-party PHP under src/, but never the vendored Core engine.
    $dirs = ['src', 'templates', 'blocks', 'assets/js'];
    foreach ($dirs as $dir) {
        $base = $root . '/' . $dir;
        if (!is_dir($base)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            $path = $file->getPathname();
            $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
            if (strpos($rel, 'src/Core/') === 0) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if ($ext === 'php' || $ext === 'js') {
                $files[] = $path;
            }
        }
    }

    sort($files);

    return $files;
}

/**
 * Read a PHP/JS string literal starting at $i (which must be a quote). Returns the
 * decoded value and the index just past the closing quote, or null if $i is not a
 * literal (a dynamic argument we cannot extract).
 *
 * @return array{0: string, 1: int}|null
 */
function read_string(string $code, int $i): ?array
{
    $len = strlen($code);
    while ($i < $len && ctype_space($code[$i])) {
        $i++;
    }
    if ($i >= $len) {
        return null;
    }
    $quote = $code[$i];
    if ($quote !== "'" && $quote !== '"') {
        return null;
    }

    $i++;
    $value = '';
    while ($i < $len) {
        $ch = $code[$i];
        if ($ch === '\\' && $i + 1 < $len) {
            $next = $code[$i + 1];
            if ($quote === "'") {
                $value .= ($next === "'" || $next === '\\') ? $next : '\\' . $next;
            } else {
                switch ($next) {
                    case 'n':
                        $value .= "\n";
                        break;
                    case 't':
                        $value .= "\t";
                        break;
                    case 'r':
                        $value .= "\r";
                        break;
                    case '"':
                    case '\\':
                    case '$':
                        $value .= $next;
                        break;
                    default:
                        $value .= '\\' . $next;
                }
            }
            $i += 2;
            continue;
        }
        if ($ch === $quote) {
            return [$value, $i + 1];
        }
        $value .= $ch;
        $i++;
    }

    return null; // Unterminated — ignore.
}

/** Skip whitespace, then a single comma, then whitespace. Returns new index or null. */
function skip_comma(string $code, int $i): ?int
{
    $len = strlen($code);
    while ($i < $len && ctype_space($code[$i])) {
        $i++;
    }
    if ($i >= $len || $code[$i] !== ',') {
        return null;
    }
    $i++;
    while ($i < $len && ctype_space($code[$i])) {
        $i++;
    }

    return $i;
}

/**
 * Skip one argument (e.g. the plural count in _nx) up to the next top-level comma,
 * respecting nested parens/brackets and strings, and return the index of the argument
 * that follows it (past the comma and whitespace), or null if there is none.
 */
function skip_arg(string $code, int $i): ?int
{
    $len = strlen($code);
    $depth = 0;
    $quote = '';
    while ($i < $len) {
        $ch = $code[$i];
        if ($quote !== '') {
            if ($ch === '\\') {
                $i += 2;
                continue;
            }
            if ($ch === $quote) {
                $quote = '';
            }
            $i++;
            continue;
        }
        if ($ch === "'" || $ch === '"') {
            $quote = $ch;
        } elseif ($ch === '(' || $ch === '[') {
            $depth++;
        } elseif ($ch === ')' || $ch === ']') {
            if ($depth === 0) {
                return null; // Reached the call's close paren — no further argument.
            }
            $depth--;
        } elseif ($ch === ',' && $depth === 0) {
            return skip_comma($code, $i);
        }
        $i++;
    }

    return null;
}

function line_of(string $code, int $offset): int
{
    return substr_count($code, "\n", 0, $offset) + 1;
}

/**
 * @param array<string, array{context: string, msgid: string, plural: string, refs: array<string, true>}> $entries
 */
function collect(string $code, string $rel, array &$entries): void
{
    $all = array_merge(FN_SIMPLE, FN_CONTEXT, FN_PLURAL, FN_PLURAL_CONTEXT);
    // Longest names first so esc_html__ is matched before __.
    usort($all, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
    $alt = implode('|', array_map(static fn(string $f): string => preg_quote($f, '/'), $all));
    // A call boundary: not preceded by an identifier char (so "gettext__(" is skipped),
    // the function name, optional space, and an open paren.
    $pattern = '/(?<![\w$>])(' . $alt . ')\s*\(/';

    if (preg_match_all($pattern, $code, $matches, PREG_OFFSET_CAPTURE) === 0) {
        return;
    }

    foreach ($matches[1] as $index => $match) {
        [$fn, $fnOffset] = $match;
        $parenOffset = (int) $matches[0][$index][1] + strlen($matches[0][$index][0]);

        $first = read_string($code, $parenOffset);
        if ($first === null) {
            continue;
        }
        $msgid = $first[0];
        $context = '';
        $plural = '';

        if (in_array($fn, FN_CONTEXT, true)) {
            $afterMsgid = skip_comma($code, $first[1]);
            if ($afterMsgid === null) {
                continue;
            }
            $ctx = read_string($code, $afterMsgid);
            if ($ctx === null) {
                continue;
            }
            $context = $ctx[0];
        } elseif (in_array($fn, FN_PLURAL, true)) {
            $afterMsgid = skip_comma($code, $first[1]);
            if ($afterMsgid === null) {
                continue;
            }
            $pl = read_string($code, $afterMsgid);
            if ($pl === null) {
                continue;
            }
            $plural = $pl[0];
        } elseif (in_array($fn, FN_PLURAL_CONTEXT, true)) {
            // _nx(single, plural, number, context, domain): plural is arg 2, context arg 4.
            $afterMsgid = skip_comma($code, $first[1]);
            if ($afterMsgid === null) {
                continue;
            }
            $pl = read_string($code, $afterMsgid);
            if ($pl === null) {
                continue;
            }
            $plural = $pl[0];
            $atNumber = skip_comma($code, $pl[1]); // Past the plural's comma, at the count.
            if ($atNumber === null) {
                continue;
            }
            $atContext = skip_arg($code, $atNumber); // Skip the (non-string) count argument.
            if ($atContext === null) {
                continue;
            }
            $ctx = read_string($code, $atContext);
            if ($ctx === null) {
                continue;
            }
            $context = $ctx[0];
        }

        if ($msgid === '') {
            continue;
        }

        $key = $context . "\x04" . $msgid . "\x04" . $plural;
        if (!isset($entries[$key])) {
            $entries[$key] = ['context' => $context, 'msgid' => $msgid, 'plural' => $plural, 'refs' => []];
        }
        $ref = $rel . ':' . line_of($code, (int) $fnOffset);
        $entries[$key]['refs'][$ref] = true;
    }
}

function pot_escape(string $text): string
{
    $text = str_replace(['\\', '"'], ['\\\\', '\\"'], $text);
    $text = str_replace(["\t", "\r"], ['\\t', '\\r'], $text);
    $text = str_replace("\n", "\\n\"\n\"", $text);

    return $text;
}

/**
 * Extract a block's translatable metadata (title, description, keywords) from its
 * block.json, using the same msgctxt conventions WordPress core (wp-cli i18n) applies,
 * so the editor-inserter labels are localisable. Only blocks declaring the 'ordo' text
 * domain are harvested — that is the domain WordPress translates their metadata under.
 *
 * @param array<string, array{context: string, msgid: string, plural: string, refs: array<string, true>}> $entries
 */
function collect_block_json(string $json, string $rel, array &$entries): void
{
    $data = json_decode($json, true);
    if (!is_array($data) || (($data['textdomain'] ?? '') !== TEXT_DOMAIN)) {
        return;
    }

    $add = static function (string $context, string $msgid) use ($rel, &$entries): void {
        if ($msgid === '') {
            return;
        }
        $key = $context . "\x04" . $msgid . "\x04";
        if (!isset($entries[$key])) {
            $entries[$key] = ['context' => $context, 'msgid' => $msgid, 'plural' => '', 'refs' => []];
        }
        $entries[$key]['refs'][$rel] = true;
    };

    if (isset($data['title']) && is_string($data['title'])) {
        $add('block title', $data['title']);
    }
    if (isset($data['description']) && is_string($data['description'])) {
        $add('block description', $data['description']);
    }
    if (isset($data['keywords']) && is_array($data['keywords'])) {
        foreach ($data['keywords'] as $keyword) {
            if (is_string($keyword)) {
                $add('block keyword', $keyword);
            }
        }
    }
}

$files = target_files($root);
/** @var array<string, array{context: string, msgid: string, plural: string, refs: array<string, true>}> $entries */
$entries = [];
foreach ($files as $path) {
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
    collect((string) file_get_contents($path), $rel, $entries);
}

// Block metadata lives in block.json, translated by WordPress at registration.
$blockManifests = glob($root . '/blocks/*/block.json') ?: [];
sort($blockManifests);
foreach ($blockManifests as $path) {
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
    collect_block_json((string) file_get_contents($path), $rel, $entries);
}

ksort($entries);

$version = '0.1.0';
$header = <<<POT
# Copyright (C) Introibo
# This file is distributed under the GPL-2.0-or-later license.
msgid ""
msgstr ""
"Project-Id-Version: Ordo {$version}\\n"
"Report-Msgid-Bugs-To: https://github.com/Introibo-App/Ordo/issues\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
"X-Domain: ordo\\n"

POT;

$blocks = [$header];
foreach ($entries as $entry) {
    $refs = array_keys($entry['refs']);
    sort($refs);

    $block = '';
    foreach (array_chunk($refs, 4) as $chunk) {
        $block .= '#: ' . implode(' ', $chunk) . "\n";
    }
    if ($entry['context'] !== '') {
        $block .= 'msgctxt "' . pot_escape($entry['context']) . "\"\n";
    }
    $block .= 'msgid "' . pot_escape($entry['msgid']) . "\"\n";
    if ($entry['plural'] !== '') {
        $block .= 'msgid_plural "' . pot_escape($entry['plural']) . "\"\n";
        $block .= "msgstr[0] \"\"\n";
        $block .= "msgstr[1] \"\"\n";
    } else {
        $block .= "msgstr \"\"\n";
    }
    $blocks[] = $block;
}

$pot = implode("\n", $blocks);
if (substr($pot, -1) !== "\n") {
    $pot .= "\n";
}

$arguments = $_SERVER['argv'] ?? [];
if (is_array($arguments) && in_array('--print', $arguments, true)) {
    fwrite(STDOUT, $pot);
    exit(0);
}

$outDir = $root . '/languages';
if (!is_dir($outDir) && !mkdir($outDir, 0777, true) && !is_dir($outDir)) {
    fwrite(STDERR, "make-pot: cannot create languages/ directory\n");
    exit(1);
}
file_put_contents($outDir . '/ordo.pot', $pot);
fwrite(STDOUT, 'make-pot: wrote languages/ordo.pot (' . count($entries) . " strings)\n");
exit(0);
