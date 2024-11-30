import pickle
import numpy as np
import pandas as pd
from scipy.sparse import coo_matrix, csr_matrix
from lightfm import LightFM
from flask import Flask, jsonify, request
from flask_cors import CORS
from geopy.distance import geodesic
import logging
from math import radians, sin, cos, sqrt, atan2


# Initialize Flask app
app = Flask(__name__)
CORS(app)

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
print(place_data.columns)

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
  # Fetch user location from user_locations dictionary

def recommend_for_user(user_id, user_locations, num_recommendations=10):
    """
    Recommend places to the user based on their ID and location.
    If the user has no preferences or interactions, provide fallback recommendations based on highest-rated places.
    """
    user_location = user_locations.get(user_id)
    logging.debug(f"User location: {user_location}")

    # Map user_id to index
    user_index = user_id_map.get(user_id)
    
    if user_index is None:
        # If the user has no previous preferences or interactions, recommend based on the highest-rated places
        top_places = place_data.sort_values(by='Ratings', ascending=False).head(num_recommendations)
        recommendations = []
        for index, row in top_places.iterrows():
            place_id = row['ID']
            place_name = place_names.get(place_id, "Unknown Place")
            category = category_dict.get(place_id, "Unknown Category")
            rating = row['Ratings']
            recommendations.append({
                'place_id': int(place_id),
                'place_name': place_name,
                'granular_category': category,
                'average_rating': rating,
                'distance_km': None,  # Distance is not relevant when no location is available
                'source': 'highest_rated'  # Indicating that this is from the highest-rated fallback
            })
        return recommendations

    # If the user has previous preferences, continue with hybrid recommendation logic
    item_features_matrix = csr_matrix(np.random.rand(len(place_id_map), 7))  # Dummy features
    scores = model.predict(user_index, np.arange(len(place_id_map)), item_features=item_features_matrix)
    top_items = np.argsort(scores)[::-1][:num_recommendations]

    recommendations = []
    for item_index in top_items:
        place_id = place_data.iloc[item_index]['ID']
        place_name = place_names.get(place_id, "Unknown Place")
        category = category_dict.get(place_id, "Unknown Category")
        rating = rating_dict.get(place_id, 0)
        
        place_location = (place_data.iloc[item_index]['lat'], place_data.iloc[item_index]['lng'])
        distance_km = None
        if user_location:
            distance = geodesic(user_location, place_location).kilometers
            distance_km = round(distance, 2) if distance > 0 else 0.0

        recommendations.append({
            'place_id': int(place_id),
            'place_name': place_name,
            'granular_category': category,
            'average_rating': rating,
            'distance_km': distance_km,
            'source': 'hybrid'  # Indicating that this recommendation came from the hybrid approach
        })

    # Sort recommendations by distance if user location is provided
    if user_location:
        recommendations.sort(key=lambda x: x['distance_km'] if x['distance_km'] is not None else float('inf'))

    return recommendations



@app.route('/api/save_location', methods=['POST'])
def save_location():
    try:
        data = request.get_json()

        if 'lat' not in data or 'lng' not in data or 'user_id' not in data:
            return jsonify({'error': 'Missing latitude, longitude, or user_id'}), 400

        # Save the user location in the dictionary
        user_id = data['user_id']
        user_location = (data['lat'], data['lng'])
        user_locations[user_id] = user_location

        print(f"Received data: {data}")
        print(f"User location saved: {user_locations}")

        return jsonify({'message': 'Location saved successfully!'}), 200

    except Exception as e:
        print(f"Error: {e}")
        return jsonify({'error': 'Failed to save location'}), 500

@app.route('/api/recommendations_hybrid/<int:user_id>', methods=['GET'])
def get_recommendations(user_id):
    """
    Endpoint to get recommendations for a user based on their ID and location.
    """
 
    
    # Generate recommendations
    try:
        recommendations = recommend_for_user(user_id, user_locations)
        logging.debug(f"user_locations: {user_locations}")

        logging.debug(f"Recommendations for user {user_id}: {recommendations}")
    except Exception as e:
        logging.error(f"Error generating recommendations for user {user_id}: {e}")
        return jsonify({'error': 'Failed to generate recommendations'}), 500

    # Return the recommendations as a JSON response
    return jsonify(recommendations)


if __name__ == '__main__':
    app.run(debug=True, port=5003)