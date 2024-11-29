import logging

import pandas as pd
from flask import Flask, jsonify, request
from flask_cors import CORS
from geopy.distance import geodesic
from recommenders.evaluation.python_evaluation import (ndcg_at_k,
                                                       precision_at_k,
                                                       recall_at_k, rsquared)
from sklearn.metrics import mean_absolute_error, mean_squared_error
from vowpalwabbit import pyvw

# Flask app setup
app = Flask(__name__)
CORS(app)

# Data paths
PLACES_DATA_PATH = 'DATADATA.csv'
RATINGS_DATA_PATH = 'modified_ratings.csv'

# Load datasets
places_df = pd.read_csv(PLACES_DATA_PATH, encoding='utf-8')
ratings_df = pd.read_csv(RATINGS_DATA_PATH)

# Preprocess places data
places_df = places_df.rename(columns={'id': 'place_id'})
essential_place_columns = ['place_id', 'place_name', 'average_rating', 'granular_category', 'lat', 'lng']
places_df = places_df.dropna(subset=essential_place_columns)
places_df['place_id'] = places_df['place_id'].astype(int)
places_df['average_rating'] = pd.to_numeric(places_df['average_rating'], errors='coerce').fillna(0.0)

# Preprocess ratings data
ratings_df = ratings_df.dropna(subset=['user_id', 'place_id', 'rating'])
ratings_df['user_id'] = ratings_df['user_id'].astype(int)
ratings_df['place_id'] = ratings_df['place_id'].astype(int)
ratings_df['rating'] = ratings_df['rating'].astype(float)

# Train-test split
train = ratings_df.sample(frac=0.9, random_state=42)
test = ratings_df.drop(train.index)

# User locations (in-memory storage)
user_locations = {}

# Prepare Vowpal Wabbit data 
def prepare_vw_data(data, places_df, user_locations):
    """
    Prepare VW formatted data with additional location-based features.
    """
    vw_data = []
    data = data.merge(places_df, on='place_id')
    for _, row in data.iterrows():
        user_id = row['user_id']
        place_location = (row['lat'], row['lng'])
        user_location = user_locations.get(user_id)

        # Calculate distance if user location is available
        if user_location:
            distance = geodesic(user_location, place_location).kilometers
            adjusted_distance = 1 / distance if distance > 0 else 0.0  # Adjusted distance
        else:
            distance = 0.0
            adjusted_distance = 0.0

        # VW features
        features = (f"|features user_{user_id}:1 "
                    f"avg_rating:{row['average_rating']} "
                    f"granular_category_{row['granular_category']}:1 "
                    f"adjusted_distance:{adjusted_distance:.5f} ")  # Use adjusted distance
        label = row['rating']
        vw_data.append(f"{label} '{user_id}_{row['place_id']} {features}")
    return vw_data

# Prepare training and testing data
train_data = prepare_vw_data(train, places_df, user_locations)
test_data = prepare_vw_data(test, places_df, user_locations)

# Train Vowpal Wabbit model
vw_model = pyvw.vw("--loss_function squared --l2 0.00001 --learning_rate 0.3 --bit_precision 25")
for _ in range(5):  # Iterate over epochs
    for row in train_data:
        vw_model.learn(row)

logging.info("Model training completed.")

