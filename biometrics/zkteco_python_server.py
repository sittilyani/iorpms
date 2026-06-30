# zkteco_python_server.py
from flask import Flask, jsonify, request
from flask_cors import CORS
import ctypes
import os
import base64
import sys

app = Flask(__name__)
CORS(app)  # Enable CORS for all routes

# CORRECT PATH FOR YOUR ZKTECO INSTALLATION
ZKFP_DLL_PATH = r"C:\Program Files (x86)\FPSensor\Biokey\ZKFPCap.dll"
USB_DLL_PATH = r"C:\Program Files (x86)\FPSensor\Biokey\USB.dll"

print("=" * 60)
print("ZKTeco Fingerprint Server")
print("=" * 60)
print(f"Python version: {sys.version}")
print(f"Working directory: {os.getcwd()}")
print(f"Looking for DLL at: {ZKFP_DLL_PATH}")

# Check if DLL exists
if not os.path.exists(ZKFP_DLL_PATH):
    print(f"ERROR: DLL not found at {ZKFP_DLL_PATH}")
    print("Please check your ZKTeco installation.")
    # List files in FPSensor directory
    fpsensor_dir = r"C:\Program Files (x86)\FPSensor\Biokey"
    if os.path.exists(fpsensor_dir):
        print(f"\nFiles in {fpsensor_dir}:")
        try:
            for file in os.listdir(fpsensor_dir):
                if file.lower().endswith('.dll'):
                    print(f"  - {file}")
        except:
            print("  Cannot list directory")
else:
    print(f"? DLL found: {ZKFP_DLL_PATH}")

print("=" * 60)

# Load the DLLs
try:
    # First try to load USB DLL
    if os.path.exists(USB_DLL_PATH):
        usb_dll = ctypes.WinDLL(USB_DLL_PATH)
        print(f"? Loaded USB DLL: {USB_DLL_PATH}")
    else:
        print(f"? USB DLL not found: {USB_DLL_PATH}")

    # Load ZKTeco fingerprint DLL
    zkfp = ctypes.WinDLL(ZKFP_DLL_PATH)
    print(f"? Successfully loaded ZKTeco DLL")

except Exception as e:
    print(f"? Error loading DLLs: {e}")
    print("\nTroubleshooting tips:")
    print("1. Make sure ZKTeco drivers are installed")
    print("2. Run this script as Administrator")
    print("3. Check if DLL is 32-bit or 64-bit (should be 32-bit)")
    zkfp = None

# Define function signatures if DLL loaded successfully
if zkfp:
    # Initialize SDK
    zkfp.ZKFPM_Init.restype = ctypes.c_int
    zkfp.ZKFPM_Init.argtypes = []

    # Terminate SDK
    zkfp.ZKFPM_Terminate.restype = ctypes.c_int
    zkfp.ZKFPM_Terminate.argtypes = []

    # Get device count
    zkfp.ZKFPM_GetDeviceCount.restype = ctypes.c_int
    zkfp.ZKFPM_GetDeviceCount.argtypes = []

    # Open device
    zkfp.ZKFPM_OpenDevice.restype = ctypes.c_void_p
    zkfp.ZKFPM_OpenDevice.argtypes = [ctypes.c_int]

    # Close device
    zkfp.ZKFPM_CloseDevice.restype = ctypes.c_int
    zkfp.ZKFPM_CloseDevice.argtypes = [ctypes.c_void_p]

    # Acquire fingerprint
    zkfp.ZKFPM_AcquireFingerprint.restype = ctypes.c_int
    zkfp.ZKFPM_AcquireFingerprint.argtypes = [
        ctypes.c_void_p,                    # handle
        ctypes.POINTER(ctypes.c_byte),      # image buffer
        ctypes.POINTER(ctypes.c_int),       # image size
        ctypes.POINTER(ctypes.c_byte),      # template buffer
        ctypes.POINTER(ctypes.c_int)        # template size
    ]

    # Get image from device
    zkfp.ZKFPM_GetImage.restype = ctypes.c_int
    zkfp.ZKFPM_GetImage.argtypes = [
        ctypes.c_void_p,
        ctypes.POINTER(ctypes.c_byte),
        ctypes.c_int
    ]

    # Extract template from image
    zkfp.ZKFPM_ExtractFromImage.restype = ctypes.c_int
    zkfp.ZKFPM_ExtractFromImage.argtypes = [
        ctypes.c_void_p,
        ctypes.POINTER(ctypes.c_byte),
        ctypes.c_int,
        ctypes.POINTER(ctypes.c_byte),
        ctypes.POINTER(ctypes.c_int)
    ]

