// zkteco_server.js
const express = require('express');
const ffi = require('ffi-napi');
const ref = require('ref-napi');
const cors = require('cors');
const path = require('path');

const app = express();
app.use(cors());
app.use(express.json());

// Use port 3001 to avoid conflicts with other services
const PORT = 3001;

const fs = require('fs');

const POSSIBLE_ZKFP_PATHS = [
    'C:\\Program Files (x86)\\FPSensor\\Biokey\\ZKFPCap.dll',
    'C:\\Program Files (x86)\\ZKTeco\\ZKAccess3.5\\ZKFPCap.dll',
    'C:\\Program Files (x86)\\ZKTeco\\ZKFPCap.dll',
    'C:\\Program Files (x86)\\FPSensor\\ZKFPCap.dll',
    'C:\\Program Files\\FPSensor\\Biokey\\ZKFPCap.dll',
    'C:\\Program Files\\ZKTeco\\ZKAccess3.5\\ZKFPCap.dll',
    'C:\\Program Files\\ZKTeco\\ZKFPCap.dll'
];

const POSSIBLE_USB_PATHS = [
    'C:\\Program Files (x86)\\FPSensor\\Biokey\\USB.dll',
    'C:\\Program Files (x86)\\ZKTeco\\ZKAccess3.5\\USB.dll',
    'C:\\Program Files (x86)\\ZKTeco\\USB.dll',
    'C:\\Program Files (x86)\\FPSensor\\USB.dll',
    'C:\\Program Files\\FPSensor\\Biokey\\USB.dll',
    'C:\\Program Files\\ZKTeco\\ZKAccess3.5\\USB.dll',
    'C:\\Program Files\\ZKTeco\\USB.dll'
];

let ZKFP_DLL_PATH = POSSIBLE_ZKFP_PATHS.find(p => fs.existsSync(p)) || null;
let USB_DLL_PATH = POSSIBLE_USB_PATHS.find(p => fs.existsSync(p)) || null;

console.log('='.repeat(70));
console.log('ZKTeco Fingerprint Server');
console.log('='.repeat(70));
console.log(`Node.js ${process.version}`);
console.log(`Architecture: ${process.arch}`);
console.log(`DLL Path: ${ZKFP_DLL_PATH || 'NOT FOUND'}`);
console.log(`Server: http://localhost:${PORT}`);
console.log('='.repeat(70));

// Check if DLL exists
if (!ZKFP_DLL_PATH) {
    console.error(`\nERROR: ZKFPCap.dll not found in any standard path.`);
    console.log('Checked paths:', POSSIBLE_ZKFP_PATHS);
    process.exit(1);
}

console.log('? DLL found, loading...');

// Load ZKTeco DLL
let zkfp;
try {
    // Try to load USB DLL first if it exists
    if (fs.existsSync(USB_DLL_PATH)) {
        try {
            const usbDll = ffi.Library(USB_DLL_PATH, {});
            console.log('? USB DLL loaded');
        } catch (e) {
            console.log('? USB DLL load failed (may be normal)');
        }
    }

    // Load ZKTeco fingerprint DLL
    zkfp = ffi.Library(ZKFP_DLL_PATH, {
        // Initialization functions
        'ZKFPM_Init': ['int', []],
        'ZKFPM_Terminate': ['int', []],
        'ZKFPM_GetDeviceCount': ['int', []],
        'ZKFPM_OpenDevice': ['pointer', ['int']],
        'ZKFPM_CloseDevice': ['int', ['pointer']],

        // Capture functions
        'ZKFPM_AcquireFingerprint': ['int', [
            'pointer',    // handle
            'pointer',    // image buffer
            ref.refType('int'),  // image size pointer
            'pointer',    // template buffer
            ref.refType('int')   // template size pointer
        ]],

        // DBMatch
        'ZKFPM_DBMatch': ['int', [
            'pointer',    // handle
            'pointer',    // template 1
            'int',        // size 1
            'pointer',    // template 2
            'int'         // size 2
        ]],

        // Get last error
        'ZKFPM_GetLastError': ['int', []]
    });

    console.log('? ZKTeco DLL loaded successfully');
} catch (error) {
    console.error('? Failed to load ZKTeco DLL:', error.message);
    console.log('\nTroubleshooting:');
    console.log('1. Make sure ZKTeco SDK is installed');
    console.log('2. Run this script as Administrator');
    console.log('3. The DLL might be 32-bit only');
    console.log('4. Try installing Microsoft Visual C++ Redistributable');
    process.exit(1);
}

