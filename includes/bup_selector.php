<?php
/**
 * includes/bup_selector.php
 *
 * Renders the Buprenorphine tablet-strength picker. Include INSIDE a <form>.
 * Expects: $conn, $bup_mat_id (string), $bup_dose_mg (float).
 *
 * Posts bup_tabs[<drug name>] = <number of tablets>. The last selection for
 * the client is pre-filled (it persists until the dispenser changes it); when
 * nothing valid is saved a fewest-tablets split is suggested.
 * The page can call bupSelectorValid() to block submit when the tablets do
 * not add up to the client's dose.
 */
require_once __DIR__ . '/pharmacy_helpers.php';

$__bupStrengths = bup_strengths($conn);
$__bupDose      = (float)$bup_dose_mg;
$__bupSaved     = bup_get_saved($conn, $bup_mat_id);
$__bupStock    = bup_stock_available($conn, $__bupStrengths);
// Pre-fill, in order: (1) the formulation the dispenser last chose for this client, (2) how the last dose was actually issued,
// (3) a split of mostly 4 mg + 2 mg limited to what is in stock.
$__bupSource   = 'suggested';
$__bupPrefill   = [];
if (!bup_validate($__bupSaved, $__bupDose, $__bupStrengths)) { $__bupPrefill = $__bupSaved; $__bupSource = 'saved'; }
else {
    $__bupLast = bup_last_dispensed_split($conn, $bup_mat_id, $__bupStrengths);
    if ($__bupLast && !bup_validate($__bupLast, $__bupDose, $__bupStrengths)) { $__bupPrefill = $__bupLast; $__bupSource = 'last'; }
    else { $__bupPrefill = bup_stock_split($__bupDose, $__bupStrengths, $__bupStock) ?: bup_default_split($__bupDose, $__bupStrengths); }
}
$__bupIsSaved   = $__bupSource !== 'suggested';
?>
<div class="form-group bup-selector" style="border:1px solid #2D008A;border-radius:8px;padding:10px;background:#f4f7fb;">
    <label style="font-weight:700;color:#2D008A;">
        Buprenorphine tablets to issue (dose <?= htmlspecialchars((string)(rtrim((string)(rtrim((string)(number_format($__bupDose, 2, '.', '')), '0')), '.'))) ?> mg)
    </label>
    <?php if (!$__bupStrengths): ?>
        <div style="color:#b00020;">No Buprenorphine strengths are set up in the drug list.</div>
    <?php else: ?>
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:6px;">
        <?php foreach ($__bupStrengths as $name => $s): ?>
            <label style="display:flex;flex-direction:column;font-weight:600;">
                <?= htmlspecialchars((string)(str_ireplace('Buprenorphine ', '', $name))) ?> tablets
                <small style="font-weight:400;color:<?= (isset($__bupStock[$name]) && $__bupStock[$name] <= 0) ? '#E41E39' : '#777' ?>"><?= isset($__bupStock[$name]) ? 'in stock: ' . number_format($__bupStock[$name]) : 'stock not tracked' ?></small>
                <input type="number" min="0" step="0.5" class="bup-tab-input" data-mg="<?= $s['mg'] ?>" data-stock="<?= isset($__bupStock[$name]) ? (float)$__bupStock[$name] : '' ?>"
                       name="bup_tabs[<?= htmlspecialchars((string)($name)) ?>]"
                       value="<?= (float)($__bupPrefill[$name] ?? 0) ?>" style="width:90px;">
            </label>
        <?php endforeach; ?>
        </div>
        <div id="bupTotal" style="margin-top:6px;font-weight:700;"></div>
        <div style="font-size:.8rem;color:#555;">
            <?= $__bupSource === 'saved' ? 'Using the strengths you last chose for this client.' : ($__bupSource === 'last' ? 'Same tablets as the last dispensing - adjust if issuing different strengths.' : 'Suggested from stock (mostly 4 mg + 2 mg) - this client has no earlier dispensing to copy.') ?>
            The selection is remembered until you change it.
        </div>
    <?php endif; ?>
</div>
<script>
(function () {
    var dose = <?= json_encode($__bupDose) ?>;
    function total() {
        var mg = 0;
        document.querySelectorAll('.bup-tab-input').forEach(function (i) {
            mg += (parseFloat(i.value) || 0) * parseFloat(i.dataset.mg);
        });
        return Math.round(mg * 100) / 100;
    }
    function render() {
        var el = document.getElementById('bupTotal');
        if (!el) return;
        var t = total(), ok = Math.abs(t - dose) < 0.001;
        el.style.color = ok ? '#1b7f3b' : '#b00020';
        var short = [];
        document.querySelectorAll('.bup-tab-input').forEach(function (i) { var st = i.dataset.stock; if (st !== '' && (parseFloat(i.value) || 0) > parseFloat(st)) short.push(i.closest('label').firstChild.textContent.trim()); });
        el.textContent = 'Selected: ' + t + ' mg ' + (ok ? '✓ matches dose' : '(must equal ' + dose + ' mg)') + (short.length ? '  -  NOT ENOUGH STOCK: ' + short.join(', ') : '');
        if (short.length) el.style.color = '#b00020';
        document.dispatchEvent(new CustomEvent('bup-selection', { detail: { mg: t } }));
    }
    window.bupSelectorValid = function () {
        if (!document.querySelector('.bup-tab-input')) return true;
        var cb = document.getElementById('dispense_mat');
        if (cb && !cb.checked) return true;
        if (Math.abs(total() - dose) < 0.001) return true;
        alert('The Buprenorphine tablets selected total ' + total() + ' mg but the dose is ' + dose + ' mg.');
        return false;
    };
    document.querySelectorAll('.bup-tab-input').forEach(function (i) { i.addEventListener('input', render); });
    render();
})();
</script>
