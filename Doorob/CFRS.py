import sys
import logging
import numpy as np
import pandas as pd
from flask import Flask, jsonify, request, render_template
from flask_cors import CORS  # Import CORS

app = Flask(__name__)
CORS(app)  # Enable CORS for all routes

# SAR and evaluation imports
from recommenders.utils.python_utils import binarize
from recommenders.datasets.python_splitters import python_stratified_split
from recommenders.models.sar import SAR
from recommenders.evaluation.python_evaluation import (
    map,
    ndcg_at_k,
    precision_at_k,
    recall_at_k,
    rmse,
    mae,
    logloss,
    rsquared,
    exp_var
)

# Top K items to recommend
TOP_K = 10
# Define datasets with actual file paths
PLACES_DATA_PATH = 'riyadh_places_8836x9.csv'          
RATINGS_DATA_PATH = 'modified_ratings.csv' 

# Load your datasets
ratings_df = pd.read_csv(RATINGS_DATA_PATH)
places_df = pd.read_csv(PLACES_DATA_PATH)

# PREPROCESSING
places_df = places_df.rename(columns={'id': 'place_id'})
essential_place_columns = ['place_id', 'place_name', 'is_restaurant', 'categories', 
                           'average_rating', 'rate_count', 'granular_category', 
                           'latitude', 'longitude']
places_df = places_df.dropna(subset=essential_place_columns)

essential_rating_columns = ['user_id', 'place_id', 'rating']
ratings_df = ratings_df.dropna(subset=essential_rating_columns)

# Convert IDs to integers
places_df['place_id'] = places_df['place_id'].astype(int)
ratings_df['user_id'] = ratings_df['user_id'].astype(int)
ratings_df['place_id'] = ratings_df['place_id'].astype(int)

# Additional type conversions
places_df['place_name'] = places_df['place_name'].astype(str)
places_df['is_restaurant'] = places_df['is_restaurant'].astype(bool)
places_df['categories'] = places_df['categories'].astype(str)
places_df['average_rating'] = places_df['average_rating'].astype(float)
places_df['rate_count'] = places_df['rate_count'].astype(int)
places_df['granular_category'] = places_df['granular_category'].astype(str)
places_df['latitude'] = places_df['latitude'].astype(float)
places_df['longitude'] = places_df['longitude'].astype(float)

ratings_df['rating'] = ratings_df['rating'].astype(float)
ratings_df = ratings_df.drop_duplicates(subset=['user_id', 'place_id'])

# Preparing data
data = ratings_df
data["rating"] = data["rating"].astype(np.float32)

# Train-test split
train, test = python_stratified_split(data, ratio=0.75, col_user="user_id", col_item="place_id", seed=42)

# Create the SAR model
model = SAR(
    col_user="user_id",
    col_item="place_id",
    col_rating="rating",
    similarity_type="jaccard", 
    normalize=True
)

# Fit the model on training data
model.fit(train)

@app.route('/api/recommendations/<int:user_id>', methods=['GET'])
def get_recommendations_by_id(user_id):
    try:
        # Fetch user recommendations
        user_recommendations = model.recommend_k_items(pd.DataFrame({'user_id': [user_id]}), top_k=TOP_K, remove_seen=True)

        # Log the user recommendations for debugging
        print("User Recommendations:", user_recommendations)  # Log recommendations before filtering

        # Check if recommendations are empty
        if user_recommendations.empty:
            return jsonify({"error": "No recommendations found for this user."}), 404

        # Filter out NaN predictions
        user_recommendations = user_recommendations[user_recommendations['prediction'].notna()]

        # Prepare recommended places without scores
        recommended_places = pd.DataFrame(user_recommendations, columns=['place_id', 'prediction'])

        # Merge with places DataFrame
        recommended_places_details = recommended_places.merge(places_df, on='place_id')

        # Prepare the response, excluding the score
        response = recommended_places_details[['place_id', 'place_name', 'is_restaurant', 'categories', 'average_rating', 'rate_count', 'granular_category', 'latitude', 'longitude']].to_dict(orient='records')

        print("Response:", response)  # Log the response for debugging
        return jsonify(response)

    except Exception as e:
        # Log the error and return a JSON response
        print(f"Error occurred: {str(e)}")  # Log the error for debugging
        return jsonify({"error": "An unexpected error occurred."}), 500



if __name__ == '__main__':
    app.run(debug=True)
