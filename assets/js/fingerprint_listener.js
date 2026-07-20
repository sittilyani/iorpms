// assets/js/fingerprint_listener.js
/**
 * EasyFlow-L Fingerprint Scanner Integration
 * Automatically handles patient identification and dispensing verification.
 */

let fingerServerPort = 3000;
let isScanningActive = false;
let scanningLoopTimeout = null;

// Detect active fingerprint server
let isAutoStarting = false;
async function detectFingerprintServerPort() {
    try {
        let resp = await fetch('http://localhost:3000/health');
        if (resp.ok) {
            fingerServerPort = 3000;
            console.log("Fingerprint scanner server detected on port 3000 (Python)");
            return 3000;
        }
    } catch(e) {}
    try {
        let resp = await fetch('http://localhost:3001/health');
        if (resp.ok) {
            fingerServerPort = 3001;
            console.log("Fingerprint scanner server detected on port 3001 (Node.js)");
            return 3001;
        }
    } catch(e) {}
    
    // Server is offline, trigger auto-start (only once per detection round)
    if (!isAutoStarting) {
        isAutoStarting = true;
        console.warn("No fingerprint scanner server detected on port 3000 or 3001. Launching in background...");
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
    
    return 3000;
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
    
    async function scan() {
        if (!isScanningActive) return;
        
        try {
            let resp = await fetch(`http://localhost:${fingerServerPort}/capture`);
            if (resp.ok) {
                let data = await resp.json();
                if (data.success && data.fingerprint_template) {
                    updateScannerUI("Fingerprint scanned. Identifying...", "scanning");
                    
                    // Send to server to identify
                    let idResp = await fetch(`http://localhost:${fingerServerPort}/identify`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            captured_template: data.fingerprint_template,
                            candidates: candidates
                        })
                    });
                    
                    if (idResp.ok) {
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
            }
        } catch(e) {
            // Server might be offline or call timed out
            console.log("Scanner connection waiting...");
        }
        
        // Loop again
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
    
    async function scan() {
        if (!isScanningActive) return;
        
        try {
            let resp = await fetch(`http://localhost:${fingerServerPort}/capture`);
            if (resp.ok) {
                let data = await resp.json();
                if (data.success && data.fingerprint_template) {
                    updateScannerUI("Fingerprint scanned. Verifying...", "scanning");
                    
                    // Compare templates
                    let matchResp = await fetch(`http://localhost:${fingerServerPort}/match`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            template1: data.fingerprint_template,
                            template2: registeredTemplateB64
                        })
                    });
                    
                    if (matchResp.ok) {
                        let matchData = await matchResp.json();
                        if (matchData.success && matchData.match) {
                            updateScannerUI("Fingerprint Verified! Submitting dose dispensing...", "success");
                            
                            // Trigger form submit
                            setTimeout(() => {
                                const form = document.getElementById('dispenseForm');
                                if (form) {
                                    // Call form validation if defined
                                    if (typeof validateForm === 'function' && !validateForm()) {
                                        updateScannerUI("Form validation failed. Check dosage/fields.", "error");
                                        isScanningActive = false;
                                        // Resume scan loop in case they fix it
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
