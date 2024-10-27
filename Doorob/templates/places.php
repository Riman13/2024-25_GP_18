<?php
include 'config.php'; 
include 'session.php';
$sql = "SELECT id, place_name, is_restaurant, granular_category, average_rating , place_id FROM  riyadhplaces_doroob";
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
<title>Doroob</title>

<!--======== FONTS ========-->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

<!--======== CSS ========-->
<link rel="stylesheet" href="styles/footer-header-styles.css">
<link rel="stylesheet" href="styles/places.css">

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
          Doroob</a>
      <div class="nav__menu" id="nav-menu">
        <ul class="nav__list">
          <li class="nav__item">
            <a href="homepage.php" class="nav__link active-link">Home</a>
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
     
        <i class='bx bx-moon change-theme' id="theme-button"></i>
        <i class='bx bxs-bell nav__notification' id="notification-button"></i>
        <i class='bx bx bx-log-out nav__sign-out' id="signout-button"></i>
  
       
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
    <!-   
  <div style="height: 80px;"></div> 
<!--intro

<div class="intro">
    <div class="intro-text">
        <h1>DISCOVER</h1>
        <h2>NEW PLACE</h2>
        <p>Start your journey of exploring new destinations in Riyadh with Doroob</p>
    </div>
</div>-->
 <!--============ Filter =============-->
 <div class="filter-container">
    <h2>What are you looking for?</h2>
    <div class="filter-boxes">
        <div class="filter-box" data-category="restaurants" onclick="filterPlaces('restaurants')">
            <i class="fas fa-utensils"></i>
            <p>Restaurants & Cafes</p>
        </div>
        <div class="filter-box" data-category="hotel" onclick="filterPlaces('hotel')">
            <i class="fas fa-hotel"></i>
            <p>Hotels</p>
        </div>
        <div class="filter-box" data-category="shopping_mall" onclick="filterPlaces('shopping_mall')">
            <i class="fas fa-shopping-bag"></i>
            <p>Malls</p>
        </div>
        <div class="filter-box" data-category="beauty" onclick="filterPlaces('beauty')">
            <i class="fas fa-spa"></i>
            <p>Beauty</p>
        </div>
        <div class="filter-box" data-category="park" onclick="filterPlaces('park')">
            <i class="fas fa-tree"></i>
            <p>Parks</p>
        </div>
        <div class="filter-box" data-category="art_gallery" onclick="filterPlaces('art_gallery')">
            <i class="fas fa-palette"></i>
            <p>Art Gallery</p>
        </div>
        <div class="filter-box" data-category="zoo" onclick="filterPlaces('zoo')">
            <i class="fas fa-hippo"></i>
            <p>Zoo</p>
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
<div ></div>
<div class="des" id="destination1">
    <div class="places-container" id="placesContainer">
    <!-- Places will be dynamically injected here -->
</div>
</div>

<!-- Pagination Controls -->
<div class="pagination">
    <button onclick="navigate(-1)">&#8249;</button> <!-- Previous button -->
    <span id="currentPage">1</span> <!-- Current page indicator -->
    <button onclick="navigate(1)">&#8250;</button> <!-- Next button -->
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
<script>
// Initialize variables
let places = <?php echo json_encode($places); ?>;
let filteredPlaces = places; // Use this for filtering
let currentIndex = 0;
const placesPerPage = 12;

// Function to render places
function renderPlaces() {
    const placesContainer = document.getElementById('placesContainer');
    placesContainer.innerHTML = ''; // Clear previous content

    const displayedPlaces = filteredPlaces.slice(currentIndex, currentIndex + placesPerPage);

    displayedPlaces.forEach((place) => {
        const placeDiv = document.createElement('div');
        placeDiv.className = 'place';
        placeDiv.innerHTML = `
            <img src='imgs/Riyadh.jpg' alt='${place.place_name}'>
            <h3>${place.place_name}</h3>
            <p>Category: ${place.granular_category}</p>
            <p>Rating: ${'★'.repeat(Math.floor(place.average_rating)) + '☆'.repeat(5 - Math.floor(place.average_rating))}</p>
            <i class="fas fa-heart favorite" onclick="toggleFavorite(${place.id})"></i>
            <button onclick="window.location.href='placedetails.php?id=${place.id}'">More Details</button>
        `;
        placesContainer.appendChild(placeDiv);
    });

    document.getElementById('currentPage').innerText = Math.floor(currentIndex / placesPerPage) + 1;
}

// Function to navigate through pages
function navigate(direction) {
    const newIndex = currentIndex + direction * placesPerPage;
    if (newIndex >= 0 && newIndex < filteredPlaces.length) {
        currentIndex = newIndex;
        renderPlaces();
    }
}

// Function to filter places by category
function filterPlaces(category) {
    filteredPlaces = places.filter(place => place.granular_category === category);
    currentIndex = 0; // Reset to the first page
    renderPlaces();
}

// Initial render
renderPlaces();
</script>

   
  </body>
  </html>