// Helper function to get error message
function getErrorMessage(code) {
    const errors = {
        1: 'No finger detected',
        2: 'Capture failed',
        3: 'Fingerprint image is too dry',
        4: 'Fingerprint image is too wet',
        5: 'Fingerprint image is disorderly',
        6: 'Fingerprint image is too small',
        7: 'Fingerprint lacks center',
        8: 'Fingerprint lacks side',
        9: 'Fingerprint is too short',
        10: 'Image quality is too low',
        11: 'Timeout',
        12: 'Device not connected',
        13: 'Device already opened',
        14: 'Invalid parameter',
        15: 'Not enough memory',
        16: 'Database operation failed'
    };
    return errors[code] || `Error code: ${code}`;
}

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({
        success: true,
        service: 'ZKTeco Fingerprint Server',
        version: '1.0.0',
        node_version: process.version,
        architecture: process.arch,
        port: PORT,
        dll_loaded: !!zkfp,
        dll_path: ZKFP_DLL_PATH,
        timestamp: new Date().toISOString()
    });
});

// Test scanner connection
app.get('/test', (req, res) => {
    console.log('\n[TEST] Testing scanner connection...');

    try {
        // Initialize SDK
        const initResult = zkfp.ZKFPM_Init();
        console.log(`[TEST] SDK Init result: ${initResult}`);

        if (initResult !== 0) {
            const lastError = zkfp.ZKFPM_GetLastError();
            console.log(`[TEST] Last error code: ${lastError}`);

            // Try to terminate anyway
            try { zkfp.ZKFPM_Terminate(); } catch {}

            return res.json({
                success: false,
                message: getErrorMessage(initResult) || `SDK Init failed with code: ${initResult}`,
                error_code: initResult,
                last_error: lastError
            });
        }

        // Get device count
        const deviceCount = zkfp.ZKFPM_GetDeviceCount();
        console.log(`[TEST] Device count: ${deviceCount}`);

        // Try to open and close a device
        let deviceInfo = [];
        if (deviceCount > 0) {
            for (let i = 0; i < deviceCount; i++) {
                const handle = zkfp.ZKFPM_OpenDevice(i);
                if (handle.isNull()) {
                    deviceInfo.push({ index: i, status: 'Failed to open' });
                } else {
                    deviceInfo.push({ index: i, status: 'Connected' });
                    zkfp.ZKFPM_CloseDevice(handle);
                }
            }
        }

        // Terminate SDK
        zkfp.ZKFPM_Terminate();

        console.log(`[TEST] Test completed successfully`);

        res.json({
            success: true,
            message: deviceCount > 0 ?
                `Found ${deviceCount} fingerprint device(s)` :
                'Scanner connected but no devices found',
            device_count: deviceCount,
            devices: deviceInfo,
            dll_path: ZKFP_DLL_PATH
        });

    } catch (error) {
        console.error('[TEST] Error:', error.message);
        res.json({
            success: false,
            message: error.message,
            stack: process.env.NODE_ENV === 'development' ? error.stack : undefined
        });
    }
});

