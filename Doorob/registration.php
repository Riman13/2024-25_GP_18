<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="styles/registration.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/footer-header-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
     <link rel="shortcut icon" href="imgs/logo.png" type="image/x-icon">
</head>
<body>
  <header class="header" id="header">
    <nav class="nav container">
      <a href="#" class="nav__logo">
       
          <img src="imgs/logo.png" alt="Logo" class="nav__logo-img">
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

  <div style="height: 80px;"></div> 
    <div class="registration">
    <div class="container1" id="container1">
        <div class="form-container1 sign-in-container1">
            <form action="login.php" method="post">
                <h1>Sign In</h1>
                <span>Welcome Back!</span>
                <input type="email" placeholder="Email" id="email"  name="email" required/>
                <input type="password" placeholder="Password" id="password" name="password" required/>
                <button id="signin1">Sign In</button>
                <label for="resetPassword">
                 <a href="resetPassword.html" class="reset-password-link">
                        Reset Password <i class="fas fa-arrow-right"></i></a>
                </label>
            </form>
        </div>

        <div class="form-container1 sign-up-container1">
            <form action="signup.php" method="post">
                <h1>Create Account</h1>
                <span>Please enter your details below</span>
                <input type="text" placeholder="Name" id="name" name="name" required />
                <input type="email" placeholder="Email" id="eml" name="eml" required />
                <input type="password" placeholder="Password" id="pass" name="pass" required />
                <button id="signUp1">Sign Up</button>
            </form>
        </div>

        <div class="overlay-container1">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>You Already Have An Account?</h1>
                    <p>Login to continue your journey with us</p>
                    <button class="ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>New Here?</h1>
                    <p>Sign Up and start your journey with us</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container1');

        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });
    </script>
</div>
<div style="display: flex; justify-content: center; align-items: center;">
    <?php
        if(isset($_GET['error'])){
            echo '<p style="color:red; text-align: center;">'.$_GET['error'].'</p>';
        }
    ?>
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
</body>
</html>
