<?php
/**
 * translate_proxy.php
 * ====================
 * PHP proxy for LibreTranslate API with file-based caching.
 *
 * WHY THIS APPROACH?
 * ------------------
 * • LibreTranslate is FREE and open-source (self-hostable or use the public API)
 * • Each unique phrase is translated ONCE, then cached as a JSON file on disk
 * • Zero translation cost after the first request for any given phrase
 * • Supported languages: French (fr), Portuguese (pt), Spanish (es), and 100+ more
 * • No vendor lock-in — switch the API URL to DeepL/Google with one config change
 *
 * HOW TO USE (two modes):
 * -----------------------
 * 1. AJAX (from JS in any app page):
 *      fetch('/iorpms/translate_proxy.php', {
 *        method: 'POST',
 *        headers: {'Content-Type': 'application/json'},
 *        body: JSON.stringify({ text: 'Hello', target: 'fr' })
 *      }).then(r => r.json()).then(d => console.log(d.translated));
 *
 * 2. PHP (server-side, include this file):
 *      require_once 'translate_proxy.php';
 *      $french = translate_text('Hello', 'fr');  // returns string
 *
 * SETUP:
 * ------
 * Option A — Public LibreTranslate (free, no account needed, rate-limited):
 *   Set LIBRE_API_URL = 'https://libretranslate.com/translate'
 *   Set LIBRE_API_KEY = ''  (leave blank — a free key can be requested at libretranslate.com)
 *
 * Option B — Self-hosted (recommended for production):
 *   docker run -ti --rm -p 5000:5000 libretranslate/libretranslate
 *   Set LIBRE_API_URL = 'http://localhost:5000/translate'
 *
 * Option C — DeepL Free (best quality for FR/PT, 500k chars/month free):
 *   Set TRANSLATE_ENGINE = 'deepl'
 *   Set DEEPL_API_KEY = 'your-deepl-free-key:fx'
 *   Get key at: https://www.deepl.com/pro-api (free tier)
 *
 * HOW TO ADD TRANSLATION TO ANY APP PAGE:
 * ----------------------------------------
 *   1. Add <div data-translate="1"> around translatable content (or use data-i18n attributes)
 *   2. Include the JS snippet below at the bottom of the page:
 *
 *   <script>
 *   function translatePage(targetLang) {
 *     if (targetLang === 'en') { location.reload(); return; }
 *     document.querySelectorAll('[data-t]').forEach(el => {
 *       const original = el.dataset.original || el.textContent.trim();
 *       if (!original) return;
 *       el.dataset.original = original;
 *       fetch('/iorpms/translate_proxy.php', {
 *         method: 'POST',
 *         headers: {'Content-Type':'application/json'},
 *         body: JSON.stringify({text: original, target: targetLang})
 *       }).then(r=>r.json()).then(d=>{ if(d.translated) el.textContent = d.translated; });
 *     });
 *   }
 *   </script>
 *
 *   3. Add data-t="1" to any element you want translated:
 *      <th data-t="1">Client Name</th>
 *      <td data-t="1">No records found</td>
 *
 *   4. Add a language switcher button somewhere:
 *      <button onclick="translatePage('fr')">FR</button>
 *      <button onclick="translatePage('pt')">PT</button>
 */

// ── Configuration ─────────────────────────────────────────────────────────────
define('TRANSLATE_ENGINE',  'libretranslate');  // 'libretranslate' or 'deepl'
define('LIBRE_API_URL',     'https://libretranslate.com/translate');
define('LIBRE_API_KEY',     '');   // blank for public endpoint; or your free key
define('DEEPL_API_KEY',     '');   // your DeepL Free API key (ends in :fx)
define('DEEPL_API_URL',     'https://api-free.deepl.com/v2/translate');
define('SOURCE_LANG',       'en'); // all app content is English
define('CACHE_DIR',         __DIR__ . '/cache/translations/');
define('CACHE_TTL_DAYS',    90);   // re-translate after 90 days (handles term changes)

// ── Supported languages ───────────────────────────────────────────────────────
$SUPPORTED = ['fr', 'pt', 'es', 'sw', 'ar', 'de', 'zh', 'hi'];

