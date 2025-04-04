<?php
include 'config.php'; 
include 'session.php';

// Query to get places from the database
$sql = "SELECT id, place_name, granular_category, average_rating , place_id FROM riyadhplaces_doroob";
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

// Query to get the user's name
$query = "SELECT Name FROM users WHERE UserID = $user_id";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $username = $user['Name'];
} else {
    $username = "Guest";
}

// API call to fetch context-based recommendations
$context_api_url = 'http://127.0.0.1:5002/api/recommendations_context/' . $user_id;
$ch_context = curl_init();
curl_setopt($ch_context, CURLOPT_URL, $context_api_url);
curl_setopt($ch_context, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_context, CURLOPT_HTTPGET, true);
curl_setopt($ch_context, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch_context, CURLOPT_TIMEOUT, 10); 

$context_response = curl_exec($ch_context);
$context_http_status = curl_getinfo($ch_context, CURLINFO_HTTP_CODE);
curl_close($ch_context);

$context_recommendations = [];
if ($context_response && $context_http_status == 200) {
    $context_recommendations = json_decode($context_response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $context_recommendations = [];
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
 <div class="filter-container">
    <h2>Best Nearby Destinations </h2>
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


<!-- Modal Structure -->
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

    <!-- Photo Carousel Section -->
    <div id="photoCarousel" class="carousel">
      <!-- Photos will be inserted here by JavaScript -->
    </div>
    <!-- Dots for navigation -->
    <div id="carouselDots" class="carousel-dots"></div>
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

// Initialize variables
let places = <?php echo json_encode($context_recommendations); ?>;
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
            showDetails(place.id);
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
            closeModal();
        } else {
            alert("Error saving rating: " + data.error);
        }
    })
    .catch(error => console.error("Error submitting rating:", error));
}
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
}

function closeModal() {
    document.getElementById('detailsModal').style.display = "none";
}


</script>

   
  </body>
  </html>
