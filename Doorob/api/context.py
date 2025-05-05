import logging
from flask import Blueprint, jsonify, request

import pandas as pd
import pymysql
from flask import Flask, jsonify, request
from flask_cors import CORS
from geopy.distance import geodesic
from recommenders.evaluation.python_evaluation import (ndcg_at_k,
                                                       precision_at_k,
                                                       recall_at_k, rsquared)
from sklearn.metrics import mean_absolute_error, mean_squared_error
from vowpalwabbit import pyvw

# Flask app setup
#app = Flask(__name__)
#CORS(app)Blueprint
context_bp = Blueprint('context', __name__, url_prefix='/context')
# Data paths
PLACES_DATA_PATH = 'DATADATA.csv'
RATINGS_CSV_PATH = 'modified_ratings.csv'

# Load places data from CSV
places_df = pd.read_csv(PLACES_DATA_PATH, encoding='utf-8')

# Preprocess places data
places_df = places_df.rename(columns={'id': 'place_id'})
essential_place_columns = ['place_id', 'place_name', 'average_rating', 'granular_category', 'lat', 'lng']
places_df = places_df.dropna(subset=essential_place_columns)
places_df['place_id'] = places_df['place_id'].astype(int)
places_df['average_rating'] = pd.to_numeric(places_df['average_rating'], errors='coerce').fillna(0.0)

# Dictionary to store user locations
user_locations = {}

# Function to establish MySQL connection
def get_db_connection():
    return pymysql.connect(
        host="Doroob.mysql.pythonanywhere-services.com",
        user="Doroob",
        password="RASL1234",
        database="Doroob$doroob",
        cursorclass=pymysql.cursors.DictCursor
    )

# Function to fetch ratings from MySQL
def fetch_mysql_ratings():
    connection = get_db_connection()
    try:
        with connection.cursor() as cursor:
            cursor.execute("SELECT id, UserID AS user_id, PlaceID AS place_id, Rating AS rating FROM ratings")
            ratings = cursor.fetchall()
            return pd.DataFrame(ratings)
    finally:
        connection.close()

# Function to load ratings from both MySQL and CSV
def load_ratings():
    csv_ratings = pd.read_csv(RATINGS_CSV_PATH)
    mysql_ratings = fetch_mysql_ratings()

    # Merge MySQL and CSV ratings
    ratings_data = pd.concat([csv_ratings, mysql_ratings], ignore_index=True)

    # Remove duplicates (keep latest rating)
    ratings_data.drop_duplicates(subset=['user_id', 'place_id'], keep='last', inplace=True)
    
    return ratings_data

# Function to prepare data for Vowpal Wabbit
def prepare_vw_data(data, places_df, user_locations):
    """
    Prepare data in VW format with additional location-based features.
    """
    vw_data = []
    data = data.merge(places_df, on='place_id')
    for _, row in data.iterrows():
        user_id = row['user_id']
        place_location = (row['lat'], row['lng'])
        user_location = user_locations.get(user_id)

        # Compute distance-based feature if user location is available
        if user_location:
            distance = geodesic(user_location, place_location).kilometers
            adjusted_distance = 1 / distance if distance > 0 else 0.0
        else:
            distance, adjusted_distance = 0.0, 0.0

        # Construct VW feature string
        features = (f"|features user_{user_id}:1 "
                    f"avg_rating:{row['average_rating']} "
                    f"granular_category_{row['granular_category']}:1 "
                    f"adjusted_distance:{adjusted_distance:.5f} ")
        label = row['rating']
        vw_data.append(f"{label} '{user_id}_{row['place_id']} {features}")
    
    return vw_data

# Train Vowpal Wabbit model on initial dataset
ratings_df = load_ratings()  # Load initial ratings
train = ratings_df.sample(frac=0.9, random_state=42)
test = ratings_df.drop(train.index)
train_data = prepare_vw_data(train, places_df, user_locations)

vw_model = pyvw.vw("--loss_function squared --l2 0.00001 --learning_rate 0.3 --bit_precision 25")
for _ in range(5):  # Train for 5 epochs
    for row in train_data:
        vw_model.learn(row)

logging.info("Initial model training completed.")

