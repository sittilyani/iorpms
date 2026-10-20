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
$__bupPrefill   = (!bup_validate($__bupSaved, $__bupDose, $__bupStrengths))
    ? $__bupSaved
    : bup_default_split($__bupDose, $__bupStrengths);
$__bupIsSaved   = !bup_validate($__bupSaved, $__bupDose, $__bupStrengths);
?>
<div class="form-group bup-selector" style="border:1px solid #2C3162;border-radius:8px;padding:10px;background:#f4f7fb;">
    <label style="font-weight:700;color:#2C3162;">
        Buprenorphine tablets to issue (dose <?= htmlspecialchars(rtrim(rtrim(number_format($__bupDose, 2, '.', ''), '0'), '.')) ?> mg)
    </label>
    <?php if (!$__bupStrengths): ?>
        <div style="color:#b00020;">No Buprenorphine strengths are set up in the drug list.</div>
    <?php else: ?>
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:6px;">
        <?php foreach ($__bupStrengths as $name => $s): ?>
            <label style="display:flex;flex-direction:column;font-weight:600;">
                <?= htmlspecialchars(str_ireplace('Buprenorphine ', '', $name)) ?> tablets
                <input type="number" min="0" step="1" class="bup-tab-input" data-mg="<?= $s['mg'] ?>"
                       name="bup_tabs[<?= htmlspecialchars($name) ?>]"
                       value="<?= (int)($__bupPrefill[$name] ?? 0) ?>" style="width:90px;">
            </label>
        <?php endforeach; ?>
        </div>
        <div id="bupTotal" style="margin-top:6px;font-weight:700;"></div>
        <div style="font-size:.8rem;color:#555;">
            <?= $__bupIsSaved ? 'Using the strengths last issued to this client.' : 'Suggested split - adjust if issuing different strengths.' ?>
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
            mg += (parseInt(i.value, 10) || 0) * parseFloat(i.dataset.mg);
        });
        return Math.round(mg * 100) / 100;
    }
    function render() {
        var el = document.getElementById('bupTotal');
        if (!el) return;
        var t = total(), ok = Math.abs(t - dose) < 0.001;
        el.style.color = ok ? '#1b7f3b' : '#b00020';
        el.textContent = 'Selected: ' + t + ' mg ' + (ok ? '✓ matches dose' : '(must equal ' + dose + ' mg)');
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