// Capture fingerprint endpoint
app.get('/capture', async (req, res) => {
    console.log('\n' + '='.repeat(60));
    console.log('[CAPTURE] Starting fingerprint capture...');

    try {
        // Initialize SDK
        console.log('[CAPTURE] Initializing SDK...');
        const initResult = zkfp.ZKFPM_Init();

        if (initResult !== 0) {
            console.log(`[CAPTURE] SDK Init failed: ${initResult}`);
            return res.json({
                success: false,
                message: getErrorMessage(initResult) || `SDK initialization failed: ${initResult}`,
                error_code: initResult
            });
        }

        console.log('[CAPTURE] SDK initialized');

        // Get device count
        const deviceCount = zkfp.ZKFPM_GetDeviceCount();
        console.log(`[CAPTURE] Found ${deviceCount} device(s)`);

        if (deviceCount <= 0) {
            zkfp.ZKFPM_Terminate();
            console.log('[CAPTURE] No devices found');
            return res.json({
                success: false,
                message: 'No fingerprint scanner detected. Please connect the device.',
                error_code: 12
            });
        }

        // Open first device
        console.log('[CAPTURE] Opening device...');
        const handle = zkfp.ZKFPM_OpenDevice(0);

        if (handle.isNull()) {
            zkfp.ZKFPM_Terminate();
            console.log('[CAPTURE] Failed to open device');
            return res.json({
                success: false,
                message: 'Failed to open fingerprint device',
                error_code: 13
            });
        }

        console.log('[CAPTURE] Device opened successfully');
        console.log('[CAPTURE] Waiting for fingerprint... Place finger on scanner');

        // Prepare buffers
        const IMAGE_BUFFER_SIZE = 256 * 360; // Typical fingerprint image size
        const TEMPLATE_BUFFER_SIZE = 1024;

        const imageBuffer = Buffer.alloc(IMAGE_BUFFER_SIZE);
        const imageSizePtr = ref.alloc('int', IMAGE_BUFFER_SIZE);

        const templateBuffer = Buffer.alloc(TEMPLATE_BUFFER_SIZE);
        const templateSizePtr = ref.alloc('int', TEMPLATE_BUFFER_SIZE);

        // Capture fingerprint
        const captureResult = zkfp.ZKFPM_AcquireFingerprint(
            handle,
            imageBuffer,
            imageSizePtr,
            templateBuffer,
            templateSizePtr
        );

        console.log(`[CAPTURE] Capture result: ${captureResult}`);

        if (captureResult !== 0) {
            const errorMsg = getErrorMessage(captureResult);
            console.log(`[CAPTURE] Capture failed: ${errorMsg}`);

            zkfp.ZKFPM_CloseDevice(handle);
            zkfp.ZKFPM_Terminate();

            return res.json({
                success: false,
                message: errorMsg,
                error_code: captureResult,
                suggestion: 'Please try again with clean, dry finger'
            });
        }

        // Get actual data sizes
        const actualImageSize = ref.deref(imageSizePtr);
        const actualTemplateSize = ref.deref(templateSizePtr);

        console.log(`[CAPTURE] Capture successful!`);
        console.log(`[CAPTURE] Image size: ${actualImageSize} bytes`);
        console.log(`[CAPTURE] Template size: ${actualTemplateSize} bytes`);

        // Extract data from buffers
        const imageData = imageBuffer.slice(0, Math.min(actualImageSize, IMAGE_BUFFER_SIZE));
        const templateData = templateBuffer.slice(0, Math.min(actualTemplateSize, TEMPLATE_BUFFER_SIZE));

        // Calculate quality score (simplified)
        let qualityScore = 80; // Default good quality

        if (imageData.length > 100) {
            // Simple quality estimation based on image variance
            let sum = 0;
            let sumSquares = 0;
            const sampleSize = Math.min(1000, imageData.length);

            for (let i = 0; i < sampleSize; i++) {
                const value = imageData[i];
                sum += value;
                sumSquares += value * value;
            }

            const mean = sum / sampleSize;
            const variance = (sumSquares / sampleSize) - (mean * mean);
            qualityScore = Math.min(100, Math.max(50, Math.floor(variance / 2 + 70)));
        }

        console.log(`[CAPTURE] Quality score: ${qualityScore}/100`);

        // Clean up
        zkfp.ZKFPM_CloseDevice(handle);
        zkfp.ZKFPM_Terminate();

        console.log('[CAPTURE] Capture completed successfully!');
        console.log('='.repeat(60));

        // Send response
        res.json({
            success: true,
            message: 'Fingerprint captured successfully',
            fingerprint_data_base64: imageData.toString('base64'),
            fingerprint_template: templateData.toString('base64'),
            quality_score: qualityScore,
            image_size: actualImageSize,
            template_size: actualTemplateSize,
            timestamp: new Date().toISOString()
        });

    } catch (error) {
        console.error('[CAPTURE] Exception:', error.message);
        console.error(error.stack);

        // Try to clean up on error
        try { zkfp.ZKFPM_Terminate(); } catch {}

        res.json({
            success: false,
            message: `Capture error: ${error.message}`,
            error: process.env.NODE_ENV === 'development' ? error.stack : undefined
        });
    }
});

// List all devices
app.get('/devices', (req, res) => {
    console.log('\n[DEVICES] Listing devices...');

    try {
        const initResult = zkfp.ZKFPM_Init();
        if (initResult !== 0) {
            return res.json({
                success: false,
                message: `SDK Init failed: ${initResult}`
            });
        }

        const deviceCount = zkfp.ZKFPM_GetDeviceCount();
        const devices = [];

        for (let i = 0; i < deviceCount; i++) {
            const handle = zkfp.ZKFPM_OpenDevice(i);
            const isConnected = !handle.isNull();

            devices.push({
                index: i,
                connected: isConnected,
                handle: isConnected ? `0x${handle.address().toString(16)}` : null,
                status: isConnected ? 'Ready' : 'Not connected'
            });

            if (isConnected) {
                zkfp.ZKFPM_CloseDevice(handle);
            }
        }

        zkfp.ZKFPM_Terminate();

        res.json({
            success: true,
            device_count: deviceCount,
            devices: devices
        });

    } catch (error) {
        console.error('[DEVICES] Error:', error.message);
        try { zkfp.ZKFPM_Terminate(); } catch {}
        res.json({ success: false, message: error.message });
    }
});

