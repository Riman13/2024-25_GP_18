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

// Location Button Logic
const locationBtn = document.getElementById('locationBtn');
let locationEnabled = <?php echo json_encode(isset($_SESSION['location']) && $_SESSION['location'] === true); ?>;

if (locationEnabled) {
    locationBtn.innerText = 'Turn Off';
    locationBtn.classList.add('active', 'pressed');
} else {
    locationBtn.innerText = 'Turn On';
}



function createToast(type, icon, title, text) {
    let newToast = {
        type: type,
        icon: icon,
        title: title,
        text: text
    };
    
    // Send toast data to parent window
    window.parent.postMessage({ action: 'showToast', toast: newToast }, '*');
}


locationBtn.addEventListener('click', function () {
    if (!locationEnabled) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const data = {
                        user_id: <?php echo json_encode($_SESSION['userID'] ?? null); ?>,
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };

                    // Save to APIs
                    ['http://127.0.0.1:5002/api/save_location', 'http://127.0.0.1:5003/api/save_location'].forEach((url) => {
                        fetch(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(data)
                        });
                    });

                    // Update session to "enabled"
                    fetch('update_session.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status: 'enable_location' })
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            locationEnabled = true;
                            locationBtn.innerText = 'Turn Off';
                            locationBtn.classList.add('active', 'pressed');
                            createToast('success','fa-solid fa-circle-check', 'Success', 'Your location has been saved successfully!');

                        }
                    });
                },
                (error) => {

                    createToast('error', 'fa-solid fa-circle-exclamation', 'Error', "Location error: " + error.message);

                }
            );
        } else {
            createToast('error', 'fa-solid fa-circle-exclamation', 'Error', 'Geolocation is not supported by your browser.');

        }
    } else {
        // Turn off location (just update session)
        fetch('update_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: 'disable_location' })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                locationEnabled = false;
                locationBtn.innerText = 'Turn On';
                locationBtn.classList.remove('active', 'pressed');
                createToast('success', 'fa-solid fa-circle-check', 'Success', 'Location access has been disabled.');

            }
        });
    }
});


// Camera Button Logic
const cameraBtn = document.getElementById('cameraBtn');
let cameraEnabled = <?php echo json_encode(isset($_SESSION['camera']) && $_SESSION['camera'] === true); ?>;
let cameraStream = null;

if (cameraEnabled) {
    cameraBtn.innerText = 'Turn Off';
    cameraBtn.classList.add('active', 'pressed');
} else {
    cameraBtn.innerText = 'Turn On';
}

cameraBtn.addEventListener('click', function () {
    if (!cameraEnabled) {
        navigator.mediaDevices.getUserMedia({ video: true })
            .then((stream) => {
                cameraStream = stream;
                fetch('update_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: 'enable_camera' })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        cameraEnabled = true;
                        cameraBtn.innerText = 'Turn Off';
                        cameraBtn.classList.add('active', 'pressed');
                        createToast('success','fa-solid fa-circle-check', 'Success', 'Camera is now enabled.');

                    }
                });
            })
            .catch(() => {
            createToast('error', 'fa-solid fa-circle-exclamation', 'Error', 'Camera access denied or not supported.');

            });
    } else {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }

        fetch('update_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: 'disable_camera' })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                cameraEnabled = false;
                cameraBtn.innerText = 'Turn On';
                cameraBtn.classList.remove('active', 'pressed');
                createToast('success', 'fa-solid fa-circle-check', 'Success', 'Camera access has been disabled.');

            }
        });
    }
});

    </script>
</body>
</html>