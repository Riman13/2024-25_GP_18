import logging
import sys

import numpy as np
import pandas as pd
import pymysql
from flask import Blueprint, jsonify, request
from flask_cors import CORS
from recommenders.datasets.python_splitters import python_stratified_split
from recommenders.evaluation.python_evaluation import (exp_var, logloss, mae,
                                                       map, ndcg_at_k,
                                                       precision_at_k,
                                                       recall_at_k, rmse,
                                                       rsquared)
from recommenders.models.sar import SAR
from recommenders.utils.python_utils import binarize
from sklearn.metrics import roc_auc_score
from sklearn.preprocessing import minmax_scale

# إعداد الـ logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s %(levelname)-8s %(message)s')

# إنشاء Blueprint
recommendations_bp = Blueprint('recommendations', __name__, url_prefix='/recommendations')

TOP_K = 3
TEST_SIZE = 0.2
RANDOM_SEED = 42

PLACES_DATA_PATH = '/home/Doroob/2024-25_GP_18/Doorob/DATADATA.csv'
RATINGS_DATA_PATH = '/home/Doroob/2024-25_GP_18/Doorob/modified_ratings.csv'

places_df = pd.read_csv(PLACES_DATA_PATH)

def get_db_connection():
    return pymysql.connect(
        host="77.37.35.85",
        user="u783774210_mig",
        password="g]I/EHm=v6",
        database="u783774210_mig",
        cursorclass=pymysql.cursors.DictCursor
    )
import mysql.connector
from mysql.connector import Error

try:
    connection = mysql.connector.connect(
        host='your_hostinger_mysql_host',      # e.g., 'mysql.hostinger.com' or an IP
        database='doroob_database',            # your DB name
        user='your_db_username',               # your DB user
        password='your_db_password'            # your DB password
    )

    if connection.is_connected():
        print("Connected successfully to Doroob DB!")
        cursor = connection.cursor()
        cursor.execute("SHOW TABLES;")
        for table in cursor.fetchall():
            print(table)

except Error as e:
    print(f"Error while connecting: {e}")

finally:
    if 'connection' in locals() and connection.is_connected():
        connection.close()
        print("Connection closed.")
        
def fetch_mysql_ratings():
    connection = get_db_connection()
    try:
        with connection.cursor() as cursor:
            cursor.execute("SELECT UserID AS user_id, PlaceID AS place_id, Rating AS rating FROM ratings")
            ratings = cursor.fetchall()
            return pd.DataFrame(ratings)
    finally:
        connection.close()

mysql_ratings = fetch_mysql_ratings()
csv_ratings = pd.read_csv(RATINGS_DATA_PATH)

ratings_df = pd.concat([csv_ratings, mysql_ratings], ignore_index=True)
ratings_df = ratings_df.drop_duplicates(subset=['user_id', 'place_id'], keep='last')
ratings_df['rating'] = ratings_df['rating'].astype(float)

places_df = places_df.rename(columns={'id': 'place_id'})
essential_place_columns = ['place_id', 'place_name', 'average_rating', 'granular_category', 'lat', 'lng']
places_df = places_df.dropna(subset=essential_place_columns)

essential_rating_columns = ['user_id', 'place_id', 'rating']
ratings_df = ratings_df.dropna(subset=essential_rating_columns)

places_df['place_id'] = places_df['place_id'].astype(int)
ratings_df['user_id'] = ratings_df['user_id'].astype(int)
ratings_df['place_id'] = ratings_df['place_id'].astype(int)

places_df['average_rating'] = pd.to_numeric(places_df['average_rating'], errors='coerce').fillna(3.0)
places_df['place_name'] = places_df['place_name'].astype(str)
places_df['average_rating'] = places_df['average_rating'].astype(float)
places_df['granular_category'] = places_df['granular_category'].astype(str)
places_df['lat'] = places_df['lat'].astype(float)
places_df['lng'] = places_df['lng'].astype(float)

ratings_df['rating'] = ratings_df['rating'].astype(float)
ratings_df = ratings_df.drop_duplicates(subset=['user_id', 'place_id'])

data = ratings_df
data["rating"] = data["rating"].astype(np.float32)

train, test = python_stratified_split(data, ratio=0.80, col_user="user_id", col_item="place_id", seed=42)

model = SAR(
    col_user="user_id",
    col_item="place_id",
    col_rating="rating",
    similarity_type="jaccard",
    normalize=True
)

model.fit(train)
top_k = model.recommend_k_items(test, top_k=TOP_K, remove_seen=True)

eval_map = map(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating", k=TOP_K)
eval_ndcg = ndcg_at_k(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating", k=TOP_K)
eval_precision = precision_at_k(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating", k=TOP_K)
eval_recall = recall_at_k(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating", k=TOP_K)
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
        actual = test[test[col_user] == user].set_index(col_item)[col_rating]
        predicted = top_k[top_k[col_user] == user].set_index(col_item)[col_prediction]
        common_items = actual.index.intersection(predicted.index)
        if len(common_items) > 0:
            y_true = (actual.loc[common_items] > positivity_threshold).astype(int)
            y_pred = predicted.loc[common_items]
            if len(set(y_true)) > 1:
                auc_scores.append(roc_auc_score(y_true, y_pred))
    return np.mean(auc_scores) if auc_scores else 0

eval_auc = calculate_auc(test, top_k, col_user="user_id", col_item="place_id", col_rating="rating", col_prediction="prediction")

# استخدام logging بدلاً من print
logging.info(
    "Model Evaluation:\n"
    f"Top K: {TOP_K}\n"
    f"MAP: {eval_map:.6f}\n"
    f"NDCG: {eval_ndcg:.6f}\n"
    f"Precision@K: {eval_precision:.6f}\n"
    f"Recall@K: {eval_recall:.6f}\n"
    f"RMSE: {eval_rmse:.6f}\n"
    f"MAE: {eval_mae:.6f}\n"
    f"R2: {eval_rsquared:.6f}\n"
    f"Exp var: {eval_exp_var:.6f}\n"
    f"Logloss: {eval_logloss:.6f}\n"
    f"AUC: {eval_auc:.6f}"
)

@recommendations_bp.route('/<int:user_id>', methods=['GET'])
def get_recommendations_by_id(user_id):
    try:
        category_filter = request.args.get('category')
        user_recommendations = model.recommend_k_items(pd.DataFrame({'user_id': [user_id]}), top_k=100, remove_seen=True)

        rated_place_ids = ratings_df[ratings_df['user_id'] == user_id]['place_id'].tolist()
        user_recommendations = user_recommendations[~user_recommendations['place_id'].isin(rated_place_ids)]

        if user_recommendations.empty:
            return jsonify({"error": "No recommendations found for this user."}), 404

        user_recommendations = user_recommendations[user_recommendations['prediction'].notna()]
        merged = user_recommendations.merge(places_df, on='place_id')

        if category_filter:
            merged = merged[merged['granular_category'].str.lower() == category_filter.lower()]

        final_result = merged.sort_values('prediction', ascending=False).head(TOP_K)

        response = final_result[['place_id', 'place_name', 'average_rating', 'granular_category', 'lat', 'lng']].to_dict(orient='records')
        
        logging.info(f"Response: {response}")
        return jsonify(response)

    except Exception as e:
        logging.error(f"Error occurred: {str(e)}")
        return jsonify({"error": "An unexpected error occurred."}), 500
