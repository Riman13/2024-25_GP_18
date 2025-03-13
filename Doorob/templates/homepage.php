<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php'; // Include the database connection
include 'session.php';

// Query to get the first 21 places for Discover Section

$sql = "SELECT id, place_name,  granular_category, average_rating, place_id FROM riyadhplaces_doroob  LIMIT 21";


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
// Query to get the first 21 places for Discover Section END HERE



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
// END HERE


// ====================
// 2. Communicate with Flask API FOR CF
// ====================

// Define the Flask API URL
$api_url = 'http://127.0.0.1:5001/api/recommendations/' . $user_id;

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
$error_message = ""; // Initialize error message

// ====================
// 3. Handle API Response
// ====================

if ($response === false) {
    $curl_error = curl_error($ch); // Capture the cURL error
    $error_message = "Unable to fetch recommendations. cURL error: " . htmlspecialchars($curl_error);

    // Optionally log the error for debugging
} elseif ($http_status != 200) {
    $error_message = "API Error: Unable to fetch data (HTTP status: " . $http_status . ").";
        // Optionally check for specific error cases
        if ($http_status === 400) {
            $error_message = "No recommendations available. Please provide past ratings.";
        } elseif ($http_status === 500) {
            $error_message = "Internal server error. Please try again later.";
        }
} else {
    $recommendations = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_message = "Data format error. Please contact support.";
        $recommendations = [];
    }
}


// Fetch context-based recommendations from Flask API
$context_api_url = 'http://127.0.0.1:5002/api/recommendations_context/' . $user_id;

// Initialize cURL session
$ch_context = curl_init();
curl_setopt($ch_context, CURLOPT_URL, $context_api_url);
curl_setopt($ch_context, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_context, CURLOPT_HTTPGET, true);
curl_setopt($ch_context, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch_context, CURLOPT_TIMEOUT, 10); // Adjusted timeout

// Execute the cURL request
$context_response = curl_exec($ch_context);
$context_http_status = curl_getinfo($ch_context, CURLINFO_HTTP_CODE);
curl_close($ch_context);

// Process the API response
$context_recommendations = [];
if ($context_response && $context_http_status == 200) {
    $context_recommendations = json_decode($context_response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $context_recommendations = []; // Fallback if JSON decoding fails
    }
}
// Fetch context-based recommendations from Flask API
$hybrid_api_url = 'http://127.0.0.1:5003/api/recommendations_hybrid/' . $user_id;

// Initialize cURL session
$ch_hybrid = curl_init();
curl_setopt($ch_hybrid, CURLOPT_URL, $hybrid_api_url);
curl_setopt($ch_hybrid, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_hybrid, CURLOPT_HTTPGET, true);
curl_setopt($ch_hybrid, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch_hybrid, CURLOPT_TIMEOUT, 10); // Adjusted timeout

// Execute the cURL request
$hybrid_response = curl_exec($ch_hybrid );
$hybrid_http_status = curl_getinfo($ch_hybrid, CURLINFO_HTTP_CODE);
curl_close($ch_hybrid);

// Process the API response
$hybrid_recommendations = [];


if ($hybrid_response && $hybrid_http_status == 200) {
    $hybrid_recommendations = json_decode($hybrid_response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $hybrid_recommendations = []; // Fallback if JSON decoding fails
     
    }
}


// rate 


?>





<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doroob</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
     
      <i class='bx bxs-bell nav__notification' id="notification-button"></i>
<div id="notification-dropdown" class="notification-dropdown hidden">
    <ul id="notification-list"></ul>
</div>


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
<p>Embark on your journey with Doroob from the heart of Riyadh, where hidden gems and fascinating experiences await. Explore cultural landmarks and modern wonders, creating unforgettable memories as you discover Saudi Arabia's capital's rich heritage and dynamic attractions.</p>       <button class="cta-btn" onclick="window.location.href='places.php';">Explore Destinations</button>
   
</div>
</div>

