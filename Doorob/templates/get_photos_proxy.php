<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$placeId = $_GET['place_id'];
$apiKey = 'AIzaSyBKbwrFBautvuemLAp5-GpZUHGnR_gUFNs';

// URL to Google Places API
$url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=photos&key={$apiKey}";

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute and capture the response
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo json_encode(["error" => curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Decode the JSON to check for validity
$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["error" => "Invalid JSON response from Google API"]);
} else {
    // Re-encode the JSON response for consistency
    echo json_encode($data);
}


include 'config.php'; // Include your database connection file

if (isset($_GET['place_id'])) {
    $placeId = $_GET['place_id'];
    $apiKey = 'AIzaSyBKbwrFBautvuemLAp5-GpZUHGnR_gUFNs'; // Replace with your actual API key
    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&key={$apiKey}";

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    // Check if photos are available
    if (isset($data['result']['photos'])) {
        $photos = [];
        foreach ($data['result']['photos'] as $photo) {
            $photos[] = [
                'photo_reference' => $photo['photo_reference'],
                'width' => $photo['width'],
                'height' => $photo['height']
            ];
        }
        echo json_encode($photos);
    } else {
        echo json_encode(["error" => "No photos available"]);
    }
}

