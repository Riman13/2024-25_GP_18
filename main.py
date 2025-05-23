import pickle
import numpy as np
import pandas as pd
from flask import Flask, jsonify, request
from flask_cors import CORS
from geopy.distance import geodesic
import logging
from lightfm import LightFM
from scipy.sparse import csr_matrix
import joblib
import mysql.connector
from lightfm.data import Dataset

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
def get_mysql_connection():
    return mysql.connector.connect(
        host="77.37.35.85",
        user="u783774210_mig",
        password="g]I/EHm=v6",
        database="u783774210_mig"
    )
# Log the place data and model
logging.debug(f"Loaded place data:\n{place_data.head()}")
logging.debug(f"Model loaded successfully.")

def load_new_ratings_from_mysql():
    conn = get_mysql_connection()
    query = "SELECT UserID, placeID, Rating FROM ratings"
    new_ratings = pd.read_sql(query, conn)
    conn.close()
    return new_ratings


new_ratings = load_new_ratings_from_mysql()
all_ratings = pd.concat([ratings_data, new_ratings], ignore_index=True)
user_id_map = {user_id: idx for idx, user_id in enumerate(all_ratings['user_id'].unique())}

# Place names dictionary
place_names = dict(zip(place_data['ID'], place_data['Name']))  # Use 'ID' instead of 'id'

# Create user and place mappings (using 'ID' instead of 'id')
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
        cat = place_row['Category']
        rat = place_row['Ratings']
        
        # Calculate distance if user location is provided
        distance = None
        if user_lat is not None and user_lng is not None:
            distance = geodesic((user_lat, user_lng), (place_lat, place_lng)).km
        
        recommendation = {
            
            'place_id': int(place_id),
            'place_name': place_name,
            'distance_km': distance,
            'average_rating': rat,
            'granular_category':cat
        }
        recommendations.append(recommendation)

    # Sort recommendations by distance if user location is provided
    if user_lat is not None and user_lng is not None:
        recommendations.sort(key=lambda x: x['distance_km'] if x['distance_km'] is not None else float('inf'))

    return recommendations

def retrain_model_with_user(all_ratings_df, item_features_matrix):
    dataset = Dataset()
    dataset.fit(all_ratings_df['user_id'], all_ratings_df['placeID'])
    (interactions, _) = dataset.build_interactions(
        ((row['user_id'], row['placeID'], row['Rating']) for _, row in all_ratings_df.iterrows())
    )

    model = LightFM(no_components=100, loss='warp-kos')
    model.fit(interactions, item_features=item_features_matrix, epochs=10, num_threads=2)
    return model

@app.route('/api/recommendations_hybrid/<int:user_id>', methods=['GET'])
def get_recommendations(user_id):
    """
    Endpoint to get recommendations for a user.
    """
    global model
    # Get user's location if saved
    user_location = user_locations.get(user_id)

    if user_location and isinstance(user_location, tuple) and len(user_location) == 2:
        user_lat, user_lng = user_location
    else:
        user_lat, user_lng = None, None
        logging.warning(f"User location for {user_id} not found or invalid: {user_location}")

    user_ratings_count = all_ratings[all_ratings['user_id'] == user_id].shape[0]

    if user_id > 7500 and user_ratings_count >= 9:
        logging.debug(f"Retraining model for user {user_id} with {user_ratings_count} ratings...")
        model = retrain_model_with_user(all_ratings, item_features_matrix)
    else:
        logging.warning(f"No retraining. User {user_id} has only {user_ratings_count} ratings.")


    # Fetch recommendations from your recommendation function
    recommendations = recommend_for_user(user_id, user_lat, user_lng)
    
    logging.debug(f"Recommendations for user {user_id}: {recommendations}")
    
    # Save the best place (first recommendation) for notification
    if recommendations:
        best_place_notifications[user_id] = recommendations[0]
        logging.debug(f"Saved best place notification for user {user_id}: {recommendations[0]}")

    
    return jsonify(recommendations)


#
#if __name__ == '__main__':
    # Run the Flask app with debugging enabled
   # app.run(debug=True, port=5003)