// Compare two templates
app.post('/match', (req, res) => {
    console.log('\n[MATCH] Comparing two templates...');
    try {
        const { template1, template2 } = req.body;
        if (!template1 || !template2) {
            return res.status(400).json({ success: false, message: 'Missing template1 or template2' });
        }

        const temp1Buffer = Buffer.from(template1, 'base64');
        const temp2Buffer = Buffer.from(template2, 'base64');

        const initResult = zkfp.ZKFPM_Init();
        if (initResult !== 0) {
            return res.status(500).json({ success: false, message: `SDK Init failed: ${initResult}` });
        }

        const handle = zkfp.ZKFPM_OpenDevice(0);
        if (handle.isNull()) {
            zkfp.ZKFPM_Terminate();
            return res.status(500).json({ success: false, message: 'Failed to open device' });
        }

        const score = zkfp.ZKFPM_DBMatch(handle, temp1Buffer, temp1Buffer.length, temp2Buffer, temp2Buffer.length);

        zkfp.ZKFPM_CloseDevice(handle);
        zkfp.ZKFPM_Terminate();

        res.json({
            success: true,
            score: score,
            match: score >= 80
        });

    } catch (error) {
        console.error('[MATCH] Error:', error.message);
        try { zkfp.ZKFPM_Terminate(); } catch {}
        res.status(500).json({ success: false, message: error.message });
    }
});

// Identify a template against candidates
app.post('/identify', (req, res) => {
    console.log('\n[IDENTIFY] Identifying template...');
    try {
        const { captured_template, candidates } = req.body;
        if (!captured_template || !candidates) {
            return res.status(400).json({ success: false, message: 'Missing captured_template or candidates' });
        }

        const capBuffer = Buffer.from(captured_template, 'base64');

        const initResult = zkfp.ZKFPM_Init();
        if (initResult !== 0) {
            return res.status(500).json({ success: false, message: `SDK Init failed: ${initResult}` });
        }

        const handle = zkfp.ZKFPM_OpenDevice(0);
        if (handle.isNull()) {
            zkfp.ZKFPM_Terminate();
            return res.status(500).json({ success: false, message: 'Failed to open device' });
        }

        let match_id = null;
        let max_score = 0;
        const threshold = 80;

        for (const candidate of candidates) {
            if (!candidate.template) continue;
            try {
                const candBuffer = Buffer.from(candidate.template, 'base64');
                const score = zkfp.ZKFPM_DBMatch(handle, capBuffer, capBuffer.length, candBuffer, candBuffer.length);
                if (score > max_score) {
                    max_score = score;
                    if (score >= threshold) {
                        match_id = candidate.id;
                    }
                }
            } catch (ex) {
                console.error(`[IDENTIFY] Error matching candidate ${candidate.id}:`, ex.message);
            }
        }

        zkfp.ZKFPM_CloseDevice(handle);
        zkfp.ZKFPM_Terminate();

        res.json({
            success: true,
            match_id: match_id,
            score: max_score,
            matched: match_id !== null
        });

    } catch (error) {
        console.error('[IDENTIFY] Error:', error.message);
        try { zkfp.ZKFPM_Terminate(); } catch {}
        res.status(500).json({ success: false, message: error.message });
    }
});

// Error handling middleware
app.use((err, req, res, next) => {
    console.error('[SERVER] Error:', err.message);
    res.status(500).json({
        success: false,
        message: 'Internal server error',
        error: process.env.NODE_ENV === 'development' ? err.message : undefined
    });
});

// Start server
app.listen(PORT, '0.0.0.0', () => {
    console.log(`\n? Server running at http://localhost:${PORT}`);
    console.log('\nAvailable endpoints:');
    console.log(`  http://localhost:${PORT}/health`);
    console.log(`  http://localhost:${PORT}/test`);
    console.log(`  http://localhost:${PORT}/capture`);
    console.log(`  http://localhost:${PORT}/devices`);
    console.log('\n' + '='.repeat(70));
    console.log('Press Ctrl+C to stop the server');
    console.log('='.repeat(70));
});

// Handle graceful shutdown
process.on('SIGINT', () => {
    console.log('\n\n?? Server shutting down...');
    process.exit(0);
});