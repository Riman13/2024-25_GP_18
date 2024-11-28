<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="styles/reset-password.css">
    <link rel="stylesheet" href="styles/footer-header-styles.css">
    <!--======== ICONS ========-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
     <!--======== WEBSITEICON ========-->
     <link rel="shortcut icon" href="imgs/logo.png" type="image/x-icon">
</head>
<div>

<header class="header" id="header">
    <nav class="nav container">
      <a href="#" class="nav__logo">
       
          <img src="imgs/logo.png" alt="Logo" class="nav__logo-img">
          Doroob
        
      </a>
      <div class="nav__menu" id="nav-menu">
  
        <div class="nav__close" id="nav-close">
          <i class='bx bx-x'></i>
        </div>
      </div>
  
      <div class="nav__btns">
     
        <!--<i class='bx bx-moon change-theme' id="theme-button"></i>-->

  
        
        <div class="nav__toggle" id="nav-toggle">
          <i class='bx bx-grid-alt' ></i>
      </div>
      </div>
    </nav>
  </header>
  <div style="height: 40px;"></div> 
 
  <div class="reset">
    <div class="container2">
<div class="form-container2 reset-password-container2">
    <h1>Reset Password</h1>

    <form id="resetPasswordForm" method="post">
        <div class="form-group">
            <label for="email">Please enter your email to send a password reset link</label>
            <input type="email" name="email" id="email" required placeholder="Enter your email">
        </div>
        <button type="submit" class="submit-btn">Send</button>
    </form>

    <div class="message" id="message"></div>
</div>
    </div>

<script>
    const form = document.getElementById('resetPasswordForm');
    const messageDiv = document.getElementById('message');
    const emailInput = form.email;


    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent default form submission

        const email = form.email.value; // Get email value

        // Send the email to the PHP script using Fetch API
        fetch('send-password-reset.php', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email }) // Send the email as a JSON object
        })
        .then(response => response.json()) // Parse response as JSON
        .then(data => {
            // Check for success response from PHP
            if (data.success) {
                messageDiv.textContent = 'Message sent, please check your inbox.';
                messageDiv.style.color = 'green';
            } else {
                messageDiv.textContent = 'The entered email does not exist.';
                messageDiv.style.color = 'red';
            }
        })
        .catch(error => {
            messageDiv.textContent = 'An error occurred. Please try again.';
            messageDiv.style.color = 'red';
        });
    });

        // Clear the message when the user focuses on the email input
        emailInput.addEventListener('focus', function() {
        messageDiv.textContent = ''; // Clear the message
    });
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
                        <a href="Index.php" class="footer__link">About Us</a>
                    </li>
                    
                </ul>
            </div>
      
            <div class="footer__content">
                <h3 class="footer__title section__title">Doroob</h3>
      
                <ul class="footer__links">
                    <li>
                        <a href="   Index.php" class="footer__link">Home</a>
                    </li>
                    
                </ul>
            </div>
      
        <!--    <div class="footer__content">
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
            </div>-->
        </div>
      
        <span class="footer__copy">Doorob &#169;All rigths reserved</span>
      </footer>
      
       <!--========== JS ==========-->
       <script src="scripts/scripts-fh.js"></script>
</body>
</html>