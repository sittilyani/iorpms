# zkteco_python_server.py
from flask import Flask, jsonify, request
from flask_cors import CORS
import ctypes
import os
import base64
import sys

app = Flask(__name__)
CORS(app)  # Enable CORS for all routes

# POSSIBLE PATHS FOR YOUR ZKTECO / FPSENSOR INSTALLATION
POSSIBLE_ZKFP_PATHS = [
    r"C:\Program Files (x86)\FPSensor\Biokey\ZKFPCap.dll",
    r"C:\Program Files (x86)\ZKTeco\ZKAccess3.5\ZKFPCap.dll",
    r"C:\Program Files (x86)\ZKTeco\ZKFPCap.dll",
    r"C:\Program Files (x86)\FPSensor\ZKFPCap.dll",
    r"C:\Program Files\FPSensor\Biokey\ZKFPCap.dll",
    r"C:\Program Files\ZKTeco\ZKAccess3.5\ZKFPCap.dll",
    r"C:\Program Files\ZKTeco\ZKFPCap.dll",
]

POSSIBLE_USB_PATHS = [
    r"C:\Program Files (x86)\FPSensor\Biokey\USB.dll",
    r"C:\Program Files (x86)\ZKTeco\ZKAccess3.5\USB.dll",
    r"C:\Program Files (x86)\ZKTeco\USB.dll",
    r"C:\Program Files (x86)\FPSensor\USB.dll",
    r"C:\Program Files\FPSensor\Biokey\USB.dll",
    r"C:\Program Files\ZKTeco\ZKAccess3.5\USB.dll",
    r"C:\Program Files\ZKTeco\USB.dll",
]

ZKFP_DLL_PATH = None
for p in POSSIBLE_ZKFP_PATHS:
    if os.path.exists(p):
        ZKFP_DLL_PATH = p
        break

USB_DLL_PATH = None
for p in POSSIBLE_USB_PATHS:
    if os.path.exists(p):
        USB_DLL_PATH = p
        break

print("=" * 60)
print("ZKTeco Fingerprint Server")
print("=" * 60)
print(f"Python version: {sys.version}")
print(f"Working directory: {os.getcwd()}")
print(f"Looking for DLL at: {ZKFP_DLL_PATH}")

# Check if DLL exists
if not ZKFP_DLL_PATH:
    print("ERROR: ZKFPCap.dll not found in any standard installation directory.")
    print("Checked paths:", POSSIBLE_ZKFP_PATHS)
else:
    print(f"DLL found: {ZKFP_DLL_PATH}")

print("=" * 60)

# Load the DLLs
zkfp = None
try:
    if USB_DLL_PATH and os.path.exists(USB_DLL_PATH):
        try:
            usb_dll = ctypes.WinDLL(USB_DLL_PATH)
            print(f"Loaded USB DLL: {USB_DLL_PATH}")
        except Exception as e:
            print(f"USB DLL load warning: {e}")

    if ZKFP_DLL_PATH:
        # Change current dir or add DLL directory to PATH/DLL search path for dependencies
        dll_dir = os.path.dirname(ZKFP_DLL_PATH)
        if hasattr(os, 'add_dll_directory'):
            try:
                os.add_dll_directory(dll_dir)
            except Exception:
                pass
        os.environ['PATH'] = dll_dir + os.pathsep + os.environ.get('PATH', '')
        
        zkfp = ctypes.WinDLL(ZKFP_DLL_PATH)
        print(f"Successfully loaded ZKTeco DLL from {ZKFP_DLL_PATH}")

except Exception as e:
    print(f"Error loading DLLs: {e}")
    print("\nTroubleshooting tips:")
    print("1. Make sure ZKTeco drivers are installed")
    print("2. Run this script as Administrator")
    print("3. Check if DLL is 32-bit or 64-bit (32-bit Python requires 32-bit DLL, 64-bit Python requires 64-bit DLL)")
    zkfp = None

