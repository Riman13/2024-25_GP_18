<?php
header('Content-Type: application/json');

$placeId = $_GET['place_id'];
$apiKey = 'AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g';

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


function fetchPhotos($place_id, $lat = null, $lng = null) {
    $photos = fetchPhotosByPlaceId($place_id);

    if (!empty($photos)) {
        return $photos;
    }

    if ($lat && $lng) {
        $photos = fetchPhotosByLatLng($lat, $lng);
        if (!empty($photos)) {
            return $photos;
        }
    }

    if ($lat && $lng) {
        $related_place_id = findRelatedPlaceIdByLatLng($lat, $lng);
        if ($related_place_id) {
            $photos = fetchPhotosByPlaceId($related_place_id);
            if (!empty($photos)) {
                return $photos;
            }
        }
    }

    return [];
}

function fetchPhotosByPlaceId($place_id) {
    $apiKey = 'AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g';
    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$place_id&fields=photo&key=$apiKey";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    return $data['result']['photos'] ?? [];
}

function fetchPhotosByLatLng($lat, $lng) {
    $apiKey = 'AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g';
    $radius = 1000;
    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=$lat,$lng&radius=$radius&key=$apiKey";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    return $data['results'][0]['photos'] ?? [];
}

function findRelatedPlaceIdByLatLng($lat, $lng) {
    $apiKey = 'AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g';
    $radius = 1000;
    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=$lat,$lng&radius=$radius&key=$apiKey";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    return $data['results'][0]['place_id'] ?? null;
}

