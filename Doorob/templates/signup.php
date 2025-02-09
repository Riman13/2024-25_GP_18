<?php
session_start();
include 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['eml'];
    $password = $_POST['pass'];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['pass'];
    
        // Regular Expression for Validation
        $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/';
    
        if (!preg_match($passwordRegex, $password)) {
            header('Location: registration.php?error=Password does not meet the criteria');
            exit();
        } else {
            // Save the password securely (use hashing)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Save $hashedPassword to the database
        }
    }

    $checkEmailQuery = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkEmailQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header("Location: registration.php?error=Email already exists. Please try logging in.");
        exit();
    } else {
       
        $insertQuery = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sss", $name, $email, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['userID'] = $conn->insert_id;
            $_SESSION['user_type'] = 'user';  
            $_SESSION['new_user'] = $name;
            echo "<script>
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const data = {
                            user_id: " . $_SESSION['userID'] . ",
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
        
                        // Call the function to save location to all APIs
                        saveLocationToAPIs(data);
        
                        // Redirect to homepage after saving location
                        window.location.href = 'homepage.php';
                    },
                    function (error) {
                        console.error('Location access denied:', error);
                        alert('Location access denied. Redirecting to homepage.');
                        window.location.href = 'homepage.php';
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
                window.location.href = 'homepage.php';
            }
        
            // The saveLocationToAPIs function
            function saveLocationToAPIs(data) {
                const apiUrls = [
                    'http://127.0.0.1:5002/api/save_location',
                    'http://127.0.0.1:5003/api/save_location'
                ];
        
                apiUrls.forEach((url) => {
                    fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data),
                    })
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error(`Failed to save location to \${url}: \${response.statusText}`);
                            }
                            return response.json();
                        })
                        .then((result) => {
                            console.log(`Location successfully saved to \${url}:`, result);
                                                    fetch('update_session.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status: 'Allow', lat: data.lat, lng: data.lng }),
                    })
                        })
                        .catch((error) => {
                            console.error(`Error sending location data to \${url}:`, error);
                        });
                });
            }
        </script>";

 

        
            exit();
        } else {
            header("Location: registration.php?error=There was an error signing up. Please try again.");
            exit();
        }
    }
}
?>