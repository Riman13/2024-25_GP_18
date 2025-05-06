import threading  # Added for non-blocking emotion analysis
import time
import logging
import base64
from io import BytesIO
from PIL import Image
import numpy as np
import mysql.connector
from deepface import DeepFace
from flask import Flask, jsonify, request, Blueprint
from flask_cors import CORS

# إعداد الـ logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s %(levelname)-8s %(message)s')

# إنشاء البلوبرينت
emotion_bp = Blueprint('emotion', __name__, url_prefix='/emotion')

# Database configuration
db_config = {
    'host': '77.37.35.85',
    'user': 'u783774210_mig',
    'password': 'g]I/EHm=v6',
    'database': 'u783774210_mig',
}

# Dictionary to track active sessions
active_sessions = {}

# Function to map emotions to ratings
def map_emotion_to_rating(emotion_dict):
    """
    Maps detected emotions to a rating between 1 and 5 based on
    the difference between positive and negative emotions.
    """
    # Print each emotion with its value
    logging.info("[Emotion Breakdown]")
    for emotion, value in emotion_dict.items():
        logging.info(f"  {emotion.capitalize():<10}: {value:.2f}")

    # Calculate the total percentage of positive and negative emotions
    positive_score = emotion_dict.get('happy', 0) + emotion_dict.get('surprise', 0)
    negative_score = emotion_dict.get('angry', 0) + emotion_dict.get('sad', 0) + \
                     emotion_dict.get('fear', 0) + emotion_dict.get('disgust', 0)
    neutral_score = emotion_dict.get('neutral', 0)

    # Compute the response value as the difference between positive and negative scores
    logging.info(f"[Emotion Analysis]")
    logging.info(f"  Positive Score: {positive_score}")
    logging.info(f"  Negative Score: {negative_score}")
    logging.info(f"  Neutral Score:  {neutral_score}")
    logging.info(f"  Response Value: {response_value := positive_score - negative_score}")

    # Assign a rating based on the response value range
    if response_value >= 60:
        return 5  # Very Satisfied
    elif 20 <= response_value < 60:
        return 4  # Satisfied
    elif -20 <= response_value < 20:
        return 3  # Neutral
    elif -60 <= response_value < -20:
        return 2  # Unsatisfied
    else:
        return 1  # Very Unsatisfied

    return np.nan  # Default case in case of an error

# Function to save or update rating in the database
def save_rating_to_db(user_id, place_id, rating):
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()

        # Check if a rating already exists for the same user and place
        check_query = """
            SELECT * FROM ratings WHERE userID = %s AND placeID = %s
        """
        cursor.execute(check_query, (user_id, place_id))
        existing_rating = cursor.fetchone()

        if existing_rating:
            # Update the existing rating
            update_query = """
                UPDATE ratings
                SET Rating = %s
                WHERE userID = %s AND placeID = %s
            """
            cursor.execute(update_query, (rating, user_id, place_id))
            logging.info(f"Rating updated in the database for user {user_id} at place {place_id}.")
        else:
            # Insert a new rating
            insert_query = """
                INSERT INTO ratings (userID, placeID, Rating)
                VALUES (%s, %s, %s)
            """
            cursor.execute(insert_query, (user_id, place_id, rating))
            logging.info(f"New rating inserted into the database for user {user_id} at place {place_id}.")

        conn.commit()
        cursor.close()
        conn.close()

    except mysql.connector.Error as err:
        logging.error(f"Database Error: {err}")

# API to start emotion analysis
@emotion_bp.route('/start_emotion_analysis', methods=['POST'])
def start_emotion_analysis():
    data = request.json
    session_id = str(data.get('sessionId'))
    user_id = data.get('userId')
    place_id = data.get('placeId')
    image_base64 = data.get('image')  # الحصول على الصورة Base64

    if not session_id or not user_id or not place_id or not image_base64:
        return jsonify({"success": False, "error": "Missing sessionId, userId, placeId, or image"}), 400

    # تحويل الـ Base64 إلى صورة
    try:
        # إزالة الـ Prefix الخاص بـ Data URL إذا كان موجود
        image_data = base64.b64decode(image_base64.split(',')[1])
        image = Image.open(BytesIO(image_data))
        frame = np.array(image)  # تحويل الصورة إلى numpy array
    except Exception as e:
        logging.error(f"Error decoding image: {e}")
        return jsonify({"success": False, "error": "Error decoding image"}), 400

    # بدء الجلسة
    active_sessions[session_id] = {
        "user_id": user_id,
        "place_id": place_id,
        "stop_analysis": False,
    }

    # تشغيل التحليل في ثريد منفصل
    thread = threading.Thread(target=analyze_emotion_in_session, args=(session_id, frame))  # مرر الصورة
    thread.start()

    return jsonify({"success": True, "sessionId": session_id})

# API to stop emotion analysis
@emotion_bp.route('/stop_emotion_analysis', methods=['POST'])
def stop_emotion_analysis():
    data = request.json
    session_id = str(data.get('sessionId'))

    if not session_id or session_id not in active_sessions:
        return jsonify({"success": False, "error": "Invalid sessionId"}), 400

    # Mark the session for stopping
    active_sessions[session_id]['stop_analysis'] = True
    return jsonify({"success": True })

# Function to handle emotion analysis
def analyze_emotion_in_session(session_id, frame):
    if session_id not in active_sessions:
        return

    session_data = active_sessions[session_id]
    user_id = session_data["user_id"]
    place_id = session_data["place_id"]

    emotion_scores_list = []
    start_time = time.time()

    try:
        # تحليل الإيموشن من الصورة
        try:
            analysis = DeepFace.analyze(frame, actions=['emotion'], enforce_detection=False)
            if isinstance(analysis, list):
                analysis = analysis[0]
            emotion_scores = analysis['emotion']
            emotion_scores_list.append(emotion_scores)
        except Exception as e:
            logging.error(f"Error analyzing image: {e}")

    finally:
        # تنفيذ عملية حفظ التقييم بعد التحليل
        if len(emotion_scores_list) > 0 and time.time() - start_time >= 10:
            avg_emotion_scores = {key: sum(d[key] for d in emotion_scores_list) / len(emotion_scores_list) for key in emotion_scores_list[0]}
            rating = map_emotion_to_rating(avg_emotion_scores)
            save_rating_to_db(user_id, place_id, rating)
            logging.info(f"Session {session_id}: Final Rating {rating} saved.")
            active_sessions[session_id]["rating"] = rating
        else:
            logging.warning(f"Session {session_id}: Insufficient data or time for rating.")
            active_sessions[session_id]["error"] = "Insufficient data or time for rating."

# route to send the rating to the frontend
@emotion_bp.route('/get_rating', methods=['GET'])
def get_rating():
    session_id = request.args.get('sessionId')

    # Debugging print to see if session exists
    logging.info(f"Checking session: {session_id}")

    if session_id in active_sessions:
        if "rating" in active_sessions[session_id]:
            logging.info(f"Returning rating: {active_sessions[session_id]['rating']}")
            rating = active_sessions[session_id]["rating"]
            # Clean up session after rating is retrieved
            del active_sessions[session_id]
            return jsonify({"success": True, "rating": rating})
        
        elif "error" in active_sessions[session_id]:
            logging.warning(f"Returning error: {active_sessions[session_id]['error']}")
            error = active_sessions[session_id]["error"]
            # Clean up session after error is retrieved
            del active_sessions[session_id]
            return jsonify({"success": False, "error": error})
    
    logging.info("Session not found or still processing.")
    return jsonify({"success": False, "error": "Session not found or still processing."}), 404
