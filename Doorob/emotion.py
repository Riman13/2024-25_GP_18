import threading  # Added for non-blocking emotion analysis
import time

import cv2
import mysql.connector
import numpy as np
from deepface import DeepFace
from flask import Flask, jsonify, request
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

# Database configuration
db_config = {
    'host': 'localhost',
    'user': 'root',  
    'password': '', 
    'database': 'doroob',
}

# Dictionary to track active sessions
active_sessions = {}
# Function to map emotions to a rating (1-5) 
def map_emotion_to_rating(emotion_dict):
    """
    Maps emotions to a rating from 1 to 5, adjusting for the fact that 
    negative emotions outnumber positive emotions.
    """
    # Define emotion groups
    positive_emotions = ['happy', 'surprise']
    negative_emotions = ['sad', 'angry', 'fear', 'disgust']

    # Compute the total positive and negative emotion intensities
    positive_response = sum(emotion_dict.get(emotion, 0) for emotion in positive_emotions)
    negative_response = sum(emotion_dict.get(emotion, 0) for emotion in negative_emotions)

    neutral_response = emotion_dict.get('neutral', 0)
    total_response = positive_response + negative_response + neutral_response

    if total_response == 0:
        return 3  # Default to neutral if no emotion is detected

    # Normalize positive and negative responses
    normalized_positive = positive_response / len(positive_emotions)  # Divide by 2
    normalized_negative = negative_response / len(negative_emotions)  # Divide by 4

    # Compute emotion balance as a percentage
    response_value = ((normalized_positive - normalized_negative) / total_response) * 100

    # Convert the response value into a 1-5 rating scale
    if response_value >= 40:
        return 5  # Extremely satisfied
    elif 40 > response_value >= 10:
        return 4  # Well pleased
    elif 10 > response_value >= -10:
        return 3  # Neutral
    elif -10 > response_value >= -40:
        return 2   # Somewhat dissatisfied
    else:
        return 1  # Highly disappointed

# Function to save or update the rating in the database
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
            print("Rating updated in the database.")
        else:
            # Insert a new rating
            insert_query = """
                INSERT INTO ratings (userID, placeID, Rating)
                VALUES (%s, %s, %s)
            """
            cursor.execute(insert_query, (user_id, place_id, rating))
            print("New rating inserted into the database.")

        conn.commit()
        cursor.close()
        conn.close()

    except mysql.connector.Error as err:
        print(f"Database Error: {err}")

# API to start emotion analysis
@app.route('/start_emotion_analysis', methods=['POST'])
def start_emotion_analysis():
    """
    Starts emotion analysis for a user session.
    Runs the analysis in a separate thread to avoid blocking the API.
    """
    data = request.json
    session_id = str(data.get('sessionId'))
    user_id = data.get('userId')
    place_id = data.get('placeId')

    if not session_id or not user_id or not place_id:
        return jsonify({"success": False, "error": "Missing sessionId, userId, or placeId"}), 400

    # Start a new session
    active_sessions[session_id] = {
        "user_id": user_id,
        "place_id": place_id,
        "stop_analysis": False,
    }

    # Run emotion analysis in a separate thread
    thread = threading.Thread(target=analyze_emotion_in_session, args=(session_id,))
    thread.start()

    return jsonify({"success": True, "sessionId": session_id})

# API to stop emotion analysis
@app.route('/stop_emotion_analysis', methods=['POST'])
def stop_emotion_analysis():
    """
    Stops an ongoing emotion analysis session.
    """
    data = request.json
    session_id = str(data.get('sessionId'))

    if not session_id or session_id not in active_sessions:
        return jsonify({"success": False, "error": "Invalid sessionId"}), 400

    # Mark the session for stopping
    active_sessions[session_id]['stop_analysis'] = True
    return jsonify({"success": True })

# Function to handle emotion analysis
def analyze_emotion_in_session(session_id):
    """
    Captures frames from the webcam and analyzes emotions over a period of time.
    After collecting enough data, it calculates an average rating and stores it in the database.
    """

    if session_id not in active_sessions:
        return

    session_data = active_sessions[session_id]
    user_id = session_data["user_id"]
    place_id = session_data["place_id"]

    cap = cv2.VideoCapture(0)  # Open webcam
    emotion_scores_list = []
    start_time = time.time()

    try:
        while time.time() - start_time < 10:  # Ensure at least 10 seconds of analysis
            if session_data["stop_analysis"]:  # Stop if requested
                print(f"Session {session_id} stopped by user.")
                break

            ret, frame = cap.read()
            if not ret:
                print("Camera capture failed.")
                break

            try:
                # Analyze emotions from the frame
                analysis = DeepFace.analyze(frame, actions=['emotion'], enforce_detection=False)
                if isinstance(analysis, list):
                    analysis = analysis[0]
                emotion_scores = analysis['emotion']
                emotion_scores_list.append(emotion_scores)
            except Exception as e:
                print(f"Error analyzing frame: {e}")

    finally:
        cap.release()
        cv2.destroyAllWindows()

    # Only save the rating if analysis was conducted for the minimum time
    if len(emotion_scores_list) > 0 and time.time() - start_time >= 10:
        # Compute the average emotion scores over the session
        avg_emotion_scores = {key: sum(d[key] for d in emotion_scores_list) / len(emotion_scores_list) for key in emotion_scores_list[0]}

        # Convert emotions to a rating using the adjusted method
        rating = map_emotion_to_rating(avg_emotion_scores)

        # Save the computed rating to the database
        save_rating_to_db(user_id, place_id, rating)
        print(f"Session {session_id}: Final Rating {rating} saved.")
    else:
        print(f"Session {session_id}: Insufficient data or time for rating.")

    # Clean up session data
    if session_id in active_sessions:
        del active_sessions[session_id]

if __name__ == '__main__':
    app.run(port=5000)
