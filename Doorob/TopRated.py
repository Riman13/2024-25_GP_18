
from flask import Flask, jsonify, request
from flask_cors import CORS
import pandas as pd
import logging

# Initialize Flask app
app = Flask(__name__)
CORS(app)

# Setup logging for debugging
logging.basicConfig(level=logging.DEBUG)

# Assuming place_data contains your places and their ratings
 # Replace with actual data file
place_data = pd.read_excel('DATADATA.xlsx')

# A dictionary for place names, categories, etc. (assuming you have this data)
category_dict = dict(zip(place_data['ID'], place_data['Category']))
rating_dict = dict(zip(place_data['ID'], place_data['Ratings']))

@app.route('/api/top_rated', methods=['GET'])
def top_rated():
    try:
        # Number of results (you can make this dynamic if needed)
        top_n = 10
        
        # Sort the places by their ratings
        top_places = place_data.sort_values(by='Ratings', ascending=False).head(top_n)
        
        # Prepare the results
        results = []
        for _, row in top_places.iterrows():
            place_id = int(row['ID'])
            place_name = row['Name']
            average_rating = row['Ratings']
            granular_category = category_dict.get(place_id, "Unknown")
            
            results.append({
                'place_id': place_id,
                'place_name': place_name,
                'average_rating': average_rating,
                'granular_category': granular_category,
                'lat': row['lat'],
                'lng': row['lng']
            })
        
        # Return the results as a JSON response
        return jsonify(results)

    except Exception as e:
        logging.error(f"Error in /sapi/top_rated: {e}")
        return jsonify({'error': 'Could not retrieve top-rated places'}), 500

if __name__ == '__main__':
    # Run the Flask app
    app.run(debug=True, port=5004)
