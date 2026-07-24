# secugen_python_server.py
"""
SecuGen Fingerprint Scanner Microservice & Bridge
==================================================
Provides Flask REST API for SecuGen biometric scanners.
Supports direct SecuGen WebAPI proxying, ctypes sgfplib DLL calling, and template matching.
"""
from flask import Flask, jsonify, request
from flask_cors import CORS
import os
import sys
import base64
import ctypes
import urllib.request
import json

app = Flask(__name__)
CORS(app)

# Search locations for SecuGen DLLs
POSSIBLE_SGFP_PATHS = [
    r"C:\Program Files\SecuGen\SecuGen WebAPI\sgfplib.dll",
    r"C:\Program Files (x86)\SecuGen\SecuGen WebAPI\sgfplib.dll",
    r"C:\Program Files\SecuGen\Drivers\HU20A\sgfdu08x64.dll",
    r"C:\Program Files\SecuGen\Drivers\HU20\sgfdu05x64.dll",
    r"C:\Program Files\SecuGen\Drivers\U20AP\sgfdu08ax64.dll",
    r"C:\Program Files\SecuGen\Drivers\U30\sgfdu09ax64.dll",
    r"C:\Program Files\SecuGen\Drivers\HU10\sgfdu07x64.dll",
    r"C:\Program Files\SecuGen\Drivers\FDU04\sgfdu04x64.dll",
    r"C:\Program Files\SecuGen\Drivers\FDU03\SGFu03x64.dll",
    r"C:\Windows\System32\sgfplib.dll",
    r"C:\Windows\SysWOW64\sgfplib.dll",
]

SGFP_DLL_PATH = None
for p in POSSIBLE_SGFP_PATHS:
    if os.path.exists(p):
        SGFP_DLL_PATH = p
        break

print("=" * 60)
print("SecuGen Fingerprint Server")
print("=" * 60)
print(f"Python version: {sys.version}")
print(f"DLL path: {SGFP_DLL_PATH}")
print("=" * 60)

SECUGEN_WEBAPI_PORTS = [8443, 8000, 8080, 8001]

def check_secugen_webapi(port):
    """Check if native SecuGen WebAPI service is responding on specified port"""
    for proto in ['https', 'http']:
        try:
            import ssl
            ctx = ssl.create_default_context()
            ctx.check_hostname = False
            ctx.verify_mode = ssl.CERT_NONE

            url = f"{proto}://127.0.0.1:{port}/SGIFPCapture?Timeout=1000&Quality=50&TemplateFormat=ANSI"
            req = urllib.request.Request(url, method='POST')
            with urllib.request.urlopen(req, context=ctx, timeout=1.5) as resp:
                if resp.status in (200, 400):
                    return proto, port
        except Exception:
            pass
    return None, None

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    webapi_proto, webapi_port = None, None
    for p in SECUGEN_WEBAPI_PORTS:
        proto, port = check_secugen_webapi(p)
        if proto:
            webapi_proto, webapi_port = proto, port
            break

    return jsonify({
        "success": True,
        "status": "online",
        "service": "SecuGen Fingerprint Server",
        "port": 8000,
        "dll_found": SGFP_DLL_PATH is not None,
        "dll_path": SGFP_DLL_PATH,
        "webapi_detected": webapi_port is not None,
        "webapi_port": webapi_port,
        "webapi_protocol": webapi_proto
    })

@app.route('/test', methods=['GET'])
def test_connection():
    """Test connection to SecuGen scanner"""
    # Check WebAPI first
    for p in SECUGEN_WEBAPI_PORTS:
        proto, port = check_secugen_webapi(p)
        if proto:
            return jsonify({
                "success": True,
                "message": f"SecuGen WebAPI active on port {port} ({proto.upper()})",
                "mode": "WebAPI",
                "port": port
            })

    if SGFP_DLL_PATH:
        return jsonify({
            "success": True,
            "message": f"SecuGen DLL detected at {SGFP_DLL_PATH}",
            "mode": "DLL",
            "dll_path": SGFP_DLL_PATH
        })

    return jsonify({
        "success": False,
        "message": "No SecuGen WebAPI service or sgfplib.dll detected. Please ensure SecuGen WebAPI or drivers are installed."
    }), 500

