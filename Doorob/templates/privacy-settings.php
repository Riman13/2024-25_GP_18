<?php include 'session.php'; include 'config.php'; ?>
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
        <?php
        echo (isset($_SESSION['Location_Status']) && $_SESSION['Location_Status'] === 'Allow') 
            ? 'Location Allowed' 
            : 'Turn On';
        ?>
    </button>
        </div>

        <div class="setting-item">
            <label for="cameraToggle">Allow Camera Access</label>
            <button id="cameraBtn" class="toggle-btn">Turn On</button>
        </div>
    </div>

    <script>
const locationBtn = document.getElementById('locationBtn');

locationBtn.addEventListener('click', function () {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const data = {
                    user_id: <?php echo $_SESSION['userID']; ?>, 
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };

                // Save location to multiple APIs
                saveLocationToAPIs(data);

                locationBtn.innerText = 'Location Allowed';
                locationBtn.disabled = true;
                alert('Your location has been saved successfully!');
            },
            (error) => {
                handleGeolocationError(error);
            }
        );
    } else {
        alert('Geolocation is not supported by your browser.');
        locationBtn.innerText = 'Location Not Supported';
        locationBtn.disabled = true;
    }
});

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
    locationBtn.innerText = 'Location Disabled';
    locationBtn.disabled = true;
}




const cameraBtn = document.getElementById('cameraBtn');
let cameraStream;

cameraBtn.addEventListener('click', function() {
    if (cameraBtn.innerText === 'Turn On') {
        navigator.mediaDevices.getUserMedia({ video: true })
            .then((stream) => {
                cameraStream = stream;
                cameraBtn.innerText = 'Turn Off';
                cameraBtn.classList.add('active');
                cameraBtn.classList.add('pressed'); // Add pressed class
            })
            .catch(() => {
                cameraBtn.innerText = 'Turn On';
            });
    } else {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }
        cameraBtn.innerText = 'Turn On';
        cameraBtn.classList.remove('active');
        cameraBtn.classList.remove('pressed'); // Remove pressed class
    }
});

                    
    </script>
</body>
</html>