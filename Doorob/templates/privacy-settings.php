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
    <button id="locationBtn" class="toggle-btn"></button>
</div>


        <div class="setting-item">
            <label for="cameraToggle">Allow Camera Access</label>
            <button id="cameraBtn" class="toggle-btn"></button>
        </div>
    </div>

    <script>

        
        // Initialize locationEnabled state based on PHP session value
        let locationEnabled = <?php echo json_encode(isset($_SESSION['location']) && $_SESSION['location'] === true); ?>;
        const locationBtn = document.getElementById('locationBtn');

        // Update the button text on page load
        if (locationEnabled) {
        locationBtn.innerText = 'Turn Off';
        locationBtn.classList.add('active');
        locationBtn.classList.add('pressed');
        } else {
        locationBtn.innerText = 'Turn On';}


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

                    // Update session on the server side
                    fetch('update_session.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status: 'location' })
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            locationEnabled = true; // Update the state
                            locationBtn.innerText = 'Turn Off'; // Update button text
                            locationBtn.classList.add('active');
                            locationBtn.classList.add('pressed');
                            alert('Your location has been saved successfully!');
                        } else {
                            console.error('Failed to update location session.');
                        }
                    });
                },
                (error) => {
                    handleGeolocationError(error);
                }
            );
        } else {
            alert('Geolocation is not supported by your browser.');
        }
    } else {
        // Turn location off: we can't disable location from JavaScript directly, so we clear the session on the server side
        locationEnabled = false; // Update state


        // Optionally, clear location info from session on the server side
        fetch('update_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: 'location' })
        })
        .then(res => res.json())
        .then(res => {
            locationBtn.innerText = 'Turn On'; // Update button text
            locationBtn.classList.remove('active'); // Remove active class
            locationBtn.classList.remove('pressed'); // Remove pressed class

            alert('Location access has been disabled.');
        })
        .catch(err => {
            console.error('Error while disabling location:', err);
        });
    }
});

// Handle geolocation errors
function handleGeolocationError(error) {
    if (error.code === error.PERMISSION_DENIED) {
        alert('Location permission denied. Please enable location access in your browser settings.');
    } else {
        alert('An error occurred while retrieving your location.');
    }
}

        // Camera Button Event Listener
        const cameraBtn = document.getElementById('cameraBtn');
        let cameraStream;


// Update the camera button state based on session value (from PHP)
let cameraEnabled = <?php echo json_encode(isset($_SESSION['camera']) && $_SESSION['camera'] === true); ?>;

if (cameraEnabled) {
    cameraBtn.innerText = 'Turn Off';
    cameraBtn.classList.add('active');
    cameraBtn.classList.add('pressed');
} else {
    cameraBtn.innerText = 'Turn On';
}

        cameraBtn.addEventListener('click', function() {
            if (cameraBtn.innerText === 'Turn On') {
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then((stream) => {
                        cameraStream = stream;
                                        // Update session on the server to reflect camera access is granted
                fetch('update_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: 'camera' })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        cameraBtn.innerText = 'Turn Off'; // Update button text
                        cameraBtn.classList.add('active'); // Add active class for UI feedback
                        cameraBtn.classList.add('pressed'); // Add pressed class for UI feedback
                        alert('Camera is now enabled.');
                    } else {
                        console.error('Failed to update camera session.');
                    }
                });

            })
                    .catch(() => {
                        alert('Camera access denied or not supported.');
                        cameraBtn.innerText = 'Turn On';
                    });
                } else {
        // Turn camera off: stop the camera stream
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }

        // Update session on the server to reflect camera access is disabled
        fetch('update_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: 'camera' })
        })
        .then(res => res.json())
        .then(res => {
            cameraBtn.innerText = 'Turn On'; // Update button text
            cameraBtn.classList.remove('active'); // Remove active class
            cameraBtn.classList.remove('pressed'); // Remove pressed class
            alert('Camera access has been disabled.');
        })
        .catch(err => {
            console.error('Error while disabling camera:', err);
        });
    }
});
    </script>
</body>
</html>