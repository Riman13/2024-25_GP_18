<?php include 'session.php';
include 'config.php';

// Retrieve the user_id from the session
$user_id = $_SESSION['userID'];


// Query to get the user's name directly
$query = "SELECT Name FROM users WHERE UserID = $user_id";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $username = $user['Name'];
} else {
    $username = "Guest";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="styles/reset-password.css">
    <link rel="stylesheet" href="styles/footer-header-styles.css">
    <!--======== ICONS ========-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
     <!--======== WEBSITEICON ========-->
     <link rel="shortcut icon" href="imgs/logo.png" type="image/x-icon">
</head>

 <!--============ Header =============-->
 <header class="header" id="header">
    <nav class="nav container">
      <a href="#" class="nav__logo">
       
          <img src="imgs/logo.png" alt="Logo" class="nav__logo-img">
          Doorob
        
      </a>
      <div class="nav__menu" id="nav-menu">
        <ul class="nav__list">
          <li class="nav__item">
            <a href="homepage.php" class="nav__link">Home</a>
          </li>
          <li class="nav__item">
            <a href="profile.php" class="nav__link">Profile</a>
          </li>
          <li class="nav__item">
            <a href="places.php" class="nav__link">Places</a>
          </li>
        </ul>
  
        <div class="nav__close" id="nav-close">
          <i class='bx bx-x'></i>
        </div>
      </div>
  
      <div class="nav__btns">
     
      <!-- <i class='bx bx-moon change-theme' id="theme-button"></i>-->
        <i class='bx bxs-bell nav__notification' id="notification-button"></i>
        <a href="logout.php">
    <i class='bx bx-log-out nav__sign-out' id="signout-button"></i>
</a>
  
        
        <div class="nav__toggle" id="nav-toggle">
          <i class='bx bx-grid-alt' ></i>
      </div>
      </div>

      <div class="UserName"> 
    <div class="user-profile">
        <span><i class="ri-user-3-fill"></i></span>
        <?php echo htmlspecialchars($username); ?>
    </div>
</div>
    </nav>
  </header>

  <div style="height: 40px;"></div> 
<body>
    <div class="reset">
    <div class="container2">
        <div class="form-container2 reset-password-container2">
            <h1>Reset Password</h1>
            <form id="resetPasswordForm">
                <div class="form-group">
                    <label for="newPassword"></i> New Password</label>
                    <input type="password" id="newPassword" name="newPassword" required placeholder="Enter new password">
                </div>
                <div class="form-group">
                    <label for="confirmPassword"></i> Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required placeholder="Confirm new password">
                </div>
                <button type="submit" class="submit-btn">Reset Password</button>
            </form>
            <div class="message" id="message"></div>
        </div>
    </div>

    <script>
        const form = document.getElementById('resetPasswordForm');
        const messageDiv = document.getElementById('message');

        form.addEventListener('submit', function(event) {
    event.preventDefault();
    const newPassword = form.newPassword.value;
    const confirmPassword = form.confirmPassword.value;

    if (newPassword === confirmPassword) {
        fetch('reset_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ newPassword })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.textContent = 'Password has been reset successfully!';
                messageDiv.style.color = 'green';

                setTimeout(() => {
                    window.location.href = 'profile.php';

            }, 2000); // 2-second delay

            } else {
                messageDiv.textContent = 'Failed to reset password: ' + data.error;
                messageDiv.style.color = 'red';
            }
        });
    } else {
        messageDiv.textContent = 'Passwords do not match. Please try again.';
        messageDiv.style.color = 'red';
    }
});

    </script>
</div>
<footer class="footer section">
    <div class="footer__container container grid">
        <div class="footer__content">
            <h3 class="footer__title section__title">Our information</h3>
  
            <ul class="footer__list">
                <li>1234 -  Saudi Arabia</li>
                <li>Riyadh Region</li>
                <li>123-456-789</li>
            </ul>
        </div>
        <div class="footer__content">
            <h3 class="footer__title section__title">About Us</h3>
  
            <ul class="footer__links">
                <li>
                    <a href="#" class="footer__link">Contact Us</a>
                </li>
             
                <li>
                    <a href="Index.php" class="footer__link">About Us</a>
                </li>
                
            </ul>
        </div>
  
        <div class="footer__content">
            <h3 class="footer__title section__title">Doroob</h3>
  
            <ul class="footer__links">
                <li>
                    <a href="   Index.php" class="footer__link">Home</a>
                </li>
                
            </ul>
        </div>
  
    <!--    <div class="footer__content">
            <h3 class="footer__title section__title">Social</h3>
  
            <ul class="footer__social">
                <a href="https://www.facebook.com/" target="_blank" class="footer__social-link">
                    <i class='bx bxl-facebook'></i>
                </a>
  
                <a href="https://twitter.com/" target="_blank" class="footer__social-link">
                    <i class='bx bxl-twitter' ></i>
                </a>
  
                <a href="https://www.instagram.com/" target="_blank" class="footer__social-link">
                    <i class='bx bxl-instagram' ></i>
                </a>
            </ul>
        </div>-->
    </div>
  
    <span class="footer__copy">Doorob &#169;All rigths reserved</span>
  </footer>
  
   <!--========== JS ==========-->
   <script src="scripts/scripts-fh.js"></script>
</body>
</html>