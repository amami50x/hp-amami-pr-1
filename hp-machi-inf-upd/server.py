from flask import Flask, send_from_directory
from flask_cors import CORS

app = Flask(__name__)
CORS(app)  # CORSを許可

@app.route('/data.json')
def send_json():
    return send_from_directory('.', 'data.json')

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=8000)