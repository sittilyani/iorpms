<?php
session_start();
include '../includes/config.php';

// AJAX Live Search for Active Patients
if (isset($_GET['ajax_search'])) {
    header('Content-Type: application/json');
    $q = trim($_GET['q'] ?? '');
    if ($q === '') {
        echo json_encode([]);
        exit;
    }
    
    $search_term = "%$q%";
    $st = $conn->prepare("
        SELECT mat_id, mat_number, clientName, drugname, dosage
        FROM patients
        WHERE current_status = 'Active'
          AND (
               mat_id LIKE ?
            OR mat_number LIKE ?
            OR clientName LIKE ?
            OR drugname LIKE ?
          )
        LIMIT 15
    ");
    $st->bind_param('ssss', $search_term, $search_term, $search_term, $search_term);
    $st->execute();
    $result = $st->get_result();
    $patients = [];
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
    $st->close();
    echo json_encode($patients);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}
$prescriber = $_SESSION['full_name'] ?? 'Clinician';

// ── Flash messages ────────────────────────────────────────────
$flashSuccess = $_SESSION['dose_success'] ?? null; unset($_SESSION['dose_success']);
$flashErrors  = $_SESSION['dose_errors']  ?? [];   unset($_SESSION['dose_errors']);

// ── Patient lookup ────────────────────────────────────────────
$mat_id       = $_GET['mat_id'] ?? '';
$patient      = null;
$schedules    = [];
$drug_options = [];

if ($mat_id) {
    $st = $conn->prepare("SELECT mat_id, clientName, drugname, dosage, current_status FROM patients WHERE mat_id = ? AND current_status = 'Active'");
    $st->bind_param('s', $mat_id);
    $st->execute();
    $patient = $st->get_result()->fetch_assoc();
    $st->close();

    if ($patient) {
        // Load existing schedules for this patient
        $st2 = $conn->prepare(
            "SELECT * FROM dose_schedules WHERE mat_id = ? ORDER BY start_date ASC"
        );
        $st2->bind_param('s', $mat_id);
        $st2->execute();
        $res2 = $st2->get_result();
        while ($row = $res2->fetch_assoc()) {
            $schedules[] = $row;
        }
        $st2->close();
    }
}

// ── Drug list for dropdown ────────────────────────────────────
$res = $conn->query("SELECT drugname FROM drug ORDER BY drugname ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) $drug_options[] = $r['drugname'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Dosage — EasyFlow</title>
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css">
<style>
body { background:#f5f7fa; font-family:'Segoe UI',sans-serif; }
.card { background:#fff; border-radius:8px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,.08); margin-bottom:20px; }
h2 { color:#2C3162; }
.badge-active   { background:#28a745; color:#fff; padding:3px 8px; border-radius:12px; font-size:.8rem; }
.badge-super    { background:#6c757d; color:#fff; padding:3px 8px; border-radius:12px; font-size:.8rem; }
.badge-cancel   { background:#dc3545; color:#fff; padding:3px 8px; border-radius:12px; font-size:.8rem; }
.dose-row       { border:1px solid #dee2e6; border-radius:6px; padding:14px; margin-bottom:12px; background:#fafafa; }
.dose-row .row-num { font-weight:700; color:#2C3162; font-size:1.1rem; }
#doseList .dose-row:last-child { border:2px solid #007bff; background:#f0f8ff; }
label.required::after { content:' *'; color:red; }
.alert-overlap  { display:none; color:#dc3545; font-weight:600; margin-top:4px; font-size:.85rem; }
.btn-add { background:#007bff; color:#fff; border:none; padding:8px 18px; border-radius:5px; cursor:pointer; }
.btn-add:hover { background:#0056b3; }
.btn-remove { background:#dc3545; color:#fff; border:none; padding:5px 12px; border-radius:5px; cursor:pointer; font-size:.8rem; }
table.schedule-table { width:100%; border-collapse:collapse; font-size:.9rem; }
table.schedule-table th { background:#2C3162; color:#fff; padding:8px 10px; }
table.schedule-table td { padding:8px 10px; border-bottom:1px solid #eee; }
table.schedule-table tr:hover td { background:#f0f4ff; }
.search-box { max-width:480px; }
.search-container { position: relative; max-width: 480px; }
.btn-edit-dose { background:#fd7e14; color:#fff; border:none; padding:4px 11px; border-radius:5px; cursor:pointer; font-size:.8rem; }
.btn-edit-dose:hover { background:#e06000; }
#searchResults {
    position: absolute;
    left: 0;
    right: 0;
    top: 100%;
    z-index: 1000;
    max-height: 320px;
    overflow-y: auto;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: 1px solid #ced4da;
    border-radius: 4px;
    background: #fff;
}
#searchResults .list-group-item {
    border-left: none;
    border-right: none;
    border-radius: 0;
    text-align: left;
}
#searchResults .list-group-item:last-child {
    border-bottom: none;
}
</style>
</head>
<body>
<div class="container-fluid py-3 px-4">

<?php if ($flashSuccess): ?>
  <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashErrors): ?>
  <div class="alert alert-danger">
    <strong><i class="fa fa-exclamation-circle"></i> Errors:</strong>
    <ul class="mb-0"><?php foreach ($flashErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<?php if (!$mat_id || !$patient): ?>
<!-- ── SEARCH ──────────────────────────────────────────────── -->
<div class="card">
  <h2><i class="fa fa-pills"></i> Update Dosage — Patient Search</h2>
  <p class="text-muted">Search for an <strong>Active</strong> patient by typing their Name, MAT ID, MAT Number, or Drug name.</p>
  <form method="GET" action="" class="form-inline search-box search-container">
    <div class="input-group w-100">
      <input type="text" id="patientSearchInput" class="form-control" placeholder="Type name, MAT ID, number, or drug..." autocomplete="off">
      <div class="input-group-append">
        <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Search</button>
      </div>
    </div>
    <div id="searchResults" class="list-group" style="display:none;"></div>
  </form>
  <?php if ($mat_id && !$patient): ?>
    <div class="alert alert-warning mt-3">
      <i class="fa fa-exclamation-triangle"></i>
      No <strong>Active</strong> patient found for MAT ID <strong><?= htmlspecialchars($mat_id) ?></strong>.
      Only active patients can have their dose updated.
    </div>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- ── PATIENT CARD ────────────────────────────────────────── -->
<div class="card">
  <div class="d-flex justify-content-between align-items-start">
    <div>
      <h2><i class="fa fa-pills"></i> Dose Schedule — <?= htmlspecialchars($patient['clientName']) ?></h2>
      <p class="mb-1"><strong>MAT ID:</strong> <?= htmlspecialchars($patient['mat_id']) ?> &nbsp;|&nbsp;
         <strong>Drug:</strong> <?= htmlspecialchars($patient['drugname']) ?> &nbsp;|&nbsp;
         <strong>Current Dose:</strong> <?= htmlspecialchars($patient['dosage']) ?> mg &nbsp;|&nbsp;
         <span class="badge-active">Active</span>
      </p>
    </div>
    <a href="?mat_id=" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i> New Search</a>
  </div>
</div>

<!-- ── EXISTING SCHEDULES ──────────────────────────────────── -->
<?php if ($schedules): ?>
<div class="card">
  <h5><i class="fa fa-history"></i> Existing Dose Schedules</h5>
  <table class="schedule-table">
    <thead>
      <tr>
        <th>#</th><th>Drug</th><th>Dose (mg)</th><th>Start Date</th>
        <th>End Date</th><th>Comments</th><th>Created By</th><th>Status</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($schedules as $i => $sc): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($sc['drugname']) ?></td>
        <td><?= htmlspecialchars($sc['dose_mg']) ?></td>
        <td><?= htmlspecialchars($sc['start_date']) ?></td>
        <td><?= $sc['end_date'] ? htmlspecialchars($sc['end_date']) : '<em style="color:#888">Open-ended</em>' ?></td>
        <td><?= htmlspecialchars($sc['comments']) ?></td>
        <td><?= htmlspecialchars($sc['created_by']) ?></td>
        <td>
          <?php if ($sc['status'] === 'active'): ?>
            <span class="badge-active">Active</span>
          <?php elseif ($sc['status'] === 'superseded'): ?>
            <span class="badge-super">Superseded</span>
          <?php else: ?>
            <span class="badge-cancel">Cancelled</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($sc['status'] === 'active'): ?>
            <button type="button" class="btn-edit-dose"
              onclick="openEditModal(<?= htmlspecialchars(json_encode([
                'id'         => $sc['id'],
                'drugname'   => $sc['drugname'],
                'dose_mg'    => $sc['dose_mg'],
                'start_date' => $sc['start_date'],
                'end_date'   => $sc['end_date'] ?? '',
                'comments'   => $sc['comments'],
              ]), ENT_QUOTES) ?>)">
              <i class="fa fa-pencil"></i> Edit
            </button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- ── ADD NEW DOSE PERIODS ────────────────────────────────── -->
<div class="card">
  <h5><i class="fa fa-plus-circle"></i> Add / Update Dose Periods</h5>
  <p class="text-muted small">
    Each row is one dose period. Periods <strong>must not overlap</strong>.
    For <strong>Buprenorphine</strong>, you may specify skip dates (dates within the period when no dose is given).
    For <strong>Methadone</strong>, leaving a gap between one period's end and the next's start will prompt a confirmation.
    If no end date is set, you will be asked to confirm before saving.
    Each dose period <strong>requires a comment</strong>.
  </p>

  <form id="doseForm" action="process_dose_update.php" method="POST">
    <input type="hidden" name="mat_id"    value="<?= htmlspecialchars($patient['mat_id']) ?>">
    <input type="hidden" name="prescriber" value="<?= htmlspecialchars($prescriber) ?>">

    <div id="doseList"></div>

    <button type="button" class="btn-add mb-3" onclick="addDoseRow()">
      <i class="fa fa-plus"></i> Add Dose Period
    </button>

    <div class="text-right">
      <button type="button" class="btn btn-success btn-lg" onclick="submitDoses()">
        <i class="fa fa-save"></i> Save All Dose Periods
      </button>
    </div>
  </form>
</div>
<?php endif; ?>
</div><!-- /container -->

<!-- ── EDIT ACTIVE DOSE MODAL ──────────────────────────────────────────── -->
<div class="modal fade" id="editDoseModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:#fd7e14;color:#fff;">
        <h5 class="modal-title"><i class="fa fa-pencil"></i> Edit Active Dose</h5>
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;"><span>&times;</span></button>
      </div>
      <form id="editDoseForm" action="process_dose_update.php" method="POST">
        <input type="hidden" name="action"    value="edit_active">
        <input type="hidden" name="mat_id"    value="<?= htmlspecialchars($patient['mat_id'] ?? '') ?>">
        <input type="hidden" name="prescriber" value="<?= htmlspecialchars($prescriber) ?>">
        <input type="hidden" name="edit_id"   id="editId">
        <div class="modal-body">
          <div class="alert alert-info" style="font-size:.85rem;">
            <i class="fa fa-info-circle"></i>
            <strong>Start date is locked</strong> — changing it would affect dispensing records already saved under this period.
            Only dose amount, end date, and notes can be changed.
            Past dispenses are <strong>not</strong> affected by this edit.
          </div>

          <div class="form-group">
            <label><strong>Start Date (locked)</strong></label>
            <input type="date" class="form-control" id="editStartDisplay" readonly style="background:#f0f0f0;">
          </div>

          <div class="form-group">
            <label><strong>Drug</strong></label>
            <input type="text" class="form-control" id="editDrug" name="edit_drugname" readonly style="background:#f0f0f0;">
          </div>

          <div class="form-group">
            <label class="required"><strong>New Dose (mg)</strong></label>
            <input type="number" class="form-control" id="editDoseMg" name="edit_dose_mg"
                   min="0.5" step="0.5" required placeholder="e.g. 40">
          </div>

          <div class="form-group">
            <label><strong>New End Date</strong> <small class="text-muted">(leave blank = open-ended)</small></label>
            <input type="date" class="form-control" id="editEndDate" name="edit_end_date"
                   onchange="checkEditOverlap()">
            <div id="editOverlapAlert" class="alert-overlap" style="display:none;margin-top:4px;">
              <i class="fa fa-exclamation-triangle"></i> <span id="editOverlapMsg"></span>
            </div>
          </div>

          <div class="form-group">
            <label class="required"><strong>Reason for Change / Clinical Notes</strong></label>
            <textarea class="form-control" id="editComments" name="edit_comments" rows="3" required
                      placeholder="Mandatory: reason for this dose change, e.g. 'Dose increase due to clinical review on 2026-06-26'"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning" onclick="return validateEditForm()">
            <i class="fa fa-save"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── NO-END-DATE CONFIRMATION MODAL ────────────────────────────────── -->
<div class="modal fade" id="noEndDateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="fa fa-exclamation-triangle"></i> No End Date</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p id="noEndDateMsg"></p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-warning" onclick="confirmNoEndDate()">Yes, submit without end date</button>
        <button class="btn btn-secondary" data-dismiss="modal">Go back and set end date</button>
      </div>
    </div>
  </div>
</div>

<!-- ── METHADONE GAP CONFIRMATION MODAL ─────────────────────────────── -->
<div class="modal fade" id="gapModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="fa fa-exclamation-triangle"></i> Date Gap Detected</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p id="gapMsg"></p>
        <p><strong>Are you sure you want some dates with no active dose?</strong> During the gap, dispensing will be blocked.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-warning" onclick="confirmGap()">Yes, I understand — save anyway</button>
        <button class="btn btn-secondary" data-dismiss="modal">No, fix the gap</button>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
<script>
const drugname   = <?= json_encode($patient['drugname'] ?? '') ?>;
const isBupreno  = drugname.toLowerCase().includes('buprenorphine');
const isMethadone= drugname.toLowerCase().includes('methadone');

// Existing schedules for gap / overlap detection
const existing = <?= json_encode($schedules) ?>;
let rowCount = 0;
let pendingSubmit = false;
let noEndDateRows = [];
let gapInfo = null;

function addDoseRow() {
    rowCount++;
    const html = `
    <div class="dose-row" id="doseRow${rowCount}" data-row="${rowCount}">
      <div class="row align-items-center mb-2">
        <div class="col-auto"><span class="row-num">Period ${rowCount}</span></div>
        <div class="col-auto ml-auto">
          <button type="button" class="btn-remove" onclick="removeRow(${rowCount})">
            <i class="fa fa-trash"></i> Remove
          </button>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="required">Drug</label>
            <input type="text" class="form-control drug-field" name="drug[]"
                   value="${htmlEscape(drugname)}" readonly>
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label class="required">Dose (mg)</label>
            <input type="number" class="form-control dose-field" name="dose_mg[]"
                   min="0.5" step="0.5" required placeholder="e.g. 30">
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label class="required">Start Date</label>
            <input type="date" class="form-control start-field" name="start_date[]"
                   required onchange="checkOverlap(${rowCount})">
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>End Date <small class="text-muted">(leave blank = open-ended)</small></label>
            <input type="date" class="form-control end-field" name="end_date[]"
                   onchange="checkOverlap(${rowCount})">
          </div>
        </div>
        ${isBupreno ? `
        <div class="col-md-3">
          <div class="form-group">
            <label>Skip Dates <small class="text-muted">(comma-separated YYYY-MM-DD)</small></label>
            <input type="text" class="form-control" name="skip_dates[]"
                   placeholder="2025-07-04, 2025-07-05">
          </div>
        </div>` : '<input type="hidden" name="skip_dates[]" value="">'}
      </div>
      <div class="form-group">
        <label class="required">Comments / Clinical Notes for this dose period</label>
        <textarea class="form-control comments-field" name="comments[]" rows="2" required
                  placeholder="Mandatory: reason for this dose, e.g. 'Dose reduction due to stable progress'"></textarea>
      </div>
      <div class="alert-overlap" id="overlap${rowCount}">
        <i class="fa fa-exclamation-triangle"></i> <span id="overlapMsg${rowCount}"></span>
      </div>
    </div>`;
    document.getElementById('doseList').insertAdjacentHTML('beforeend', html);
    updateRowNumbers();
}

function removeRow(n) {
    const el = document.getElementById('doseRow' + n);
    if (el) el.remove();
    updateRowNumbers();
}

function updateRowNumbers() {
    document.querySelectorAll('.dose-row').forEach((el, i) => {
        el.querySelector('.row-num').textContent = 'Period ' + (i + 1);
    });
}

function htmlEscape(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function getAllRows() {
    return Array.from(document.querySelectorAll('.dose-row'));
}

function checkOverlap(n) {
    const row     = document.getElementById('doseRow' + n);
    if (!row) return;
    const start   = row.querySelector('.start-field').value;
    const end     = row.querySelector('.end-field').value;
    const alertEl = document.getElementById('overlap' + n);
    const msgEl   = document.getElementById('overlapMsg' + n);

    if (!start) { alertEl.style.display = 'none'; return; }

    // Check against existing DB schedules
    let overlap = null;
    for (const sc of existing) {
        if (sc.status !== 'active') continue;
        const sStart = sc.start_date;
        const sEnd   = sc.end_date || '9999-12-31';
        const myEnd  = end || '9999-12-31';
        if (start <= sEnd && myEnd >= sStart) {
            overlap = `Overlaps with existing dose period ${sStart} – ${sc.end_date || 'open-ended'} (${sc.dose_mg} mg).`;
            break;
        }
    }

    // Check against other new rows in form
    if (!overlap) {
        getAllRows().forEach(otherRow => {
            if (otherRow.id === 'doseRow' + n) return;
            const oStart = otherRow.querySelector('.start-field').value;
            const oEnd   = otherRow.querySelector('.end-field').value;
            if (!oStart) return;
            const myEnd  = end || '9999-12-31';
            const thEnd  = oEnd || '9999-12-31';
            if (start <= thEnd && myEnd >= oStart) {
                overlap = `Overlaps with another period in this submission (${oStart} – ${oEnd || 'open-ended'}).`;
            }
        });
    }

    if (overlap) {
        msgEl.textContent  = overlap;
        alertEl.style.display = 'block';
    } else {
        alertEl.style.display = 'none';
    }
}

function detectGaps() {
    // Build a sorted list of all periods (existing active + new form rows)
    const periods = [];

    existing.filter(s => s.status === 'active').forEach(s => {
        periods.push({ start: s.start_date, end: s.end_date || null });
    });

    getAllRows().forEach(row => {
        const s = row.querySelector('.start-field').value;
        const e = row.querySelector('.end-field').value;
        if (s) periods.push({ start: s, end: e || null });
    });

    if (periods.length < 2) return null;

    // Sort by start_date
    periods.sort((a, b) => a.start.localeCompare(b.start));

    for (let i = 0; i < periods.length - 1; i++) {
        const cur  = periods[i];
        const next = periods[i + 1];
        if (!cur.end) continue; // open-ended period → no gap after it
        // Gap exists if next.start > cur.end + 1 day
        const curEndDate  = new Date(cur.end);
        const nextStartDate = new Date(next.start);
        curEndDate.setDate(curEndDate.getDate() + 1);
        if (nextStartDate > curEndDate) {
            const gapStart = cur.end;
            const gapEnd   = next.start;
            return `There is a gap between ${gapStart} and ${gapEnd} with no active dose.`;
        }
    }
    return null;
}

function submitDoses() {
    // 1. Validate required fields
    let valid = true;
    getAllRows().forEach((row, i) => {
        const dose     = row.querySelector('.dose-field').value;
        const start    = row.querySelector('.start-field').value;
        const comments = row.querySelector('.comments-field').value.trim();
        if (!dose || !start || !comments) {
            alert('Period ' + (i+1) + ': Dose, Start Date, and Comments are required.');
            valid = false;
        }
    });
    if (!valid) return;

    // 2. Check for overlaps still showing
    const hasOverlap = document.querySelectorAll('.alert-overlap[style*="block"]').length > 0;
    if (hasOverlap) {
        alert('Please fix overlapping dose periods before saving.');
        return;
    }

    // 3. Check for rows with no end date
    noEndDateRows = [];
    getAllRows().forEach((row, i) => {
        const end = row.querySelector('.end-field').value;
        if (!end) noEndDateRows.push(i + 1);
    });

    if (noEndDateRows.length > 0) {
        document.getElementById('noEndDateMsg').textContent =
            'Period(s) ' + noEndDateRows.join(', ') + ' have no end date. ' +
            'This means the dose will continue indefinitely. Are you sure?';
        $('#noEndDateModal').modal('show');
        return;
    }

    afterNoEndDateCheck();
}

function confirmNoEndDate() {
    $('#noEndDateModal').modal('hide');
    afterNoEndDateCheck();
}

function afterNoEndDateCheck() {
    // 4. Check for Methadone gaps
    if (isMethadone) {
        const gap = detectGaps();
        if (gap) {
            gapInfo = gap;
            document.getElementById('gapMsg').textContent = gap;
            $('#gapModal').modal('show');
            return;
        }
    }
    doSubmit();
}

function confirmGap() {
    $('#gapModal').modal('hide');
    doSubmit();
}

function doSubmit() {
    document.getElementById('doseForm').submit();
}

// Live Autocomplete Search Handler
$(document).ready(function() {
    let searchTimeout = null;
    $('#patientSearchInput').on('input', function() {
        clearTimeout(searchTimeout);
        const q = $(this).val().trim();
        if (q.length < 1) {
            $('#searchResults').hide().empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: 'update_dose.php',
                data: { ajax_search: 1, q: q },
                dataType: 'json',
                success: function(data) {
                    const resultsContainer = $('#searchResults');
                    resultsContainer.empty();
                    
                    if (data.length === 0) {
                        resultsContainer.append('<div class="list-group-item text-muted">No active patients found matching "' + htmlEscape(q) + '"</div>');
                        resultsContainer.show();
                        return;
                    }
                    
                    data.forEach(function(p) {
                        const item = $('<a href="?mat_id=' + encodeURIComponent(p.mat_id) + '" class="list-group-item list-group-item-action">' +
                            '<strong>' + htmlEscape(p.clientName) + '</strong> (' + htmlEscape(p.mat_id) + ')<br>' +
                            '<small class="text-muted">No: ' + htmlEscape(p.mat_number) + ' | Drug: ' + htmlEscape(p.drugname) + ' | Current Dose: ' + htmlEscape(p.dosage) + ' mg</small>' +
                            '</a>');
                        resultsContainer.append(item);
                    });
                    resultsContainer.show();
                }
            });
        }, 150); // Debounce delay
    });

    // Handle form submit to select first match
    $('.search-container').on('submit', function(e) {
        e.preventDefault();
        const firstLink = $('#searchResults a').first();
        if (firstLink.length > 0 && firstLink.attr('href')) {
            window.location.href = firstLink.attr('href');
        }
    });

    // Close search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#patientSearchInput, #searchResults').length) {
            $('#searchResults').hide();
        }
    });

    // Show search results on focus if not empty
    $('#patientSearchInput').on('focus', function() {
        if ($(this).val().trim().length > 0) {
            $('#searchResults').show();
        }
    });
});

// Auto-add first row on load
addDoseRow();

// ── Edit Active Dose ────────────────────────────────────────────────────────
let editingScheduleId   = null;
let editingScheduleStart = null;

function openEditModal(sc) {
    editingScheduleId    = sc.id;
    editingScheduleStart = sc.start_date;

    document.getElementById('editId').value          = sc.id;
    document.getElementById('editStartDisplay').value = sc.start_date;
    document.getElementById('editDrug').value         = sc.drugname;
    document.getElementById('editDoseMg').value       = sc.dose_mg;
    document.getElementById('editEndDate').value      = sc.end_date || '';
    document.getElementById('editComments').value     = '';  // always blank — clinician must enter reason

    document.getElementById('editOverlapAlert').style.display = 'none';
    $('#editDoseModal').modal('show');
}

function checkEditOverlap() {
    const newEnd = document.getElementById('editEndDate').value;
    const alertEl = document.getElementById('editOverlapAlert');
    const msgEl   = document.getElementById('editOverlapMsg');

    if (!editingScheduleStart) { alertEl.style.display = 'none'; return; }

    const myStart = editingScheduleStart;
    const myEnd   = newEnd || '9999-12-31';

    // Check against all OTHER active schedules (not the one we're editing)
    let overlap = null;
    for (const sc of existing) {
        if (sc.id == editingScheduleId) continue;          // skip self
        if (sc.status !== 'active') continue;
        const sStart = sc.start_date;
        const sEnd   = sc.end_date || '9999-12-31';
        if (myStart <= sEnd && myEnd >= sStart) {
            overlap = `Overlaps with another active period ${sStart} – ${sc.end_date || 'open-ended'} (${sc.dose_mg} mg).`;
            break;
        }
    }

    if (overlap) {
        msgEl.textContent = overlap;
        alertEl.style.display = 'block';
    } else {
        alertEl.style.display = 'none';
    }
}

function validateEditForm() {
    const dose     = document.getElementById('editDoseMg').value;
    const comments = document.getElementById('editComments').value.trim();

    if (!dose || parseFloat(dose) <= 0) {
        alert('Please enter a valid dose greater than 0.');
        return false;
    }
    if (!comments) {
        alert('A reason / clinical note is required for dose changes.');
        return false;
    }
    // Block if overlap is showing
    if (document.getElementById('editOverlapAlert').style.display === 'block') {
        alert('Please fix the date overlap before saving.');
        return false;
    }

    const endDate   = document.getElementById('editEndDate').value;
    const startDate = editingScheduleStart;
    if (endDate && endDate < startDate) {
        alert('End date cannot be before the start date (' + startDate + ').');
        return false;
    }
    return true;
}
</script>
</body>
</html>
