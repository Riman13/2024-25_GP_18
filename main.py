import pickle
import numpy as np
import pandas as pd
from flask import Flask, jsonify, request
from flask_cors import CORS
from flask import Blueprint, jsonify, request
from geopy.distance import geodesic
import logging
from lightfm import LightFM
from scipy.sparse import csr_matrix
import joblib
import pymysql


# Initialize Flask app
app = Flask(__name__)
CORS(app)
# Setup logging for debugging


#logging.basicConfig(level=logging.DEBUG)
# Initialize the Blueprint
#lightfm_bp = Blueprint('lightfm', __name__)
# Load the saved model (trained model)
   #with open('trained_mode2.pkl', 'rb') as f:
 #   model = pickle.load(f)

# Load the original item features used in training
#with open('item_features.pkl', 'rb') as f:
  #  item_features_matrix = pickle.load(f)

model = joblib.load('Doorob/trained_model.joblib')
item_features_matrix = joblib.load('Doorob/item_features1.joblib')

# Load or define your place and user data (place names, mappings)
place_data = pd.read_excel('Doorob/DATADATA.xlsx')  # Assuming you have this file
ratings_data = pd.read_csv('Doorob/modified_ratings.csv')  # Assuming this is your ratings data

# Log the place data and model
logging.debug(f"Loaded place data:\n{place_data.head()}")
logging.debug(f"Model loaded successfully.")
def get_db_connection():
    return pymysql.connect(
        host="77.37.35.85",
        user="u783774210_mig",
        password="g]I/EHm=v6",
        database="u783774210_mig",
        cursorclass=pymysql.cursors.DictCursor
    )
# Place names dictionary
place_names = dict(zip(place_data['ID'], place_data['Name']))  # Use 'ID' instead of 'id'

# Create user and place mappings (using 'ID' instead of 'id')
user_id_map = {user_id: idx for idx, user_id in enumerate(ratings_data['user_id'].unique())}
place_id_map = {place_id: idx for idx, place_id in enumerate(place_data['ID'].unique())}  # Use 'ID'

# Dictionary to store user locations
user_locations = {}

# Dictionary to store best place notifications
best_place_notifications = {}


@app.route('/api/save_location', methods=['POST'])
def save_user_location():
    """
    Save user location in memory.
    """
    data = request.get_json()
    user_id = data.get('user_id')
    user_lat = data.get('lat')
    user_lng = data.get('lng')
    
    if not user_id or not user_lat or not user_lng:
        return jsonify({"error": "Invalid data"}), 400
    
    user_locations[user_id] = (float(user_lat), float(user_lng))
    logging.debug(f"Location saved for user ID {user_id}: {user_locations[user_id]}")
    logging.debug(f"User locations dict: {user_locations}")

    return jsonify({"message": "Location saved successfully"}), 200

def get_user_ratings_from_db(user_id):
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            query = """
                SELECT PlaceID, Rating
                FROM ratings
                WHERE UserID= %s
                LIMIT 10
            """
            cursor.execute(query, (user_id,))
            return cursor.fetchall()
    finally:
        conn.close()

def recommend_for_user(user_id, user_lat=None, user_lng=None, num_recommendations=10):
    """
    Recommend places to the user based on their ID and location using the trained LightFM model.
    """
    # Map user_id to index
    user_index = user_id_map.get(user_id)
    if user_index is None:
        return []  # Return empty list if the user ID is not valid

    # Get scores for all items for the given user
    #scores = model.predict(user_index, np.arange(len(place_id_map)))
    
    scores = model.predict(user_index, np.arange(len(place_id_map)), item_features=item_features_matrix)
    



    # Get top N items based on scores
    top_items = np.argsort(scores)[::-1][:num_recommendations]

    recommendations = []
    for item_index in top_items:
        # Get place_id from place_data using item_index
        # place_id = place_data.iloc[item_index]['ID']  # Use 'ID' instead of 'id'
        # place_name = place_names.get(place_id, "Unknown Place")
        
        # Add latitude and longitude
        # place_lat = place_data.iloc[item_index]['lat']
        # place_lng = place_data.iloc[item_index]['lng']

        # new code 
        # Get full row from place_data
        place_row = place_data.iloc[item_index]
    
        # Use the internal incremental ID from 'num' column
        place_id = place_row['num']  # ✅ Use 'num' instead of 'ID'
        place_name = place_row['Name']
    
        # Add latitude and longitude
        place_lat = place_row['lat']
        place_lng = place_row['lng']
        rat = place_row['average_rating']
        cat = place_row['granular_category']
        # Calculate distance if user location is provided
        distance = None
        if user_lat is not None and user_lng is not None:
            distance = geodesic((user_lat, user_lng), (place_lat, place_lng)).km
        
        recommendation = {
            
            'place_id': int(place_id),
            'place_name': place_name,
            'distance_km': distance,
            'average_rating': rat,
            'granular_category': cat,
            'lat': place_row['lat'],
            'lng': place_row['lng']
        }
        recommendations.append(recommendation)

    # Sort recommendations by distance if user location is provided
    if user_lat is not None and user_lng is not None:
        recommendations.sort(key=lambda x: x['distance_km'] if x['distance_km'] is not None else float('inf'))

    return recommendations

@app.route('/api/recommendations_hybrid/<int:user_id>', methods=['GET'])
def get_recommendations(user_id):
    """
    Endpoint to get recommendations for a user.
    """
    # Get user's location if saved
    user_location = user_locations.get(user_id)

    if user_location and isinstance(user_location, tuple) and len(user_location) == 2:
        user_lat, user_lng = user_location
    else:
        user_lat, user_lng = None, None
        logging.warning(f"User location for {user_id} not found or invalid: {user_location}")

    # Fetch recommendations
    recommendations = recommend_for_user(user_id, user_lat, user_lng)

    logging.debug(f"Recommendations for user {user_id}: {recommendations}")

    # Save the best recommendation
    if recommendations:
        best_place_notifications[user_id] = recommendations[0]
        logging.debug(f"Saved best place notification for user {user_id}: {recommendations[0]}")

    # If user ID > 9000, fetch and return recent ratings too
    if user_id > 9000:
        user_ratings = get_user_ratings_from_db(user_id)
        if len(user_ratings) >= 10:
            return jsonify({
                "recommendations": recommendations,
                "user_recent_ratings": user_ratings
            })

    # Default return if not eligible for ratings
    return jsonify(recommendations)




#if __name__ == '__main__':
    # Run the Flask app with debugging enabled
   # app.run(debug=True, port=5003)  