<script>
    /*
document.addEventListener("DOMContentLoaded", function () {
    const bellIcon = document.getElementById("notification-button");
    const notificationList = document.getElementById("notification-list");
    const dropdown = document.getElementById("notification-dropdown");

    function fetchNotifications() {
    fetch("notifications.php")
        .then(response => response.json())
        .then(data => {
            notificationList.innerHTML = ""; // Clear old notifications
            let hasNotifications = false;

            if (data.notifications && data.notifications.length > 0) {
                hasNotifications = true;
                data.notifications.forEach(notification => {
                    const listItem = document.createElement("li");
                    listItem.textContent = notification.message;
                    notificationList.appendChild(listItem);
                });

                // Show the dropdown with a delay before hiding
                dropdown.classList.add("show");
                setTimeout(() => {
                    dropdown.classList.remove("show");
                }, 15000); // Keep it visible for 5 seconds
            }

            if (hasNotifications) {
                bellIcon.classList.add("shake");
            } else {
                bellIcon.classList.remove("shake");
            }
        })
        .catch(error => console.error("Error fetching notifications:", error));
}


    // Toggle notification dropdown visibility on bell icon click
    bellIcon.addEventListener("click", function (event) {
        event.stopPropagation(); // Prevent click from bubbling up
        dropdown.classList.toggle("show");
        bellIcon.classList.remove("shake"); // Stop shaking effect when opened
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function (event) {
        if (!bellIcon.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove("show");
        }
    });

    // Fetch notifications every 5 seconds
    setInterval(fetchNotifications, 5000);
    fetchNotifications(); // Fetch notifications immediately on page load
});
*/
document.addEventListener("DOMContentLoaded", function () {
    const bellIcon = document.getElementById("notification-button");
    const notificationList = document.getElementById("notification-list");
    const dropdown = document.getElementById("notification-dropdown");

    function fetchNotifications() {
        fetch("notifications.php")
            .then(response => response.json())
            .then(data => {
                notificationList.innerHTML = ""; // Clear old notifications
                let hasNotifications = false;

                // If there's a notification, display it
                if (data.notifications && data.notifications.length > 0) {
                    hasNotifications = true;
                    data.notifications.forEach(notification => {
                        const listItem = document.createElement("li");
                        listItem.textContent = notification.message;
                        notificationList.appendChild(listItem);
                    });
                }

                // Show the notification dropdown if notifications exist
                if (hasNotifications) {
                    dropdown.classList.add("show");
                    bellIcon.classList.add("shake");
                    // Keep the dropdown visible for 10 seconds before hiding
                    setTimeout(() => {
                        dropdown.classList.remove("show");
                    }, 10000); // Change this value for longer/shorter visibility
                } else {
                    bellIcon.classList.remove("shake");
                }
            })
            .catch(error => console.error("Error fetching notifications:", error));
    }

    // Fetch notifications when the page loads
    fetchNotifications();

    // Fetch notifications every 5 seconds to check for updates
    setInterval(fetchNotifications, 5000);

    // Toggle the notification dropdown visibility on bell icon click
    bellIcon.addEventListener("click", function (event) {
        event.stopPropagation(); // Prevent click from bubbling up
        dropdown.classList.toggle("show");
        bellIcon.classList.remove("shake"); // Stop the shaking effect when opened
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function (event) {
        if (!bellIcon.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove("show");
        }
    });
});

</script>

<!--============ Mission & Values Section =============--><!--
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
-->
<!--============ Mission & Values Section End Here =============--><!--
<BR><BR><BR>-->
 <!--============ Gallrey =============--><!--
<section>
    <div class="gallrey">
        <h3>Explore & Discover New Destinations</h>
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
</section>-->
<!--============ Gallrey End Here =============-->

<!-- Recommendation -->

<!--top rated places -->
<section class="product" id="rt-section"> 
    <!--Recommended Destinations Based on What Others Like--> 
    <h2 class="product-category">Best Rated Destinations</h2>
    <button class="pre-btn"><img src="imgs/arrow.png" alt=""></button>
    <button class="nxt-btn"><img src="imgs/arrow.png" alt=""></button>
    <div class="product-container" id="RTproduct-container">
    </div>
</section>


<!-- CF error -->
<section class="product" id="cf-message" style="display: none;">
<h2 class="product-category">Recommended Destinations Based on What Others Like</h2>
</section>
<!-- CF results -->
<section class="product" id="cf-section" style="display: none;"> 
    <!--Recommended Destinations Based on What Others Like--> 
    <h2 class="product-category">Favorites Destinations Inspired by Similar Users <a href="all_places_CFRS.html" class="view-all-link">
    <img src="imgs/arrow.png" alt="View All" class="view-all-arrow">
    </a> </h2>
    <button class="pre-btn"><img src="imgs/arrow.png" alt=""></button>
    <button class="nxt-btn"><img src="imgs/arrow.png" alt=""></button>
    <div class="product-container" id="CFproduct-container">
    </div>
</section>


<!-- Context -->
<section class="product" id="context-section"> 
    <!--Best Places for You Based on Your Location --> 
    <h2 class="product-category">Best Nearby Destinations <a href="all_places_Context.php" class="view-all-link">
        <img src="imgs/arrow.png" alt="View All" class="view-all-arrow">
    </a> </h2>
    <button class="pre-btn"><img src="imgs/arrow.png" alt=""></button>
    <button class="nxt-btn"><img src="imgs/arrow.png" alt=""></button>
    <div class="product-container" id="CXproduct-container">
    </div>
</section>

<!-- hybird error -->
<section class="product" id="hybrid-message" style="display: none;">
<h2 class="product-category">Top Recommended Destinations for You </h2>
</section>
<!-- Hybrid -->
<section class="product" id="hybrid-section" style="display: none;"> 
    <!--Personalized Destinations Just for You-->
    <h2 class="product-category">Top Recommended Destinations for You <a href="all_places_hybird.php" class="view-all-link">
        <img src="imgs/arrow.png" alt="View All" class="view-all-arrow">
    </a></h2>
    <button class="pre-btn"><img src="imgs/arrow.png" alt=""></button>
    <button class="nxt-btn"><img src="imgs/arrow.png" alt=""></button>
    <div class="product-container" id="HYproduct-container">
    </div>
</section>
<!-- Recommendation End Here -->

<!-- Place Details Modal Structure -->
<div id="detailsModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <i class="fas fa-map-marker-alt name-icon"></i>
    <h2 id="placeName"></h2>
    <hr> <!-- Line under the name -->
    <div class="info-row">
      <div class="left-section">
      <!--  <p><strong>Category:</strong> <span id="placeCategory"></span></p>-->
        <p><strong>Granular Category:</strong> <span id="placeGranularCategory"></span></p><br>
        <button class="favorite-btn"><i class="fas fa-bookmark"></i> Bookmark This Place</button>
      </div>
      <div class="divider"></div>
      <div class="right-section">
    <p><strong>Rating:</strong> <span id="placeRating"></span></p><br>
    <div class="right-section">
   
    <button id="rateThisPlaceBtn" class="rate-btn" onclick="toggleRating()"> <p><i class="fas fa-star"></i><strong>Rate This Place:</strong></p></button>

<div id="rate-btn" class="rt" display: none;>
    <div id="starRating">
        <!-- Stars will be filled by JavaScript dynamically -->
        <span class="star" data-value="1">&#9733;</span>
        <span class="star" data-value="2">&#9733;</span>
        <span class="star" data-value="3">&#9733;</span>
        <span class="star" data-value="4">&#9733;</span>
        <span class="star" data-value="5">&#9733;</span>
    </div><br>
    <button class="submit-rating-btn" onclick="submitRating()">Submit</button>
    </div>  
</div>
</div>
<script>
    const stars = document.querySelectorAll('#starRating .star');
    let currentRating = 0; // Store the current rating
    
    // Add hover and click event listeners to each star
    stars.forEach((star, index) => {
        // Hover: turn stars gold up to the hovered star
        star.addEventListener('mouseover', () => {
            updateStarDisplay(index + 1);
        });

        // Mouseout: reset stars based on the current rating
        star.addEventListener('mouseout', () => {
            updateStarDisplay(currentRating);
        });

        // Click: set the rating and fix the display
        star.addEventListener('click', () => {
            currentRating = index + 1;
            updateStarDisplay(currentRating);
        });
    });

    // Function to update the display based on the rating
    function updateStarDisplay(rating) {
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('gold'); // Turn star gold
        } else {
            star.classList.remove('gold'); // Turn star grey
        }
    });
}
function toggleRating() {
    const ratingContainer = document.getElementById('rate-btn');
    // Toggle display between 'none' and 'block'
    ratingContainer.style.display = ratingContainer.style.display === 'none' ? 'block' : 'none';
}
</script>
</div>
<hr>

    <!-- Photo Carousel Section -->
    <div id="photoCarousel" class="carousel">
      <!-- Photos will be inserted here by JavaScript -->
    </div>
    <!-- Dots for navigation -->
    <div id="carouselDots" class="carousel-dots"></div>
  </div>
