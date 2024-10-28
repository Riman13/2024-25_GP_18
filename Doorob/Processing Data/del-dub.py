import pandas as pd

def remove_duplicates(input_file, output_file):
    # Load the CSV file into a DataFrame
    df = pd.read_csv(input_file)
    
    # Drop duplicate rows based on the 'place_id' column
    df_cleaned = df.drop_duplicates(subset='place_id', keep='first')
    
    # Save the cleaned DataFrame to a new CSV file
    df_cleaned.to_csv(output_file, index=False)
    print(f"Duplicates removed. Cleaned data saved to {output_file}")

# Example usage with your specified file path
input_file = r"C:\Users\riman\Downloads\riyadh_places_dataset (37).csv"
output_file = r"C:\Users\riman\Downloads\cleaned_riyadh_places_dataset111222.csv"
remove_duplicates(input_file, output_file)