class ZKTecoSDKWrapper:
    def __init__(self, zkfp):
        self.zkfp = zkfp
        self.api_type = 'sensor' if hasattr(zkfp, 'sensorInit') else 'ZKFPM'
        self._setup_signatures()

    def _setup_signatures(self):
        if self.api_type == 'sensor':
            self.zkfp.sensorInit.restype = ctypes.c_int
            self.zkfp.sensorInit.argtypes = []

            if hasattr(self.zkfp, 'sensorFree'):
                self.zkfp.sensorFree.restype = ctypes.c_int
                self.zkfp.sensorFree.argtypes = []

            self.zkfp.sensorGetCount.restype = ctypes.c_int
            self.zkfp.sensorGetCount.argtypes = []

            self.zkfp.sensorOpen.restype = ctypes.c_void_p
            self.zkfp.sensorOpen.argtypes = [ctypes.c_int]

            self.zkfp.sensorClose.restype = ctypes.c_int
            self.zkfp.sensorClose.argtypes = [ctypes.c_void_p]

            self.zkfp.sensorCapture.restype = ctypes.c_int
            self.zkfp.sensorCapture.argtypes = [
                ctypes.c_void_p,
                ctypes.POINTER(ctypes.c_byte),
                ctypes.POINTER(ctypes.c_int),
                ctypes.POINTER(ctypes.c_byte),
                ctypes.POINTER(ctypes.c_int)
            ]
        else:
            if hasattr(self.zkfp, 'ZKFPM_Init'):
                self.zkfp.ZKFPM_Init.restype = ctypes.c_int
                self.zkfp.ZKFPM_Init.argtypes = []
            if hasattr(self.zkfp, 'ZKFPM_Terminate'):
                self.zkfp.ZKFPM_Terminate.restype = ctypes.c_int
                self.zkfp.ZKFPM_Terminate.argtypes = []
            if hasattr(self.zkfp, 'ZKFPM_GetDeviceCount'):
                self.zkfp.ZKFPM_GetDeviceCount.restype = ctypes.c_int
                self.zkfp.ZKFPM_GetDeviceCount.argtypes = []
            if hasattr(self.zkfp, 'ZKFPM_OpenDevice'):
                self.zkfp.ZKFPM_OpenDevice.restype = ctypes.c_void_p
                self.zkfp.ZKFPM_OpenDevice.argtypes = [ctypes.c_int]
            if hasattr(self.zkfp, 'ZKFPM_CloseDevice'):
                self.zkfp.ZKFPM_CloseDevice.restype = ctypes.c_int
                self.zkfp.ZKFPM_CloseDevice.argtypes = [ctypes.c_void_p]
            if hasattr(self.zkfp, 'ZKFPM_AcquireFingerprint'):
                self.zkfp.ZKFPM_AcquireFingerprint.restype = ctypes.c_int
                self.zkfp.ZKFPM_AcquireFingerprint.argtypes = [
                    ctypes.c_void_p,
                    ctypes.POINTER(ctypes.c_byte),
                    ctypes.POINTER(ctypes.c_int),
                    ctypes.POINTER(ctypes.c_byte),
                    ctypes.POINTER(ctypes.c_int)
                ]

    def init(self):
        if self.api_type == 'sensor':
            return self.zkfp.sensorInit()
        elif hasattr(self.zkfp, 'ZKFPM_Init'):
            return self.zkfp.ZKFPM_Init()
        return 0

    def terminate(self):
        if self.api_type == 'sensor':
            if hasattr(self.zkfp, 'sensorFree'):
                return self.zkfp.sensorFree()
            return 0
        elif hasattr(self.zkfp, 'ZKFPM_Terminate'):
            return self.zkfp.ZKFPM_Terminate()
        return 0

    def get_device_count(self):
        if self.api_type == 'sensor':
            return self.zkfp.sensorGetCount()
        elif hasattr(self.zkfp, 'ZKFPM_GetDeviceCount'):
            return self.zkfp.ZKFPM_GetDeviceCount()
        return 0

    def open_device(self, index=0):
        if self.api_type == 'sensor':
            return self.zkfp.sensorOpen(index)
        elif hasattr(self.zkfp, 'ZKFPM_OpenDevice'):
            return self.zkfp.ZKFPM_OpenDevice(index)
        return None

    def close_device(self, handle):
        if not handle:
            return 0
        if self.api_type == 'sensor':
            return self.zkfp.sensorClose(handle)
        elif hasattr(self.zkfp, 'ZKFPM_CloseDevice'):
            return self.zkfp.ZKFPM_CloseDevice(handle)
        return 0

    def acquire_fingerprint(self, handle, img_buf, img_sz_ptr, tmp_buf, tmp_sz_ptr):
        if self.api_type == 'sensor':
            return self.zkfp.sensorCapture(handle, img_buf, img_sz_ptr, tmp_buf, tmp_sz_ptr)
        elif hasattr(self.zkfp, 'ZKFPM_AcquireFingerprint'):
            return self.zkfp.ZKFPM_AcquireFingerprint(handle, img_buf, img_sz_ptr, tmp_buf, tmp_sz_ptr)
        return -1