</div>

<!-- Place Details End Here -->




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
                    <a href="#" class="footer__link">Home</a>
                </li>
                <li>
                    <a href="profile.php" class="footer__link">Profile page</a>
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
<script src="https://unpkg.com/scrollreveal"></script>
<script src="scripts/homepage-js.js"></script>
<script src="scripts/script.js"></script>

<script>

//Discover Destenation Section Start Here



// Initialize variables
let currentIndexCFRS = 0; // Initialize current index for CFRS
const recommendations = <?php echo json_encode($recommendations); ?>; // Convert PHP array to JavaScript array

// Check if recommendations have data
if (recommendations && recommendations.length > 0) {
    // Show the CF section if there are recommendations
    const cfSection = document.getElementById('cf-section'); // Get the CF section by ID
    if (cfSection) {
        cfSection.style.display = 'block'; // Make the CF section visible
    }
} else {
    // Show a message to the user if there are no recommendations
    const messageContainer = document.getElementById('cf-message'); // Container for the message
    if (messageContainer) {
        // Create a new div for the error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'errormassage'; // Add the class for styling

        // Set the error message content
        errorDiv.innerHTML = `<p>No recommendations available to you because you do not have past ratings.</p>`;

        // Append the error div to the message container
        messageContainer.appendChild(errorDiv);

        // Make the message container visible
        messageContainer.style.display = 'block';
    }
}
// Cache to store fetched place images
const cfrsPlaceImageCache = {};



        
function renderCFRSPlaces() {
    const cfrsPlacesContainer = document.getElementById('CFproduct-container');
    cfrsPlacesContainer.innerHTML = ''; // Clear current recommendations

    // Display the next set of recommendations (3 at a time)
    for (let i = currentIndexCFRS; i < recommendations.length; i++) {
        const place = recommendations[i];
        const placeDiv = document.createElement('div');
        placeDiv.className = 'card'; // Apply uniform style

        placeDiv.innerHTML = `

            <div class="face front">
                <img id="cfrs-place-img-${place.place_id}" src="imgs/logo.png" alt="${place.place_name}" class="product-thumb">
                <div class="info-container">
                    <h3 class="product-brand">${place.place_name}</h3>
                    <button class="details-btn ri-arrow-right-line" data-id="${place.place_id}" data-lat="${place.lat}" data-lng="${place.lng}"></button>
                </div>
            </div>

            
                        
        `;

        cfrsPlacesContainer.appendChild(placeDiv); // Corrected this line

        // Attach click event to "More Details" button
        placeDiv.querySelector('.details-btn').addEventListener('click', function () {
            showDetails(place.place_id);
        });

        // Load the image for this place (check cache first)
        if (cfrsPlaceImageCache[place.place_id]) {
            // Use cached image
            document.getElementById(`cfrs-place-img-${place.place_id}`).src = cfrsPlaceImageCache[place.place_id];
        } else {
            // Fetch image details and cache it
            fetchCFRSPlaceImage(place.place_id);
        }
    }
}


