import logging
import os

import numpy as np
import pandas as pd
from flask import Flask, jsonify, request
from flask_cors import CORS
# API Endpoint for recommendations
from geopy.distance import geodesic
from recommenders.evaluation.python_evaluation import (exp_var, map, ndcg_at_k,
                                                       precision_at_k,
                                                       recall_at_k, rsquared)
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from vowpalwabbit import pyvw

app = Flask(__name__)
CORS(app)

# Paths to your datasets
PLACES_DATA_PATH = 'DATADATA.csv'
RATINGS_DATA_PATH = 'modified_ratings.csv'

# Load datasets
places_df = pd.read_csv(PLACES_DATA_PATH)
ratings_df = pd.read_csv(RATINGS_DATA_PATH)

# Preprocess places data
places_df = places_df.rename(columns={'id': 'place_id'})
essential_place_columns = ['place_id', 'place_name', 'average_rating', 'granular_category', 'lat', 'lng']
places_df = places_df.dropna(subset=essential_place_columns)
places_df['place_id'] = places_df['place_id'].astype(int)
places_df['average_rating'] = pd.to_numeric(places_df['average_rating'], errors='coerce').fillna(3.0)

# Preprocess ratings data
ratings_df = ratings_df.dropna(subset=['user_id', 'place_id', 'rating'])
ratings_df['user_id'] = ratings_df['user_id'].astype(int)
ratings_df['place_id'] = ratings_df['place_id'].astype(int)
ratings_df['rating'] = ratings_df['rating'].astype(float)

# Train-test split
train = ratings_df.sample(frac=0.9, random_state=42)
test = ratings_df.drop(train.index)

# Utility function to prepare Vowpal Wabbit data
def prepare_vw_data(data, places_df):
    vw_data = []
    data = data.merge(places_df, on='place_id')
    for _, row in data.iterrows():
        features = (f"|features user_{row['user_id']}:1 "
                    f"avg_rating:{row['average_rating']} "
                    f"granular_category_{row['granular_category']}:1 ")
        label = row['rating']
        vw_data.append(f"{label} '{row['user_id']}_{row['place_id']} {features}")
    return vw_data


# Prepare training and testing data
train_data = prepare_vw_data(train, places_df)
test_data = prepare_vw_data(test, places_df)

# Train Vowpal Wabbit model
vw_model = pyvw.vw("--loss_function squared --l2 0.00001 --learning_rate 0.3 --bit_precision 25")
for _ in range(5):  
    for row in train_data:
        vw_model.learn(row)

logging.info("Model trained.")

def evaluate_model(vw_model, test_data, test, k=5):
    """
    Evaluate VW model using metrics from the recommenders library.
    """
    predictions, actuals, place_ids = [], [], []

    for row in test_data:
        try:
            # Split row to extract label and features
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
                else:
                    logging.warning(f"Skipping row due to malformed id_part: {id_part}")
            else:
                logging.warning(f"Skipping row due to missing or malformed place_id: {row}")
        except (IndexError, ValueError) as e:
            logging.warning(f"Skipping row due to parsing error: {row}. Error: {e}")
            continue

    # Ensure arrays are not empty
    if not predictions or not actuals or not place_ids:
        logging.error("Empty predictions, actuals, or place_ids. Cannot compute metrics.")
        return None

    # Prepare DataFrames for evaluation
    rating_true = pd.DataFrame({
        'user_id': test['user_id'].iloc[:len(actuals)],  # Align with actuals length
        'item_id': place_ids,
        'rating': actuals
    })

    rating_pred = pd.DataFrame({
        'user_id': test['user_id'].iloc[:len(predictions)],
        'item_id': place_ids,
        'prediction': predictions
    })

    # Compute evaluation metrics
    rmse_val = mean_squared_error(actuals, predictions, squared=False)
    mae_val = mean_absolute_error(actuals, predictions)
    r2_val = rsquared(rating_true, rating_pred, col_user="user_id", col_item="item_id", col_rating="rating", col_prediction="prediction")
    ndcg = ndcg_at_k(rating_true, rating_pred, col_user="user_id", col_item="item_id", col_rating="rating", col_prediction="prediction", k=k)
    precision = precision_at_k(rating_true, rating_pred, col_user="user_id", col_item="item_id", col_rating="rating", col_prediction="prediction", k=k)
    recall = recall_at_k(rating_true, rating_pred, col_user="user_id", col_item="item_id", col_rating="rating", col_prediction="prediction", k=k)

    # Log metrics
    logging.info(f"Evaluation Metrics:\nRMSE: {rmse_val:.4f}\nMAE: {mae_val:.4f}\nR-squared: {r2_val:.4f}\n"
                 f"NDCG@{k}: {ndcg:.4f}\nPrecision@{k}: {precision:.4f}\nRecall@{k}: {recall:.4f}")

    # Return metrics
    return {
        "rmse": rmse_val,
        "mae": mae_val,
        "r2": r2_val,
        "ndcg": ndcg,
        "precision": precision,
        "recall": recall
    }

