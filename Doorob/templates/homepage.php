<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php'; // Include the database connection
include 'session.php';
// Query to get the first 21 places


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
<?php
foreach ($places as &$place) {
    // For each place, send a request to the external API to get the image URL
    $api_image_url = 'http://api.example.com/get-image/' . $place['place_id'];  // Replace with your actual API URL
    
    // Use cURL to fetch the image URL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_image_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    // Assume the response contains the image URL
    $image_data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($image_data['image_url'])) {
        $place['image_url'] = $image_data['image_url'];  // Add the image URL to the place data
    } else {
        $place['image_url'] = 'default-image.jpg';  // Use a fallback image if no image is found
    }
}
function fetchPhotosByLatLng($lat, $lng) {
    $apiKey = 'AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g';
    $radius = 1000; // Adjust the radius as needed
    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=$lat,$lng&radius=$radius&key=$apiKey";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    // Fetch photos for the first place result
    if (!empty($data['results'])) {
        $firstPlaceId = $data['results'][0]['place_id'];
        return fetchPhotosByPlaceId($firstPlaceId);
    }

    return [];
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

// abeer

// Initialize variables
let currentIndex = 0; // Initialize current index
const places = <?php echo json_encode($places); ?>; // Convert PHP array to JavaScript array

// Cache to store fetched place images
const placeImageCache = {};

// Function to render places based on the current index
function renderPlaces() {
    const placesContainer = document.getElementById('placesContainer');
    placesContainer.innerHTML = ''; // Clear current places

    // Display the next set of places (3 at a time)
    for (let i = currentIndex; i < currentIndex + 3 && i < places.length; i++) {
        const place = places[i];
        const placeDiv = document.createElement('div');
        placeDiv.className = i === currentIndex + 1 ? 'place large' : 'place small';
        
        placeDiv.innerHTML = `
            <img id="place-img-${place.id}" src="imgs/logo.png" alt="${place.place_name}">
            <h3>${place.place_name}</h3>
            <p>Category: ${place.granular_category}</p>
            <p>Rating: ${'★'.repeat(Math.floor(place.average_rating)) + '☆'.repeat(5 - Math.floor(place.average_rating))}</p>
            <button class="details-btn" data-id="${place.place_id}" data-lat="${place.lat}" data-lng="${place.lng}">More Details</button>
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
    }

    // Disable arrows based on the current index
    document.getElementById('AllLeftArrow').disabled = currentIndex === 0;
    document.getElementById('AllRightArrow').disabled = currentIndex + 3 >= places.length;
}

// Function to fetch place image and cache it
function fetchPlaceImage(placeId) {
    fetch(`get_place_details.php?id=${placeId}`)
        .then(response => response.text())  // Get raw text
        .then(data => {
            try {
                const jsonData = JSON.parse(data);  // Parse JSON
                const placeImage = document.getElementById(`place-img-${placeId}`);
                
                if (jsonData.photos && jsonData.photos.length > 0) {
                    // Use the first photo in the list
                    const firstPhoto = jsonData.photos[0];
                    const imageUrl = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${firstPhoto.photo_reference}&key=AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g`;
                    placeImage.src = imageUrl;
                    // Cache the image URL
                    placeImageCache[placeId] = imageUrl;
                } else {
                    // No photos available, use default image
                    const defaultImage = 'imgs/logo.png';
                    placeImage.src = defaultImage;
                    placeImageCache[placeId] = defaultImage;
                }
            } catch (error) {
                console.error('Error parsing JSON:', error);
            }
        })
        .catch(error => {
            console.error('Error fetching place details:', error);
        });
}

// Function to navigate through places
function navigate(direction) {
    const newIndex = currentIndex + direction;
    
    // Ensure the new index is within bounds
    if (newIndex >= 0 && newIndex + 3 <= places.length) {
        currentIndex = newIndex;
        renderPlaces(); // Update the displayed places
    }
}

// Initial rendering of places
renderPlaces();

// Initialize variables
let currentIndexCFRS = 0; // Initialize current index for CFRS
const recommendations = <?php echo json_encode($recommendations); ?>; // Convert PHP array to JavaScript array

// Cache to store fetched place images
const cfrsPlaceImageCache = {};

// Function to render CFRS recommendations based on the current index
function renderCFRSPlaces() {
    const cfrsPlacesContainer = document.getElementById('cfrsPlacesContainer');
    cfrsPlacesContainer.innerHTML = ''; // Clear current recommendations

    // Display the next set of recommendations (3 at a time)
    for (let i = currentIndexCFRS; i < currentIndexCFRS + 3 && i < recommendations.length; i++) {
        const place = recommendations[i];
        const placeDiv = document.createElement('div');
        placeDiv.className = i === currentIndexCFRS + 1 ? 'place large' : 'place small';

        // Calculate the average rating for display
        const averageRating = parseFloat(place.average_rating); // Ensure to parse the rating as a float
        const filledStars = '★'.repeat(Math.floor(averageRating)); // Filled stars based on the rating
        const emptyStars = '☆'.repeat(5 - Math.floor(averageRating)); // Empty stars to fill up to 5
        const ratingDisplay = filledStars + emptyStars; // Combine filled and empty stars

        placeDiv.innerHTML = `
            <img id="cfrs-place-img-${place.place_id}" src='imgs/logo.png' alt='${place.place_name}'>
            <h3>${place.place_name}</h3>
            <p>Category: ${place.granular_category}</p>
            <p>Rating: ${ratingDisplay}</p> <!-- Use the calculated rating display here -->
            <button class="details-btn" data-id="${place.place_id}" data-lat="${place.lat}" data-lng="${place.lng}">More Details</button>
        `;
        
        cfrsPlacesContainer.appendChild(placeDiv);

        // Attach click event to "More Details" button
        placeDiv.querySelector('.details-btn').addEventListener('click', function() {
            showDetails(place.place_id); // Fixed to use place.place_id instead of place.id
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

    // Disable arrows based on the current index
    document.getElementById('cfrsLeftArrow').disabled = currentIndexCFRS === 0;
    document.getElementById('cfrsRightArrow').disabled = currentIndexCFRS + 3 >= recommendations.length;
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
                    const imageUrl = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${firstPhoto.photo_reference}&key=AIzaSyAXILlpWx0kAcGYMB6VeRbDSzyRw2Xsg9g`;
                    placeImage.src = imageUrl;
                    // Cache the image URL
                    cfrsPlaceImageCache[placeId] = imageUrl;
                } else {
                    // No photos available, use default image
                    const defaultImage = 'imgs/Riyadh.jpg';
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

// Function to navigate through CFRS recommendations
function navigateCFRS(direction) {
    const newIndex = currentIndexCFRS + direction;
    
    // Ensure the new index is within bounds
    if (newIndex >= 0 && newIndex + 3 <= recommendations.length) {
        currentIndexCFRS = newIndex;
        renderCFRSPlaces(); // Update the displayed recommendations
    }
}

// Initial rendering of CFRS places
renderCFRSPlaces();

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