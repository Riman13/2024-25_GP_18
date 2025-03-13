<?php
include 'config.php'; 
include 'session.php';
$sql = "SELECT id, place_name, granular_category, average_rating , place_id ,lng ,lat FROM  riyadhplaces_doroob";
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
<link rel="stylesheet" href="styles/places.css">


<!--======== ICONS ========-->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

<!--======== WEBSITE ICON ========-->
<link rel="shortcut icon" href="imgs/logo.png" type="image/x-icon">
       
</head>
<div>

<!--============ Header =============-->
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
            <a href="profile.php" class="nav__link">Profile</a>
          </li>
          <li class="nav__item">
            <a href="places.php" class="nav__link active-link">Places</a>
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
 <div class="filter-container" id="filt">
    <h2>What are you looking for?</h2>
    <div class="filter-boxes">
    <div class="filter-box" data-category="all" onclick="filterPlaces('all')">
            <i class="fas fa-list"></i>
            <p>Show All</p>
        </div>
        <div class="filter-box" data-category="restaurant" onclick="filterPlaces('restaurant')">
            <i class="fas fa-utensils"></i>
            <p>Restaurants</p>
        </div>
        <div class="filter-box" data-category="hotel" onclick="filterPlaces('hotel')">
            <i class="fas fa-hotel"></i>
            <p>Hotels</p>
        </div>
        <div class="filter-box" data-category="shopping_mall" onclick="filterPlaces('shopping_mall')">
            <i class="fas fa-shopping-bag"></i>
            <p>Malls</p>
        </div>
        <div class="filter-box" data-category="cafe" onclick="filterPlaces('cafe')">
            <i class="fas fa-coffee"></i>
            <p>Cafes</p>
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
<div class="pagination" id="paginationss">
    <button onclick="navigate(-1)">&#8249;</button> <!-- Previous button -->
    <span id="currentPage">1</span> <!-- Current page indicator -->
    <button onclick="navigate(1)">&#8250;</button> <!-- Next button -->
</div>


<!-- Modal Structure -->
<div id="detailsModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <i class="fas fa-map-marker-alt name-icon"></i>
    <h2 id="placeName"></h2>
    <hr>

<!-- Photo Carousel Section -->
<div id="photoCarousel" class="carousel">
  <!-- Photos will be inserted here by JavaScript -->
</div>

<!--FER notification-->
<div class="notifications"></div>

<!-- Dots for navigation -->
<div id="carouselDots" class="carousel-dots"></div>
    <hr> <!-- Line under the name -->
    <div class="info-row">
      <div class="left-section">
      <!--  <p><strong>Category:</strong> <span id="placeCategory"></span></p>-->
        <p><strong>Granular Category:</strong> <span id="placeGranularCategory"></span></p><br>
        <button class="favorite-btn"><i class="fas fa-bookmark"></i> Save This Place</button>
        
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
    <div id="carouselDots" class="carousel-dots"></div>
  <!-- CF error -->
<section class="product" id="cf-message" style="display: none;">
<h3 class="product-category">Recommended Destinations Based on What Others Like</h3>
</section>
<!-- CF results -->
<section class="product" id="cf-section" style="display: none;"> 
    <!--Recommended Destinations Based on What Others Like--> 
    <h3 class="product-category">Favorites Destinations Inspired by Similar Users <a href="all_places_CFRS.html" class="view-all-link">
    <img src="imgs/arrow.png" alt="View All" class="view-all-arrow">
    </a> </h3>
    <button class="pre-btn"><img src="imgs/arrow.png" alt=""></button>
    <button class="nxt-btn"><img src="imgs/arrow.png" alt=""></button>
    <div class="product-container" id="CFproduct-container">
    </div>
</section>