@app.route('/test', methods=['GET'])
def test_connection():
    """Test if scanner SDK is working"""
    try:
        if not zkfp:
            return jsonify({"success": False, "message": "DLL not loaded"}), 500

        # Initialize SDK
        result = zkfp.ZKFPM_Init()
        if result != 0:
            return jsonify({
                "success": False,
                "message": f"SDK Init failed with code: {result}",
                "error_code": result
            }), 500

        # Get device count
        device_count = zkfp.ZKFPM_GetDeviceCount()
        print(f"Device count: {device_count}")

        # Try to open first device
        device_info = []
        for i in range(device_count):
            handle = zkfp.ZKFPM_OpenDevice(i)
            if handle:
                device_info.append({
                    "index": i,
                    "handle": f"0x{handle:X}",
                    "status": "Connected"
                })
                zkfp.ZKFPM_CloseDevice(handle)
            else:
                device_info.append({
                    "index": i,
                    "handle": None,
                    "status": "Failed to open"
                })

        # Terminate SDK
        zkfp.ZKFPM_Terminate()

        return jsonify({
            "success": True,
            "message": f"ZKTeco SDK initialized successfully",
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
        if not zkfp:
            return jsonify({"success": False, "message": "DLL not loaded"}), 500

        print("\n" + "="*50)
        print("Starting fingerprint capture...")

        # Initialize SDK
        result = zkfp.ZKFPM_Init()
        if result != 0:
            print(f"SDK Init failed with code: {result}")
            return jsonify({
                "success": False,
                "message": f"SDK Init failed with code: {result}"
            }), 500

        print("SDK initialized successfully")

        # Get device count
        device_count = zkfp.ZKFPM_GetDeviceCount()
        print(f"Found {device_count} device(s)")

        if device_count <= 0:
            zkfp.ZKFPM_Terminate()
            print("No devices found")
            return jsonify({
                "success": False,
                "message": "No fingerprint device found. Please connect scanner."
            }), 500

        # Open first device
        handle = zkfp.ZKFPM_OpenDevice(0)
        if not handle:
            zkfp.ZKFPM_Terminate()
            print("Failed to open device")
            return jsonify({
                "success": False,
                "message": "Failed to open device"
            }), 500

        print(f"Device opened successfully. Handle: 0x{handle:X}")

        # Prepare buffers
        IMAGE_WIDTH = 256
        IMAGE_HEIGHT = 360
        IMAGE_SIZE = IMAGE_WIDTH * IMAGE_HEIGHT
        TEMPLATE_SIZE = 2048

        image_buffer = (ctypes.c_byte * IMAGE_SIZE)()
        image_size = ctypes.c_int(IMAGE_SIZE)

        template_buffer = (ctypes.c_byte * TEMPLATE_SIZE)()
        template_size = ctypes.c_int(TEMPLATE_SIZE)

        print("Waiting for fingerprint... Place finger on scanner")

        # Capture fingerprint
        capture_result = zkfp.ZKFPM_AcquireFingerprint(
            handle,
            image_buffer,
            ctypes.byref(image_size),
            template_buffer,
            ctypes.byref(template_size)
        )

        print(f"Capture result code: {capture_result}")

        if capture_result != 0:
            zkfp.ZKFPM_CloseDevice(handle)
            zkfp.ZKFPM_Terminate()

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
                10: "Image quality too low"
            }

            error_msg = error_messages.get(capture_result, f"Error code: {capture_result}")
            print(f"Capture failed: {error_msg}")

            return jsonify({
                "success": False,
                "message": f"Capture failed: {error_msg}",
                "error_code": capture_result
            }), 500

        print(f"Capture successful!")
        print(f"  Image size: {image_size.value} bytes")
        print(f"  Template size: {template_size.value} bytes")

        # Convert buffers to bytes
        actual_image_size = min(image_size.value, IMAGE_SIZE)
        image_bytes = bytes(image_buffer[:actual_image_size])

        actual_template_size = min(template_size.value, TEMPLATE_SIZE)
        template_bytes = bytes(template_buffer[:actual_template_size])

        # Calculate quality score (simple estimation based on image variance)
        quality = 85  # Default good quality
        if image_bytes:
            # Simple quality estimation
            import statistics
            try:
                pixel_values = list(image_bytes[:min(1000, len(image_bytes))])
                if pixel_values:
                    variance = statistics.variance(pixel_values) if len(pixel_values) > 1 else 0
                    quality = min(100, int(variance / 2 + 70))
            except:
                pass  # Keep default quality

        # Convert to base64
        image_base64 = base64.b64encode(image_bytes).decode('utf-8')
        template_base64 = base64.b64encode(template_bytes).decode('utf-8')

        # Close device and terminate
        zkfp.ZKFPM_CloseDevice(handle)
        zkfp.ZKFPM_Terminate()

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
        if not zkfp:
            return jsonify({"success": False, "message": "DLL not loaded"}), 500

        result = zkfp.ZKFPM_Init()
        if result != 0:
            return jsonify({"success": False, "message": f"SDK Init failed: {result}"}), 500

        device_count = zkfp.ZKFPM_GetDeviceCount()

        devices = []
        for i in range(device_count):
            handle = zkfp.ZKFPM_OpenDevice(i)
            status = "Connected" if handle else "Not connected"

            device_info = {
                "index": i,
                "status": status,
                "handle": f"0x{handle:X}" if handle else None
            }

            if handle:
                zkfp.ZKFPM_CloseDevice(handle)

            devices.append(device_info)

        zkfp.ZKFPM_Terminate()

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
    return jsonify({
        "status": "online",
        "service": "ZKTeco Fingerprint Server",
        "port": 3000,
        "dll_loaded": zkfp is not None,
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

    # Run the server
    app.run(host='0.0.0.0', port=3000, debug=True)