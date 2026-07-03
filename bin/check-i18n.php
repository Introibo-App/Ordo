<?php

/**
 * Internationalisation guard (issue #22): fail CI on an i18n regression.
 *
 * Three checks, all deterministic:
 *   1. Domain — every translation call in first-party code carries the 'ordo' text
 *      domain (a missing or foreign domain means the string silently won't translate).
 *   2. Unwrapped prose — no user-facing sentence is escaped for output without also
 *      being translated (e.g. esc_html('Save changes') that should be esc_html__).
 *   3. Freshness — languages/ordo.pot matches a fresh regeneration, so a newly added
 *      string cannot land without being registered in the template.
 *
 * Latin liturgical text is emitted from variables (feast names, feriae) or the
 * LatinCalendar, never as an escaped English literal, so it does not trip check 2.
 *
 * Run in CI on every push/PR. Exits non-zero (listing every violation) on failure.
 *
 * @package Introibo\Ordo
 */

declare(strict_types=1);

const TEXT_DOMAIN = 'ordo';
const TRANSLATION_FUNCTIONS = [
    '__', '_e', 'esc_html__', 'esc_attr__', 'esc_html_e', 'esc_attr_e',
    '_x', '_ex', 'esc_html_x', 'esc_attr_x', '_n', '_nx',
];
const ESCAPE_ONLY_FUNCTIONS = ['esc_html', 'esc_attr'];

$root = dirname(__DIR__);
$errors = [];

/**
 * @return list<string>
 */
function i18n_target_files(string $root): array
{
    $files = [];
    foreach (['ordo.php', 'uninstall.php'] as $name) {
        if (is_file($root . '/' . $name)) {
            $files[] = $root . '/' . $name;
        }
    }
    foreach (['src', 'templates', 'blocks', 'assets/js'] as $dir) {
        $base = $root . '/' . $dir;
        if (!is_dir($base)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            if (strpos($rel, 'src/Core/') === 0) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if ($ext === 'php' || $ext === 'js') {
                $files[] = $file->getPathname();
            }
        }
    }
    sort($files);

    return $files;
}

/** The index of the paren that closes the one at $open, respecting strings. Or null. */
function matching_paren(string $code, int $open): ?int
{
    $len = strlen($code);
    $depth = 0;
    $i = $open;
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
        } elseif ($ch === '(') {
            $depth++;
        } elseif ($ch === ')') {
            $depth--;
            if ($depth === 0) {
                return $i;
            }
        }
        $i++;
    }

    return null;
}

function line_at(string $code, int $offset): int
{
    return substr_count($code, "\n", 0, $offset) + 1;
}

/**
 * Split a call's argument list (the text between its parens) on top-level commas,
 * respecting nested parens/brackets and string literals so a comma inside a msgid or a
 * nested call is not a split point.
 *
 * @return list<string>
 */
function top_level_args(string $inner): array
{
    $parts = [];
    $buffer = '';
    $depth = 0;
    $quote = '';
    $len = strlen($inner);
    for ($i = 0; $i < $len; $i++) {
        $ch = $inner[$i];
        if ($quote !== '') {
            $buffer .= $ch;
            if ($ch === '\\' && $i + 1 < $len) {
                $buffer .= $inner[$i + 1];
                $i++;
            } elseif ($ch === $quote) {
                $quote = '';
            }
            continue;
        }
        if ($ch === "'" || $ch === '"') {
            $quote = $ch;
            $buffer .= $ch;
        } elseif ($ch === '(' || $ch === '[') {
            $depth++;
            $buffer .= $ch;
        } elseif ($ch === ')' || $ch === ']') {
            $depth--;
            $buffer .= $ch;
        } elseif ($ch === ',' && $depth === 0) {
            $parts[] = $buffer;
            $buffer = '';
        } else {
            $buffer .= $ch;
        }
    }
    $parts[] = $buffer;

    return $parts;
}

// ── Checks 1 & 2: scan every call site ────────────────────────────────────────
$files = i18n_target_files($root);
foreach ($files as $path) {
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
    $code = (string) file_get_contents($path);

    // Check 1 — the domain argument (always last) must be the 'ordo' literal. Validating
    // the position, not a substring, so a msgid that happens to contain the word "ordo"
    // cannot mask a missing or wrong domain.
    $fnAlt = implode('|', array_map(static fn(string $f): string => preg_quote($f, '/'), TRANSLATION_FUNCTIONS));
    if (preg_match_all('/(?<![\w$>])(' . $fnAlt . ')\s*\(/', $code, $m, PREG_OFFSET_CAPTURE) > 0) {
        foreach ($m[0] as $match) {
            $open = (int) $match[1] + strlen($match[0]) - 1;
            $close = matching_paren($code, $open);
            if ($close === null) {
                continue;
            }
            $args = top_level_args(substr($code, $open + 1, $close - $open - 1));
            $last = '';
            for ($k = count($args) - 1; $k >= 0; $k--) {
                if (trim($args[$k]) !== '') {
                    $last = trim($args[$k]);
                    break;
                }
            }
            if (preg_match('/^([\'"])' . TEXT_DOMAIN . '\1$/', $last) !== 1) {
                $errors[] = $rel . ':' . line_at($code, (int) $match[1])
                    . " — {$match[0]} call is missing the '" . TEXT_DOMAIN . "' text domain";
            }
        }
    }

    // Check 2 — no escaped English sentence that should have been translated.
    $escAlt = implode('|', ESCAPE_ONLY_FUNCTIONS);
    if (
        preg_match_all(
            '/(?<![\w$>])(' . $escAlt . ')\s*\(\s*([\'"])((?:[^\'"\\\\]|\\\\.)*)\2\s*\)/',
            $code,
            $mm,
            PREG_OFFSET_CAPTURE
        ) > 0
    ) {
        foreach ($mm[3] as $index => $literal) {
            $text = $literal[0];
            // Prose heuristic: a space between letters, at least a few letters — a
            // sentence a translator would want, not a CSS token or single word.
            if (preg_match('/[A-Za-z]{2,}\s+[A-Za-z]{2,}/', $text) === 1) {
                $errors[] = $rel . ':' . line_at($code, (int) $mm[0][$index][1])
                    . " — {$mm[1][$index][0]}('{$text}') outputs an untranslated literal (use "
                    . "{$mm[1][$index][0]}__ )";
            }
        }
    }
}

// ── Check 3: the committed .pot is up to date ─────────────────────────────────
$potPath = $root . '/languages/ordo.pot';
if (!is_file($potPath)) {
    $errors[] = 'languages/ordo.pot is missing — run: composer run-script i18n';
} else {
    $cmd = escapeshellarg(PHP_BINARY) . ' '
        . escapeshellarg($root . '/bin/make-pot.php') . ' --print';
    $generated = shell_exec($cmd);
    if ($generated === null) {
        $errors[] = 'could not regenerate the .pot to compare (make-pot.php failed)';
    } elseif (rtrim((string) $generated, "\r\n") !== rtrim((string) file_get_contents($potPath), "\r\n")) {
        $errors[] = 'languages/ordo.pot is stale — regenerate with: composer run-script i18n';
    }
}

if ($errors !== []) {
    fwrite(STDERR, "check-i18n: FAILED\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '  - ' . $error . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "check-i18n: clean — every string is wrapped in the 'ordo' domain and the .pot is current.\n");
exit(0);
