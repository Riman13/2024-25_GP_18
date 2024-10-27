<?php
include 'config.php'; // Include your database connection file

if (isset($_GET['id'])) {
    $placeId = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT place_name, categories, granular_category, average_rating FROM riyadhplaces_doroob WHERE id = ?");
    $stmt->bind_param("i", $placeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(["error" => "Place not found"]);
    }
    
    $stmt->close();
}
$conn->close();
?>