<!-- Context -->
<section class="product" id="context-section"> 
    <!--Best Places for You Based on Your Location --> 
    <h3 class="product-category">Best Nearby Destinations <a href="all_places_Context.php" class="view-all-link">
        <img src="imgs/arrow.png" alt="View All" class="view-all-arrow">
    </a> </h3>
    <button class="pre-btn"><img src="imgs/arrow.png" alt=""></button>
    <button class="nxt-btn"><img src="imgs/arrow.png" alt=""></button>
    <div class="product-container" id="CXproduct-container">
    </div>
</section>

<!-- Hybrid -->
<section class="product" id="hybrid-section"> 
    <!--Personalized Destinations Just for You-->
    <h3 class="product-category">Top Recommended Destinations for You <a href="all_places_hybird.php" class="view-all-link">
        <img src="imgs/arrow.png" alt="View All" class="view-all-arrow">
    </a></h3>
    <button class="pre-btn"><img src="imgs/arrow.png" alt=""></button>
    <button class="nxt-btn"><img src="imgs/arrow.png" alt=""></button>
    <div class="product-container" id="HYproduct-container">
    </div>
</section>
<!-- Recommendation End Here -->
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
</div>

</div>
   

 <!--============ FOOTER =============-->
 <footer class="footer section" id="foort">
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

<script>

//FER messages script 

let notifications = document.querySelector('.notifications');
// Flag to check if the second info toast has been shown
let hasShownSecondInfoToast = false;

// Generalized createToast function without countdown
function createToast(type, title, message) {
    let newToast = document.createElement('div');
    newToast.classList.add('toast', type);

// Apply different background colors based on the toast type
if (type === 'success') {
    newToast.style.backgroundImage = 'linear-gradient(to right, hsl(150, 40%, 95%), hsl(150, 45%, 85%) 30%)'; // Soft, cool green
} else if (type === 'info') {
    newToast.style.backgroundImage = 'linear-gradient(to right, hsl(210, 36%, 96%), hsl(200, 42%, 85%) 30%)'; // Soft blue
}

    newToast.innerHTML = `
        <i class="${type === 'success' ? 'fa-solid fa-check-circle' : 'fa-solid fa-circle-info'}"></i>
        <div class="content">
            <div class="title">${title}</div>
            <span>${message}</span>
        </div>
    `;
    notifications.appendChild(newToast);

    // Ensure toast stays for 10 seconds before removal
    setTimeout(() => {
        // Remove the toast after 10 seconds
        if (newToast) {
            newToast.remove();
        }

        // Only show the second toast after the first one is removed
        if (type === 'info' && !hasShownSecondInfoToast) {
            hasShownSecondInfoToast = true;
            createToast('info', 'info', 'We have started analyzing your emotions.');
        }

        // Only show the second toast after the first one is removed
        if (type === 'success' && hasShownSecondInfoToast) {
            hasShownSecondInfoToast = false;
        }
    }, 7500);  // Toast will disappear after 10 seconds

}

// Success Toast with a different icon and message arrangement
function showSuccessToast(rating) {
    createToast('success', 'Analysis Complete', 
    `Your emotion analysis is complete with a rating of ${rating}.`);

}

//END FER messages

// Initialize variables
let places = <?php echo json_encode($places); ?>;
let filteredPlaces = places; // Use this for filtering
let currentIndex = 0;
const placesPerPage = 12;

const placeImageCache = {};

