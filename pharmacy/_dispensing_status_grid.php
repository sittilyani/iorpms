<?php
/**
 * Full-width grid shown just above the Visit Date row on the dispensing pages:
 *   [ Last dispensed / visit date ]  [ day-4 ] [ day-3 ] [ day-2 ] [ yesterday ] [ today ]
 * Each of the last 5 days shows Dispensed (dose, time, officer), Missed, Not due (alternate-day dosing) or Due today.
 * Needs: $conn, $userId (mat_id).
 */
if (empty($userId)) { return; }
require_once __DIR__ . '/../includes/dose_schedule_helpers.php';

$__first = date('Y-m-d', strtotime('-4 days'));
$__stmt = $conn->prepare("SELECT DATE(dispDate) d, TRIM(drugname) drug, SUM(dosage) mg, MAX(dispDate) t, MAX(pharm_officer_name) officer FROM pharmacy WHERE mat_id = ? AND dispDate >= ? GROUP BY DATE(dispDate), TRIM(drugname) ORDER BY d");
$__stmt->bind_param('ss', $userId, $__first);
$__stmt->execute();
$__byDay = [];
foreach ($__stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $__r) { $__byDay[$__r['d']][] = $__r; }
$__stmt->close();

$__last = $conn->prepare("SELECT dispDate, TRIM(drugname) drug, dosage, pharm_officer_name FROM pharmacy WHERE mat_id = ? ORDER BY dispDate DESC LIMIT 1");
$__last->bind_param('s', $userId); $__last->execute();
$__lastRow = $__last->get_result()->fetch_assoc(); $__last->close();
$__daysAgo = $__lastRow ? (int)floor((strtotime((string)(date('Y-m-d'))) - strtotime((string)(substr($__lastRow['dispDate'], 0, 10)))) / 86400) : null;

$__sch = [];
$__s2 = $conn->prepare("SELECT start_date, end_date, skip_dates, dosing_interval_days FROM dose_schedules WHERE mat_id = ? AND status = 'active' AND start_date <= CURDATE() ORDER BY start_date DESC LIMIT 1");
if ($__s2) { $__s2->bind_param('s', $userId); $__s2->execute(); $__sch = $__s2->get_result()->fetch_assoc() ?: []; $__s2->close(); }

$__ps = $conn->prepare("SELECT current_status FROM patients WHERE mat_id = ? LIMIT 1"); $__ps->bind_param('s', $userId); $__ps->execute(); $__pstat = trim((string)($__ps->get_result()->fetch_row()[0] ?? '')); $__ps->close();
$__pcol = ['active' => '#04B04B', 'transit' => '#6B3FD1', 'weaned' => '#2D008A', 'dead' => '#555', 'ltfu' => '#E41E39', 'defaulted' => '#E41E39'][strtolower($__pstat)] ?? '#a87500';
$__cells = [];
for ($__i = 4; $__i >= 1; $__i--) {
    $__d = date('Y-m-d', strtotime((string)("-$__i days"))); $__label = $__i === 0 ? 'Today' : ($__i === 1 ? 'Yesterday' : date('D j M', strtotime((string)($__d))));
    if (isset($__byDay[$__d])) {
        $__txt = []; foreach ($__byDay[$__d] as $__x) { $__txt[] = htmlspecialchars((string)(rtrim((string)(rtrim((string)(number_format((float)$__x['mg'], 2, '.', '')), '0')), '.'))) . ' mg ' . htmlspecialchars((string)($__x['drug'])); }
        $__cells[] = ['cls' => 'ok', 'label' => $__label, 'status' => 'Dispensed', 'sub' => implode('<br>', $__txt) . '<br><small>' . date('H:i', strtotime((string)($__byDay[$__d][0]['t']))) . ' &middot; ' . htmlspecialchars((string)$__byDay[$__d][0]['officer']) . '</small>'];
    } elseif ($__sch && !isDoseDueOnDate($__sch, $__d)) {
        $__cells[] = ['cls' => 'na', 'label' => $__label, 'status' => 'Not due', 'sub' => 'alternate-day / skipped'];
    } elseif ($__i === 0) {
        $__cells[] = ['cls' => 'due', 'label' => $__label, 'status' => 'Due today', 'sub' => 'not yet dispensed'];
    } else {
        $__cells[] = ['cls' => 'miss', 'label' => $__label, 'status' => 'Missed', 'sub' => 'no dispensing recorded'];
    }
}
?>
<style>
.dsg{display:grid;grid-template-columns:minmax(200px,1.4fr) repeat(4,minmax(0,1fr));gap:10px;width:100%;box-sizing:border-box;margin:10px 0 14px;font-family:Segoe UI,Arial,sans-serif}
.dsg>div{border-radius:10px;padding:10px 12px;border:1px solid #e5e0f2;background:#fff;min-width:0}
.dsg .lbl{font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#6b5aa8}
.dsg .big{font-size:20px;font-weight:700;color:#2D008A;line-height:1.2;margin-top:2px}
.dsg .sub{font-size:12px;color:#555;margin-top:3px;line-height:1.35}
.dsg .st{font-size:15px;font-weight:700;margin:2px 0}
.dsg .ok{border-top:5px solid #04B04B;background:#f1fbf5}.dsg .ok .st{color:#04803a}
.dsg .miss{border-top:5px solid #E41E39;background:#fff3f4}.dsg .miss .st{color:#E41E39}
.dsg .due{border-top:5px solid #FFC12E;background:#fffaf0}.dsg .due .st{color:#a87500}
.dsg .na{border-top:5px solid #cfc6ea;background:#faf8ff}.dsg .na .st{color:#777}
.dsg .last{border-top:5px solid #2D008A;background:#f6f1ff}
@media(max-width:900px){.dsg{grid-template-columns:repeat(2,1fr)}.dsg .last{grid-column:1/-1}}
</style>
<div class="dsg" id="dispensingStatusGrid">
  <div class="last">
    <div class="lbl">Patient status</div>
    <div style="margin:3px 0 8px"><span style="display:inline-block;background:<?= $__pcol ?>;color:#fff;border-radius:999px;padding:3px 14px;font-weight:700;font-size:13px"><?= htmlspecialchars($__pstat !== '' ? $__pstat : 'Unknown') ?></span></div>
    <div class="lbl">Last dispensed / visit date</div>
    <?php if ($__lastRow): ?>
      <div class="big"><?= htmlspecialchars((string)(substr($__lastRow['dispDate'], 0, 10))) ?></div>
      <div class="sub"><?= $__daysAgo === 0 ? 'Today' : $__daysAgo . ' day' . ($__daysAgo === 1 ? '' : 's') . ' ago' ?> &middot; <?= htmlspecialchars((string)(rtrim((string)(rtrim((string)(number_format((float)$__lastRow['dosage'], 2, '.', '')), '0')), '.'))) ?> mg <?= htmlspecialchars((string)($__lastRow['drug'])) ?><br><?= htmlspecialchars((string)$__lastRow['pharm_officer_name']) ?></div>
    <?php else: ?>
      <div class="big" style="font-size:16px">No previous visit</div><div class="sub">This client has never been dispensed.</div>
    <?php endif; ?>
  </div>
  <?php foreach ($__cells as $__c): ?>
  <div class="<?= $__c['cls'] ?>">
    <div class="lbl"><?= htmlspecialchars((string)($__c['label'])) ?></div>
    <div class="st"><?= $__c['status'] ?></div>
    <div class="sub"><?= $__c['sub'] ?></div>
  </div>
  <?php endforeach; ?>
</div>
