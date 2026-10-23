<?php
/**
 * Dose-schedule modal for the dispensing pages. Include just before </body>.
 * Needs: $userId (mat_id), $currentSettings (patient row), $doseBlocked (bool), $activeDose (row|null).
 * Opens when the client has no active dose schedule today; saving it reloads this same page so dispensing continues.
 */
?>
<div id="doseToast" style="display:none;position:fixed;top:16px;right:16px;background:#04B04B;color:#fff;border-radius:8px;padding:10px 18px;z-index:10001;font-family:Segoe UI,Arial,sans-serif;box-shadow:0 4px 14px rgba(0,0,0,.25)"></div>
<script>try { var __t = sessionStorage.getItem('doseToast'); if (__t) { sessionStorage.removeItem('doseToast'); var __el = document.getElementById('doseToast'); __el.textContent = __t; __el.style.display = 'block'; setTimeout(function () { __el.style.display = 'none'; }, 5000); } } catch (e) {}</script>
<?php
$__needsDose = !empty($userId) && empty($activeDose);
if (!$__needsDose) { return; }
$__mustSet   = !empty($doseBlocked) || (float)($currentSettings['dosage'] ?? 0) <= 0;   // cannot continue without a dose
$__drug      = trim((string)($currentSettings['drugname'] ?? ''));
$__dose      = (float)($currentSettings['dosage'] ?? 0);
?>
<div id="doseModal" style="display:none;position:fixed;inset:0;background:rgba(20,0,60,.6);z-index:10000;align-items:flex-start;justify-content:center;overflow-y:auto;font-family:Segoe UI,Arial,sans-serif">
  <div id="doseBox" style="background:#fff;border-radius:12px;max-width:520px;width:94%;margin:16px 0;border-top:6px solid #FFC12E;padding:22px;box-shadow:0 12px 40px rgba(0,0,0,.3)">
    <h3 style="margin:0 0 6px;color:#2D008A">No dose schedule for this client</h3>
    <p style="margin:0 0 14px;font-size:14px;color:#444"><b><?= htmlspecialchars((string)($currentSettings['clientName'] ?? $userId)) ?></b> (<?= htmlspecialchars((string)($userId)) ?>) has no active dose for today. Set it now and you can continue dispensing on this page.</p>
    <div id="doseErr" style="display:none;background:#fdeaec;border:1px solid #E41E39;border-radius:6px;padding:8px 12px;font-size:13px;margin-bottom:10px"></div>
    <form id="doseForm" autocomplete="off" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <input type="hidden" name="mat_id" value="<?= htmlspecialchars((string)($userId)) ?>">
      <label style="font-size:12px;font-weight:600;color:#4a3a7a">Drug<input name="drugname" value="<?= htmlspecialchars((string)($__drug)) ?>" readonly style="width:100%;padding:8px;border:1px solid #cfc6ea;border-radius:6px;background:#f3effb"></label>
      <label style="font-size:12px;font-weight:600;color:#4a3a7a">Dose (mg) *<input type="number" name="dose_mg" id="doseMg" min="0.5" max="300" step="0.5" required value="<?= $__dose > 0 ? htmlspecialchars((string)$__dose) : '' ?>" style="width:100%;padding:8px;border:1px solid #cfc6ea;border-radius:6px"></label>
      <label style="font-size:12px;font-weight:600;color:#4a3a7a">Start date<input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required style="width:100%;padding:8px;border:1px solid #cfc6ea;border-radius:6px"></label>
      <label style="font-size:12px;font-weight:600;color:#4a3a7a">End date (optional)<input type="date" name="end_date" min="<?= date('Y-m-d') ?>" style="width:100%;padding:8px;border:1px solid #cfc6ea;border-radius:6px"></label>
      <label style="grid-column:1/-1;font-size:12px;font-weight:600;color:#4a3a7a">Reason *
        <select name="reason" required style="width:100%;padding:8px;border:1px solid #cfc6ea;border-radius:6px">
          <option value="">Select reason</option><option>Confirm current dose</option><option>Initiation dose</option><option>Dose adjustment - titration</option><option>Dose reduction - weaning</option><option>Continuation after missed doses</option><option>Transfer-in / transit dose</option>
        </select></label>
      <div style="grid-column:1/-1;text-align:right;margin-top:4px">
        <?php if (!$__mustSet): ?><button type="button" id="doseSkip" style="background:#888;color:#fff;border:0;border-radius:6px;padding:9px 16px;margin-right:8px;cursor:pointer">Continue without a schedule</button><?php endif; ?>
        <button type="submit" id="doseSave" style="background:#04B04B;color:#fff;border:0;border-radius:6px;padding:9px 20px;font-weight:600;cursor:pointer">Save dose &amp; continue dispensing</button>
      </div>
    </form>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('doseModal'), form = document.getElementById('doseForm'), err = document.getElementById('doseErr');
  var grid = document.getElementById('dispensingStatusGrid'), box = document.getElementById('doseBox');
  // Show the last-dispensed / 5-day status grid at full brightness ABOVE the dim layer and put the modal underneath it,
  // so the history can be read while the dose is being set.
  function openDose() {
    window.scrollTo(0, 0);
    try { if (window.frameElement) window.frameElement.scrollIntoView({ block: 'start' }); } catch (e) {}   // the menu stretches this iframe to the page height: bring its top (grid + modal) into view
    if (grid) { grid.style.position = 'relative'; grid.style.zIndex = '10001'; grid.style.background = '#fff'; grid.style.padding = '8px'; grid.style.borderRadius = '12px'; }
    modal.style.display = 'flex';
    var top = grid ? Math.round(grid.getBoundingClientRect().bottom + 12) : 24;
    box.style.marginTop = Math.min(top, Math.max(16, window.innerHeight - box.offsetHeight - 16 < 16 ? 16 : top)) + 'px';
  }
  function closeDose() { modal.style.display = 'none'; if (grid) { grid.style.zIndex = ''; grid.style.position = ''; grid.style.background = ''; grid.style.padding = ''; } }
  <?php if ($__mustSet): ?>openDose();<?php else: ?>
  // legacy client with no schedule records but a dose on file: ask once per visit, allow skipping
  try { if (!sessionStorage.getItem('doseSkipped:<?= htmlspecialchars((string)($userId)) ?>')) openDose(); } catch (e) { openDose(); }
  var skip = document.getElementById('doseSkip');
  if (skip) skip.onclick = function () { try { sessionStorage.setItem('doseSkipped:<?= htmlspecialchars((string)($userId)) ?>', '1'); } catch (e) {} closeDose(); };
  <?php endif; ?>
  form.addEventListener('submit', function (ev) {
    ev.preventDefault(); err.style.display = 'none';
    var btn = document.getElementById('doseSave'); btn.disabled = true; btn.textContent = 'Saving...';
    fetch('quick_dose_update.php', { method: 'POST', body: new URLSearchParams(new FormData(form)), credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (r) {
        if (!r.ok) { err.textContent = r.error || 'Could not save the dose.'; err.style.display = 'block'; btn.disabled = false; btn.textContent = 'Save dose & continue dispensing'; return; }
        try { sessionStorage.setItem('doseToast', 'Dose set to ' + r.dose + ' mg. You can continue dispensing.'); } catch (e) {}
        location.reload();   // same client, same page - now with the active schedule
      })
      .catch(function () { err.textContent = 'Network problem - the dose was not saved.'; err.style.display = 'block'; btn.disabled = false; btn.textContent = 'Save dose & continue dispensing'; });
  });
})();
</script>
