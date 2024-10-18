# We used this code to get place_id from lat/lng using Google Places API Nearby Search


import pandas as pd
import requests
import time

API_KEY = 'AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g'

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
    

def process_all_rows(input_file, output_file):
    df = pd.read_csv(input_file)

    if 'lat' not in df.columns or 'lng' not in df.columns:
        print("The CSV file must contain 'lat' and 'lng' columns")
        return

    df['place_id'] = None

    for i, row in df.iterrows():
        lat = row['lat']
        lng = row['lng']
        place_id = get_place_id(lat, lng)
        df.at[i, 'place_id'] = place_id
        
        if (i + 1) % 100 == 0:  
            print(f"Processed {i + 1} rows.")

       


    df.to_csv(output_file, index=False)
    print(f"Updated CSV with place_id saved to {output_file}")


if __name__ == '__main__':
    input_csv = r"C:\Users\riman\OneDrive\Documents\riyadh_places_keg.csv"  
    output_csv = r"C:\Users\riman\OneDrive\Documents\updated_places.csv"  
    process_all_rows(input_csv, output_csv)