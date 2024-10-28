import logging
import sys

import numpy as np
import pandas as pd
from flask import Flask, jsonify, render_template, request
from flask_cors import CORS  # Import CORS
from recommenders.datasets.python_splitters import python_stratified_split
from recommenders.evaluation.python_evaluation import (exp_var, logloss, mae,
                                                       map, ndcg_at_k,
                                                       precision_at_k,
                                                       recall_at_k, rmse,
                                                       rsquared)
from recommenders.models.sar import SAR
# SAR and evaluation imports
from recommenders.utils.python_utils import binarize
from sklearn.preprocessing import minmax_scale

app = Flask(__name__)
CORS(app)  # Enable CORS for all routes


# Top K items to recommend
TOP_K = 5

TEST_SIZE = 0.2  # 20% for testing, 80% for training

RANDOM_SEED = 42

PLACES_DATA_PATH = 'doroob_places.csv'          
RATINGS_DATA_PATH = 'synthetic_ratings_riyadh_places.csv' 

# Load your datasets
ratings_df = pd.read_csv(RATINGS_DATA_PATH)
places_df = pd.read_csv(PLACES_DATA_PATH)

# PREPROCESSING
places_df = places_df.rename(columns={'id': 'place_id'})
essential_place_columns = ['place_id', 'place_name', 
                           'average_rating', 'granular_category', 
                           'lat', 'lng']
places_df = places_df.dropna(subset=essential_place_columns)

essential_rating_columns = ['user_id', 'place_id', 'rating']
ratings_df = ratings_df.dropna(subset=essential_rating_columns)

# Convert IDs to integers
places_df['place_id'] = places_df['place_id'].astype(int)
ratings_df['user_id'] = ratings_df['user_id'].astype(int)
ratings_df['place_id'] = ratings_df['place_id'].astype(int)

# Additional type conversions
places_df['place_name'] = places_df['place_name'].astype(str)
places_df['average_rating'] = places_df['average_rating'].astype(float)
places_df['granular_category'] = places_df['granular_category'].astype(str)
places_df['lat'] = places_df['lat'].astype(float)
places_df['lng'] = places_df['lng'].astype(float)

ratings_df['rating'] = ratings_df['rating'].astype(float)
ratings_df = ratings_df.drop_duplicates(subset=['user_id', 'place_id'])

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

logging.basicConfig(level=logging.DEBUG, 
                    format='%(asctime)s %(levelname)-8s %(message)s')

# Fit the model on training data
model.fit(train)
top_k = model.recommend_k_items(test, top_k=TOP_K, remove_seen=True)

#evaluation
# Ranking metrics
eval_map = map(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating", k=TOP_K)
eval_ndcg = ndcg_at_k(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating", k=TOP_K)
eval_precision = precision_at_k(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating", k=TOP_K)
eval_recall = recall_at_k(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating", k=TOP_K)
# Rating metrics
eval_rmse = rmse(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating")
eval_mae = mae(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating")
eval_rsquared = rsquared(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating")
eval_exp_var = exp_var(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating")

positivity_threshold = 2
test_bin = test.copy()
test_bin["rating"] = binarize(test_bin["rating"], positivity_threshold)

top_k_prob = top_k.copy()
top_k_prob["prediction"] = minmax_scale(top_k_prob["prediction"].astype(float))

eval_logloss = logloss(
    test_bin, top_k_prob, col_user="user_id", col_item="place_id", col_rating="rating"
)

print("Model:\t",
      "Top K:\t%d" % TOP_K,
      "MAP:\t%f" % eval_map,
      "NDCG:\t%f" % eval_ndcg,
      "Precision@K:\t%f" % eval_precision,
      "Recall@K:\t%f" % eval_recall,
      "RMSE:\t%f" % eval_rmse,
      "MAE:\t%f" % eval_mae,
      "R2:\t%f" % eval_rsquared,
      "Exp var:\t%f" % eval_exp_var,
      "Logloss:\t%f" % eval_logloss,
      sep='\n')

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
        response = recommended_places_details[['place_id', 'place_name', 'average_rating', 'granular_category', 'lat', 'lng']].to_dict(orient='records')

        print("Response:", response)  # Log the response for debugging
        return jsonify(response)

    except Exception as e:
        # Log the error and return a JSON response
        print(f"Error occurred: {str(e)}")  # Log the error for debugging
        return jsonify({"error": "An unexpected error occurred."}), 500



if __name__ == '__main__':
    app.run(debug=True)
