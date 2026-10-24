<?php
/** Consent form support: schema, thumbprint widget and the PDF, shared by create_consent.php and update_consent.php. */
require_once __DIR__ . '/../dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

function consent_ensure_schema(mysqli $conn): void
{
    foreach (['client_signature' => 'VARCHAR(255) NULL', 'sign_mode' => "VARCHAR(10) NOT NULL DEFAULT 'sign'", 'thumb_template' => 'LONGTEXT NULL', 'thumb_captured_at' => 'DATETIME NULL'] as $c => $ddl) {
        $r = $conn->query("SHOW COLUMNS FROM client_consents LIKE '$c'");
        if ($r && $r->num_rows === 0) { $conn->query("ALTER TABLE client_consents ADD COLUMN $c $ddl"); }
    }
}

/** Write the consent PDF. $v holds the form values under the names used in the template (client_name, mat_id, ...). */
function consent_make_pdf(array $v, string $full_pdf_path): void
{
    extract($v);
    // Set up options
                $options = new Options();
                $options->set('isRemoteEnabled', true);
                $dompdf = new Dompdf($options);

                // Include a separate, clean HTML template or dynamically create it
                // For simplicity, we create the HTML dynamically here:

                // IMPORTANT: Use absolute paths for images in DOMPDF HTML
                $logo_path = realpath('../assets/images/Government of Kenya.png');
                $logo_url = 'file:///' . str_replace('\\', '/', $logo_path);

                $pdf_html = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                    <title>Client Consent Form</title>
                    <style>
                        body { font-family: sans-serif; font-size: 10pt; line-height: 1.6; margin: 0; padding: 0; }
                        .form-container { width: 100%; margin: 0 auto; padding: 20px; }
                        .form-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
                        .form-header img { width: 80px; height: 60px; float: left; margin-right: 20px; }
                        .header-center { display: inline-block; }
                        .form-header h2 { color: #2c3e50; font-size: 14pt; margin: 0; }
                        .form-header h4 { color: #6633CC; font-size: 12pt; margin: 5px 0; }
                        .form-header p { font-size: 8pt; float: right; margin: 0; padding-top: 10px; }
                        .section-title { color: blue; font-weight: bold; font-size: 12pt; margin: 15px 0 8px 0; border-bottom: 1px solid #ccc; padding-bottom: 5px;}
                        .data-pair { margin: 5px 0; }
                        .data-pair span { font-weight: bold; }
                        .consent-list { margin-left: 20px; }
                        .consent-list p { margin: 5px 0; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 9pt; }
                        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .red-text { color: red; font-style: italic; }
                        .blue-text { color: blue; font-weight: bold; }
                        .signature-line { display: inline-block; border-bottom: 1px solid #000; min-width: 150px; padding: 0 5px; }
                    </style>
                </head>
                <body>
                    <div class="form-container">
                        <div class="form-header">
                            <img src="' . $logo_url . '" alt="Government Logo">
                            <div class="header-center">
                                <h2>MEDICALLY ASSISTED THERAPY</h2>
                                <h4>CLIENT CONSENT FORM</h4>
                            </div>
                            <p>FORM 1A VER. APR. 2023</p>
                            <div style="clear: both;"></div>
                        </div>

                        <div class="section-title">Client Information</div>
                        <div class="data-pair"><span>Date:</span> ' . htmlspecialchars((string)($visitDate_raw)) . '</div>
                        <div class="data-pair"><span>Name of Client:</span> ' . htmlspecialchars((string)($client_name)) . '</div>
                        <div class="data-pair"><span>MAT ID:</span> ' . htmlspecialchars((string)($mat_id)) . '</div>
                        <div class="data-pair"><span>Sex:</span> ' . htmlspecialchars((string)($sex)) . '</div>
                        <div class="data-pair"><span>CSO:</span> ' . htmlspecialchars((string)($cso)) . '</div>

                        <div class="section-title">Client Declaration</div>
                        <p>I <span class="signature-line">' . htmlspecialchars((string)($client_name)) . '</span> of telephone number <span class="signature-line">' . htmlspecialchars((string)($declaration_phone)) . '</span> and ID number <span class="signature-line">' . htmlspecialchars((string)($national_id)) . '</span></p>
                        <p><span class="red-text">(where the client is under the age of 18 years, state the age of the patient)</span> Age: <span class="signature-line">' . htmlspecialchars((string)($client_age)) . '</span></p>
                        <p>and accompanied by <span class="signature-line">' . htmlspecialchars((string)($guardian_accompany)) . '</span></p>
                        <p>Guardian Name: <span class="signature-line">' . htmlspecialchars((string)($guardian_name)) . '</span>, Guardian ID: <span class="signature-line">' . htmlspecialchars((string)($guardian_id)) . '</span></p>

                        <div class="section-title">Consent Agreement</div>
                        <p class="blue-text" style="font-size: 11pt;">I do hereby willingly consent to the following:</p>
                        <div class="consent-list">
                            <p>1. That I have been given information at the CSO about the MAT program</p>
                            <p>2. I have been taken through the rules and regulations of in the MAT program</p>
                            <p>3. I understand that participation in the program is voluntary</p>
                            <p>4. I have been informed of the risks and benefits of being in the MAT program</p>
                            <p>5. Although I understand that the treatment is beneficial to me, I have the right to withdraw from treatment</p>
                            <p>7. I agree to keep, and be on time for all my scheduled appointments with the service provider and his/her health care team at the clinic/treatment Centre.</p>
                            <p>8. I understand that the staff at the clinic/treatment Centre will need to confirm my identity every time before issuing my medication.</p>
                            <p>9. I agree to conduct myself in a courteous manner at the clinic/treatment Centre; No violence, verbal abuse, physical assault and repeated unacceptable destructive behavior to staff and or fellow clients.</p>
                            <p>10. I agree not to arrive at the clinic/treatment Centre intoxicated or under the influence of drugs. If I do, the doctor may not see me and I may not be given any medication until my next scheduled appointment.</p>
                            <p>11. I agree not to sell, share or give any of my medication to another person. I understand that such mishandling of my medication is a serious violation of this agreement and would result in my treatment being terminated without recourse for appeal.</p>
                            <p>12. I agree not to deal, steal or conduct any other illegal or disruptive activities in the clinic/treatment Centre –Drug possession/dealing, carrying weapons and property damage within and around the facility.</p>
                            <p>13. I agree to collect my medication personally at my regular clinic/treatment Centre through daily visits and to consume the whole dose under direct observation of dispensing staff.</p>
                            <p>14. I understand that if I miss an appointment and fail to collect my medication on any day I will not be given an extra dose the following day.</p>
                            <p>15. I understand that if I miss three or more consecutive doses of my medication, the prescription will be cancelled and can only be renewed after another full medical check-up. </p>
                            <p>16. I agree that it is my responsibility to take the full dose of medication I receive from the clinic/treatment Centre staff. I agree that any medication that spills/drops while being taken will not be replaced regardless of the reasons for the loss.</p>
                            <p>17. I understand the dangers of taking more than my prescribed dose of methadone. I agree not to obtain similar medications from any other physicians, pharmacies or other sources without informing my primary treatment providers.</p>
                            <p>18. I understand that mixing my methadone/buprenorphine with other substances, especially alcohol, benzodiazepines such as Diazepam, and other drugs of abuse, can be dangerous. I also understand that death can occur among persons mixing methadone/buprenorphine with benzodiazepines.</p>
                            <p>19. I agree to take my medication as the doctor has instructed and not to alter the way I take my medication without first consulting the doctor.</p>
                            <p>20. I understand that methadone/buprenorphine alone is not sufficient treatment for my dependence and I agree to participate in the patient education and relapse prevention program, as provided, to assist me in my treatment.</p>
                            <p>21. I understand that the consent form will be administered after 3 months of induction and when need arise.</p>
                            <p>22. I understand that consenting to the above listed rules will apply to the mobile van. I will also be bound by all MAT clinic regulations.</p>
                        </div>

                        <p style="margin-top: 15px;">I FREELY and VOLUNTARILY agree to undergo MAT at <span class="signature-line">' . htmlspecialchars((string)($mat_facility)) . '</span> or any other MAT outlet.</p>

                        <div style="margin-top: 15px;">
                            <span>Client\'s Signature or Left thumb print:</span> <span class="signature-line">' . htmlspecialchars((string)($client_signature)) . '</span>
                        </div>

                        <div class="section-title">Treatment Team</div>
                        <table>
                            <tr><th>Designation</th><th>Name</th><th>Organization</th><th>Signature</th><th>Date</th></tr>
                            <tr><td>MAT Clinician</td><td>' . htmlspecialchars((string)($clinician_name)) . '</td><td>' . htmlspecialchars((string)($clinician_org)) . '</td><td>' . htmlspecialchars((string)($clinician_signature)) . '</td><td>' . htmlspecialchars((string)($clinician_date_raw)) . '</td></tr>
                            <tr><td>MAT Counselor</td><td>' . htmlspecialchars((string)($counselor_name)) . '</td><td>' . htmlspecialchars((string)($counselor_org)) . '</td><td>' . htmlspecialchars((string)($counselor_signature)) . '</td><td>' . htmlspecialchars((string)($counselor_date_raw)) . '</td></tr>
                        </table>
                    </div>
                </body>
                </html>';

                $dompdf->loadHtml($pdf_html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                // Output the generated PDF to the file system
                file_put_contents($full_pdf_path, $dompdf->output());
}

/** Signature / left-thumbprint block with scanner capture (through the SecuGen PHP bridge). Fields: sign_mode, client_signature, thumb_template. */
function consent_thumb_widget(string $bridge, string $mode = 'sign', string $sig = ''): void
{
    $m = $mode === 'thumb' ? 'thumb' : 'sign';
    ?>
    <div class="form-group" id="consentSign">
        <label class="required-field">Client's signature or left thumbprint:</label>
        <div style="display:flex;gap:18px;flex-wrap:wrap;margin:6px 0">
            <label style="font-weight:600"><input type="radio" name="sign_mode" value="sign" <?php echo $m === 'sign' ? 'checked' : ''; ?>> Client signs</label>
            <label style="font-weight:600"><input type="radio" name="sign_mode" value="thumb" <?php echo $m === 'thumb' ? 'checked' : ''; ?>> Left thumbprint required (client cannot sign)</label>
        </div>
        <div id="signBox"><input type="text" name="client_signature" id="clientSig" placeholder="Client signature/initials" value="<?php echo htmlspecialchars($m === 'sign' ? $sig : ''); ?>"></div>
        <div id="thumbBox" style="display:none;background:#f3effb;border-left:5px solid #2D008A;border-radius:8px;padding:12px 16px">
            <p style="margin:0 0 8px"><b>Fingerprint scanner:</b> ask the client to place the <b>left thumb</b> on the scanner, then press the button.</p>
            <button type="button" id="thumbBtn" style="background:#2D008A;color:#fff;border:0;border-radius:6px;padding:8px 20px;cursor:pointer">Capture left thumb</button>
            <span id="thumbMsg" style="margin-left:12px;font-weight:600"></span>
            <input type="hidden" name="thumb_template" id="thumbTemplate" value="">
        </div>
    </div>
    <script>
    (function () {
        var form = document.getElementById('consentSign').closest('form'), sig = document.getElementById('clientSig');
        var tb = document.getElementById('thumbBox'), sb = document.getElementById('signBox'), msg = document.getElementById('thumbMsg'), tpl = document.getElementById('thumbTemplate'), btn = document.getElementById('thumbBtn');
        function mode() { return (form.querySelector('input[name="sign_mode"]:checked') || {}).value || 'sign'; }
        function sync() { var t = mode() === 'thumb'; tb.style.display = t ? '' : 'none'; sb.style.display = t ? 'none' : ''; sig.required = !t; }
        form.addEventListener('change', function (e) { if (e.target.name === 'sign_mode') sync(); });
        btn.addEventListener('click', function () {
            btn.disabled = true; msg.style.color = '#555'; msg.textContent = 'Waiting for the thumb... (up to 25 seconds)';
            var c = new AbortController(), t = setTimeout(function () { c.abort(); }, 28000);
            fetch(<?php echo json_encode($bridge); ?> + '?action=capture', { signal: c.signal })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.success && d.fingerprint_template) { tpl.value = d.fingerprint_template; msg.style.color = '#04803a'; msg.textContent = 'Thumbprint captured'; }
                    else { tpl.value = ''; msg.style.color = '#E41E39'; msg.textContent = (d.message || 'No print captured') + ' - check the scanner and try again.'; }
                })
                .catch(function () { tpl.value = ''; msg.style.color = '#E41E39'; msg.textContent = 'Scanner not reachable. Check it is plugged in and the scanner service is running.'; })
                .finally(function () { clearTimeout(t); btn.disabled = false; });
        });
        form.addEventListener('submit', function (e) { if (mode() === 'thumb' && !tpl.value) { e.preventDefault(); alert("Capture the client's left thumbprint on the scanner first."); } });
        sync();
    })();
    </script>
    <?php
}