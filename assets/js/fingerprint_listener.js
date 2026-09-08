// assets/js/fingerprint_listener.js
/**
 * EasyFlow-L Fingerprint Scanner Integration
 * Automatically handles patient identification and dispensing verification.
 */

let fingerServerPort = 3000;
let fingerServerProto = 'http';
let fingerServerType = 'zkteco';
let isScanningActive = false;
let scanningLoopTimeout = null;

// Path to the SecuGen PHP bridge, from either a biometrics/ page or a page
// one level up (dispensing.php, dispensingData.php, etc.)
function getSecugenApiPath() {
    return window.location.pathname.indexOf('/biometrics/') !== -1
        ? 'secugen_api.php'
        : '../biometrics/secugen_api.php';
}

// fetch() has no built-in timeout, and the raw SecuGen WebAPI silently drops
// requests to routes it doesn't recognize (e.g. /health) instead of
// responding with 404 - that leaves an unbounded fetch() hanging forever.
// Every direct, unguarded probe below is wrapped with this so detection can
// never freeze the page.
function fetchWithTimeout(url, options = {}, timeoutMs = 3000) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);
    return fetch(url, { ...options, signal: controller.signal }).finally(() => clearTimeout(timer));
}

// Detect active fingerprint server (ZKTeco or SecuGen)
let isAutoStarting = false;
async function detectFingerprintServerPort() {
    // SecuGen: always go through the PHP bridge (secugen_api.php), never the
    // raw WebAPI directly. The bridge already knows which port is open, adds
    // the Origin header the WebAPI requires, and answers with a fast TCP
    // check rather than physically engaging the sensor just to say "online".
    try {
        const resp = await fetchWithTimeout(`${getSecugenApiPath()}?action=health`, {}, 6000);
        if (resp.ok) {
            const data = await resp.json();
            if (data.success) {
                fingerServerType = 'secugen';
                fingerServerPort = data.port || 8000;
                fingerServerProto = data.protocol || 'http';
                console.log(`SecuGen WebAPI detected via PHP bridge on port ${fingerServerPort}`);
                return fingerServerPort;
            }
        }
    } catch (e) {}

    // ZKTeco: talk to its local REST server directly (it responds to /health
    // quickly and correctly, unlike the SecuGen WebAPI).
    for (let port of [3000, 3001]) {
        try {
            let resp = await fetchWithTimeout(`http://localhost:${port}/health`, {}, 1500);
            if (resp.ok) {
                fingerServerPort = port;
                fingerServerProto = 'http';
                fingerServerType = 'zkteco';
                console.log(`ZKTeco server detected on port ${port}`);
                return port;
            }
        } catch (e) {}
    }

    // Server is offline, trigger auto-start
    if (!isAutoStarting) {
        isAutoStarting = true;
        console.warn("No fingerprint scanner server detected. Launching in background...");
        let autoStartPath = '../biometrics/auto_start_server.php';
        if (window.location.pathname.indexOf('/biometrics/') !== -1) {
            autoStartPath = 'auto_start_server.php';
        }

        try {
            fetch(autoStartPath).then(() => {
                setTimeout(() => { isAutoStarting = false; }, 5000);
            });
        } catch(err) {
            isAutoStarting = false;
        }
    }

    return fingerServerPort;
}

