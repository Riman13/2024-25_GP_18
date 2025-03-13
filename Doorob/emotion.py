# Backend Flask Script (Python)
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
    'password': 'root', 
    'database': 'doroob7',
}

# Dictionary to track active sessions
active_sessions = {}

# Function to map emotions to ratings
def map_emotion_to_rating(emotion_dict):
    """
    Maps detected emotions to a rating between 1 and 5 based on 
    the difference between positive and negative emotions.
    """

    # Calculate the total percentage of positive and negative emotions
    positive_score = emotion_dict.get('happy', 0) + emotion_dict.get('surprise', 0)
    negative_score = emotion_dict.get('angry', 0) + emotion_dict.get('sad', 0) + \
                     emotion_dict.get('fear', 0) + emotion_dict.get('disgust', 0)
    neutral_score = emotion_dict.get('neutral', 0)  # Neutral score for balance reference

    # Compute the response value as the difference between positive and negative scores
    response_value = positive_score - negative_score

    # Assign a rating based on the response value range
    if response_value >= 40:  
        return 5  # Very Satisfied
    elif 15 <= response_value < 40:
        return 4  # Satisfied
    elif -10 <= response_value < 15:
        return 3  # Neutral
    elif -35 <= response_value < -10:
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
            print(f"Rating updated in the database for user {user_id} at place {place_id}.")
        else:
            # Insert a new rating
            insert_query = """
                INSERT INTO ratings (userID, placeID, Rating)
                VALUES (%s, %s, %s)
            """
            cursor.execute(insert_query, (user_id, place_id, rating))
            print(f"New rating inserted into the database for user {user_id} at place {place_id}.")

        conn.commit()
        cursor.close()
        conn.close()

    except mysql.connector.Error as err:
        print(f"Database Error: {err}")

# API to start emotion analysis
@app.route('/start_emotion_analysis', methods=['POST'])
def start_emotion_analysis():
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
    data = request.json
    session_id = str(data.get('sessionId'))

    if not session_id or session_id not in active_sessions:
        return jsonify({"success": False, "error": "Invalid sessionId"}), 400

    # Mark the session for stopping
    active_sessions[session_id]['stop_analysis'] = True
    return jsonify({"success": True })

# Function to handle emotion analysis
# Function to process and analyze emotions
def analyze_emotion_in_session(session_id):
    if session_id not in active_sessions:
        return

    session_data = active_sessions[session_id]
    user_id = session_data["user_id"]
    place_id = session_data["place_id"]

    cap = cv2.VideoCapture(0)
    emotion_scores_list = []
    start_time = time.time()

    try:
        while time.time() - start_time < 10:  # Ensure minimum 5 seconds of analysis
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
        avg_emotion_scores = {key: sum(d[key] for d in emotion_scores_list) / len(emotion_scores_list) for key in emotion_scores_list[0]}
        rating = map_emotion_to_rating(avg_emotion_scores)
        save_rating_to_db(user_id, place_id, rating)
        print(f"Session {session_id}: Final Rating {rating} saved.")
        # Store the rating in the session so frontend can retrieve it
        active_sessions[session_id]["rating"] = rating
    else:
        print(f"Session {session_id}: Insufficient data or time for rating.")
        # Store an error message if detection failed
        active_sessions[session_id]["error"] = "Insufficient data or time for rating."

    # route to send the rating to the frontend 
@app.route('/get_rating', methods=['GET'])
def get_rating():
    session_id = request.args.get('sessionId')

    # Debugging print to see if session exists
    print(f"Checking session: {session_id}")

    if session_id in active_sessions:
        if "rating" in active_sessions[session_id]:
            print(f"Returning rating: {active_sessions[session_id]['rating']}")
            rating = active_sessions[session_id]["rating"]
            # Clean up session after rating is retrieved
            del active_sessions[session_id]
            return jsonify({"success": True, "rating": rating})
        
        elif "error" in active_sessions[session_id]:
            print(f"Returning error: {active_sessions[session_id]['error']}")
            error = active_sessions[session_id]["error"]
            # Clean up session after error is retrieved
            del active_sessions[session_id]
            return jsonify({"success": False, "error": error})
    
    print("Session not found or still processing.")
    return jsonify({"success": False, "error": "Session not found or still processing."}), 404
        
if __name__ == '__main__':
    app.run(port=5000)
