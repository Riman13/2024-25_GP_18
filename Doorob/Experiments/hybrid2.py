import logging
import os
import pickle

import numpy as np
import pandas as pd
from flask import Flask, jsonify, request
from flask_cors import CORS
from geopy.distance import geodesic
# Train-test split
from recommenders.datasets.python_splitters import python_stratified_split
from sklearn.feature_extraction.text import CountVectorizer, TfidfVectorizer
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.metrics.pairwise import cosine_similarity
from sklearn.model_selection import train_test_split
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
essential_place_columns = ['place_id', 'place_name', 
                           'average_rating', 'granular_category', 
                           'lat', 'lng']
places_df = places_df.dropna(subset=essential_place_columns)
places_df['average_rating'] = pd.to_numeric(places_df['average_rating'], errors='coerce').fillna(3.0)


# Additional type conversions
places_df['place_name'] = places_df['place_name'].astype(str)
places_df['average_rating'] = places_df['average_rating'].astype(float)
places_df['granular_category'] = places_df['granular_category'].astype(str)
places_df['lat'] = places_df['lat'].astype(float)
places_df['lng'] = places_df['lng'].astype(float)


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
# Prepare data for SVD
reader = Reader(rating_scale=(1, 5))
data = Dataset.load_from_df(ratings_df[['user_id', 'place_id', 'rating']], reader)

# Load or train the SVD model
model_file = "svd_model.pkl"
try:
    with open(model_file, "rb") as f:
        svd_model = pickle.load(f)
        logging.debug("Loaded saved SVD model.")
except FileNotFoundError:
    logging.debug("No saved SVD model found. Training a new model...")
    # Train-test split
    trainset, testset = train_test_split(data, test_size=0.20, random_state=42)

    # Train the SVD model
    svd_model = SVD()
    svd_model.fit(trainset)

    # Save the trained model
    with open(model_file, "wb") as f:
        pickle.dump(svd_model, f)
        logging.debug("Saved the trained SVD model.")

# Evaluate the model on the test set
trainset, testset = train_test_split(data, test_size=0.20, random_state=42)
predictions = svd_model.test(testset)

# Compute evaluation metrics
rmse = accuracy.rmse(predictions, verbose=False)
mae = accuracy.mae(predictions, verbose=False)

logging.debug(f"SVD Model Evaluation:\nRMSE: {rmse}\nMAE: {mae}")

def collaborative_filtering_recommendations(user_id, top_k=5):
    """
    Generate Collaborative Filtering recommendations using the SVD model.
    """
    try:
        # Get all unique place IDs
        all_place_ids = ratings_df['place_id'].unique()

        # Get user's already-rated places
        rated_places = ratings_df[ratings_df['user_id'] == user_id]['place_id'].tolist()

        # Generate predictions for unrated places
        recommendations = []
        for place_id in all_place_ids:
            if place_id not in rated_places:  # Only predict for unrated places
                prediction = svd_model.predict(user_id, place_id)
                recommendations.append((place_id, prediction.est))

        # Sort by predicted rating in descending order and select top-k
        recommendations = sorted(recommendations, key=lambda x: x[1], reverse=True)[:top_k]

        # Merge with place details
        recommended_places = pd.DataFrame(recommendations, columns=['place_id', 'predicted_rating'])
        recommended_places = recommended_places.merge(places_df, on='place_id')

        # Prepare the response
        response = recommended_places[['place_id', 'place_name', 'average_rating', 'granular_category', 'predicted_rating']]
        response['similarity_score'] = None  # Placeholder for SVD
        response['distance_km'] = None  # Not applicable for SVD
        print (response)
        return response
    except Exception as e:
        logging.error(f"Error in SVD recommendations: {str(e)}")
        return pd.DataFrame()

# ========Content Based ========
# Utility Functions
def prepare_vw_data(ratings_df, places_df, user_id):
    """
    Prepare Vowpal Wabbit input data.
    """
    data = []
    user_rated_places = ratings_df[ratings_df['user_id'] == user_id]

    if user_rated_places.empty:
        logging.warning(f"No ratings found for user {user_id}.")
        return None

    user_rated_places = user_rated_places.merge(places_df, on='place_id')
    for _, row in user_rated_places.iterrows():
        features = f"|features avg_rating:{row['average_rating']} granular_category_{row['granular_category']}:1"
        label = row['rating']
        data.append(f"{label} '{row['user_id']}_{row['place_id']} {features}")

    return data