# Evaluation function
def evaluate_model(vw_model, test_data, test, k=5):
    predictions, actuals, place_ids = [], [], []

    for row in test_data:
        try:
            # Split row into label and features
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
        logging.error("Empty predictions, actuals, or place_ids. Cannot compute metrics.")
        return None

    # DataFrames for evaluation
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

    # Compute evaluation metrics
    rmse_val = mean_squared_error(actuals, predictions, squared=False)
    mae_val = mean_absolute_error(actuals, predictions)
    r2_val = rsquared(rating_true, rating_pred, col_user="user_id", col_item="item_id", col_rating="rating", col_prediction="prediction")
    ndcg = ndcg_at_k(rating_true, rating_pred, col_user="user_id", col_item="item_id", col_rating="rating", col_prediction="prediction", k=k)
    precision = precision_at_k(rating_true, rating_pred, col_user="user_id", col_item="item_id", col_rating="rating", col_prediction="prediction", k=k)
    recall = recall_at_k(rating_true, rating_pred, col_user="user_id", col_item="item_id", col_rating="rating", col_prediction="prediction", k=k)

    logging.info(f"Evaluation Metrics:\nRMSE: {rmse_val:.4f}\nMAE: {mae_val:.4f}\nR-squared: {r2_val:.4f}\n"
                 f"NDCG@{k}: {ndcg:.4f}\nPrecision@{k}: {precision:.4f}\nRecall@{k}: {recall:.4f}")

    return {
        "rmse": rmse_val,
        "mae": mae_val,
        "r2": r2_val,
        "ndcg": ndcg,
        "precision": precision,
        "recall": recall
    }

@app.route('/api/save_location', methods=['POST'])
def save_user_location():
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
    Generate content-based recommendations for a specific user, considering location if available.
    """
    try:
        # Retrieve user ratings
        user_data = ratings_df[ratings_df['user_id'] == user_id]

        # Get user location if available
        user_location = user_locations.get(user_id)
        train_data = prepare_vw_data(user_data, places_df, user_locations)

        # Train user-specific model
        user_model = pyvw.vw("--loss_function squared --l2 0.00001 --learning_rate 0.3 --bit_precision 25")
        for row in train_data:
            user_model.learn(row)

        # Filter out places the user has already rated
        rated_places = user_data['place_id'].tolist()
        unrated_places = places_df[~places_df['place_id'].isin(rated_places)]

        # Generate recommendations
        recommendations = []
        for _, place in unrated_places.iterrows():
            # Calculate distance if user location is available
            if user_location:
                distance = geodesic(user_location, (place['lat'], place['lng'])).kilometers
                adjusted_distance = 1 / distance if distance > 0 else 0.0
            else:
                distance = 0.0
                adjusted_distance = 0.0

            # Prepare VW feature string
            features = (f"|features user_{user_id}:1 "
                        f"avg_rating:{place['average_rating']} "
                        f"granular_category_{place['granular_category']}:1 "
                        f"adjusted_distance:{adjusted_distance:.5f} ")
            score = user_model.predict(features)

            # Print predicted rating and distance to terminal
            print(f"Predicted Rating for Place {place['place_name']} (ID: {place['place_id']}): {score:.2f}")
            print(f"Distance to Place {place['place_name']}: {distance:.2f} km")

            recommendations.append((place['place_id'], place['place_name'], place['average_rating'], place['granular_category'], place['lat'], place['lng'], score))

        # Sort recommendations by predicted score
        recommendations = sorted(recommendations, key=lambda x: x[6], reverse=True)[:5]
        

        # Create DataFrame for recommended places
        recommended_places_details = pd.DataFrame(recommendations, columns=['place_id', 'place_name', 'average_rating', 'granular_category', 'lat', 'lng', 'predicted_rating'])

        # Prepare the response
        response = recommended_places_details[['place_id', 'place_name', 'average_rating', 'granular_category', 'lat', 'lng']].to_dict(orient='records')

        # Print response to terminal for debugging
        print(f"Recommended Places Response: {response}")
        
        return jsonify(response)

    except Exception as e:
        logging.error(f"Error generating recommendations for user {user_id}: {e}")
        return jsonify({"error": "Unable to generate recommendations"}), 500


if __name__ == '__main__':
    metrics = evaluate_model(vw_model, test_data, test, k=5)
    if metrics:
        print("\nEvaluation Metrics:")
        for metric, value in metrics.items():
            print(f"{metric.upper()}: {value:.4f}")
    else:
        print("Evaluation metrics could not be computed.")
    app.run(debug=True)
