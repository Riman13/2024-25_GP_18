# Read the CSV file, skipping the first 8838 rows (since row index starts at 0) and encoding='utf-8-sig'


import pandas as pd


file_path = r'C:\Users\riman\Downloads\translated_riyadh_places_dataset.csv'

df = pd.read_csv(file_path, encoding='utf-8-sig')


print(df.head())

df.to_csv(file_path, index=False, encoding='utf-8-sig')

print(f"File processed and saved successfully starting from row 8839 at {file_path}")
