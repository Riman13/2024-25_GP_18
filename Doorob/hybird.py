import logging

import numpy as np
import pandas as pd
from flask import Flask, jsonify, request
from flask_cors import CORS
from geopy.distance import geodesic
from sklearn.decomposition import NMF
from sklearn.feature_extraction.text import CountVectorizer, TfidfVectorizer
from sklearn.metrics import mean_absolute_error, mean_squared_error
from sklearn.metrics.pairwise import cosine_similarity
from sklearn.neighbors import NearestNeighbors
from sklearn.preprocessing import MinMaxScaler
from surprise import SVD, Dataset, Reader, accuracy
from surprise.model_selection import cross_validate, train_test_split
from vowpalwabbit import pyvw

# Logging setup
logging.basicConfig(level=logging.DEBUG)

app = Flask(__name__)
CORS(app)

# Load datasets
PLACES_DATA_PATH = 'DATADATA.csv'
RATINGS_DATA_PATH = 'modified_ratings.csv'
places_df = pd.read_csv(PLACES_DATA_PATH)
ratings_df = pd.read_csv(RATINGS_DATA_PATH)

# Preprocess places
places_df = places_df.rename(columns={'id': 'place_id'})
places_df = places_df[['place_id', 'place_name', 'average_rating', 'granular_category', 'lat', 'lng']].dropna()
places_df['place_id'] = places_df['place_id'].astype(int)
places_df['average_rating'] = pd.to_numeric(places_df['average_rating'], errors='coerce').fillna(3.0)

# Store user locations
user_locations = {}

# ======== Part 1: Context-Aware Based On Location Recommendations ========
def get_closest_places(user_lat, user_lng, top_k=5):
    """
    Get top K places closest to the user's location.
    """
    user_location = (user_lat, user_lng)
    places_df['distance_km'] = places_df.apply(
        lambda row: geodesic(user_location, (row['lat'], row['lng'])).km, axis=1
    )
    closest_places = places_df.sort_values(by='distance_km').head(top_k)
    logging.debug(f"Location-Based Recommendations:\n{closest_places[['place_name', 'distance_km']]}")
    return closest_places

# ======== Part 2: Collaborative Filtering with SVD ========
# Train the CF model once and reuse it
def train_cf_model():
    """
    Train the SVD collaborative filtering model once and reuse it.
    """
    # Prepare data for Surprise
    reader = Reader(rating_scale=(1, 5))
    data = Dataset.load_from_df(ratings_df[['user_id', 'place_id', 'rating']], reader)

    # Split data into train and test sets
    trainset, testset = train_test_split(data, test_size=0.2, random_state=42)

    # Train the SVD model
    svd = SVD()
    svd.fit(trainset)

    # Evaluate the model on the test set
    predictions = svd.test(testset)
    rmse = accuracy.rmse(predictions, verbose=True)
    mae = accuracy.mae(predictions, verbose=True)

    # Log the evaluation results for reference
    logging.info(f"Collaborative Filtering Evaluation:\nRMSE: {rmse:.4f}, MAE: {mae:.4f}")

    return svd

# Initialize the model at startup
cf_model = train_cf_model()

def collaborative_filtering_recommendations(user_id, top_k=5):
    """
    Get top K recommendations using the pre-trained SVD collaborative filtering model.
    """
    # Generate predictions for all place_ids in places_df
    user_predictions = [
        (place_id, cf_model.predict(user_id, place_id).est)
        for place_id in places_df['place_id'].unique()
    ]

    # Sort predictions by predicted rating
    sorted_predictions = sorted(user_predictions, key=lambda x: x[1], reverse=True)[:top_k]

    # Prepare recommendations list
    recommendations_list = [
        {
            'place_id': place_id,
            'place_name': places_df.loc[places_df['place_id'] == place_id, 'place_name'].values[0],
            'predicted_rating': rating
        }
        for place_id, rating in sorted_predictions
    ]

    # Convert to DataFrame
    recommendations_cf = pd.DataFrame(recommendations_list)

    logging.debug(f"Collaborative Filtering Recommendations:\n{recommendations_cf}")
    return recommendations_cf