// Update status message UI
function updateScannerUI(message, statusClass) {
    let indicator = document.getElementById('fingerprint-scanner-indicator');
    if (!indicator) {
        // Create indicator bar if it doesn't exist
        indicator = document.createElement('div');
        indicator.id = 'fingerprint-scanner-indicator';
        indicator.style.padding = '12px';
        indicator.style.margin = '15px 0';
        indicator.style.borderRadius = '8px';
        indicator.style.fontWeight = 'bold';
        indicator.style.textAlign = 'center';
        indicator.style.fontSize = '15px';
        indicator.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
        
        // Insert at top of main form or body
        const target = document.querySelector('.header') || document.querySelector('h2') || document.body.firstChild;
        if (target) {
            target.parentNode.insertBefore(indicator, target.nextSibling);
        } else {
            document.body.prepend(indicator);
        }
    }
    
    indicator.textContent = message;
    indicator.style.transition = 'all 0.3s';
    
    if (statusClass === 'ready') {
        indicator.style.backgroundColor = '#e3f2fd';
        indicator.style.color = '#0d47a1';
        indicator.style.border = '1px solid #90caf9';
    } else if (statusClass === 'scanning') {
        indicator.style.backgroundColor = '#fff3e0';
        indicator.style.color = '#e65100';
        indicator.style.border = '1px solid #ffb74d';
    } else if (statusClass === 'success') {
        indicator.style.backgroundColor = '#e8f5e9';
        indicator.style.color = '#1b5e20';
        indicator.style.border = '1px solid #a5d6a7';
    } else if (statusClass === 'error') {
        indicator.style.backgroundColor = '#ffebee';
        indicator.style.color = '#c62828';
        indicator.style.border = '1px solid #ef9a9a';
    } else {
        indicator.style.backgroundColor = '#f5f5f5';
        indicator.style.color = '#616161';
        indicator.style.border = '1px solid #e0e0e0';
    }
}

// Start identification loop (for search pages: dispensing.php, dispensing_pump.php)
async function startFingerprintIdentifyLoop(isPumpMode) {
    if (isScanningActive) return;
    isScanningActive = true;
    
    await detectFingerprintServerPort();
    updateScannerUI("Fetching registered fingerprints...", "scanning");
    
    let candidates = [];
    try {
        let resp = await fetch('get_all_fingerprints.php');
        let res = await resp.json();
        if (res.success) {
            candidates = res.candidates;
            console.log(`Loaded ${candidates.length} candidate fingerprint templates.`);
        } else {
            updateScannerUI("Failed to load patient fingerprints", "error");
            isScanningActive = false;
            return;
        }
    } catch(e) {
        updateScannerUI("Database/session connection error", "error");
        isScanningActive = false;
        return;
    }
    
    if (candidates.length === 0) {
        updateScannerUI("No patients with registered fingerprints found.", "ready");
        isScanningActive = false;
        return;
    }
    
    updateScannerUI("Fingerprint Scanner: Ready (Place client's finger to identify)", "ready");

    async function fetchCapturedTemplate() {
        if (fingerServerType === 'secugen') {
            // Some SecuGen devices/drivers take ~10s per capture attempt even
            // when no finger is present, so give this a generous timeout -
            // but always through the PHP bridge, never a raw /capture route
            // on the WebAPI (it doesn't have one, and would hang forever).
            try {
                let resp = await fetchWithTimeout(`${getSecugenApiPath()}?action=capture`, {}, 25000);
                if (resp.ok) {
                    let data = await resp.json();
                    if (data.success && data.fingerprint_template) {
                        return data.fingerprint_template;
                    }
                }
            } catch(e) {}
            return null;
        }
        try {
            let resp = await fetchWithTimeout(`${fingerServerProto}://localhost:${fingerServerPort}/capture`, {}, 5000);
            if (resp.ok) {
                let data = await resp.json();
                if (data.success && data.fingerprint_template) {
                    return data.fingerprint_template;
                }
            }
        } catch(e) {}
        return null;
    }

    async function scan() {
        if (!isScanningActive) return;

        try {
            let capturedTemplate = await fetchCapturedTemplate();
            if (capturedTemplate) {
                updateScannerUI("Fingerprint scanned. Identifying...", "scanning");

                let idResp;
                if (fingerServerType === 'secugen') {
                    idResp = await fetchWithTimeout(`${getSecugenApiPath()}?action=identify`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            captured_template: capturedTemplate,
                            candidates: candidates
                        })
                    }, 8000);
                } else {
                    idResp = await fetchWithTimeout(`http://localhost:${fingerServerPort}/identify`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            captured_template: capturedTemplate,
                            candidates: candidates
                        })
                    }, 8000);
                }
                
                if (idResp && idResp.ok) {
                    let idData = await idResp.json();
                    if (idData.success && idData.matched) {
                        updateScannerUI(`Patient Identified! Opening dispensing page...`, "success");
                        setTimeout(() => {
                            const targetPage = isPumpMode ? 'dispensingData_pump.php' : 'dispensingData.php';
                            window.location.href = `${targetPage}?mat_id=${encodeURIComponent(idData.match_id)}`;
                        }, 1000);
                        return; // Stop scanning since we are redirecting
                    } else {
                        updateScannerUI("No matching registered patient found. Try again.", "error");
                    }
                } else {
                    updateScannerUI("Identification error on scanner server.", "error");
                }
            }
        } catch(e) {
            console.log("Scanner connection waiting...");
        }
        
        scanningLoopTimeout = setTimeout(scan, 2000);
    }
    
    scan();
}

