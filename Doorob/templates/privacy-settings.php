<?php
include 'session.php';
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Settings</title>
    <link rel="stylesheet" href="styles/privacy-settings.css">
</head>
<body>
    <div class="privacy-settings">
        <h2>Privacy Settings</h2>

        <div class="setting-item">
            <label for="locationToggle">Allow Location Access</label>
            <button id="locationBtn" class="toggle-btn">
                <?php echo htmlspecialchars(isset($_SESSION['Location_Status']) && $_SESSION['Location_Status'] === 'Allow' 
                    ? 'Location Allowed' 
                    : 'Turn On'); ?>
            </button>
        </div>

        <div class="setting-item">
            <label for="cameraToggle">Allow Camera Access</label>
            <button id="cameraBtn" class="toggle-btn">Turn On</button>
        </div>
    </div>

    <script>
        const locationBtn = document.getElementById('locationBtn');
        let cameraStream;
        let locationEnabled = false; // Track the location state

        // Function to save location to multiple APIs
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
                        throw new Error(`Failed to save location to ${url}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then((result) => {
                    console.log(`Location saved to ${url}:`, result);
                })
                .catch((error) => {
                    console.error(`Error sending location data to ${url}:`, error);
                });
            });
        }

        // Function to handle geolocation errors
        function handleGeolocationError(error) {
            let message = '';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Location access denied.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Location unavailable. Please try again later.';
                    break;
                case error.TIMEOUT:
                    message = 'Request for location timed out. Please try again.';
                    break;
                default:
                    message = 'An unknown error occurred while retrieving location.';
                    break;
            }
            alert(message);
        }

        // Location Button Event Listener
        locationBtn.addEventListener('click', function () {
            if (!locationEnabled) {
                // Turn location on
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const data = {
                                user_id: <?php echo json_encode($_SESSION['userID'] ?? null); ?>,
                                lat: position.coords.latitude,
                                lng: position.coords.longitude,
                            };

                            saveLocationToAPIs(data);

                            locationEnabled = true; // Update state
                            locationBtn.innerText = 'Location Allowed';
                            alert('Your location has been saved successfully!');
                        },
                        (error) => {
                            handleGeolocationError(error);
                        }
                    );
                } else {
                    alert('Geolocation is not supported by your browser.');
                }
            } else {
                // Turn location off
                locationEnabled = false; // Update state
                locationBtn.innerText = 'Turn On';
                alert('Location access has been disabled.');
            }
        });

        // Camera Button Event Listener
        const cameraBtn = document.getElementById('cameraBtn');

        cameraBtn.addEventListener('click', function() {
            if (cameraBtn.innerText === 'Turn On') {
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then((stream) => {
                        cameraStream = stream;
                        cameraBtn.innerText = 'Turn Off';
                        cameraBtn.classList.add('active');
                        cameraBtn.classList.add('pressed');
                    })
                    .catch(() => {
                        alert('Camera access denied or not supported.');
                        cameraBtn.innerText = 'Turn On';
                    });
            } else {
                if (cameraStream) {
                    cameraStream.getTracks().forEach(track => track.stop());
                    cameraStream = null;
                }
                cameraBtn.innerText = 'Turn On';
                cameraBtn.classList.remove('active');
                cameraBtn.classList.remove('pressed');
            }
        });
    </script>
</body>
</html>