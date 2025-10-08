from flask import Flask, jsonify
from zkfp import ZKFP2

app = Flask(__name__)
zk = ZKFP2()

@app.route('/api/initialize', methods=['GET'])
def initialize():
    try:
        zk.init()
        return jsonify({'status': 'success', 'message': 'Scanner initialized'})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/api/capture', methods=['GET'])
def capture():
    try:
        template, image = zk.capture()
        return jsonify({
            'status': 'success',
            'fingerprint': {
                'template': template,  # Base64-encoded template
                'image': image         # Base64-encoded BMP image
            }
        })
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=3000)