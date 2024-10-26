<?php include 'session.php';
include 'config.php';  ?>
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
        locationBtn.addEventListener('click', function() {
            if (locationBtn.innerText === 'Turn On') {
                locationBtn.innerText = 'Turn Off';
                locationBtn.classList.add('active');
            } else {
                locationBtn.innerText = 'Turn On';
                locationBtn.classList.remove('active');
            }
        });

        const cameraBtn = document.getElementById('cameraBtn');
        cameraBtn.addEventListener('click', function() {
            if (cameraBtn.innerText === 'Turn On') {
                cameraBtn.innerText = 'Turn Off';
                cameraBtn.classList.add('active');
            } else {
                cameraBtn.innerText = 'Turn On';
                cameraBtn.classList.remove('active');
            }
        });
    </script>
</body>
</html>