// Function to fetch place image and cache it
function fetchCFRSPlaceImage(placeId) {
    fetch(`get_place_details.php?id=${placeId}`)
        .then(response => response.text())  // Get raw text
        .then(data => {
            try {
                const jsonData = JSON.parse(data);  // Parse JSON
                const placeImage = document.getElementById(`cfrs-place-img-${placeId}`);
                
                if (jsonData.photos && jsonData.photos.length > 0) {
                    // Use the first photo in the list
                    const firstPhoto = jsonData.photos[0];
                    const imageUrl = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${firstPhoto.photo_reference}&key=AIzaSyBKbwrFBautvuemLAp5-GpZUHGnR_gUFNs`;
                    placeImage.src = imageUrl;
                    // Cache the image URL
                    cfrsPlaceImageCache[placeId] = imageUrl;
                } else {
                    // No photos available, use default image
                    const defaultImage = 'imgs/logo.png';
                    placeImage.src = defaultImage;
                    cfrsPlaceImageCache[placeId] = defaultImage;
                }
            } catch (error) {
                console.error('Error parsing JSON:', error);
            }
        })
        .catch(error => {
            console.error('Error fetching place details:', error);
        });
}




renderCFRSPlaces();



//Discover Destenation Section END Here

//CF Section Start Here




//cxb 

let currentIndexCx= 0; // Initialize current index for CFRS
const context_recommendations = <?php echo json_encode($context_recommendations); ?> ;// Convert PHP array to JavaScript array

// Check if recommendations have data
if (context_recommendations && context_recommendations.length > 0) {
    // Show the CF section if there are recommendations
    const cxSection = document.getElementById('context-section'); // Get the CF section by ID
    if (cxSection) {
        cxSection.style.display = 'block'; // Make the CF section visible
    }
}
const cxPlaceImageCache = {};
function renderCXPlaces() {
    const cxPlacesContainer = document.getElementById('CXproduct-container');
    cxPlacesContainer.innerHTML = ''; // Clear current recommendations
    // <p class="product-short-description">Category: ${place.granular_category}</p>
    // Display the next set of recommendations (3 at a time)
    for (let i = currentIndexCx; i < context_recommendations.length; i++) {
        const place = context_recommendations[i];
        const placeDiv = document.createElement('div');
        placeDiv.className = 'card'; // Apply uniform style
        placeDiv.innerHTML = `

            <div class="face front">
            <img id="cx-place-img-${place.place_id}" src="imgs/logo.png" alt="${place.place_name}" class="product-thumb" >
            <div class="info-container">
                <h3 class="product-brand">${place.place_name}</h3>
                <button class="details-btn ri-arrow-right-line" data-id="${place.place_id}" data-lat="${place.lat}" data-lng="${place.lng}"></button>
            </div>
            </div>
           
        `;

        
        cxPlacesContainer.appendChild(placeDiv);

        // Attach click event to "More Details" button
        placeDiv.querySelector('.details-btn').addEventListener('click', function() {
            showDetails(place.place_id);
        });

        if (cxPlaceImageCache[place.place_id]) {
    document.getElementById(`cx-place-img-${place.place_id}`).src = cxPlaceImageCache[place.place_id];
} else {
    fetchCXPlaceImage(place.place_id);
}

    }

}

// Fetch place image for CX
function fetchCXPlaceImage(placeId) {
    fetch(`get_place_details.php?id=${placeId}`)
        .then(response => response.text())
        .then(data => {
            try {
                const jsonData = JSON.parse(data);
                const placeImage = document.getElementById(`cx-place-img-${placeId}`);
                if (jsonData.photos && jsonData.photos.length > 0) {
                    const firstPhoto = jsonData.photos[0];
                    const imageUrl = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${firstPhoto.photo_reference}&key=AIzaSyBKbwrFBautvuemLAp5-GpZUHGnR_gUFNs`;
                    placeImage.src = imageUrl;
                    cxPlaceImageCache[placeId] = imageUrl;
                } else {
                    placeImage.src = 'imgs/logo.png';
                    cxPlaceImageCache[placeId] = 'imgs/logo.png';
                }
            } catch (error) {
                console.error('Error parsing JSON:', error);
            }
        })
        .catch(error => {
            console.error('Error fetching place details:', error);
        });
}

