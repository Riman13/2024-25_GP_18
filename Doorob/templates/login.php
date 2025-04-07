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
(async function () {
    const userId = " . $_SESSION['userID'] . ";
    const data = { user_id: userId };
    let locationAsked = false;

    const locationPromise = new Promise((resolve) => {
        if (!locationAsked && navigator.geolocation) {
            locationAsked = true;
            navigator.geolocation.getCurrentPosition(
                async function (position) {
                    data.lat = position.coords.latitude;
                    data.lng = position.coords.longitude;

                    try {
                        await saveLocationToAPIs(data);
                        await fetch('update_session.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ status: 'enable_location' })
                        });
                        console.log('Location access granted.');
                    } catch (e) {
                        console.error('Location save/update failed:', e);
                    }
                    resolve();
                },
                function (error) {
                    console.warn('Location denied or failed:', error);
                    resolve(); // still resolve
                }
            );
        } else {
            console.warn('Geolocation not supported or already triggered.');
            resolve();
        }
    });

    const cameraPromise = new Promise(async (resolve) => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            stream.getTracks().forEach(track => track.stop());

            await fetch('update_session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: 'enable_camera' })
            });
            console.log('Camera access granted.');
        } catch (error) {
            console.warn('Camera denied or failed:', error);
        }
        resolve();
    });

    await Promise.allSettled([locationPromise, cameraPromise]);
    window.location.href = 'homepage.php';

    function saveLocationToAPIs(data) {
        const apiUrls = [
            'http://127.0.0.1:5002/api/save_location',
            'http://127.0.0.1:5003/api/save_location'
        ];

        return Promise.all(apiUrls.map(url =>
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to save location to \${url}`);
                }
                return response.json();
            })
            .then(result => {
                console.log(`Saved to \${url}:`, result);
            })
            .catch(error => {
                console.error(`Error saving to \${url}:`, error);
            })
        ));
    }
})();

        </script>";
        

        
        exit();
    } else {
        header("Location: registration.php?error=Invalid email or password");
        exit();
    }
}
?>