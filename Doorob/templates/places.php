<?php
include 'config.php'; 
include 'session.php';
$sql = "SELECT id, place_name, granular_category, average_rating , place_id FROM  riyadhplaces_doroob";
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
    <h2>What are you looking for?</h2>
    <div class="filter-boxes">
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
        <button class="favorite-btn"><i class="fas fa-heart"></i> Favorite This Place</button>
      </div>
      <div class="divider"></div>
      <div class="right-section">
    <p><strong>Rating:</strong> <span id="placeRating"></span></p><br>
    <div class="right-section">
    <p><strong>Rate This Place:</strong></p>
    <select id="ratingDropdown">
        <option value="" disabled selected>Select your rating</option>
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
    </select>
    <button class="submit-rating-btn" onclick="submitRating()">Submit</button>
</div>
</div>
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
            <button class="details-btn" data-id="${place.place_id}" data-lat="${place.lat}" data-lng="${place.lng}" >More Details</button>
        `;
        placesContainer.appendChild(placeDiv);
        placeDiv.querySelector('.details-btn').addEventListener('click', function() {
            showDetails(place.id); })
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

    //reman -api photos
    function submitRating() {
    const ratingDropdown = document.getElementById('ratingDropdown');
    const selectedRating = parseInt(ratingDropdown.value);

    if (!selectedRating) {
        alert("Please select a rating before submitting.");
        return;
    }

    const placeId = document.getElementById('placeName').getAttribute('data-id');

    const requestData = {
        userId: <?php echo $user_id; ?>,
        placeId: placeId,
        rating: selectedRating
    };
    console.log("Request Data:", requestData);

    fetch('save_rating.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        console.log("Server Response:", data);
        if (data.success) {
            alert("Rating submitted successfully!");
            closeModal();
        } else {
            alert("Error submitting rating: " + data.error);
        }
    })
    .catch(error => {
        console.error("Error submitting rating:", error);
        alert("An error occurred. Please try again.");
    });
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
                document.getElementById('detailsModal').style.display = "block";

                  // Populate photo carousel
                  const photoCarousel = document.getElementById('photoCarousel');
                photoCarousel.innerHTML = ''; // Clear previous photos if any

                if (jsonData.photos && jsonData.photos.length > 0) {
                    // Add photos to carousel if available
                    jsonData.photos.forEach(photo => {
                        const img = document.createElement('img');
                        img.src = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${photo.photo_reference}&key=AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g`;
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
                                    img.src = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${photo.photo_reference}&key=AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g`;
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