renderCXPlaces();

// Initialize current index for iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii
let currentIndexHYBRID = 0; // Initialize index
const hybrid_recommendations = <?php echo json_encode($hybrid_recommendations); ?>;


// Check if recommendations have data
if (hybrid_recommendations && hybrid_recommendations.length > 0) {
    const HybridSection = document.getElementById('hybrid-section');
    if (HybridSection) {
        HybridSection.style.display = 'block'; // Show section if recommendations exist
    }
} else {
    // Show a message to the user if there are no recommendations
    const messageContainer = document.getElementById('hybrid-message'); // Container for the message
    if (messageContainer) {
        // Create a new div for the error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'errormassage'; // Add the class for styling

        // Set the error message content
        errorDiv.innerHTML = `<p>No recommendations available to you because you do not have past ratings.</p>`;

        // Append the error div to the message container
        messageContainer.appendChild(errorDiv);

        // Make the message container visible
        messageContainer.style.display = 'block';
    }
}
// Cache to store fetched place images
const hybridPlaceImageCache = {}; 

function renderHybridPlaces() {
    const HYPlacesContainer = document.getElementById('HYproduct-container');
    HYPlacesContainer.innerHTML = ''; // Clear previous recommendations

    // Display 3 recommendations at a time
    for (let i = currentIndexHYBRID; i < hybrid_recommendations.length; i++) {
        const place = hybrid_recommendations[i];
        const placeDiv = document.createElement('div');
        placeDiv.className = 'card';

        placeDiv.innerHTML = `
            <div class="face front">
                <img id="hybrid-place-img-${place.place_id}" src="imgs/logo.png" alt="${place.place_name}" class="product-thumb">

            <div class="info-container">
                <h3 class="product-brand">${place.place_name}</h2>
               
               
                <button class="details-btn ri-arrow-right-line" data-id="${place.place_id}" data-lat="${place.lat}" data-lng="${place.lng}"></button>
                </div>
            </div>
        `;

        HYPlacesContainer.appendChild(placeDiv);

        // Attach event listener to the "More Details" button
        placeDiv.querySelector('.details-btn').addEventListener('click', function () {
            showDetails(place.place_id);
        });

        // Load place image
        if (hybridPlaceImageCache[place.place_id]) {
            document.getElementById(`hybrid-place-img-${place.place_id}`).src = hybridPlaceImageCache[place.place_id];
        } else {
            fetchHybridPlaceImage(place.place_id);
        }
    }
}

