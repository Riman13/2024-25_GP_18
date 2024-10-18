# We used thhis code to compine the 2 dataset into one csv file 

import pandas as pd


file1 = r'C:\Users\riman\OneDrive\Documents\updated_places.csv'
file2 = r'C:\Users\riman\Downloads\combined_file (5).csv'


df1 = pd.read_csv(file1)
df2 = pd.read_csv(file2)

desired_types = ['zoo', 'shopping_mall', 'park', 'hotel', 'art_gallery']
df2_filtered = df2[df2['type'].isin(desired_types)]

df2_filtered['granular_category'] = df2_filtered['type']
df2_filtered['place_name'] = df2_filtered['name']
df2_filtered['average_rating'] = df2_filtered['rating']
df2_filtered['lat'] = df2_filtered['lat']
df2_filtered['lng'] = df2_filtered['lng']
df2_filtered['place_id'] = df2_filtered['place_id']

if 'rate_count' in df1.columns:
    df2_filtered['rate_count'] = 'N/A'
if 'is_restaurant' in df1.columns:
    df2_filtered['is_restaurant'] = 'N/A'
if 'categories' in df1.columns:
    df2_filtered['categories'] = 'N/A'

matching_columns = [col for col in df1.columns if col in df2_filtered.columns]
df2_filtered = df2_filtered[matching_columns]

df_combined = pd.concat([df1, df2_filtered], ignore_index=True)

output_file = r'C:\Users\riman\Documents\combined_updated_places.csv'
df_combined.to_csv(output_file, index=False)

print(f"Combined file saved to {output_file}")
