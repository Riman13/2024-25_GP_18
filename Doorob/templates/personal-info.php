<?php
include 'session.php';
include 'config.php'; 

// Fetch user information from the database
$userId = $_SESSION['userID'];
$query = "SELECT name, email FROM users WHERE userId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "User not found.";
    exit();
}

$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Information</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/personal-info.css">
</head>
<body>
    <div class="personal-info-container">
        <div class="header">
            <h2>Personal Information</h2>
            <button id="editSaveBtn" class="edit-btn" onclick="toggleEdit()">
                <i class="fas fa-edit"></i> Edit
            </button>
        </div>

        <div class="info-group">
            <label for="name">Name</label>
            <input type="text" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
        </div>

        <div class="info-group">
            <label for="email">Email</label>
            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
        </div>

        <div class="info-group">
            <label for="resetPassword">
                <a href="resetPassword.php" target="_top" class="reset-password-link">
                    Reset Password <i class="fas fa-arrow-right"></i>
                </a>
            </label>
        </div>
    </div>

    <script>
        let isEditing = false;
// editing user info
        function toggleEdit() {
            isEditing = !isEditing;
            const fields = ['name', 'email'];
            const editSaveBtn = document.getElementById('editSaveBtn');

            fields.forEach(field => {
                const inputField = document.getElementById(field);
                if (isEditing) {
                    inputField.removeAttribute('readonly');
                    inputField.focus();
                    editSaveBtn.innerHTML = '<i class="fas fa-save"></i> Save';
                    editSaveBtn.classList.replace('edit-btn', 'save-btn');
                } else {
                    inputField.setAttribute('readonly', 'readonly');
                    editSaveBtn.innerHTML = '<i class="fas fa-edit"></i> Edit';
                    editSaveBtn.classList.replace('save-btn', 'edit-btn');
                    saveChanges();
                }
            });
        }

        function saveChanges() {
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;

            fetch('update_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ name, email }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Information updated successfully!');
                    location.reload();
                } else {
                    alert('Failed to update information: ' + data.error);
                }
            });
        }
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>