def evaluate_vw_model(vw_model, test_data):
    """
    Evaluate VW model using RMSE, MAE, and R-squared.
    """
    predictions, actuals = [], []

    for row in test_data:
        label, features = row.split(" ", 1)
        actual = float(label)
        prediction = vw_model.predict(features)

        actuals.append(actual)
        predictions.append(prediction)

    rmse = np.sqrt(mean_squared_error(actuals, predictions))
    mae = mean_absolute_error(actuals, predictions)
    r2 = r2_score(actuals, predictions)

    logging.info(f"Evaluation Metrics:\nRMSE: {rmse:.4f}\nMAE: {mae:.4f}\nR-squared: {r2:.4f}")
    return rmse, mae, r2

def train_and_evaluate_vw_model(ratings_df, places_df, user_id=None):
    """
    Train VW model and evaluate using RMSE, MAE, and R-squared.
    """
    vw_data = prepare_vw_data(ratings_df, places_df, user_id)
    if not vw_data:
        logging.warning("No data available for training.")
        return None, None

    # Split vw_data into training and testing 
    split_idx = int(len(vw_data) * 0.9)
    train_data = vw_data[:split_idx]
    test_data = vw_data[split_idx:]

    # Train the VW model
    vw_model = pyvw.vw("--loss_function squared --l2 0.001 --learning_rate 0.5 --bit_precision 25")
    for row in train_data:
        vw_model.learn(row)

    # Evaluate the model
    rmse, mae, r2 = evaluate_vw_model(vw_model, test_data)

    logging.info(f"Training completed. RMSE: {rmse}, MAE: {mae}, R²: {r2}")
    return vw_model, {"rmse": rmse, "mae": mae, "r2": r2}


def get_or_train_vw_model(ratings_df, places_df, user_id):
    """
    Load VW model or train if not available.
    """
    model_file = "vw_model.vw"

    try:
        if os.path.exists(model_file):
            vw_model = pyvw.vw(f"--quiet -i {model_file}")
            logging.info("Successfully loaded VW model.")
        else:
            raise FileNotFoundError("VW model file not found.")
    except FileNotFoundError as e:
        logging.warning(e)
        vw_model, eval_metrics = train_and_evaluate_vw_model(ratings_df, places_df, user_id)
        if vw_model:
            vw_model.save(model_file)
            logging.info(f"New VW model trained and saved. Metrics: {eval_metrics}")
        else:
            raise RuntimeError("Failed to train VW model.")
    
    return vw_model

def content_based_recommendations(user_id, vw_model, ratings_df, places_df, top_k=5):
    """
    Generate content-based recommendations.
    """
    rated_places = ratings_df[ratings_df['user_id'] == user_id]['place_id'].tolist()
    unrated_places = places_df[~places_df['place_id'].isin(rated_places)]

    recommendations = []
    for _, place in unrated_places.iterrows():
        features = f"|features avg_rating:{place['average_rating']} granular_category_{place['granular_category']}:1"
        score = vw_model.predict(features)
        recommendations.append((place['place_id'], place['place_name'], score, place['granular_category']))

    recommendations = sorted(recommendations, key=lambda x: x[2], reverse=True)[:top_k]
    print(recommendations)
    return pd.DataFrame(recommendations, columns=['place_id', 'place_name', 'predicted_rating', 'granular_category'])
    




# ======== Part 4: Hybrid Recommendations =======
def hybrid_recommendations(user_id, user_lat=None, user_lng=None, vw_model=None, top_k=5):
    """
    Generate hybrid recommendations combining location-based, collaborative, and content-based methods.
    """
    # Step 1: Get recommendations from all methods
    location_recs = get_closest_places(user_lat, user_lng, top_k) if user_lat and user_lng else pd.DataFrame()
    cf_recs = collaborative_filtering_recommendations(user_id, top_k * 2)
    cb_recs = content_based_recommendations(user_id, vw_model, ratings_df, places_df, top_k * 2)

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
    collaborative_weight = 0.4 if not cf_recs.empty else 0.4 # Set same weight for CF and CB
    content_weight = 0.4 if not cb_recs.empty else 0.6 # Set same weight for CF and CB

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
    # Load user location
    user_lat, user_lng = user_locations.get(user_id, (None, None))

    vw_model = get_or_train_vw_model(ratings_df, places_df, user_id)
    if vw_model is None:
        return jsonify({"error": "Content-based model could not be loaded or trained."}), 500

    # Generate recommendations
    recommendations = hybrid_recommendations(user_id, user_lat, user_lng, vw_model=vw_model)

    # Ensure recommendations are not empty before calling to_dict()
    if recommendations.empty:
        return jsonify({"error": "No recommendations found."}), 404

    return jsonify(recommendations.to_dict(orient='records')), 200


if __name__ == '__main__':
    app.run(debug=True)
