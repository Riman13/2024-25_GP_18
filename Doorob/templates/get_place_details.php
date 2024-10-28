<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php'; // Include your database connection file

if (isset($_GET['id'])) {
    $placeId = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT place_name,  granular_category, average_rating, place_id, lat, lng FROM riyadhplaces_doroob WHERE id = ?");
    $stmt->bind_param("i", $placeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
   
    if ($result->num_rows > 0) {
        $placeDetails = $result->fetch_assoc();
        // Fetch photos using the place_id
        $photos = fetchPhotos($placeDetails['place_id']);
        
        // Add photos to the place details array
        $placeDetails['photos'] = $photos;
        
        // Return the combined data as JSON
        echo json_encode($placeDetails);
    } //else {
        //echo json_encode(["error" => "Place not found"]);
   // }
    
    $stmt->close();
}
$conn->close();


// Function to fetch photos using the place_id
function fetchPhotos($placeId) {
    // Here, you'll need to replace 'YOUR_API_KEY' with your actual Google Places API key
    $apiKey = 'AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g'; 
    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=photos&key={$apiKey}";

    // Fetch the photo data from Google Places API
    $response = file_get_contents($url);
    $photoData = json_decode($response, true);

    if (isset($photoData['result']['photos'])) {
        return $photoData['result']['photos'];
    } else {
        return [];
    }
}


// Function to fetch a new place_id based on latitude and longitude
function fetchNewPlaceIdByLatLng($lat, $lng) {
    $apiKey = 'AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g';
    $radius = 500; // Adjust as needed
    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location={$lat},{$lng}&radius={$radius}&key={$apiKey}";

    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data['status'] === 'OK' && !empty($data['results'])) {
        return $data['results'][0]['place_id']; // Return the first place_id found
    }
    return null;
}