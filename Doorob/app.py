from flask import Flask
from flask_cors import CORS
from api.recommendations import recommendations_bp
from api.context import context_bp 
from api.emotion import emotion_bp
# Initialize Flask app
app = Flask(__name__)
CORS(app)

# Register Blueprints with different routes
app.register_blueprint(recommendations_bp, url_prefix='/recommendations')
app.register_blueprint(context_bp, url_prefix='/context')
app.register_blueprint(emotion_bp, url_prefix='/emotion')

