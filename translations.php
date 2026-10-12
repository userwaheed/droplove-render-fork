<?php
/**
 * translations.php — i18n dictionary loader.
 *
 * Usage: t('nav.discover') or t('dash.hello', ['name' => $me['username']]).
 * Per-language files live in lang/<code>.php and return a flat
 * 'key' => 'string' array. Missing keys fall back to English; missing
 * files fall back to English for the whole language.
 *
 * Adding a language = drop lang/xx.php + register it in config LANGS.
 */

declare(strict_types=1);

function translations_load(string $code): array
{
    static $cache = [];
    if (isset($cache[$code])) {
        return $cache[$code];
    }
    $file = __DIR__ . '/lang/' . preg_replace('/[^a-z0-9_-]/', '', $code) . '.php';
    $dict = is_file($file) ? (require $file) : [];
    $cache[$code] = is_array($dict) ? $dict : [];
    return $cache[$code];
}

/** Translate a key for the current language, with :placeholder interpolation. */
function t(string $key, array $replace = []): string
{
    $code = current_lang();
    $dict = translations_load($code);
    // Use array_key_exists so that empty-string translations ("") still fall
    // back to English. This keeps stub files (where every key = '') usable.
    $text = (array_key_exists($key, $dict) && $dict[$key] !== '')
        ? $dict[$key]
        : (translations_load(APP_DEFAULT_LANG)[$key] ?? $key);

    foreach ($replace as $k => $v) {
        $text = str_replace(':' . $k, (string)$v, $text);
    }
    return $text;
}
