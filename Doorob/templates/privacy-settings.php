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
            <button id="locationBtn" class="toggle-btn">Turn On</button>
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

                sendToBackend(data);

                
                locationBtn.innerText = 'Location Allowed';
                locationBtn.disabled = true;
                alert('Your location has been saved successfully!');
            },
            (error) => {
                
                if (error.code === error.PERMISSION_DENIED) {
                    alert('Location access denied.');
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    alert('Location unavailable. Please try again later.');
                } else if (error.code === error.TIMEOUT) {
                    alert('Request for location timed out. Please try again.');
                } else {
                    alert('An unknown error occurred while retrieving location.');
                }

                locationBtn.innerText = 'Location Disabled';
                locationBtn.disabled = true;
            }
        );
    } else {
        alert('Geolocation is not supported by your browser.');
        locationBtn.innerText = 'Location Not Supported';
        locationBtn.disabled = true;
    }
});

function sendToBackend(data) {
    fetch('http://127.0.0.1:5000/api/save_location', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Failed to save location');
            }
            return response.json();
        })
        .then((result) => {
            console.log('Location saved:', result);
        })
        .catch((error) => {
            console.error('Error sending location data:', error);
            alert('Failed to save location. Please try again later.');
        });
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
            }
        });
    </script>
</body>
</html>