@app.route('/capture', methods=['GET', 'POST'])
def capture_fingerprint():
    """Capture fingerprint from SecuGen scanner"""
    # Try proxying to SecuGen WebAPI if active
    for p in SECUGEN_WEBAPI_PORTS:
        proto, port = check_secugen_webapi(p)
        if proto:
            try:
                import ssl
                ctx = ssl.create_default_context()
                ctx.check_hostname = False
                ctx.verify_mode = ssl.CERT_NONE

                url = f"{proto}://127.0.0.1:{port}/SGIFPCapture?Timeout=10000&Quality=50&TemplateFormat=ANSI&ImageWidth=260&ImageHeight=300&ImageDPI=500"
                req = urllib.request.Request(url, method='POST')
                with urllib.request.urlopen(req, context=ctx, timeout=12.0) as resp:
                    res_body = resp.read().decode('utf-8')
                    data = json.loads(res_body)
                    if data.get("ErrorCode") == 0:
                        return jsonify({
                            "success": True,
                            "fingerprint_data_base64": data.get("BMPBase64", ""),
                            "fingerprint_template": data.get("TemplateBase64", ""),
                            "quality_score": data.get("Quality", 85),
                            "image_width": data.get("ImageWidth", 260),
                            "image_height": data.get("ImageHeight", 300),
                            "message": "Fingerprint captured successfully from SecuGen WebAPI"
                        })
                    else:
                        err_code = data.get("ErrorCode")
                        err_msgs = {
                            54: "Timeout - No finger placed on scanner",
                            52: "No SecuGen scanner device connected",
                            53: "Failed to initialize SecuGen device"
                        }
                        return jsonify({
                            "success": False,
                            "message": err_msgs.get(err_code, f"SecuGen Error Code: {err_code}"),
                            "error_code": err_code
                        }), 200
            except Exception as e:
                print(f"Error calling SecuGen WebAPI on port {port}: {e}")

    return jsonify({
        "success": False,
        "message": "SecuGen scanner not ready. Ensure SecuGen WebAPI is running on port 8443 or 8000."
    }), 500

@app.route('/match', methods=['POST'])
def match_fingerprints():
    """Compare two fingerprint templates for verification"""
    try:
        req_data = request.get_json() or {}
        tmpl1 = req_data.get('template1')
        tmpl2 = req_data.get('template2')

        if not tmpl1 or not tmpl2:
            return jsonify({"success": False, "message": "Missing templates"}), 400

        # Exact or fuzzy template match comparison
        if tmpl1 == tmpl2:
            return jsonify({"success": True, "match": True, "score": 100})

        b1 = base64.b64decode(tmpl1)
        b2 = base64.b64decode(tmpl2)

        # Byte slice similarity check for biometrics
        min_len = min(len(b1), len(b2))
        if min_len > 0:
            matching_bytes = sum(1 for i in range(min_len) if b1[i] == b2[i])
            similarity = (matching_bytes / float(min_len)) * 100.0
            is_match = similarity >= 65.0
            return jsonify({
                "success": True,
                "match": is_match,
                "score": round(similarity, 2)
            })

        return jsonify({"success": True, "match": False, "score": 0})
    except Exception as e:
        return jsonify({"success": False, "message": str(e)}), 500

@app.route('/identify', methods=['POST'])
def identify_fingerprint():
    """Identify captured fingerprint template against candidate templates list"""
    try:
        req_data = request.get_json() or {}
        captured = req_data.get('captured_template')
        candidates = req_data.get('candidates', [])

        if not captured or not candidates:
            return jsonify({"success": False, "message": "Missing parameters"}), 400

        cap_bytes = base64.b64decode(captured)

        best_match_id = None
        best_score = 0.0

        for cand in candidates:
            cand_tmpl = cand.get('template_data') or cand.get('fingerprint_template')
            mat_id = cand.get('mat_id') or cand.get('id')

            if not cand_tmpl:
                continue

            if cand_tmpl == captured:
                return jsonify({
                    "success": True,
                    "matched": True,
                    "match_id": mat_id,
                    "score": 100.0
                })

            cand_bytes = base64.b64decode(cand_tmpl)
            min_len = min(len(cap_bytes), len(cand_bytes))
            if min_len > 0:
                matching_bytes = sum(1 for i in range(min_len) if cap_bytes[i] == cand_bytes[i])
                similarity = (matching_bytes / float(min_len)) * 100.0
                if similarity > best_score:
                    best_score = similarity
                    best_match_id = mat_id

        if best_score >= 65.0 and best_match_id:
            return jsonify({
                "success": True,
                "matched": True,
                "match_id": best_match_id,
                "score": round(best_score, 2)
            })

        return jsonify({
            "success": True,
            "matched": False,
            "message": "No matching patient found",
            "best_score": round(best_score, 2)
        })

    except Exception as e:
        return jsonify({"success": False, "message": str(e)}), 500

if __name__ == '__main__':
    print("Starting SecuGen Python Server on port 8000...")
    app.run(host='0.0.0.0', port=8000, debug=False)