sdk_wrapper = ZKTecoSDKWrapper(zkfp) if zkfp else None

@app.route('/test', methods=['GET'])
def test_connection():
    """Test if scanner SDK is working"""
    try:
        if not sdk_wrapper:
            return jsonify({"success": False, "message": "DLL not loaded"}), 500

        result = sdk_wrapper.init()
        if result != 0:
            return jsonify({
                "success": False,
                "message": f"SDK Init failed with code: {result}",
                "error_code": result
            }), 500

        device_count = sdk_wrapper.get_device_count()
        print(f"Device count: {device_count}")

        device_info = []
        for i in range(device_count):
            handle = sdk_wrapper.open_device(i)
            if handle:
                device_info.append({
                    "index": i,
                    "handle": f"0x{handle:X}",
                    "status": "Connected"
                })
                sdk_wrapper.close_device(handle)
            else:
                device_info.append({
                    "index": i,
                    "handle": None,
                    "status": "Failed to open"
                })

        sdk_wrapper.terminate()

        return jsonify({
            "success": True,
            "message": f"ZKTeco SDK initialized successfully. Found {device_count} device(s).",
            "device_count": device_count,
            "devices": device_info,
            "dll_path": ZKFP_DLL_PATH
        })
    except Exception as e:
        return jsonify({"success": False, "message": str(e)}), 500