// Start verification loop (for dispensing pages: dispensingData.php, dispensingData_pump.php)
async function startFingerprintVerifyLoop(registeredTemplateB64) {
    if (!registeredTemplateB64) {
        updateScannerUI("No fingerprint registered for this patient. Manual dispensing only.", "error");
        return;
    }
    
    if (isScanningActive) return;
    isScanningActive = true;
    
    await detectFingerprintServerPort();
    updateScannerUI("Fingerprint Scanner: Ready (Scan finger again to confirm dispensing)", "ready");

    async function fetchCapturedTemplate() {
        if (fingerServerType === 'secugen') {
            // Some SecuGen devices/drivers take ~10s per capture attempt even
            // when no finger is present, so give this a generous timeout -
            // but always through the PHP bridge, never a raw /capture route
            // on the WebAPI (it doesn't have one, and would hang forever).
            try {
                let resp = await fetchWithTimeout(`${getSecugenApiPath()}?action=capture`, {}, 25000);
                if (resp.ok) {
                    let data = await resp.json();
                    if (data.success && data.fingerprint_template) {
                        return data.fingerprint_template;
                    }
                }
            } catch(e) {}
            return null;
        }
        try {
            let resp = await fetchWithTimeout(`${fingerServerProto}://localhost:${fingerServerPort}/capture`, {}, 5000);
            if (resp.ok) {
                let data = await resp.json();
                if (data.success && data.fingerprint_template) {
                    return data.fingerprint_template;
                }
            }
        } catch(e) {}
        return null;
    }

    async function scan() {
        if (!isScanningActive) return;

        try {
            let capturedTemplate = await fetchCapturedTemplate();
            if (capturedTemplate) {
                updateScannerUI("Fingerprint scanned. Verifying...", "scanning");

                let matchResp;
                if (fingerServerType === 'secugen') {
                    matchResp = await fetchWithTimeout(`${getSecugenApiPath()}?action=match`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            template1: capturedTemplate,
                            template2: registeredTemplateB64
                        })
                    }, 8000);
                } else {
                    matchResp = await fetchWithTimeout(`http://localhost:${fingerServerPort}/match`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            template1: capturedTemplate,
                            template2: registeredTemplateB64
                        })
                    }, 8000);
                }
                
                if (matchResp && matchResp.ok) {
                    let matchData = await matchResp.json();
                    if (matchData.success && matchData.match) {
                        updateScannerUI("Fingerprint Verified! Submitting dose dispensing...", "success");
                        
                        setTimeout(() => {
                            const form = document.getElementById('dispenseForm');
                            if (form) {
                                if (typeof validateForm === 'function' && !validateForm()) {
                                    updateScannerUI("Form validation failed. Check dosage/fields.", "error");
                                    isScanningActive = false;
                                    setTimeout(() => startFingerprintVerifyLoop(registeredTemplateB64), 3000);
                                    return;
                                }
                                form.submit();
                            } else {
                                updateScannerUI("Dispensing form not found on page.", "error");
                            }
                        }, 1000);
                        return; // Stop scanning since we are submitting
                    } else {
                        updateScannerUI("Verification Failed: Finger does not match this patient.", "error");
                    }
                } else {
                    updateScannerUI("Verification error on scanner server.", "error");
                }
            }
        } catch(e) {
            console.log("Scanner connection waiting...");
        }
        
        scanningLoopTimeout = setTimeout(scan, 2000);
    }
    
    scan();
}

// Stop any active scanning loops
function stopFingerprintScanning() {
    isScanningActive = false;
    if (scanningLoopTimeout) {
        clearTimeout(scanningLoopTimeout);
        scanningLoopTimeout = null;
    }
}
