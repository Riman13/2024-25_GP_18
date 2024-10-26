<?php
include 'config.php'; // Include the database connection
include 'session.php';
// Query to get the first 21 places
$sql = "SELECT id, place_name, is_restaurant, categories, granular_category, average_rating FROM riyadhplaces LIMIT 21";
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


// ====================
// 2. Communicate with Flask API
// ====================

// Define the Flask API URL
$api_url = 'http://127.0.0.1:5000/api/recommendations/' . $user_id;

// Initialize cURL session
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Adjusted timeout

// Execute the cURL request
$response = curl_exec($ch);
$curl_error = curl_error($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$recommendations = [];

// ====================
// 3. Handle API Response
// ====================

if ($response === false) {
    $curl_error = curl_error($ch); // Capture the cURL error
    $error_message = "Unable to fetch recommendations. cURL error: " . htmlspecialchars($curl_error);

    // Optionally log the error for debugging
} elseif ($http_status != 200) {
    $error_message = "API Error: Unable to fetch data (HTTP status: " . $http_status . ").";
} else {
    $recommendations = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_message = "Data format error. Please contact support.";
        $recommendations = [];
    }
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
        <a href="logout.php">
    <i class='bx bx-log-out nav__sign-out' id="signout-button"></i>
</a>
  
        
        <div class="nav__toggle" id="nav-toggle">
          <i class='bx bx-grid-alt' ></i>
      </div>
      </div>
      <div class="UserName">
      <a href="profile.php" class="user-profile-link">
  <span><i class="ri-user-3-fill"></i></span>
  <?php echo htmlspecialchars($username); ?>
</a>
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
        <span><i class="ri-more-2-fill"></i></span>
        and more
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


 <!--============ All Destinations =============-->

 <div class="destinations" id="destinations">
    <h2>Discover Your Destination</h2>
    <div class="places-container" id="placesContainer">
        <!-- Dynamically inject places -->

    </div>
    
    <!-- Left Arrow -->
    <button class="nav-btn left" id="AllLeftArrow" onclick="navigate(-1)">&lt;</button>
    <!-- Right Arrow -->
    <button class="nav-btn right" id="AllRightArrow" onclick="navigate(1)">&gt;</button>
    
    <!-- Show More button -->
    <button class="show-more" onclick="window.location.href='places.php'">Show More</button>
</div>


<div class="destinations">
    <h2>You might like this</h2>
    <div class="places-container" id="cfrsPlacesContainer">
        <!-- Dynamically inject CFRS recommendations -->

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
let currentIndex = 0; // Initialize current index
    const places = <?php echo json_encode($places); ?>; // Convert PHP array to JavaScript array

    // Function to render places based on the current index
    function renderPlaces() {
        const placesContainer = document.getElementById('placesContainer');
        placesContainer.innerHTML = ''; // Clear current places
        
        // Display the next set of places
        for (let i = currentIndex; i < currentIndex + 3 && i < places.length; i++) {
            const place = places[i];
            const placeDiv = document.createElement('div');
            placeDiv.className = i === currentIndex + 1 ? 'place large' : 'place small';
            placeDiv.innerHTML = `
                <img src='imgs/Riyadh.jpg' alt='${place.place_name}'>
                <h3>${place.place_name}</h3>
                <p>Category: ${place.granular_category}</p>
                <p>Rating: ${'★'.repeat(Math.floor(place.average_rating)) + '☆'.repeat(5 - Math.floor(place.average_rating))}</p>
                <button class="details-btn" data-id="${place.id}">More Details</button>
            `;
            placesContainer.appendChild(placeDiv);
        }

        // Disable arrows based on the current index
        document.getElementById('AllLeftArrow').disabled = currentIndex === 0;
        document.getElementById('AllRightArrow').disabled = currentIndex + 3 >= places.length;
    }

    // Function to navigate through places
    function navigate(direction) {
        currentIndex += direction;
        renderPlaces(); // Update the displayed places
    }

    // Initial rendering of places
    renderPlaces();

    let currentIndexCFRS = 0; // Initialize current index for CFRS
    const recommendations = <?php echo json_encode($recommendations); ?>; // Convert PHP array to JavaScript array

    // Function to render CFRS recommendations based on the current index
    function renderCFRSPlaces() {
        const cfrsPlacesContainer = document.getElementById('cfrsPlacesContainer');
        cfrsPlacesContainer.innerHTML = ''; // Clear current recommendations
        
// Display the next set of recommendations
for (let i = currentIndexCFRS; i < currentIndexCFRS + 3 && i < recommendations.length; i++) {
    const place = recommendations[i];
    const placeDiv = document.createElement('div');
    placeDiv.className = i === currentIndexCFRS + 1 ? 'place large' : 'place small';
    
    // Calculate the average rating for display
    const averageRating = parseFloat(place.average_rating); // Make sure to parse the rating as a float
    const filledStars = '★'.repeat(Math.floor(averageRating)); // Filled stars based on the rating
    const emptyStars = '☆'.repeat(5 - Math.floor(averageRating)); // Empty stars to fill up to 5
    const ratingDisplay = filledStars + emptyStars; // Combine filled and empty stars

    placeDiv.innerHTML = `
        <img src='imgs/Riyadh.jpg' alt='${place.place_name}'>
        <h3>${place.place_name}</h3>
        <p>Category: ${place.granular_category}</p>
        <p>Rating: ${ratingDisplay}</p> <!-- Use the calculated rating display here -->
        <button class="details-btn" data-id="${place.id}">More Details</button>
    `;
    
    cfrsPlacesContainer.appendChild(placeDiv);
}


        // Disable arrows based on the current index
        document.getElementById('cfrsLeftArrow').disabled = currentIndexCFRS === 0;
        document.getElementById('cfrsRightArrow').disabled = currentIndexCFRS + 3 >= recommendations.length;
    }

    // Function to navigate through CFRS recommendations
    function navigateCFRS(direction) {
        currentIndexCFRS += direction;
        renderCFRSPlaces(); // Update the displayed recommendations
    }

    // Initial rendering of CFRS places
    renderCFRSPlaces();


</script>

   
  </body>
  </html>