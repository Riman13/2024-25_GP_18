import logging

import numpy as np
import pandas as pd
from flask import Flask, jsonify, request
from flask_cors import CORS
from geopy.distance import geodesic
from sklearn.decomposition import NMF
# ======== Part 3: Content-Based Recommendations ========
from sklearn.feature_extraction.text import CountVectorizer, TfidfVectorizer
from sklearn.metrics import mean_absolute_error, mean_squared_error
from sklearn.metrics.pairwise import cosine_similarity
from sklearn.neighbors import NearestNeighbors
from sklearn.preprocessing import MinMaxScaler
from surprise import SVD, Dataset, Reader, accuracy
from surprise.model_selection import cross_validate, train_test_split

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
def evaluate_content_based(user_id, top_k=5, weight_similarity=0.7, weight_rating=0.3):
    """
    Evaluate content-based recommendation system by identifying the user's most preferred category
    based on the number of 5-star ratings.
    """
    #Filter places rated 5 by the user
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

    # Return recommendations and evaluation metrics
    return recommended_places, precision, recall, f1_score, map_score
user_id = 999
top_k = 5
recommended_places, precision, recall, f1_score, map_score = evaluate_content_based(user_id, top_k=top_k)

# Display recommended places
print("Recommended Places:")
print(recommended_places[['place_name', 'combined_score', 'similarity_score', 'average_rating']])
