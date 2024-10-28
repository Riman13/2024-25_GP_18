import pandas as pd
from googletrans import Translator

# Load the CSV file
file_path = r"C:\Users\riman\Downloads\cleaned_riyadh_places_dataset111222.csv"
df = pd.read_csv(file_path)



# Initialize the translator
translator = Translator()

# Translate the names from Arabic to English
df['name_en'] = df['name'].apply(lambda x: translator.translate(x, src='ar', dest='en').text)

# Save to a new CSV file
output_file_path = r"C:\Users\riman\Downloads\translated_riyadh_places_dataset4.csv"
df.to_csv(output_file_path, index=False)

print("Translation completed and saved to:", output_file_path)