// Function to render places based on the current index
function renderPlaces() {
    const placesContainer = document.getElementById('placesContainer');
    placesContainer.innerHTML = ''; // Clear current places

    // Display the next set of places
    const displayedPlaces = filteredPlaces.slice(currentIndex, currentIndex + placesPerPage);
    displayedPlaces.forEach((place, index) => {
        const placeDiv = document.createElement('div');
        placeDiv.className = 'place';
        
        placeDiv.innerHTML = `
            <img id="place-img-${place.id}" src="imgs/logo.png" alt="${place.place_name}">
            <h3>${place.place_name}</h3>
            <p>Category: ${place.granular_category}</p>
<p>
  Rating: ${
    place.average_rating === 'Not rated'
      ? '☆☆☆☆☆'
      : '★'.repeat(Math.floor(place.average_rating)) + '☆'.repeat(5 - Math.floor(place.average_rating))
  }
</p>             <button class="details-btn" data-id="${place.place_id}" data-lat="${place.latitude}" data-lng="${place.longitude}">More Details</button>
        `;
        
        placesContainer.appendChild(placeDiv);

        // Attach click event to "More Details" button
        placeDiv.querySelector('.details-btn').addEventListener('click', function() {
            const lat = parseFloat(this.getAttribute('data-lat'));
    const lng = parseFloat(this.getAttribute('data-lng'));
    showDetails(place.id, lat, lng);
        });

        // Load the image for this place (check cache first)
        if (placeImageCache[place.id]) {
            // Use cached image
            document.getElementById(`place-img-${place.id}`).src = placeImageCache[place.id];
        } else {
            // Fetch image details and cache it
            fetchPlaceImage(place.id);
        }
    });

    document.getElementById('currentPage').innerText = Math.floor(currentIndex / placesPerPage) + 1;
}

// Function to fetch place image and cache it
function fetchPlaceImage(placeId) {
    fetch(`get_place_details.php?id=${placeId}`)
        .then(response => response.json()) // Get JSON response
        .then(data => {
            const placeImage = document.getElementById(`place-img-${placeId}`);
            
            if (data.photos && data.photos.length > 0) {
                // Use the first photo in the list
                const firstPhoto = data.photos[0];
                const imageUrl = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${firstPhoto.photo_reference}&key=AIzaSyBKbwrFBautvuemLAp5-GpZUHGnR_gUFNs`;
                placeImage.src = imageUrl;
                // Cache the image URL
                placeImageCache[placeId] = imageUrl;
            } else {
                // No photos available, use default image
                placeImage.src = 'imgs/logo.png'; // Fallback image
                placeImageCache[placeId] = 'imgs/logo.png'; // Cache the fallback
            }
        })
        .catch(error => {
            console.error('Error fetching place details:', error);
            document.getElementById(`place-img-${placeId}`).src = 'imgs/logo.png'; // Fallback in case of error
        });
}

// Function to navigate through pages
function navigate(direction) {
    const newIndex = currentIndex + direction * placesPerPage;
    if (newIndex >= 0 && newIndex < filteredPlaces.length) {
        currentIndex = newIndex;
        renderPlaces(); // Update the displayed places
    }
}

// Function to filter places by category
function filterPlaces(category) {
    if (category === 'all') {
        // If "Show All" is selected, reset filteredPlaces to all places
        filteredPlaces = places; // Show all places
    } else {
        // Filter based on the selected category
        filteredPlaces = places.filter(place => place.granular_category === category);
    }
    currentIndex = 0; // Reset to the first page
    renderPlaces(); // Update the displayed places
}

// Initial render
renderPlaces();

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

let currentSessionId = Date.now().toString();
    function showDetails(placeId, lat, lng) {

        //first FER message
        createToast('info', 'Info', 'In the next few moments, we\'ll analyze your emotions about this place to improve your recommendations.');

        document.getElementById('placesContainer').style.display = 'none';
        document.getElementById('paginationss').style.visibility = 'hidden';
        document.getElementById('foort').style.visibility = 'hidden';
        document.getElementById('filt').style.visibility = 'hidden';
        currentSessionId = Date.now().toString(); // Generate new session ID for each place

    fetch('http://127.0.0.1:5000/start_emotion_analysis', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            userId: <?php echo $user_id; ?>,
            placeId: placeId,
            sessionId: currentSessionId
        })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              console.log("Emotion analysis started for session:", currentSessionId);
              setTimeout(() => {
              checkForRating(currentSessionId);
          }, 15000); // Wait for 15 seconds to allow enough time for emotion analysis
          } else {
              console.error("Error starting emotion analysis:", data.error);
          }
      }).catch(error => console.error("Error:", error));

      //retraive emotion rating 

      let currentRating = 0; // Store the rating persistently

function checkForRating(sessionId) {
    fetch(`http://127.0.0.1:5000/get_rating?sessionId=${sessionId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessToast(data.rating); // Call the success toast with the rating data
            currentRating = data.rating; // Store fetched rating
            updateStarDisplay(currentRating);
            document.getElementById("rate-btn").style.display = "block"; // Show rating section
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(error => console.error("Error checking rating:", error));
}


// Function to update stars dynamically based on rating
function updateStarDisplay(rating) {
    const stars = document.querySelectorAll('#starRating .star');
    
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('gold'); // Highlight stars up to the rating
        } else {
            star.classList.remove('gold'); // Reset other stars
        }
    });
}

