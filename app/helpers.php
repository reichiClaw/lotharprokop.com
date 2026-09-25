<?php
declare(strict_types=1);

/** HTML-Escaping für Textknoten und Attributwerte. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** Escaping für die Verwendung in URL-Pfadsegmenten. */
function eurl(string $value): string
{
    return rawurlencode($value);
}

/** Absolute URL aus einem Pfad. */
function url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    return App\Config::baseUrl() !== '' ? App\Config::baseUrl() . $path : $path;
}

/** Slug aus einem Titel: Kleinbuchstaben, ASCII, Bindestriche. */
function slugify(string $text): string
{
    $map = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss'];
    $text = strtr($text, $map);
    if (function_exists('transliterator_transliterate')) {
        $text = (string) transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
    } else {
        $text = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    }
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/**
 * Sehr kleine Textformatierung für Beschreibungen und rechtliche Seiten:
 * Leerzeilen trennen Absätze, Zeilen mit "## " werden Zwischenüberschriften,
 * Zeilen mit "- " Listenpunkte. Alles wird escaped – kein HTML aus der Datenbank.
 */
function format_text(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));
    if ($text === '') {
        return '';
    }
    $html = '';
    foreach (preg_split('/\n{2,}/', $text) ?: [] as $block) {
        $lines = explode("\n", trim($block));
        if (count($lines) > 0 && str_starts_with($lines[0], '## ')) {
            $html .= '<h2>' . e(substr($lines[0], 3)) . '</h2>';
            array_shift($lines);
            if ($lines === []) {
                continue;
            }
        }
        $isList = $lines !== [] && count(array_filter($lines, fn($l) => str_starts_with($l, '- '))) === count($lines);
        if ($isList) {
            $html .= '<ul>';
            foreach ($lines as $line) {
                $html .= '<li>' . inline_links(e(substr($line, 2))) . '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p>' . inline_links(implode('<br>', array_map('e', $lines))) . '</p>';
        }
    }
    return $html;
}

/** Wandelt bereits escapte E-Mail-Adressen und https-URLs in Links um. */
function inline_links(string $escaped): string
{
    $escaped = preg_replace('/\b([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})\b/i', '<a href="mailto:$1">$1</a>', $escaped) ?? $escaped;
    return preg_replace('/(?<!["\'>])\bhttps:\/\/[^\s<]+/i', '<a href="$0" rel="noopener">$0</a>', $escaped) ?? $escaped;
}

/** Kürzt Text für Meta-Beschreibungen. */
function excerpt(string $text, int $max = 155): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max - 1);
    $space = mb_strrpos($cut, ' ');
    return rtrim($space !== false ? mb_substr($cut, 0, $space) : $cut, ' ,.;:') . '…';
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function redirect(string $to, int $code = 302): never
{
    header('Location: ' . $to, true, $code);
    exit;
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function human_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 0, ',', '.') . ' KB';
    }
    return $bytes . ' B';
}

/** Interpretiert php.ini-Größenangaben wie "40M". */
function ini_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    $unit = strtolower(substr($value, -1));
    $number = (int) $value;
    return match ($unit) {
        'g' => $number * 1073741824,
        'm' => $number * 1048576,
        'k' => $number * 1024,
        default => (int) $value,
    };
}