# ======== Part 3: Content-Based Recommendations ========
# ======== Part 3: Content-Based Recommendations ========
def content_based_recommendations(user_id, top_k=5, weight_similarity=0.7, weight_rating=0.3):
    """
    Generate content-based recommendations by analyzing the user's preferences.
    """
    # Step 1: Filter places rated 5 by the user
    user_rated_5_places = ratings_df[(ratings_df['user_id'] == user_id) & (ratings_df['rating'] == 5)]

    if user_rated_5_places.empty:
        print("User has not rated any places with 5. Evaluation cannot proceed.")
        return None

    # Merge user ratings with places to get categories
    user_rated_5_places = user_rated_5_places.merge(places_df, on='place_id')

    # Step 2: Count the number of 5-star ratings for each category
    category_5_count = (
        user_rated_5_places.groupby('granular_category').size().sort_values(ascending=False)
    )

    # Identify the most preferred category
    most_preferred_category = category_5_count.idxmax()
    print(f"User's most preferred category: {most_preferred_category}")

    # Step 3: Perform One-Hot Encoding on categories
    places_df_encoded = pd.get_dummies(places_df.set_index('place_id')['granular_category'])

    # Filter places in the most preferred category
    preferred_place_ids = places_df[places_df['granular_category'] == most_preferred_category].index

    # Filter preferred_place_ids to match places_df_encoded index
    preferred_place_ids = [pid for pid in preferred_place_ids if pid in places_df_encoded.index]

    if not preferred_place_ids:
        print("No matching place IDs found in places_df_encoded.")
        return None

    # Compute similarity based on preferred categories
    preferred_vectors = places_df_encoded.loc[preferred_place_ids].mean(axis=0).values.reshape(1, -1)
    all_places_vectors = places_df_encoded.values
    similarity_scores = cosine_similarity(preferred_vectors, all_places_vectors).flatten()

    # Step 4: Add similarity scores to places_df
    places_df['similarity_score'] = similarity_scores

    # Filter places the user has not rated
    unrated_places = places_df[~places_df['place_id'].isin(ratings_df[ratings_df['user_id'] == user_id]['place_id'])]

    # Step 5: Calculate combined score
    unrated_places.loc[:, 'combined_score'] = (
        weight_similarity * unrated_places['similarity_score'] +
        weight_rating * unrated_places['average_rating']
    )

    # Step 6: Get top K recommendations
    recommended_places = unrated_places.sort_values(by='combined_score', ascending=False).head(top_k)

    # Evaluation
    recommended_categories = set(recommended_places['granular_category'])
    relevant_recommendations = 1 if most_preferred_category in recommended_categories else 0

    # Precision@K
    precision = relevant_recommendations / top_k

    # Recall@K
    recall = 1 if relevant_recommendations > 0 else 0

    # F1-Score@K
    if precision + recall > 0:
        f1_score = 2 * (precision * recall) / (precision + recall)
    else:
        f1_score = 0

    # Mean Average Precision (MAP)
    map_score = 1 if relevant_recommendations > 0 else 0

    # Print Metrics
    print(f"Precision@{top_k}: {precision:.2f}")
    print(f"Recall@{top_k}: {recall:.2f}")
    print(f"F1-Score@{top_k}: {f1_score:.2f}")
    print(f"MAP@{top_k}: {map_score:.2f}")
    print(recommended_places)

    # Return recommendations and evaluation metrics
    return recommended_places

# ======== Part 4: Hybrid Recommendations ========
def hybrid_recommendations(user_id, user_lat=None, user_lng=None, top_k=5):
    """
    Generate hybrid recommendations combining location-based, collaborative, and content-based methods.
    """
    # Step 1: Get recommendations from all methods
    location_recs = get_closest_places(user_lat, user_lng, top_k) if user_lat and user_lng else pd.DataFrame()
    cf_recs = collaborative_filtering_recommendations(user_id, top_k * 2)
    cb_recs = content_based_recommendations(user_id, top_k * 2)  # Only recommendations are needed from content-based

    # Step 2: Add 'source' column to identify the recommendation source
    location_recs['source'] = 'location'
    cf_recs['source'] = 'cf'
    cb_recs['source'] = 'cb'

    # Step 3: Ensure all DataFrames have consistent columns
    default_columns = ['place_id', 'place_name', 'average_rating', 'granular_category', 
                       'distance_km', 'similarity_score', 'predicted_rating', 'source']

    def prepare_dataframe(df):
        for col in default_columns:
            if col not in df.columns:
                df[col] = None  # Add missing columns with None
        return df[default_columns]

    location_recs = prepare_dataframe(location_recs)
    cf_recs = prepare_dataframe(cf_recs)
    cb_recs = prepare_dataframe(cb_recs)

    # Step 4: Combine recommendations
    combined = pd.concat([location_recs, cf_recs, cb_recs], ignore_index=True).drop_duplicates(subset=['place_id'], keep='first')

    # Step 5: Normalize scores
    if not combined.empty:
        max_distance = combined['distance_km'].max() if 'distance_km' in combined.columns and not combined['distance_km'].isna().all() else 1
        combined['distance_score'] = 1 - combined['distance_km'].fillna(max_distance) / max_distance if 'distance_km' in combined.columns else 0
        combined['predicted_rating'] = combined['predicted_rating'].fillna(0) / 5.0 if 'predicted_rating' in combined.columns else 0
        combined['similarity_score'] = combined['similarity_score'].fillna(0) if 'similarity_score' in combined.columns else 0

    # Step 6: Adjust weights dynamically based on available methods
    location_weight = 0.2 if not location_recs.empty else 0
    collaborative_weight = 0.4 if not cf_recs.empty else 0.5  # Set same weight for CF and CB
    content_weight = 0.4 if not cb_recs.empty else 0.5  # Set same weight for CF and CB

    # Normalize weights if one method is missing
    total_weight = location_weight + collaborative_weight + content_weight

    # If the total_weight is 0 (i.e., no method is available), assign equal weights
    if total_weight == 0:
        location_weight = collaborative_weight = content_weight = 1 / 3
    else:
        location_weight /= total_weight
        collaborative_weight /= total_weight
        content_weight /= total_weight

    # Step 7: Compute the final weighted score
    combined['weight'] = (
        combined['distance_score'] * location_weight +
        combined['predicted_rating'] * collaborative_weight +
        combined['similarity_score'] * content_weight 
    )

    # Step 8: Sort by weight and filter top-k recommendations
    recommendations = combined.sort_values(by='weight', ascending=False).head(top_k)

    # Step 9: Select the final output columns
    recommendations = recommendations[['place_id', 'place_name', 'granular_category', 
                                        'weight', 'source']]

    logging.debug(f"Hybrid Recommendations:\n{recommendations}")
    return recommendations

# ======== Flask Endpoints ========
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

@app.route('/api/recommendations/<int:user_id>', methods=['GET'])
def get_recommendations(user_id):
    """
    Generate recommendations for a user.
    """
    user_lat, user_lng = user_locations.get(user_id, (None, None))
    recommendations = hybrid_recommendations(user_id, user_lat, user_lng)
    
    # Ensure recommendations are not empty before calling to_dict()
    if recommendations.empty:
        return jsonify({"error": "No recommendations found."}), 404
    
    return jsonify(recommendations.to_dict(orient='records')), 200

if __name__ == '__main__':
    app.run(debug=True)
