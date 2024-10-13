<?php /*for retrieving destinations
include 'config.php'; // Include the database connection

// Query to get the first 21 places
$sql = "SELECT id, place_name, is_restaurant, categories, average_rating FROM riyadhplaces LIMIT 21";
$result = $conn->query($sql);
// Store places in an array
$places = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $places[] = $row;
    }
} else {
    echo "<p>No places found!</p>";
}*/
?>   


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doroob-fh</title>
     <!--======== CSS ========-->
  <link rel="stylesheet" href="footer-header-styles.css">
  <link rel="stylesheet" href="index2GP.css">
    <!--======== ICONS ========-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
     <!--======== WEBSITEICON ========-->
     <link rel="shortcut icon" href="Dlogo.png" type="image/x-icon">

</head>
<body>

 <!--============ Header =============-->
  <header class="header" id="header">
    <nav class="nav container">
      <a href="#" class="nav__logo">
       
          <img src="Dlogo.png" alt="Logo" class="nav__logo-img">
          Doorob
        
      </a>
      <div class="nav__menu" id="nav-menu">
        <ul class="nav__list">
          <li class="nav__item">
            <a href="#" class="nav__link active-link">Home</a>
          </li>
          <li class="nav__item">
            <a href="#" class="nav__link">Profile</a>
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

  <!--<div style="height: 700px;"></div> -->

  
  <div class="home-content">
            
            <h3 class="typing-text" style="margin-top: 50px;"> <span></span></h3>
            <br><br>
            <div class="content">
                <p >
                    Saudi Arabia is full of beautiful, fun, enjoyable destinations and activities but not many know about them. We're here to help!
                 <br><br>   <span  id="text" class="collapsed">Therefore, we present to you "Doroob"! A website that is designed to guide you to your preferred spots, from restaurants and cafes to parks and markets.
                    <br> <br> We also play a key role in marketing and promoting both new and classic tourist destinations and services and aiming to boost tourism SaudiArabia. <br> <br> We play a key role in marketing and promoting both new and classic 
                    tourist destinations and services aiming to boost tourism in Saudi Arabia.
                  <br><br>  Leave it to Doroob to recommend great places that match your preferences.
                    With the technology of facial recognition, we will be able to tell which place seems of interest to you and which doesn’t,
                     making the search process easier and time-saving!
                     <br> <br>  With Doroob, we believe that you should spend more time enjoying a place and less time trying to find one!</span>
                </p>
            </div>
            <a id="toggleButton" href="#" class="btn">Read More</a>
        </div>

        <div class="destinations">
        <h2>Discover New Destinations</h2>
        <div class="places-container" id="placesContainer">
            <!-- Places will be dynamically injected here -->
        </div>
    
        <!-- Left Arrow -->
        <button class="nav-btn left" id="leftArrow" onclick="navigate(-1)">&lt;</button>
        <!-- Right Arrow -->
        <button class="nav-btn right" id="rightArrow" onclick="navigate(1)">&gt;</button>
    
        <!-- Show More button -->
        <button class="show-more" onclick="window.location.href='allplaces.php'">Show More</button>
    </div>
    
    </div> 

 <!--============ FOOTER =============-->
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
 <script src="scripts-fh.js"></script>

 <script>
        function toggleText() {
    const text = document.getElementById("text");
    const button = document.getElementById("toggleButton");

    if (text.classList.contains("collapsed")) {
        text.classList.remove("collapsed");
        text.classList.add("expanded");
        button.textContent = "Read Less";
        button.setAttribute("aria-expanded", "true");
    } else {
        text.classList.remove("expanded");
        text.classList.add("collapsed");
        button.textContent = "Read More";
        button.setAttribute("aria-expanded", "false");
    }
}
        document.getElementById("toggleButton").addEventListener("click", function(event) {
    event.preventDefault(); // Prevent default anchor behavior
    toggleText();
});
    </script>

</body>
</html>
