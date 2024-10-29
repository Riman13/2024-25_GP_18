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
    <title>Profile Page</title>
    <link rel="stylesheet" href="styles/profile.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet"> 
  <link rel="stylesheet" href="styles/footer-header-styles.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
  <!--======== ICONS ========-->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
   <link rel="shortcut icon" href="imgs/logo.png" type="image/x-icon">
</head>
<body>
<header class="header" id="header">
    <nav class="nav container">
      <a href="#" class="nav__logo">
       
          <img src="imgs/logo.png" alt="Logo" class="nav__logo-img">
          Doroob</a>
      <div class="nav__menu" id="nav-menu">
        <ul class="nav__list">
          <li class="nav__item">
            <a href="homepage.php" class="nav__link ">Home</a>
          </li>
          <li class="nav__item">
            <a href="profile.php" class="nav__link active-link">Profile</a>
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
     
        <!--<i class='bx bx-moon change-theme' id="theme-button"></i>-->
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
  
    <div style="height: 80px;"></div> 
  
<div class="Maincontainer">
    <div class="container1">
        <div class="title1">My Profile</div>
        <div class="profile-wrapper1">
            <div class="sidebar1">
                <button onclick="showIframe('iframe1')"><i class="fas fa-user"></i> Personal Information</button>
                <button onclick="showIframe('iframe2')"><i class="fas fa-lock"></i> Privacy Settings</button>
                <button onclick="showIframe('iframe3')"><i class="fas fa-history"></i> Rating History</button>
                <button onclick="showIframe('iframe4')"><i class="fas fa-bookmark"></i> Saved Places</button>
                <!-- New Contact Us button -->
                <button onclick="showIframe('iframe5')"><i class="fas fa-envelope"></i> Contact Us</button>
            </div>

            <div class="content1">
                <iframe id="iframe1" class="iframe" src="personal-info.php"></iframe>
                <iframe id="iframe2" class="iframe" src="privacy-settings.php" style="display: none;"></iframe>
                <iframe id="iframe3" class="iframe" src="rating-history.php" style="display: none;"></iframe>
                <iframe id="iframe4" class="iframe" src="favorite-list.php" style="display: none;"></iframe>
                 <!-- New iframe for Contact Us -->
                 <iframe id="iframe5" class="iframe" src="contact-us.php" style="display: none;"></iframe>
            </div>
        </div>
            </div>
        </div>
    </div>

    <script>
        function showIframe(iframeId) {
            document.querySelectorAll('.iframe').forEach(iframe => {
                iframe.style.display = 'none';
            });
            document.getElementById(iframeId).style.display = 'block';
        }
            // footer Function to get query parameters from the URL
    function getQueryParameter(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }

    // Check if there's a query parameter and display the corresponding iframe
    const iframeToShow = getQueryParameter('iframe');
    if (iframeToShow) {
        showIframe(iframeToShow);
    }

    window.addEventListener('message', (event) => {
    if (event.data.action === 'updateUserInfo') {
        const updatedName = event.data.name;
        document.querySelector('.UserName .user-profile').innerHTML = `
            <span><i class="ri-user-3-fill"></i></span>
            ${updatedName}
        `;
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
                    <a href="profile.php?iframe=iframe5" class="footer__link">Contact Us</a>
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
                    <a href="homepage.php" class="footer__link">Home</a>
                </li>
                <li>
                    <a href="#" class="footer__link">Profile page</a>
                </li>
                <li>
                <a href="profile.php?iframe=iframe3" class="footer__link">History Ratings</a>

                </li>
                
            </ul>
        </div>
  
        
    </div>
  
    <span class="footer__copy">Doorob &#169;All rigths reserved</span>
  </footer>
  
   <!--========== JS ==========-->
   <script src="scripts/scripts-fh.js"></script>
  </body>
  </html>