// Event listeners for hover and click effects
document.querySelectorAll('#starRating .star').forEach((star, index) => {
    star.addEventListener('mouseover', () => updateStarDisplay(index + 1)); // Hover effect
    star.addEventListener('mouseout', () => updateStarDisplay(currentRating)); // Reset to actual rating
    star.addEventListener('click', () => {
        currentRating = index + 1; // Update rating on click
        updateStarDisplay(currentRating);
    });
});



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
        messageContainer.innerHTML = '';
        messageContainer.innerHTML = ` <h3 class="product-category">Recommended Destinations Based on What Others Like</h3>`;

        // Clear any existing content in the message container
        // Create a new div for the error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'errormassage'; // Add the class for styling
        
        errorDiv.innerHTML = ` <p>No recommendations available to you because you do not have past ratings.</p>`;

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
                    <button class="details-btn ri-arrow-right-line" data-id="${place.place_id}" data-lat="${place.latitude}" data-lng="${place.longitude}"></button>
                </div>
            </div>
            

            
                        
        `;

        cfrsPlacesContainer.appendChild(placeDiv); // Corrected this line

        // Attach click event to "More Details" button
        placeDiv.querySelector('.details-btn').addEventListener('click', function() {
            // Attach click event to "More Details" button
            const lat = parseFloat(this.getAttribute('data-lat'));
            const lng = parseFloat(this.getAttribute('data-lng'));
            showDetails(place.place_id, lat, lng);
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
                <button class="details-btn ri-arrow-right-line" data-id="${place.place_id}" data-lat="${place.latitude}" data-lng="${place.longitude}"></button>
            </div>
            </div>
           
        `;

        
        cxPlacesContainer.appendChild(placeDiv);

        // Attach click event to "More Details" button
        placeDiv.querySelector('.details-btn').addEventListener('click', function() {
            // Attach click event to "More Details" button
            const lat = parseFloat(this.getAttribute('data-lat'));
            const lng = parseFloat(this.getAttribute('data-lng'));
            showDetails(place.place_id, lat, lng);
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
        })
        .catch(error => console.error('Error fetching place details:', error));
}
function closeModal() {
     // Stop the emotion analysis when the modal is closed
     fetch('http://127.0.0.1:5000/stop_emotion_analysis', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sessionId: currentSessionId })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              console.log("Emotion analysis stopped for session:", currentSessionId);
          } else {
              console.error("Error stopping emotion analysis:", data.error);
          }
      }).catch(error => console.error("Error:", error));
    document.getElementById('detailsModal').style.display = "none";
    document.getElementById('placesContainer').style.display = 'grid';
    document.getElementById('paginationss').style.visibility = 'visible';
    document.getElementById('foort').style.visibility = 'visible';
    document.getElementById('filt').style.visibility = 'visible';  
}

// Ensure analysis stops when the page is closed or refreshed
window.addEventListener('beforeunload', () => {
    fetch('http://127.0.0.1:5000/stop_emotion_analysis', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sessionId: currentSessionId })
    });
});
</script>
<script 
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBKbwrFBautvuemLAp5-GpZUHGnR_gUFNs&callback=initMap&libraries=marker"
    async
    defer>
</script>
   
  </body>
  </html>
