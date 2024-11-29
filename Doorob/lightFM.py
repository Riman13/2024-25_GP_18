import pickle
import numpy as np
import pandas as pd
from scipy.sparse import coo_matrix, csr_matrix
from lightfm import LightFM
from flask import Flask, jsonify, request
from geopy.distance import geodesic
import logging

# Initialize Flask app
app = Flask(__name__)

# Setup logging for debugging
logging.basicConfig(level=logging.DEBUG)

# Load the saved model
with open('trained_model.pkl', 'rb') as f:
    model = pickle.load(f)

# Load or define your place and user data (place names, mappings)
place_data = pd.read_excel('DATADATA.xlsx')  # Assuming you have this file
ratings_data = pd.read_csv('modified_ratings.csv')  # Assuming this is your ratings data
# Log the place data and model
logging.debug(f"Loaded place data:\n{place_data.head()}")
logging.debug(f"Model loaded successfully.")

# Place names dictionary
place_names = dict(zip(place_data['ID'], place_data['Name']))

# Clean 'Ratings' column: Replace 'N\A' with NaN (Not a Number) and convert to float
place_data['Ratings'] = pd.to_numeric(place_data['Ratings'], errors='coerce')

# Fill NaN values (if any) with a default value like 0 or the average rating
place_data['Ratings'].fillna(0, inplace=True)

# Create dictionaries for category and rating
category_dict = dict(zip(place_data['ID'], place_data['Category']))
rating_dict = dict(zip(place_data['ID'], place_data['Ratings']))

# Create user and place mappings (assuming you've already done this)
user_id_map = {user_id: idx for idx, user_id in enumerate(ratings_data['user_id'].unique())}
place_id_map = {place_id: idx for idx, place_id in enumerate(place_data['ID'].unique())}

# Create interaction matrix
interaction_data = ratings_data[['user_id', 'place_id', 'rating']]
interaction_data['user_index'] = interaction_data['user_id'].map(user_id_map)
interaction_data['place_index'] = interaction_data['place_id'].map(place_id_map)
interaction_matrix = coo_matrix(
    (interaction_data['rating'], (interaction_data['user_index'], interaction_data['place_index'])),
    shape=(len(user_id_map), len(place_id_map))
)

# Dictionary to store user locations
user_locations = {}

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
    return jsonify({"message": "Location saved successfully"}), 200

def recommend_for_user(user_id, user_lat=None, user_lng=None, num_recommendations=10):
    """
    Recommend places to the user based on their ID and location.
    """
    # Map user_id to index
    user_index = user_id_map.get(user_id)
    if user_index is None:
        return []  # Return empty list if the user ID is not valid

    # Ensure the item_features_matrix is loaded or created (using dummy data for now)
    item_features_matrix = csr_matrix(np.random.rand(len(place_id_map), 7))  # Dummy features, replace with actual

    # Get scores for all items for the given user
    scores = model.predict(user_index, np.arange(len(place_id_map)), item_features=item_features_matrix)

    # Get top N items based on scores
    top_items = np.argsort(scores)[::-1][:num_recommendations]

    recommendations = []
    for item_index in top_items:
        # Get place_id from place_data using item_index
        place_id = place_data.iloc[item_index]['ID']  # Assuming 'ID' is the place_id in place_data
        place_name = place_names.get(place_id, "Unknown Place")
        
        # Use the dictionaries for category and rating
        category = category_dict.get(place_id, "Unknown Category")
        rating = rating_dict.get(place_id, 0)  # Default to 0 if rating is missing or not found
        
        # Add latitude and longitude
        place_lat = place_data.iloc[item_index]['lat']
        place_lng = place_data.iloc[item_index]['lng']
        
        # Calculate distance if user location is provided
        distance = None
        if user_lat is not None and user_lng is not None:
            distance = geodesic((user_lat, user_lng), (place_lat, place_lng)).km
        
        recommendation = {
            'place_id': int(place_id),
            'place_name': place_name,
            'granular_category': category,
            'average_rating': rating,
            'distance_km': distance
        }
        recommendations.append(recommendation)

    # Sort recommendations by distance if user location is provided
    if user_lat is not None and user_lng is not None:
        recommendations.sort(key=lambda x: x['distance_km'] if x['distance_km'] is not None else float('inf'))


    return recommendations
@app.route('/api/recommendations/<int:user_id>', methods=['GET'])
def get_recommendations(user_id):
    """
    Endpoint to get recommendations for a user.
    """
    # Get user's location if saved
    user_location = user_locations.get(user_id)
    user_lat, user_lng = user_location if user_location else (None, None)
    
    # Fetch recommendations from your recommendation function
    recommendations = recommend_for_user(user_id, user_lat, user_lng)
    
    logging.debug(f"Recommendations for user {user_id}: {recommendations}")
    
    return jsonify(recommendations)

if __name__ == '__main__':
    # Run the Flask app with debugging enabled
    app.run(debug=True)
