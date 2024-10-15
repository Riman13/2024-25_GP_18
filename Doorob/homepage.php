<?php
include 'config.php'; // Include the database connection
include 'session.php';
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
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doroob</title>

<!--======== FONTS ========-->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

<!--======== CSS ========-->
<link rel="stylesheet" href="styles/footer-header-styles.css">
<link rel="stylesheet" href="styles/homepage-styles.css">

<!--======== ICONS ========-->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!--======== WEBSITE ICON ========-->
<link rel="shortcut icon" href="imgs/logo.png" type="image/x-icon">
       
</head>
<body>

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


<div class="intro-container">
<div class="intro-image">
<div class="intro-image-card intro-image-card1">
        <span><i class="ri-map-pin-fill"></i></span>
        Riyadh
    </div>
    <div class="intro-image-card intro-image-card2">
        <span><i class="ri-hotel-line"></i></span>
        Hotel
    </div>
    <div class="intro-image-card intro-image-card3">
        <span><i class="ri-cup-fill"></i></span>
        Cafes
    </div>
    <div class="intro-image-card intro-image-card4">
        <span><i class="ri-restaurant-2-fill"></i></span>
        Resturant
    </div>
    <div class="intro-image-card intro-image-card5">
        <span><i class="ri-calendar-event-fill"></i></span>
        Events
    </div>
    
</div>
<div class="intro-content">

<h1>LET'S GO! <br/> THE<span class="highlight"> ADVENTURE</span> IS WAITING FOR You</h1>
<p>Embark on your journey with Doroob from the heart of Riyadh, where hidden gems and fascinating experiences await. Explore cultural landmarks and modern wonders, creating unforgettable memories as you discover Saudi Arabia's capital's rich heritage and dynamic attractions.</p>        <button class="cta-btn" onclick="scrollToDestinations()">Explore Destinations</button>    
</div>
</div>



<div class="body-container">

 
<!--============ Mission & Values Section =============-->
<div class="mission-values">
    <h1>MISSION & VALUES</h1>
    <div class="key-pillars">
        <div><i class="fas fa-globe"></i><br>Support Tourism in Saudi Arabia</div>
        <div><i class="fas fa-map-marked-alt"></i><br> Personalized Experiences</div>
        <div><i class="fas fa-user-friends"></i><br> Community Engagement</div>
        <div><i class="fas fa-chart-line"></i><br> Positive Impact</div>
        <div><i class="fas fa-compass"></i><br>Adventure Awaits</div>
    </div>
</div>


 <!--============ Gallrey =============-->
<section>
    <div class="gallrey">
        <h3>Explore & Discover Our Destinations</h>
        <p>Enjoy breathtaking views and unique experiences with your family</p>
    </div>
    <div class="gallrey-container">
        <div class="items item1">
            <h2>Edge Of The World</h2>
            
        </div>
        <div class="items item2">
            <h2>Via Riyadh</h2>
            
        </div>
        <div class="items item3">
            <h2>Kingdom Tower</h2>
            
        </div>
        <div class="items item4">
            <h2>King Abdullah Financial District || KAFD</h2>
            
        </div>
        <div class="items item5">
            <h2>Al-Masmak Palace</h2>
            
        </div>

    </div>
</section>

 <!--============ Filter =============-->
<div class="filter-container">
    <h2>What are you looking for?</h2>
    <div class="filter-boxes">
        <div class="filter-box">
            <i class="fas fa-utensils"></i>
            <p>Restaurants</p>
        </div>
        <div class="filter-box">
            <i class="fas fa-hotel"></i>
            <p>Hotels</p>
        </div>
        <div class="filter-box">
            <i class="fas fa-coffee"></i>
            <p>Cafes</p>
        </div>
        <div class="filter-box">
            <i class="fas fa-spa"></i>
            <p>Beauty</p>
        </div>
        <div class="filter-box">
            <i class="fas fa-calendar-alt"></i>
            <p>Events</p>
        </div>
        <div class="filter-box">
            <i class="fas fa-shopping-bag"></i>
            <p>Malls</p>
        </div>
    </div>
    <div class="search-bar">
        <div class="search-input-container">
            <input type="text" placeholder="Search destinations...">
            <button>
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>
</div>

 <!--============ All Destinations =============-->

<div class="destinations" id="destinations">
    <h2>Discover Your Destination</h2>
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

 <!--============ CFRS =============-->
<div class="destinations">
    <h2>You might like this</h2>
    <div class="places-container" id="cfrsPlacesContainer">
        <!-- CFRS Places will be dynamically injected here -->
    </div>

    <!-- Left Arrow -->
    <button class="nav-btn left" id="cfrsLeftArrow" onclick="navigateCFRS(-1)">&lt;</button>
    <!-- Right Arrow -->
    <button class="nav-btn right" id="cfrsRightArrow" onclick="navigateCFRS(1)">&gt;</button>

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
<script src="scripts/scripts-fh.js"></script>
<script src="https://unpkg.com/scrollreveal"></script>
<script src="scripts/homepage-js.js"></script>

<script>
// Initialize variables
let places = <?php echo json_encode($places); ?>;
let currentIndex = 0;

// Function to render places
function renderPlaces() {
    const placesContainer = document.getElementById('placesContainer');
    placesContainer.innerHTML = ''; // Clear previous content

    // Get the next three places to display
    const displayedPlaces = places.slice(currentIndex, currentIndex + 3);

    displayedPlaces.forEach((place, index) => {
        const placeDiv = document.createElement('div');
        const isMiddle = index === 1; // Middle place
        placeDiv.className = isMiddle ? 'place large' : 'place small';
        placeDiv.innerHTML = `
            <img src='imgs/Riyadh.jpg' alt='${place.place_name}'>
            <h3>${place.place_name}</h3>
            <p>Category: ${place.is_restaurant === 'RESTURANT' ? 'Restaurant' : place.categories}</p>
            <p>Rating: ${'★'.repeat(Math.floor(place.average_rating)) + '☆'.repeat(5 - Math.floor(place.average_rating))}</p>
            <button onclick="window.location.href='placedetails.php?id=${place.id}'">More Details</button>
        `;
        placesContainer.appendChild(placeDiv);
    });

    // Disable arrows based on current index
    document.getElementById('leftArrow').disabled = currentIndex === 0;
    document.getElementById('rightArrow').disabled = currentIndex + 3 >= places.length;
}

// Function to navigate through places
function navigate(direction) {
    currentIndex += direction;
    renderPlaces();
}

// Initial render
renderPlaces();
</script>

   
  </body>
  </html>