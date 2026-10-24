<?php
/**
 * "Send to the receiving facility" window for a saved transfer form.
 * Usage: transfer_send_modal('../reports/transfer_send.php'); then openTransferSend({id, client, toSite, email, state}).
 * The window asks for the receiving facility's email (optional), sends the acceptance link, and always shows the link
 * with Copy and WhatsApp buttons so it can also be sent from a phone or an off-site mailbox.
 */
function transfer_send_modal(string $endpoint = '../reports/transfer_send.php'): void
{
    ?>
    <style>
    #tsBack{display:none;position:fixed;inset:0;background:rgba(20,0,60,.55);z-index:99999;overflow:auto}
    #tsBox{background:#fff;border-radius:14px;max-width:560px;width:94%;margin:50px auto;box-shadow:0 12px 40px rgba(0,0,0,.35);font-family:Segoe UI,Arial,sans-serif;overflow:hidden}
    #tsBox h3{margin:0;background:linear-gradient(135deg,#2D008A,#4a14b8);color:#fff;padding:14px 20px;font-size:17px}
    #tsBox .kb{padding:18px 20px;font-size:14px;color:#222}#tsBox label{display:block;font-weight:600;color:#2D008A;font-size:13px;margin:10px 0 4px}
    #tsBox input[type=email]{width:100%;padding:9px 10px;border:1px solid #cfc6ea;border-radius:6px;font-size:14px;box-sizing:border-box}
    #tsBox .row{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
    #tsBox button,#tsBox a.b{border:0;border-radius:6px;padding:9px 18px;font-weight:600;cursor:pointer;font-size:14px;text-decoration:none;display:inline-block}
    #tsBox .g{background:#04B04B;color:#fff}#tsBox .p{background:#2D008A;color:#fff}#tsBox .w{background:#25D366;color:#fff}#tsBox .o{background:#fff;color:#2D008A;border:1px solid #2D008A}
    #tsBox .note{background:#f3effb;border-left:4px solid #2D008A;border-radius:6px;padding:8px 12px;font-size:12.5px;margin-bottom:6px}
    #tsLink{width:100%;height:70px;font-size:11px;font-family:Consolas,monospace;border:1px solid #cfc6ea;border-radius:6px;padding:6px;box-sizing:border-box;margin-top:6px}
    #tsMsg{margin-top:10px;font-weight:600}
    @media print{#tsBack{display:none!important}}
    </style>
    <div id="tsBack"><div id="tsBox">
        <h3 id="tsTitle">Send transfer</h3>
        <div class="kb">
            <div class="note" id="tsInfo"></div>
            <label for="tsEmail">Receiving facility's email address</label>
            <input type="email" id="tsEmail" placeholder="clinic@example.org">
            <div class="row">
                <button type="button" class="g" id="tsSend"><i class="fa fa-paper-plane"></i> <span id="tsSendLbl">Send email</span></button>
                <button type="button" class="p" id="tsLinkOnly"><i class="fa fa-link"></i> Create link only</button>
                <button type="button" class="o" id="tsClose">Close</button>
            </div>
            <div id="tsMsg"></div>
            <div id="tsLinkBox" style="display:none">
                <label>Acceptance link (opens the transfer on the receiving clinic's EasyFlow-L)</label>
                <textarea id="tsLink" readonly></textarea>
                <div class="row" style="margin-top:8px">
                    <button type="button" class="p" id="tsCopy"><i class="fa fa-clipboard"></i> Copy link</button>
                    <a class="b w" id="tsWa" target="_blank" rel="noopener"><i class="fa fa-whatsapp"></i> Share on WhatsApp</a>
                </div>
                <p style="font-size:12px;color:#666;margin:8px 0 0">The receiving clinic opens the link on its own EasyFlow-L computer, logs in, reviews the form and accepts. Paste the link into WhatsApp or an email from any device.</p>
            </div>
        </div>
    </div></div>
    <script>
    (function () {
        var cur = null, $ = function (i) { return document.getElementById(i); };
        function show(on) { $('tsBack').style.display = on ? 'block' : 'none'; }
        window.openTransferSend = function (o) {
            cur = o; $('tsMsg').textContent = ''; $('tsLinkBox').style.display = 'none';
            $('tsTitle').textContent = (o.state ? 'Resend transfer' : 'Send transfer') + ' - ' + o.client;
            $('tsInfo').innerHTML = 'Dispensing site: <b></b>' + (o.state ? '<br>' + o.state : '');
            $('tsInfo').querySelector('b').textContent = o.toSite;
            $('tsEmail').value = o.email || ''; $('tsSendLbl').textContent = o.state ? 'Resend email' : 'Send email';
            show(true); $('tsEmail').focus();
        };
        function go(withEmail) {
            var email = withEmail ? $('tsEmail').value.trim() : '';
            if (withEmail && !email) { $('tsMsg').style.color = '#E41E39'; $('tsMsg').textContent = 'Enter the receiving facility\'s email, or choose "Create link only".'; return; }
            $('tsMsg').style.color = '#555'; $('tsMsg').textContent = 'Working...';
            fetch(<?php echo json_encode($endpoint); ?>, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ form_id: cur.id, email: email }) })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    $('tsMsg').style.color = j.ok ? '#04803a' : '#E41E39'; $('tsMsg').textContent = j.message;
                    if (j.ok) {
                        $('tsLink').value = j.link; $('tsLinkBox').style.display = 'block';
                        $('tsWa').href = 'https://wa.me/?text=' + encodeURIComponent('MAT client transfer for ' + cur.client + ' to ' + cur.toSite + '. Open this link on your EasyFlow-L computer to review and accept: ' + j.link);
                        if (typeof cur.done === 'function') cur.done(j);
                    }
                })
                .catch(function () { $('tsMsg').style.color = '#E41E39'; $('tsMsg').textContent = 'Could not complete that. Try again.'; });
        }
        $('tsSend').onclick = function () { go(true); };
        $('tsLinkOnly').onclick = function () { go(false); };
        $('tsClose').onclick = function () { show(false); if (cur && typeof cur.closed === 'function') cur.closed(); };
        $('tsCopy').onclick = function () {
            var t = $('tsLink'); t.select();
            (navigator.clipboard ? navigator.clipboard.writeText(t.value) : Promise.reject()).catch(function () { document.execCommand('copy'); }).then(function () { $('tsMsg').style.color = '#04803a'; $('tsMsg').textContent = 'Link copied.'; });
        };
    })();
    </script>
    <?php
}