// Fetch place image
function fetchHybridPlaceImage(placeId) {
    fetch(`get_place_details.php?id=${placeId}`)
        .then(response => response.text())
        .then(data => {
            try{
            const jsonData = JSON.parse(data);
            const placeImage = document.getElementById(`hybrid-place-img-${placeId}`);
            if (jsonData.photos && jsonData.photos.length > 0) {
                const firstPhoto = jsonData.photos[0];
                const imageUrl = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${firstPhoto.photo_reference}&key=AIzaSyBKbwrFBautvuemLAp5-GpZUHGnR_gUFNs`;
                placeImage.src = imageUrl;
                hybridPlaceImageCache[placeId] = imageUrl;
            } else {
                placeImage.src = 'imgs/logo.png';
                hybridPlaceImageCache[placeId] = 'imgs/logo.png';
            }
        } catch (error) {
            console.error('Error parsing JSON:', error);
        }
    })
    .catch(error => {
            console.error('Error fetching place details:', error);
        });
}

renderHybridPlaces();


/*
    document.querySelectorAll('.details-btn').forEach(button => {
    button.addEventListener('click', function () {
        // Get place_id from data-id attribute
        const placeId = this.getAttribute('data-id');
        // Assuming lat and lng are stored in data attributes on the button or accessible in scope
        const lat = this.getAttribute('data-lat'); // Get latitude from data attribute
        const lng = this.getAttribute('data-lng'); // Get longitude from data attribute
        
        // Call fetchPlaceDetailsAndPhotos function with placeId, lat, and lng
        showDetails(placeId);
    });
});

*/
function findBestPlace() {
    if (!hybrid_recommendations || hybrid_recommendations.length === 0) {
        console.log("No recommendations available.");
        return;
    }

    // Ensure every place has a rating, default to 0 if missing
    let bestPlace = hybrid_recommendations.reduce((best, place) => {
        if (!place.rating) place.rating = 0; // Default rating if undefined/null
        return place.rating > best.rating ? place : best;
    }, { rating: -1 }); // Start with an invalid low rating

    // If no valid best place found, stop
    if (bestPlace.rating === -1) {
        console.log("No valid best place found.");
        return;
    }

    console.log("Best Place Found:", bestPlace); // Debugging

    // Send the name and rating to the server via fetch
    fetch("store_best_place.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            place_name: bestPlace.place_name,
            rating: bestPlace.rating
        }),
    })
    .then(response => response.json())
    .then(data => console.log("Server Response:", data)) // Debugging
    .catch(error => console.error("Error storing best place:", error));
}

document.addEventListener("DOMContentLoaded", function () {
    findBestPlace(); // Run after page loads
});


    //reman -api photos
    function submitRating() {
    const placeId = document.getElementById('placeName').getAttribute('data-id');
    const requestData = {
        userId: <?php echo $user_id; ?>,
        placeId: placeId,
        rating: currentRating
    };

    fetch('save_rating.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Rating saved successfully!");
            closeModal();
        } else {
            alert("Error saving rating: " + data.error);
        }
    })
    .catch(error => console.error("Error submitting rating:", error));
}
$(document).on('click', '.favorite-btn', function() {
            const placeId = $('#placeName').attr('data-id');
            const userId = <?php echo $user_id; ?>;
            const button = $(this);
            const isBookmarked = button.hasClass('bookmarked'); // Check if already bookmarked.

            $.ajax({
                url: 'bookmark.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    placeId: placeId,
                    userId: userId,
                    action: isBookmarked ? 'remove' : 'add'
                },
                success: function(response) {
                    if (response.success) {
                        if (isBookmarked) {
                            button.removeClass('bookmarked').html('<i class="fas fa-bookmark"></i> Bookmark This Place');
                        } else {
                            button.addClass('bookmarked').html('<i class="fas fa-bookmark"></i> Bookmarked');
                        }
                    } else {
                        alert(response.error || 'An error occurred.');
                    }
                },
                error: function() {
                    alert('An error occurred while communicating with the server.');
                }
            });
        });
    function showDetails(placeId) {
        
    // Fetch details from the server
    fetch(`get_place_details.php?id=${placeId}`)
        .then(response => response.text())  // Get raw text
        .then(data => {
            console.log('Place Details Response:', data);  // Log the raw response
            try {
                const jsonData = JSON.parse(data);  // Parse JSON here
                document.getElementById('placeName').setAttribute('data-id', placeId);
                document.getElementById('placeName').innerText = jsonData.place_name;
                //document.getElementById('placeCategory').innerText = jsonData.categories;
                document.getElementById('placeGranularCategory').innerText = jsonData.granular_category;
                document.getElementById('placeRating').innerText = jsonData.average_rating;
                // Fetch and display previous rating if available
              // Fetch and display previous rating if available
              fetch('get_user_rating.php?userId=<?php echo $user_id; ?>&placeId=' + placeId)
    .then(response => response.json())
    .then(data => {
        console.log("Previous rating data:", data); // Debugging line
        currentRating = data.rating || 0; // Set to previous rating or 0 if none
        updateStarDisplay(currentRating);
    })
    .catch(error => console.error("Error fetching previous rating:", error));
                document.getElementById('detailsModal').style.display = "block";

                  // Populate photo carousel
                  const photoCarousel = document.getElementById('photoCarousel');
                photoCarousel.innerHTML = ''; // Clear previous photos if any

                if (jsonData.photos && jsonData.photos.length > 0) {
                    // Add photos to carousel if available
                    jsonData.photos.forEach(photo => {
                        const img = document.createElement('img');
                        img.src = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${photo.photo_reference}&key=AIzaSyBKbwrFBautvuemLAp5-GpZUHGnR_gUFNs`;
                        img.alt = 'Place photo';
                        img.classList.add('carousel-item'); // Optional: add CSS class for styling
                        photoCarousel.appendChild(img);
                    });
                } else if (jsonData.lat && jsonData.lng) {
                    // Fallback: Fetch photos based on lat/lng if no photos available
                    fetch(`get_photos_by_location.php?lat=${jsonData.lat}&lng=${jsonData.lng}`)
                        .then(response => response.json())
                        .then(locationPhotosData => {
                            if (locationPhotosData.photos && locationPhotosData.photos.length > 0) {
                                locationPhotosData.photos.forEach(photo => {
                                    const img = document.createElement('img');
                                    img.src = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${photo.photo_reference}&key=AIzaSyBKbwrFBautvuemLAp5-GpZUHGnR_gUFNs`;
                                    img.alt = 'Place photo';
                                    img.classList.add('carousel-item');
                                    photoCarousel.appendChild(img);
                                });
                            } else {
                                const defaultMessage = document.createElement('p');
                                defaultMessage.innerText = "No photos available for this place.";
                                photoCarousel.appendChild(defaultMessage);
                            }
                        })
                        .catch(error => console.error('Error fetching location-based photos:', error));
                }
            } catch (error) {
                console.error('Error parsing JSON:', error);
            }
        })
        .catch(error => console.error('Error fetching place details:', error));


    // Check if place is bookmarked and update button appearance
    $.ajax({
        url: 'check_bookmark.php',
        type: 'POST',
        dataType: 'json',
        data: {
            placeId: placeId,
            userId: <?php echo $user_id; ?>
        },
        success: function(response) {
            const favoriteButton = $('.favorite-btn');
            if (response.bookmarked) {
                favoriteButton.addClass('bookmarked').html('<i class="fas fa-bookmark"></i> Bookmarked');
            } else {
                favoriteButton.removeClass('bookmarked').html('<i class="fas fa-bookmark"></i> Bookmark This Place');
            }
        },
        error: function() {
            console.error('Error checking bookmark status.');
        }
    });
}

function closeModal() {
    document.getElementById('detailsModal').style.display = "none";
}


</script>

  </body>
  </html>