// ── When called via HTTP (AJAX mode) ─────────────────────────────────────────
if (php_sapi_name() !== 'cli' && !defined('TRANSLATE_INCLUDE_MODE')) {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'POST only']);
        exit;
    }

    $input  = json_decode(file_get_contents('php://input'), true);
    $text   = trim($input['text']   ?? '');
    $target = strtolower($input['target'] ?? 'fr');

    if (!$text) {
        echo json_encode(['translated' => '']);
        exit;
    }

    if (!in_array($target, $SUPPORTED)) {
        echo json_encode(['error' => "Language '$target' not supported.", 'translated' => $text]);
        exit;
    }

    $result = translate_text($text, $target);
    echo json_encode(['translated' => $result, 'cached' => true]);
    exit;
}

// ── Core translation function (usable in PHP include mode) ───────────────────

/**
 * Translate $text from English to $targetLang.
 * Returns the translated string; falls back to original text on error.
 */
function translate_text(string $text, string $targetLang): string {
    if (!$text || strtolower($targetLang) === SOURCE_LANG) return $text;

    // ── Cache lookup ──────────────────────────────────────────────────────────
    $cacheFile = get_cache_path($text, $targetLang);
    if (file_exists($cacheFile)) {
        $age = (time() - filemtime($cacheFile)) / 86400;
        if ($age < CACHE_TTL_DAYS) {
            return file_get_contents($cacheFile);
        }
    }

    // ── Translate via API ─────────────────────────────────────────────────────
    try {
        $translated = (TRANSLATE_ENGINE === 'deepl')
            ? call_deepl($text, $targetLang)
            : call_libretranslate($text, $targetLang);
    } catch (Exception $e) {
        // Log but don't crash — return original text
        error_log("translate_proxy error: " . $e->getMessage());
        return $text;
    }

    // ── Cache result ──────────────────────────────────────────────────────────
    if ($translated && $translated !== $text) {
        if (!is_dir(CACHE_DIR)) {
            mkdir(CACHE_DIR, 0755, true);
        }
        file_put_contents($cacheFile, $translated);
    }

    return $translated ?: $text;
}

/**
 * Translate multiple strings at once (batches one API call per string for now).
 * Returns associative array: ['original' => 'translated', ...]
 */
function translate_batch(array $strings, string $targetLang): array {
    $results = [];
    foreach ($strings as $s) {
        $results[$s] = translate_text($s, $targetLang);
    }
    return $results;
}

// ── Cache path ────────────────────────────────────────────────────────────────
function get_cache_path(string $text, string $lang): string {
    $hash = md5(strtolower(trim($text)));
    $dir  = CACHE_DIR . $lang . '/' . substr($hash, 0, 2) . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return $dir . $hash . '.txt';
}

// ── LibreTranslate API ────────────────────────────────────────────────────────
function call_libretranslate(string $text, string $target): string {
    $payload = ['q' => $text, 'source' => SOURCE_LANG, 'target' => $target, 'format' => 'text'];
    if (LIBRE_API_KEY) $payload['api_key'] = LIBRE_API_KEY;

    $ch = curl_init(LIBRE_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) throw new Exception("LibreTranslate cURL error: $err");
    if ($http !== 200) throw new Exception("LibreTranslate HTTP $http: $resp");

    $data = json_decode($resp, true);
    return $data['translatedText'] ?? $text;
}

// ── DeepL Free API ────────────────────────────────────────────────────────────
function call_deepl(string $text, string $target): string {
    if (!DEEPL_API_KEY) throw new Exception("DeepL API key not set in translate_proxy.php");

    // DeepL uses uppercase lang codes and PT-PT / PT-BR distinction
    $targetMap = ['pt' => 'PT-PT', 'fr' => 'FR', 'es' => 'ES', 'de' => 'DE',
                  'zh' => 'ZH',    'ar' => 'AR', 'hi' => 'HI', 'sw' => 'SW'];
    $deeplTarget = $targetMap[strtolower($target)] ?? strtoupper($target);

    $payload = http_build_query([
        'auth_key'    => DEEPL_API_KEY,
        'text'        => $text,
        'source_lang' => strtoupper(SOURCE_LANG),
        'target_lang' => $deeplTarget,
    ]);

    $ch = curl_init(DEEPL_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) throw new Exception("DeepL cURL error: $err");
    if ($http !== 200) throw new Exception("DeepL HTTP $http: $resp");

    $data = json_decode($resp, true);
    return $data['translations'][0]['text'] ?? $text;
}
