<?php include 'session.php'; ?>
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
   <link rel="shortcut icon" href="imgs/logo.png" type="image/x-icon">
</head>
<body>
<header class="header" id="header">
    <nav class="nav container">
      <a href="#" class="nav__logo">
       
          <img src="imgs/logo.png" alt="Logo" class="nav__logo-img">
          Doorob</a>
      <div class="nav__menu" id="nav-menu">
        <ul class="nav__list">
          <li class="nav__item">
            <a href="homepage.php" class="nav__link active-link">Home</a>
          </li>
          <li class="nav__item">
            <a href="profile.php" class="nav__link">Profile</a>
          </li>
          <li class="nav__item">
            <a href="#" class="nav__link">Places</a>
          </li>
        </ul>
  
        <div class="nav__close" id="nav-close">
          <i class='bx bx-x'></i>
        </div>
      </div>
  
      <div class="nav__btns">
     
        <i class='bx bx-moon change-theme' id="theme-button"></i>
        <i class='bx bxs-bell nav__notification' id="notification-button"></i>
        <i class='bx bx bx-log-out nav__sign-out' id="signout-button"></i>
  
       
        <div class="nav__toggle" id="nav-toggle">
            <i class='bx bx-grid-alt' ></i>
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
                <button onclick="showIframe('iframe4')"><i class="fas fa-heart"></i> Favorite Places</button>
            </div>

            <div class="content1">
                <iframe id="iframe1" class="iframe" src="personal-info.php"></iframe>
                <iframe id="iframe2" class="iframe" src="privacy-settings.html" style="display: none;"></iframe>
                <iframe id="iframe3" class="iframe" src="rating-history.html" style="display: none;"></iframe>
                <iframe id="iframe4" class="iframe" src="favorite-list.html" style="display: none;"></iframe>
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
                    <a href="#" class="footer__link">About Us</a>
                </li>
                
            </ul>
        </div>
  
        <div class="footer__content">
            <h3 class="footer__title section__title">Doroob</h3>
  
            <ul class="footer__links">
                <li>
                    <a href="#" class="footer__link">Home</a>
                </li>
                <li>
                    <a href="#" class="footer__link">Profile page</a>
                </li>
                <li>
                    <a href="#" class="footer__link">History Ratings</a>
                </li>
                
            </ul>
        </div>
  
        <div class="footer__content">
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
        </div>
    </div>
  
    <span class="footer__copy">Doorob &#169;All rigths reserved</span>
  </footer>
  
   <!--========== JS ==========-->
   <script src="scripts/scripts-fh.js"></script>
  </body>
  </html>