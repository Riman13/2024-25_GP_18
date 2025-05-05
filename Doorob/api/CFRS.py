import logging
import sys
 
import numpy as np
import pandas as pd
# Import pymysql at the top of your script:
import pymysql
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
from sklearn.metrics import roc_auc_score
from sklearn.preprocessing import minmax_scale
from flask import Blueprint, jsonify, render_template, request

#app = Flask(__name__)
#CORS(app)  # Enable CORS for all routes

# Initialize the Blueprint
recommendations_bp = Blueprint('recommendations', __name__, url_prefix='/recommendations')

# Top K items to recommend
TOP_K = 5

TEST_SIZE = 0.2 # 20% for testing, 80% for training

RANDOM_SEED = 42

PLACES_DATA_PATH = 'DATADATA.csv'          
RATINGS_DATA_PATH = 'modified_ratings.csv' 

# Load your datasets
places_df = pd.read_csv(PLACES_DATA_PATH)

# Define a function to establish a connection to your MySQL database:
def get_db_connection():
    return pymysql.connect(
        host="Doroob.mysql.pythonanywhere-services.com",  
        user="Doroob",       
        password="RASL1234",   
        database="Doroob$doroob", 
        cursorclass=pymysql.cursors.DictCursor
    )

# Define a function to fetch ratings from MySQL:
def fetch_mysql_ratings():
    connection = get_db_connection()
    try:
        with connection.cursor() as cursor:
            # Use correct column names from your MySQL database
            cursor.execute("SELECT UserID AS user_id, PlaceID AS place_id, Rating AS rating FROM ratings")
            ratings = cursor.fetchall()
            return pd.DataFrame(ratings)
    finally:
        connection.close()
# Fetch ratings from MySQL
mysql_ratings = fetch_mysql_ratings()

# Load CSV ratings
csv_ratings = pd.read_csv(RATINGS_DATA_PATH)

# Merge MySQL and CSV ratings
ratings_df = pd.concat([csv_ratings, mysql_ratings], ignore_index=True)

# Remove duplicates (if any) and keep the latest rating for each user-place pair
ratings_df = ratings_df.drop_duplicates(subset=['user_id', 'place_id'], keep='last')

# Preprocess the merged ratings
ratings_df['rating'] = ratings_df['rating'].astype(float)

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

# Replace 'N\\A' and any other non-numeric entries in 'average_rating' with 3, then convert to float
places_df['average_rating'] = pd.to_numeric(places_df['average_rating'], errors='coerce').fillna(3.0)


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
train, test = python_stratified_split(data, ratio=0.80, col_user="user_id", col_item="place_id", seed=42)

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

def calculate_auc(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating", col_prediction="prediction"):
    auc_scores = []
    for user in test[col_user].unique():
        # Get actual ratings for the user
        actual = test[test[col_user] == user].set_index(col_item)[col_rating]
        
        # Get predicted rankings for the user
        predicted = top_k[top_k[col_user] == user].set_index(col_item)[col_prediction]
        
        # Align actual and predicted indices (intersection only)
        common_items = actual.index.intersection(predicted.index)
        if len(common_items) > 0:
            y_true = (actual.loc[common_items] > positivity_threshold).astype(int)  # Binary relevance
            y_pred = predicted.loc[common_items]  # Predicted scores
            
            # Skip users with no diversity in actual ratings (e.g., all 0s or 1s)
            if len(set(y_true)) > 1:
                auc_scores.append(roc_auc_score(y_true, y_pred))
    
    # Return mean AUC across all users
    return np.mean(auc_scores) if auc_scores else 0


# Calculate AUC
eval_auc = calculate_auc(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating", col_prediction="prediction")

# Print all evaluation metrics including AUC
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
      "AUC:\t%f" % eval_auc,
      sep='\n')
@recommendations_bp.route('/<int:user_id>', methods=['GET'])
def get_recommendations_by_id(user_id):
    try:
        category_filter = request.args.get('category')
        # Fetch user recommendations
        # Fetch all recommendations for this user
        user_recommendations = model.recommend_k_items(pd.DataFrame({'user_id': [user_id]}), top_k=100, remove_seen=True)
        rated_place_ids = ratings_df[ratings_df['user_id'] == user_id]['place_id'].tolist()
        user_recommendations = user_recommendations[~user_recommendations['place_id'].isin(rated_place_ids)]


        if user_recommendations.empty:
            return jsonify({"error": "No recommendations found for this user."}), 404

        # Filter out NaN predictions
        user_recommendations = user_recommendations[user_recommendations['prediction'].notna()]

        # Merge with places DataFrame
        merged = user_recommendations.merge(places_df, on='place_id')

        # Apply category filter BEFORE slicing top-K
        if category_filter:
            merged = merged[merged['granular_category'].str.lower() == category_filter.lower()]

        # Take top-K after filtering
        final_result = merged.sort_values('prediction', ascending=False).head(TOP_K)

        # Prepare the response, excluding the score
        response = final_result[['place_id', 'place_name', 'average_rating', 'granular_category', 'lat', 'lng']].to_dict(orient='records')
        print("Response:", response)  # Log the response for debugging
        return jsonify(response)

    except Exception as e:
        # Log the error and return a JSON response
        print(f"Error occurred: {str(e)}")  # Log the error for debugging
        return jsonify({"error": "An unexpected error occurred."}), 500



#if __name__ == '__main__':
   # app.run(debug=True, threaded=True, port=5001)