# Function to evaluate model performance
def evaluate_model(vw_model, test_data, test, k=5):
    """
    Evaluate the model using RMSE, MAE, R-squared, and ranking metrics (NDCG, Precision, Recall).
    """
    predictions, actuals, place_ids = [], [], []

    for row in test_data:
        try:
            label, features = row.split(" ", 1)
            actual = float(label)
            prediction = vw_model.predict(features)

            # Extract place_id safely
            if "'" in row:
                id_part = row.split("'")[1]
                if "_" in id_part:
                    place_id = id_part.split("_")[1].split(" ")[0]
                    place_ids.append(int(place_id))
                    actuals.append(actual)
                    predictions.append(prediction)
        except (IndexError, ValueError):
            continue

    if not predictions or not actuals or not place_ids:
        logging.error("Evaluation failed due to empty predictions.")
        return None

    # Convert to DataFrame for evaluation
    rating_true = pd.DataFrame({
        'user_id': test['user_id'].iloc[:len(actuals)],
        'item_id': place_ids,
        'rating': actuals
    })

    rating_pred = pd.DataFrame({
        'user_id': test['user_id'].iloc[:len(predictions)],
        'item_id': place_ids,
        'prediction': predictions
    })

    # Compute metrics
    rmse_val = mean_squared_error(actuals, predictions, squared=False)
    mae_val = mean_absolute_error(actuals, predictions)
    r2_val = rsquared(rating_true, rating_pred, col_user="user_id", col_item="item_id", col_rating="rating", col_prediction="prediction")
    ndcg = ndcg_at_k(rating_true, rating_pred, k=k)
    precision = precision_at_k(rating_true, rating_pred, k=k)
    recall = recall_at_k(rating_true, rating_pred, k=k)

    logging.info(f"Evaluation Metrics - RMSE: {rmse_val:.4f}, MAE: {mae_val:.4f}, R2: {r2_val:.4f}, NDCG@{k}: {ndcg:.4f}, Precision@{k}: {precision:.4f}, Recall@{k}: {recall:.4f}")

    return {"rmse": rmse_val, "mae": mae_val, "r2": r2_val, "ndcg": ndcg, "precision": precision, "recall": recall}

# API to save user location
@app.route('/api/save_location', methods=['POST'])
def save_user_location():
    """
    Store user location to personalize recommendations.
    """
    data = request.get_json()
    user_id = data.get('user_id')
    user_lat = data.get('lat')
    user_lng = data.get('lng')

    if not user_id or not user_lat or not user_lng:
        return jsonify({"error": "Invalid data"}), 400

    user_locations[user_id] = (float(user_lat), float(user_lng))
    return jsonify({"message": "Location saved successfully"}), 200

# API to generate recommendations
@context_bp .route('/<int:user_id>', methods=['GET'])
#@app.route('/api/recommendations_context/<int:user_id>', methods=['GET'])
def get_recommendations_by_id(user_id):
    """
    Generate content-based recommendations, prioritizing nearby places.
    Ensures real-time updates by fetching the latest ratings from MySQL every request.
    """
    try:
        selected_category = request.args.get('category')
        #Force reloading ratings from MySQL & CSV
        ratings_df = load_ratings()  

        # Get user's past ratings
        user_data = ratings_df[ratings_df['user_id'] == user_id]

        # Get user location if available
        user_location = user_locations.get(user_id)

        # Train user-specific model
        train_data = prepare_vw_data(user_data, places_df, user_locations)
        user_model = pyvw.vw("--loss_function squared --l2 0.00001 --learning_rate 0.3 --bit_precision 25")
        for row in train_data:
            user_model.learn(row)

        # Get unrated places (places the user has NOT rated)
        rated_places = user_data['place_id'].tolist()
        
        unrated_places = places_df[~places_df['place_id'].isin(rated_places)]
        if selected_category:
         unrated_places = unrated_places[unrated_places['granular_category'] == selected_category]

        recommendations = []
        for _, place in unrated_places.iterrows():
            distance, adjusted_distance = float('inf'), 0.0  
            
            # Calculate distance if user location is available
            if user_location:
                distance = geodesic(user_location, (place['lat'], place['lng'])).kilometers
                adjusted_distance = 1 / (distance + 1)  

            # Prepare VW feature string
            features = (f"|features user_{user_id}:1 "
                        f"avg_rating:{place['average_rating']} "
                        f"granular_category_{place['granular_category']}:1 "
                        f"adjusted_distance:{adjusted_distance:.5f} ")  
            
            # Predict score using the trained model
            score = user_model.predict(features)

            # Store place information including distance
            recommendations.append((place['place_id'], place['place_name'], place['average_rating'],
                                    place['granular_category'], place['lat'], place['lng'], score, distance))

        # **Sort by distance first, then predicted rating**
        recommendations = sorted(recommendations, key=lambda x: (x[7], -x[6]))[:5]

        # Convert results to JSON
        response = pd.DataFrame(recommendations, columns=['place_id', 'place_name', 'average_rating',
                                                           'granular_category', 'lat', 'lng', 'predicted_rating', 'distance'])
        
        return jsonify(response[['place_id', 'place_name', 'average_rating', 'granular_category', 'lat', 'lng', 'distance']].to_dict(orient='records')), 200

    except Exception as e:
        logging.error(f"Error generating recommendations for user {user_id}: {e}")
        return jsonify({"error": "Unable to generate recommendations"}), 500

#if __name__ == '__main__':
    #app.run(debug=True, threaded=True, port=5002)
