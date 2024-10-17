import pandas as pd

# Specify the file path
file_path = r'C:\Users\riman\Desktop\combined_updated_places.csv'

# Read the CSV file, skipping the first 8838 rows (since row index starts at 0)
df = pd.read_csv(file_path, encoding='utf-8-sig', skiprows=8838)

# Display the first few rows after skipping
print(df.head())

# Optionally, save it back after processing or modifications (if needed)
df.to_csv(file_path, index=False, encoding='utf-8-sig')

print(f"File processed and saved successfully starting from row 8839 at {file_path}")
