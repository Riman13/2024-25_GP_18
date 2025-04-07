<?php
session_start();
$_SESSION['location'] = false;
$_SESSION['camera'] = false;
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT userID, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['userID'] = $user['userID'];

                    
        echo "<script>
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const data = {
                        user_id: " . $_SESSION['userID'] . ",
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
        
                    // Save location to APIs
                    saveLocationToAPIs(data).then(() => {
                        // Update PHP session for location
                        return fetch('update_session.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ status: 'location' })
                        });
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            // Now ask for camera access
                            return navigator.mediaDevices.getUserMedia({ video: true });
                        } else {
                            throw new Error('Failed to update location session');
                        }
                    })
                    .then(stream => {
                        // Stop camera immediately (no preview needed)
                        stream.getTracks().forEach(track => track.stop());
        
                        // Update session for camera
                        return fetch('update_session.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ status: 'camera' })
                        });
                    })
                    .then(res => res.json())
                    .then(res => {
                        window.location.href = 'homepage.php';
                    })
                    .catch(error => {
                        console.error('Error during process:', error);
                        window.location.href = 'homepage.php';
                    });
                },
                function (error) {
                    console.error('Location access denied:', error);
                    window.location.href = 'homepage.php';
                }
            );
        } else {
            alert('Geolocation is not supported by this browser.');
            window.location.href = 'homepage.php';
        }
        
        // Save to multiple APIs
        function saveLocationToAPIs(data) {
            const apiUrls = [
                'http://127.0.0.1:5002/api/save_location',
                'http://127.0.0.1:5003/api/save_location'
            ];
        
            const promises = apiUrls.map((url) => {
                return fetch(url, {
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
                })
                .catch((error) => {
                    console.error(`Error sending location data to \${url}:`, error);
                });
            });
        
            return Promise.all(promises);
        }
        </script>";
        
        exit();
    } else {
        header("Location: registration.php?error=Invalid email or password");
        exit();
    }
}
?>