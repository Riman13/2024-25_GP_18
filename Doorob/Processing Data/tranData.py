import pandas as pd
import requests
import time

# Google Places API key
API_KEY = 'AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g'

# Function to get place_id from lat/lng using Google Places API Nearby Search
def get_place_id(lat, lng):
    url = f"https://maps.googleapis.com/maps/api/place/nearbysearch/json?location={lat},{lng}&radius=50&key={API_KEY}"
    response = requests.get(url)
    if response.status_code == 200:
        data = response.json()
        if 'results' in data and len(data['results']) > 0:
            return data['results'][0]['place_id']
        else:
            return None
    else:
        print(f"Error fetching data for lat: {lat}, lng: {lng}")
        return None
    

# Function to process all rows from the CSV file, add place_id, and save new CSV
def process_all_rows(input_file, output_file):
    # Read the CSV file
    df = pd.read_csv(input_file)

    # Ensure lat and lng columns are present
    if 'lat' not in df.columns or 'lng' not in df.columns:
        print("The CSV file must contain 'lat' and 'lng' columns")
        return

    # Add a new column for place_id
    df['place_id'] = None

    # Process all rows and get place_id for each lat/lng
    for i, row in df.iterrows():
        lat = row['lat']
        lng = row['lng']
        place_id = get_place_id(lat, lng)
        df.at[i, 'place_id'] = place_id
        
        # Print progress every 500 rows
        if (i + 1) % 100 == 0:  # +1 because i is 0-indexed
            print(f"Processed {i + 1} rows.")

        # To avoid exceeding API limits, wait a little between requests
       # time.sleep(0.1)  # Adjust sleep time as per your API limits

    # Save the updated dataframe to a new CSV file
    df.to_csv(output_file, index=False)
    print(f"Updated CSV with place_id saved to {output_file}")

# Main script to run the process
if __name__ == '__main__':
    input_csv = r"C:\Users\riman\OneDrive\Documents\riyadh_places_keg.csv"  # Your input CSV file
    output_csv = r"C:\Users\riman\OneDrive\Documents\updated_places.csv"  # Output file to save the CSV with place_id
    process_all_rows(input_csv, output_csv)