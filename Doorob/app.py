from flask import Flask
from flask_cors import CORS
from api.CFRS import recommendations_bp
from api.context import context_bp 
from api.emotion import emotion_bp
# Initialize Flask app
app = Flask(__name__)
CORS(app)
# تسجيل Blueprint مع المسار المسبق
app.register_blueprint(recommendations_bp)

app.register_blueprint(context_bp)
app.register_blueprint(emotion_bp)

if __name__ == "__main__":
    app.run(debug=True, port=5001)