@app.route('/capture', methods=['GET'])
def capture_fingerprint():
    """Capture fingerprint from ZKTeco scanner"""
    try:
        if not sdk_wrapper:
            return jsonify({"success": False, "message": "DLL not loaded"}), 500

        print("\n" + "="*50)
        print("Starting fingerprint capture...")

        result = sdk_wrapper.init()
        if result != 0:
            print(f"SDK Init failed with code: {result}")
            return jsonify({
                "success": False,
                "message": f"SDK Init failed with code: {result}"
            }), 500

        device_count = sdk_wrapper.get_device_count()
        print(f"Found {device_count} device(s)")

        if device_count <= 0:
            sdk_wrapper.terminate()
            print("No devices found")
            return jsonify({
                "success": False,
                "message": "No fingerprint device found. Please connect scanner."
            }), 500

        handle = sdk_wrapper.open_device(0)
        if not handle:
            sdk_wrapper.terminate()
            print("Failed to open device")
            return jsonify({
                "success": False,
                "message": "Failed to open device"
            }), 500

        print(f"Device opened successfully. Handle: 0x{handle:X}")

        IMAGE_WIDTH = 256
        IMAGE_HEIGHT = 360
        IMAGE_SIZE = IMAGE_WIDTH * IMAGE_HEIGHT
        TEMPLATE_SIZE = 2048

        image_buffer = (ctypes.c_byte * IMAGE_SIZE)()
        image_size = ctypes.c_int(IMAGE_SIZE)

        template_buffer = (ctypes.c_byte * TEMPLATE_SIZE)()
        template_size = ctypes.c_int(TEMPLATE_SIZE)

        print("Waiting for fingerprint... Place finger on scanner")

        # Poll capture for up to 10 seconds
        import time
        capture_result = -1
        start_time = time.time()

        while time.time() - start_time < 10.0:
            image_size.value = IMAGE_SIZE
            template_size.value = TEMPLATE_SIZE
            capture_result = sdk_wrapper.acquire_fingerprint(
                handle,
                image_buffer,
                ctypes.byref(image_size),
                template_buffer,
                ctypes.byref(template_size)
            )
            if capture_result == 0:
                break
            time.sleep(0.3)

        print(f"Capture result code: {capture_result}")

        if capture_result != 0:
            sdk_wrapper.close_device(handle)
            sdk_wrapper.terminate()

            error_messages = {
                1: "No finger detected",
                2: "Capture failed",
                3: "Image too dry",
                4: "Image too wet",
                5: "Image disorder",
                6: "Image too little",
                7: "Image lack of center",
                8: "Image lack of side",
                9: "Image too short",
                10: "Image quality too low",
                -111: "No finger detected on scanner. Please place finger firmly on scanner."
            }

            error_msg = error_messages.get(capture_result, f"Error code: {capture_result}")
            print(f"Capture failed: {error_msg}")

            return jsonify({
                "success": False,
                "message": f"Capture failed: {error_msg}",
                "error_code": capture_result
            }), 200

        print(f"Capture successful!")
        print(f"  Image size: {image_size.value} bytes")
        print(f"  Template size: {template_size.value} bytes")

        actual_image_size = min(image_size.value, IMAGE_SIZE)
        image_bytes = bytes(image_buffer[:actual_image_size])

        actual_template_size = min(template_size.value, TEMPLATE_SIZE)
        template_bytes = bytes(template_buffer[:actual_template_size])

        quality = 85
        if image_bytes:
            import statistics
            try:
                pixel_values = list(image_bytes[:min(1000, len(image_bytes))])
                if pixel_values:
                    variance = statistics.variance(pixel_values) if len(pixel_values) > 1 else 0
                    quality = min(100, int(variance / 2 + 70))
            except:
                pass

        image_base64 = base64.b64encode(image_bytes).decode('utf-8')
        template_base64 = base64.b64encode(template_bytes).decode('utf-8')

        sdk_wrapper.close_device(handle)
        sdk_wrapper.terminate()

        print(f"Quality score: {quality}/100")
        print("Capture completed successfully!")
        print("="*50)

        return jsonify({
            "success": True,
            "fingerprint_data_base64": image_base64,
            "fingerprint_template": template_base64,
            "quality_score": quality,
            "image_size": actual_image_size,
            "template_size": actual_template_size,
            "message": "Fingerprint captured successfully"
        })

    except Exception as e:
        print(f"Exception during capture: {str(e)}")
        import traceback
        traceback.print_exc()
        return jsonify({"success": False, "message": str(e)}), 500

@app.route('/devices', methods=['GET'])
def get_devices():
    """Get detailed device information"""
    try:
        if not sdk_wrapper:
            return jsonify({"success": False, "message": "DLL not loaded"}), 500

        result = sdk_wrapper.init()
        if result != 0:
            return jsonify({"success": False, "message": f"SDK Init failed: {result}"}), 500

        device_count = sdk_wrapper.get_device_count()

        devices = []
        for i in range(device_count):
            handle = sdk_wrapper.open_device(i)
            status = "Connected" if handle else "Not connected"

            device_info = {
                "index": i,
                "status": status,
                "handle": f"0x{handle:X}" if handle else None
            }

            if handle:
                sdk_wrapper.close_device(handle)

            devices.append(device_info)

        sdk_wrapper.terminate()

        return jsonify({
            "success": True,
            "device_count": device_count,
            "devices": devices,
            "dll_loaded": True,
            "dll_path": ZKFP_DLL_PATH
        })

    except Exception as e:
        return jsonify({"success": False, "message": str(e)}), 500

@app.route('/health', methods=['GET'])
def health_check():
    """Simple health check endpoint"""
    is_loaded = True if (sdk_wrapper is not None and sdk_wrapper.zkfp is not None) else False
    return jsonify({
        "success": True,
        "status": "online",
        "service": "ZKTeco Fingerprint Server",
        "port": 3000,
        "dll_loaded": is_loaded,
        "dll_path": ZKFP_DLL_PATH
    })

if __name__ == '__main__':
    print("\n" + "="*60)
    print("Starting Flask server...")
    print("Endpoints available:")
    print("  http://localhost:3000/test      - Test scanner connection")
    print("  http://localhost:3000/capture   - Capture fingerprint")
    print("  http://localhost:3000/devices   - List devices")
    print("  http://localhost:3000/health    - Health check")
    print("="*60 + "\n")

    app.run(host='0.0.0.0', port=3000, debug=False)