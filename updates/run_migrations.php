<?php
/**
 * Apply pending database migrations only (no file download). For the developer
 * machine, where files come from git but the local database still needs the changes:
 *   php updates/run_migrations.php        or    http://localhost/iorpms/updates/run_migrations.php
 */
$root = dirname(__DIR__);
if (PHP_SAPI !== 'cli') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, ['127.0.0.1', '::1'], true)) { http_response_code(403); exit('Local use only.'); }
    header('Content-Type: text/plain');
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
} else {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
require_once $root . '/includes/config.php';
require_once $root . '/includes/migrations.php';

$m = run_migrations($conn, $root . '/updates/migrations');
$r = run_repeatable($conn, $root . '/updates/repeatable');
echo "Applied: " . ($m['applied'] ? implode(', ', $m['applied']) : '(none pending)') . "\n";
echo "Repeatable: " . implode(', ', $r['ran']) . "\n";
if ($m['failed'] || $r['failed']) { echo "FAILED: " . ($m['failed'] ?: $r['failed']) . "\n"; exit(1); }
