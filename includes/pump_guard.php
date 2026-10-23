<?php
/**
 * 12-hour prime + calibrate compliance rule for pump dispensing.
 *
 * Clinics close for ~12-18 hours, so tubing dries and drifts. If the last pump
 * dispense is older than PUMP_STALE_HOURS (or there never was one), the pump may
 * not dispense until BOTH a forward Prime and a Calibration have been done after
 * that last dispense (and within the same window).
 */
if (!defined('PUMP_STALE_HOURS')) { define('PUMP_STALE_HOURS', 12); }

function pump_guard_schema(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS pump_prime_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pump_id INT NOT NULL,
        direction CHAR(1) NOT NULL DEFAULT 'D',
        primed_at DATETIME NOT NULL,
        user_id INT DEFAULT NULL,
        INDEX (pump_id, primed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

/** Record a successful prime. $direction: 'D' forward prime, 'R'/'P' reverse (emptying) prime. */
function pump_log_prime(mysqli $conn, int $pump_id, string $direction): void
{
    pump_guard_schema($conn);
    $d = $direction === 'D' ? 'D' : 'R';
    $uid = $_SESSION['user_id'] ?? null;
    $st = $conn->prepare("INSERT INTO pump_prime_log (pump_id, direction, primed_at, user_id) VALUES (?, ?, NOW(), ?)");
    $st->bind_param('isi', $pump_id, $d, $uid);
    $st->execute();
}

/** @return array{ready:bool, stale:bool, hours:?float, last_dispense:?string, last_prime:?string, last_calibration:?string, need_prime:bool, need_calibration:bool} */
function pump_readiness(mysqli $conn): array
{
    pump_guard_schema($conn);
    $one = function (string $sql) use ($conn) { $r = $conn->query($sql); $x = $r ? $r->fetch_row() : null; return $x && $x[0] ? $x[0] : null; };
    $lastDisp  = $one("SELECT MAX(dispDate) FROM pharmacy WHERE pump_id IS NOT NULL");
    $lastPrime = $one("SELECT MAX(primed_at) FROM pump_prime_log WHERE direction='D'");
    $lastCal   = $one("SELECT MAX(calibrated_at) FROM pump_calibration");
    $now = time();
    $hours = $lastDisp ? round(($now - strtotime((string)($lastDisp))) / 3600, 1) : null;
    $stale = $lastDisp === null || $hours > PUMP_STALE_HOURS;
    $fresh = function (?string $t) use ($lastDisp, $now) {
        return $t && ($now - strtotime((string)($t))) <= PUMP_STALE_HOURS * 3600 && (!$lastDisp || strtotime((string)($t)) > strtotime((string)($lastDisp)));
    };
    $needPrime = $stale && !$fresh($lastPrime);
    $needCal   = $stale && !$fresh($lastCal);
    return ['ready' => !$needPrime && !$needCal, 'stale' => $stale, 'hours' => $hours, 'last_dispense' => $lastDisp,
            'last_prime' => $lastPrime, 'last_calibration' => $lastCal, 'need_prime' => $needPrime, 'need_calibration' => $needCal];
}

/** Full-page block used by the dispensing pages. Exits. */
function pump_block_page(array $st, string $prefix = ''): void
{
    $why = [];
    $since = $st['last_dispense'] ? 'The last pump dispensing was ' . $st['hours'] . ' hours ago (' . htmlspecialchars((string)($st['last_dispense'])) . ').' : 'No pump dispensing has been recorded yet.';
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Reverse Prime, then Prime and Calibrate first</title></head>
<body style="margin:0;font-family:Segoe UI,Arial,sans-serif;background:#FDFCF9"><div style="max-width:620px;margin:60px auto;background:#fff;border:1px solid #e5e0f2;border-top:6px solid #E41E39;border-radius:10px;padding:26px">
<h2 style="margin:0 0 10px;color:#2D008A">Reverse Prime then Prime and calibrate first before you begin dispensing</h2>
<p>' . $since . ' Pump dispensing is locked after ' . PUMP_STALE_HOURS . ' hours so tubing is always primed and calibrated first &mdash; this protects every client\'s dose.</p>
<p><b>Required:</b> Reverse Prime, then Prime, then Calibrate the pump.</p>
<p><a href="' . $prefix . 'pharmacy/pump_reservoir.php" style="background:#2D008A;color:#fff;padding:10px 20px;border-radius:7px;text-decoration:none;font-weight:600">1. Reverse Prime &amp; Prime</a>
 <a href="' . $prefix . 'pharmacy/calibration.php" style="background:#04B04B;color:#fff;padding:10px 20px;border-radius:7px;text-decoration:none;font-weight:600;margin-left:8px">2. Calibrate pump</a></p>
<p style="color:#777;font-size:12px">After both are done, return to Dispense with Pump.</p></div></body></html>';
    exit;
}
