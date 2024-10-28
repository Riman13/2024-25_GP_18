

<?php
// Replace 'YOUR_GOOGLE_API_KEY' with your actual Google API Key
$apiKey = 'AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g';

// Retrieve the latitude and longitude from the query parameters
$latitude = isset($_GET['lat']) ? $_GET['lat'] : null;
$longitude = isset($_GET['lng']) ? $_GET['lng'] : null;

if ($latitude && $longitude) {
    // Define the Google Places Nearby Search API endpoint
    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location={$latitude},{$longitude}&radius=500&key={$apiKey}";

    // Make the API request
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    // Check if the request was successful and if photos are available
    $photos = [];
    if ($data['status'] === 'OK' && !empty($data['results'])) {
        foreach ($data['results'] as $place) {
            if (isset($place['photos']) && is_array($place['photos'])) {
                // Add each photo reference to the photos array
                foreach ($place['photos'] as $photo) {
                    $photos[] = [
                        'photo_reference' => $photo['photo_reference']
                    ];
                }
                // Exit the loop once we get photos from one place
                break;
            }
        }
    }

    // Send the photos array as a JSON response
    echo json_encode(['photos' => $photos]);
} else {
    // Return an error if lat/lng are not provided
    echo json_encode(['error' => 'Latitude and longitude are required']);
}
?>
