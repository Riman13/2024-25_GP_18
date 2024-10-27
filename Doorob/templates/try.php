<?php
// Fetch place details and photos from the Google Places API if a place_id is provided
if (isset($_GET['place_id'])) {
    $place_id = $_GET['place_id'];
    $api_key = 'AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g'; // Replace with your Google API Key
    
    // Build the Google Places API request URL
    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$place_id}&key={$api_key}";

    // Initialize cURL to fetch the data
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Get the API response
    $response = curl_exec($ch);
    
    // Check for errors
    if ($response === false) {
        echo 'Curl error: ' . curl_error($ch);
    } else {
        // Set header as JSON and return the result to the front-end
        header('Content-Type: application/json');
        echo $response;
    }
    
    // Close cURL session
    curl_close($ch);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Places List</title>
    <style>
        /* Basic styling */
        body {
            font-family: Arial, sans-serif;
        }
        .place-list {
            margin: 20px;
        }
        .place-item {
            cursor: pointer;
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .photos {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>

<?php
// Path to your CSV file
$csvFile = 'C:/xampp/htdocs/2024-25_GP_18/Doorob/Doroob-Dataset.csv';

// Initialize an empty array to hold the places data
$places = [];

// Check if the CSV file exists and is readable
if (file_exists($csvFile) && is_readable($csvFile)) {
    if (($handle = fopen($csvFile, 'r')) !== false) {
        $header = fgetcsv($handle); // Get the header row
        
        while (($data = fgetcsv($handle)) !== false) {
            $place = array_combine($header, $data); // Map CSV data to the header columns
            $places[] = $place; // Add each place to the array
        }
        fclose($handle);
    }
} else {
    echo "CSV file not found or unreadable.";
}
?>

<!-- HTML for displaying places -->
<div class="place-list" id="place-list">
    <?php foreach ($places as $place): ?>
        <div class="place-item" onclick="fetchPhotos('<?php echo $place['place_id']; ?>')">
            <strong><?php echo $place['place_name']; ?></strong>
            <p>Rating: <?php echo $place['average_rating']; ?> (<?php echo $place['rate_count']; ?> reviews)</p>
        </div>
    <?php endforeach; ?>
</div>

<!-- Display Photos Section -->
<div id="photo-container" class="photos"></div>

<script>
    // Function to fetch photos via the server-side proxy
  
    async function fetchPhotos(placeId) {
    const url = `<?php echo $_SERVER['PHP_SELF']; ?>?place_id=${placeId}`;

    try {
        const response = await fetch(url);
        const data = await response.json();
        console.log(data); // Log the entire response to see what's returned
       if(data.result && data.result.photos && data.result.photos.length > 0) {
            const photoReference = data.result.photos[0].photo_reference;
            const apiKey = 'AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g';
            const photoUrl = `https://maps.googleapis.com/maps/api/place/photo?photoreference=${photoReference}&key=${apiKey}&maxwidth=400`;
            displayPhotos(photoUrl);
        } else {
           console.log('No photos found for this place.');
        }
    } catch (error) {
        console.error('Error fetching photos:', error);
    }
}


    // Function to display photos
    function displayPhotos(photoUrl) {
        const photoContainer = document.getElementById('photo-container');
        photoContainer.innerHTML = `<img src="${photoUrl}" alt="Place Photo" style="width: 100%;">`;
    }
</script>


</body>
</html>
