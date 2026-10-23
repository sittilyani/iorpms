/* Client registration in parts + autosave every minute + auto-submit when all mandatory fields are filled. */
(function () {
    var form = document.querySelector('form[method="post"]');
    if (!form) return;
    var AUTOSAVE_MS = 60 * 1000, AUTOSUBMIT_GRACE_S = 10;
    var STEPS = [
        { title: 'Facility & IDs', fields: ['reg_facility', 'mflcode', 'county', 'scounty', 'mat_status', 'reg_date', 'mat_id', 'mat_number'] },
        { title: 'Client details', fields: ['clientName', 'sname', 'nickName', 'nat_id', 'dob', 'sex', 'marital_status', 'residence_scounty', 'p_address', 'client_phone'] },
        { title: 'Enrolment & referral', fields: ['transfer_id', 'referral_type', 'cso', 'referring_facility', 'reffering_fac_client_number', 'accompanment_type'] },
        { title: 'Support & status', fields: ['peer_edu_name', 'peer_edu_phone', 'rx_supporter_name', 'current_status', 'hcw_name'] }
    ];
    var DRAFT_URL = 'registration_draft.php', LS_KEY = 'iorpms_reg_draft_pending';
    var groups = {}, current = 1, submitting = false, dirty = false, countdownTimer = null;

    // ---- assign each field's .form-group to a step (anything unmapped lands in the last step) ----
    var all = Array.prototype.slice.call(form.querySelectorAll(':scope > .form-group'));
    var submitGroup = all[all.length - 1];
    STEPS.forEach(function (s, i) {
        s.groups = [];
        s.fields.forEach(function (n) {
            var el = form.elements[n]; if (!el) return;
            var g = el.closest('.form-group'); if (g && s.groups.indexOf(g) < 0) { s.groups.push(g); g.dataset.step = i + 1; }
        });
    });
    all.forEach(function (g) { if (g !== submitGroup && !g.dataset.step) g.dataset.step = STEPS.length; });

    // ---- progress bar + navigation ----
    var css = document.createElement('style');
    css.textContent = '.reg-hidden{display:none!important}.reg-bar{grid-column:1/-1;display:flex;gap:8px;flex-wrap:wrap}.reg-tab{flex:1;min-width:150px;padding:10px 14px;border-radius:8px;background:#f3effb;color:#2D008A;font-weight:600;font-size:13px;border:2px solid transparent;cursor:pointer;text-align:left}' +
        '.reg-tab.on{background:#2D008A;color:#fff}.reg-tab.done{border-color:#04B04B}.reg-tab small{display:block;font-weight:400;opacity:.8}.reg-nav{grid-column:1/-1;display:flex;justify-content:space-between;gap:10px;align-items:center}' +
        '.reg-btn{border:0;border-radius:7px;padding:11px 26px;font-weight:600;color:#fff;cursor:pointer;background:#2D008A}.reg-btn.ok{background:#04B04B}.reg-btn.alt{background:#AC80EE}' +
        '.reg-note{grid-column:1/-1;border-radius:8px;padding:10px 14px;font-size:13px;display:none}.reg-note.show{display:block}.reg-note.save{background:#f3effb;color:#2D008A}.reg-note.warn{background:#fff6dc;border:1px solid #FFC12E}.reg-note.go{background:#e6f8ee;border:1px solid #04B04B}' +
        'form .form-group label{color:#2D008A;font-weight:600;font-size:13px}form input:not([type=submit]),form select{border:1px solid #cfc6ea;border-radius:6px;padding:8px 10px}';
    document.head.appendChild(css);

    var bar = document.createElement('div'); bar.className = 'reg-bar';
    STEPS.forEach(function (s, i) {
        var b = document.createElement('button'); b.type = 'button'; b.className = 'reg-tab'; b.dataset.i = i + 1;
        b.innerHTML = (i + 1) + '. ' + s.title + '<small></small>'; b.onclick = function () { go(i + 1, true); }; bar.appendChild(b);
    });
    var note = document.createElement('div'); note.className = 'reg-note';
    var restore = document.createElement('div'); restore.className = 'reg-note warn';
    var nav = document.createElement('div'); nav.className = 'reg-nav';
    var back = btn('Back', 'alt', function () { go(current - 1, false); });
    var status = document.createElement('span'); status.style.cssText = 'font-size:12px;color:#7a6aa8';
    var next = btn('Next', 'ok', function () { go(current + 1, true); });
    nav.appendChild(back); nav.appendChild(status); nav.appendChild(next);
    form.insertBefore(bar, form.firstChild); form.insertBefore(restore, bar.nextSibling); form.insertBefore(note, restore.nextSibling); form.insertBefore(nav, note.nextSibling);
    function btn(t, c, f) { var b = document.createElement('button'); b.type = 'button'; b.className = 'reg-btn ' + c; b.textContent = t; b.onclick = f; return b; }

    function stepFields(i) { return [].concat.apply([], (i === STEPS.length ? all.filter(function (g) { return +g.dataset.step === i; }) : STEPS[i - 1].groups).map(function (g) { return [].slice.call(g.querySelectorAll('input,select,textarea')); })); }
    function stepValid(i, report) {
        var ok = true; stepFields(i).forEach(function (el) { if (el.type === 'hidden' || el.disabled) return; if (!el.checkValidity()) { if (ok && report) el.reportValidity(); ok = false; } });
        return ok;
    }
    function go(n, validate) {
        if (n < 1 || n > STEPS.length) return;
        if (validate && n > current && !stepValid(current, true)) return;
        current = n;
        all.forEach(function (g) { g.classList.toggle('reg-hidden', +g.dataset.step !== n && g !== submitGroup); });
        submitGroup.classList.toggle('reg-hidden', n !== STEPS.length);
        [].forEach.call(bar.children, function (b) {
            var i = +b.dataset.i; b.classList.toggle('on', i === n); var ok = stepValid(i, false);
            b.classList.toggle('done', ok && i !== n); b.querySelector('small').textContent = ok ? 'complete' : 'to do';
        });
        back.style.visibility = n === 1 ? 'hidden' : 'visible'; next.style.display = n === STEPS.length ? 'none' : '';
        form.scrollIntoView({ block: 'start' });
    }

    // ---- draft capture / restore ----
    function snapshot() { var o = {}; [].forEach.call(form.elements, function (el) { if (!el.name || el.type === 'submit' || el.type === 'button' || el.readOnly) return; o[el.name] = el.value; }); return o; }
    function apply(d) {
        Object.keys(d || {}).forEach(function (k) { var el = form.elements[k]; if (el && !el.readOnly) { el.value = d[k]; el.dispatchEvent(new Event('change', { bubbles: true })); } });
    }
    function save(why) {
        var snap = snapshot(), json = JSON.stringify(snap);
        try { localStorage.setItem(LS_KEY, JSON.stringify({ t: Date.now(), step: current, data: snap })); } catch (e) {}
        var fd = new URLSearchParams({ action: 'save', payload: json, step: current });
        return fetch(DRAFT_URL, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
            status.textContent = r.ok ? 'Draft autosaved at ' + r.saved_at : 'Autosave failed - kept on this computer'; dirty = false;
        }).catch(function () { status.textContent = 'Offline - draft kept on this computer'; });
    }
    function clearDraft() { try { localStorage.removeItem(LS_KEY); } catch (e) {} navigator.sendBeacon && navigator.sendBeacon(DRAFT_URL, new URLSearchParams({ action: 'delete' })); }
    form.addEventListener('input', function () { dirty = true; }); form.addEventListener('change', function () { dirty = true; });

    function offerRestore(d, step, when) {
        var keys = Object.keys(d || {}).filter(function (k) { return d[k] && !/^(reg_facility|mflcode|county|scounty|mat_id|hcw_name|current_status)$/.test(k); });
        if (!keys.length) return;
        restore.classList.add('show');
        restore.innerHTML = '<b>Unsaved registration found</b> (' + when + '). <button type="button" class="reg-btn ok" style="padding:5px 14px;margin:0 6px" id="regRestore">Restore it</button><button type="button" class="reg-btn alt" style="padding:5px 14px" id="regDiscard">Discard</button>';
        document.getElementById('regRestore').onclick = function () { apply(d); restore.classList.remove('show'); go(step || 1, false); };
        document.getElementById('regDiscard').onclick = function () { clearDraft(); restore.classList.remove('show'); };
    }
    if (document.getElementById('successmessage')) { try { localStorage.removeItem(LS_KEY); } catch (e) {} }
    else fetch(DRAFT_URL + '?action=load', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
        // keep the browser copy private to the logged-in user (shared clinic PCs)
        try { localStorage.removeItem('iorpms_reg_draft'); } catch (e) {}
        LS_KEY = 'iorpms_reg_draft_' + (r.uid || 'unknown');
        if (r.draft) { offerRestore(r.draft, r.step, 'saved ' + r.updated_at); return; }
        try { var ls = JSON.parse(localStorage.getItem(LS_KEY) || 'null'); if (ls && ls.data) offerRestore(ls.data, ls.step, 'kept on this computer'); } catch (e) {}
    }).catch(function () {});

    // ---- autosave every minute; auto-submit when every mandatory field is filled ----
    function mandatoryComplete() { return form.checkValidity(); }
    function startAutoSubmit() {
        if (countdownTimer || submitting) return; var left = AUTOSUBMIT_GRACE_S;
        note.className = 'reg-note show go';
        function paint() { note.innerHTML = '<b>All mandatory fields are filled.</b> Registering this client automatically in ' + left + 's. <button type="button" class="reg-btn alt" style="padding:4px 12px;margin-left:8px" id="regCancel">Cancel</button>'; document.getElementById('regCancel').onclick = cancelAuto; }
        paint();
        countdownTimer = setInterval(function () { left--; if (left <= 0) { clearInterval(countdownTimer); countdownTimer = null; submitting = true; form.requestSubmit ? form.requestSubmit() : form.submit(); } else paint(); }, 1000);
    }
    function cancelAuto() { clearInterval(countdownTimer); countdownTimer = null; note.className = 'reg-note'; autoOff = true; }
    var autoOff = false;
    setInterval(function () {
        if (submitting) return;
        if (dirty) save('tick');
        if (!autoOff && !countdownTimer && Object.keys(snapshot()).some(function (k) { return k === 'clientName' && form.elements[k].value; }) && mandatoryComplete()) startAutoSubmit();
    }, AUTOSAVE_MS);
    window.addEventListener('beforeunload', function () { if (dirty && !submitting) save('unload'); });
    // The draft is removed by the server only after the client is actually saved (so a failed submit loses nothing)
    form.addEventListener('submit', function () { submitting = true; });

    go(1, false);
})();