# In-memory storage for user locations
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

@app.route('/api/recommendations/<int:user_id>', methods=['GET'])
def get_recommendations_by_id(user_id):
    """
    Generate recommendations considering user location, with a fallback for users who don't allow location.
    """
    try:
        # Check if user-specific data exists
        user_data = ratings_df[ratings_df['user_id'] == user_id]

        # If user has no ratings, use global recommendations
        if user_data.empty:
            logging.info(f"No ratings found for user {user_id}. Using global recommendations.")
            global_recommendations = places_df.sort_values(by='average_rating', ascending=False).head(5)
            response = [
                {"place_id": row['place_id'], 
                 "place_name": row['place_name'], 
                 "predicted_rating": row['average_rating'], 
                 "category": row['granular_category']}
                for _, row in global_recommendations.iterrows()
            ]
            return jsonify(response)

        # Check if user location is available
        user_location = user_locations.get(user_id)

        # Prepare training data for this user
        train_data = prepare_vw_data(user_data, places_df)

        # Train a user-specific model
        user_model = pyvw.vw("--loss_function squared --l2 0.00001 --learning_rate 0.3 --bit_precision 25")
        for row in train_data:
            user_model.learn(row)

        # Get unrated places
        rated_places = user_data['place_id'].tolist()
        unrated_places = places_df[~places_df['place_id'].isin(rated_places)]

        recommendations = []

        for _, place in unrated_places.iterrows():
            # If location is available, calculate distance
            if user_location:
                place_location = (place['lat'], place['lng'])
                distance = geodesic(user_location, place_location).kilometers
            else:
                distance = None  # Location not available

            # Construct feature string
            features = (f"|features user_{user_id}:1 "
                        f"avg_rating:{place['average_rating']} "
                        f"granular_category_{place['granular_category']}:1 ")
            if distance is not None:
                features += f"distance:{distance:.2f} "

            score = user_model.predict(features)
            recommendations.append((place['place_id'], place['place_name'], score, place['granular_category'], distance))

        # Sort recommendations
        if user_location:
            # Prioritize by score and proximity
            recommendations = sorted(recommendations, key=lambda x: (x[2], -x[4]), reverse=True)[:5]
        else:
            # Prioritize by score only
            recommendations = sorted(recommendations, key=lambda x: x[2], reverse=True)[:5]

        # Prepare response
        response = [
            {
                "place_id": r[0], 
                "place_name": r[1], 
                "predicted_rating": r[2], 
                "category": r[3], 
                "distance_km": r[4] if r[4] is not None else "N/A"
            }
            for r in recommendations
        ]

        return jsonify(response)

    except Exception as e:
        logging.error(f"Error generating recommendations for user {user_id}: {e}")
        return jsonify({"error": "Unable to generate recommendations"}), 500



if __name__ == '__main__':
    # Compute evaluation metrics
    metrics = evaluate_model(vw_model, test_data, test, k=5)

    if metrics:
        print("\nEvaluation Metrics:")
        for metric, value in metrics.items():
            print(f"{metric.upper()}: {value:.4f}")
    else:
        print("Evaluation metrics could not be computed.")

    app.run(debug=True)
