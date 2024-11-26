import logging

import numpy as np
import pandas as pd
from flask import Flask, jsonify, request
from flask_cors import CORS
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import MinMaxScaler
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

# Preprocess datasets
places_df = places_df.rename(columns={'id': 'place_id'})
places_df = places_df[['place_id', 'place_name', 'average_rating', 'granular_category']].dropna()
places_df['place_id'] = places_df['place_id'].astype(int)
places_df['average_rating'] = pd.to_numeric(places_df['average_rating'], errors='coerce').fillna(3.0)

# Add review count
places_df['review_count'] = ratings_df.groupby('place_id')['rating'].transform('count').fillna(0)

# Normalize numerical features
scaler = MinMaxScaler()
places_df[['average_rating', 'review_count']] = scaler.fit_transform(places_df[['average_rating', 'review_count']])

# Prepare data for Vowpal Wabbit
def prepare_vw_data_based_on_high_ratings(ratings_df, places_df, user_id):
    """
    Prepare the dataset for Vowpal Wabbit based on high ratings (>= 4) and favorite categories of the user.
    """
    data = []
    
    # Filter places rated highly by the user (rating >= 4)
    user_rated_places = ratings_df[(ratings_df['user_id'] == user_id) & (ratings_df['rating'] >= 4)]
    
    if user_rated_places.empty:
        print(f"User {user_id} has not rated any places highly.")
        return None

    # Merge user ratings with places to get features
    user_rated_places = user_rated_places.merge(places_df, on='place_id')

    # Extract the most frequent categories the user prefers
    user_preference_categories = user_rated_places['granular_category'].value_counts().head(3).index.tolist()

    # Create the Vowpal Wabbit input for each rated place
    for _, row in user_rated_places.iterrows():
        weighted_avg_rating = row['average_rating'] * 2  # Weighted by rating
        weighted_review_count = row['review_count'] * 1.5  # Weighted by review count
        interaction = weighted_avg_rating * weighted_review_count  # Interaction feature

        # Build the features string for Vowpal Wabbit
        features = (
            f"|features avg_rating:{weighted_avg_rating} "
            f"review_count:{weighted_review_count} "
            f"interaction:{interaction} "
        )

        # Include the top categories the user prefers as additional features
        for category in user_preference_categories:
            features += f"category_{category}:1 "  # One-hot encoding for top categories

        label = row['rating']
        data.append(f"{label} '{row['user_id']}_{row['place_id']} {features}")
    
    return data, user_preference_categories  # Return user preference categories as well

# Prepare Vowpal Wabbit data for a specific user
user_id = 999 # Replace with an actual user ID
vw_data, user_preference_categories = prepare_vw_data_based_on_high_ratings(ratings_df, places_df, user_id)

# Train Vowpal Wabbit model
if vw_data:
    vw_model = pyvw.vw("--loss_function squared --l2 0.001 --learning_rate 0.3 --bit_precision 25")
    
    # Train the model with the prepared data
    for row in vw_data:
        vw_model.learn(row)

# Evaluate the model on the test data
def evaluate_vw_model(model, test_data):
    """
    Evaluate the model using Mean Squared Error (MSE) and Root Mean Squared Error (RMSE).
    """
    predictions = []
    actuals = []
    
    for row in test_data:
        parts = row.split(" ", 1)
        actual = float(parts[0])  # The true rating
        actuals.append(actual)
        prediction = model.predict(parts[1])  # The predicted rating
        predictions.append(prediction)
    
    # Calculate MSE and RMSE
    mse = np.mean((np.array(actuals) - np.array(predictions)) ** 2)
    rmse = np.sqrt(mse)
    
    print(f"Mean Squared Error (MSE): {mse:.4f}")
    print(f"Root Mean Squared Error (RMSE): {rmse:.4f}")
    
    return mse, rmse

# Split data for testing (using a smaller portion of the data for testing)
train_data, test_data = train_test_split(vw_data, test_size=0.05, random_state=42)

# Train the model on the training data
for row in train_data:
    vw_model.learn(row)

# Now, evaluate the model on the test data
evaluate_vw_model(vw_model, test_data)

# Recommend places based on content-based filtering using Vowpal Wabbit
def recommend_places_based_on_high_ratings_and_category(user_id, places_df, ratings_df, model, top_k=5, user_preference_categories=None):
    """
    Recommend places for a given user based on the categories of places they rated highly.
    """
    if user_preference_categories is None:
        return []

    rated_places = ratings_df[ratings_df['user_id'] == user_id]['place_id'].tolist()
    unrated_places = places_df[~places_df['place_id'].isin(rated_places)]
    
    recommendations = []

    for _, place in unrated_places.iterrows():
        weighted_avg_rating = place['average_rating'] * 2  # Weighted by rating
        weighted_review_count = place['review_count'] * 1.5  # Weighted by review count
        interaction = weighted_avg_rating * weighted_review_count  # Interaction feature

        # Build features string for Vowpal Wabbit
        features = (
            f"|features avg_rating:{weighted_avg_rating} "
            f"review_count:{weighted_review_count} "
            f"interaction:{interaction} "
        )

        # Include the top categories the user prefers as additional features
        for category in user_preference_categories:
            features += f"category_{category}:1 "
        
        score = model.predict(features)  # Get prediction score
        recommendations.append((place['place_name'], score))

    # Sort recommendations based on score and return top K
    recommendations = sorted(recommendations, key=lambda x: x[1], reverse=True)[:top_k]
    return recommendations

# Get top 5 recommendations for a user
recommended_places = recommend_places_based_on_high_ratings_and_category(user_id, places_df, ratings_df, vw_model, top_k=5, user_preference_categories=user_preference_categories)

# Display recommended places
print("Recommended Places:")
for place, score in recommended_places:
    print(f"Place: {place}, Score: {score:.4f}")
