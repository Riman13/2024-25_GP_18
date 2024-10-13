const navMenu = document.getElementById('nav-menu');
  const navToggle = document.getElementById('nav-toggle');
  const navClose = document.getElementById('nav-close');
  const themeButton = document.getElementById('theme-button');
  const darkTheme = 'dark-theme';
  const iconTheme = 'bx-sun';
  if (navToggle) {
    navToggle.addEventListener('click', () => {
      navMenu.classList.add('show-menu');
    });
  }
  // Close the menu when the nav-close is clicked
  if (navClose) {
    navClose.addEventListener('click', () => {
      navMenu.classList.remove('show-menu');
    });
  }
  // Previously selected theme (if user selected it before)
  const selectedTheme = localStorage.getItem('selected-theme');
  const selectedIcon = localStorage.getItem('selected-icon');
  // Get current theme
  const getCurrentTheme = () => document.body.classList.contains(darkTheme) ? 'dark' : 'light';
  const getCurrentIcon = () => themeButton.classList.contains(iconTheme) ? 'bx-moon' : 'bx-sun';
  // Check if user previously chose a theme
  if (selectedTheme) {
    document.body.classList[selectedTheme === 'dark' ? 'add' : 'remove'](darkTheme);
    themeButton.classList[selectedIcon === 'bx-moon' ? 'add' : 'remove'](iconTheme);
  }
  // Activate / deactivate the theme manually
  themeButton.addEventListener('click', () => {
    // Add or remove the dark theme
    document.body.classList.toggle(darkTheme);
    themeButton.classList.toggle(iconTheme);
    // Save the user's choice
    localStorage.setItem('selected-theme', getCurrentTheme());
    localStorage.setItem('selected-icon', getCurrentIcon());
  });
  
/*=============== SHOW SCROLL UP ===============*/ 
const scrollUp = () =>{
	const scrollUp = document.getElementById('scroll-up')
    // When the scroll is higher than 350 viewport height, add the show-scroll class to the a tag with the scrollup class
	this.scrollY >= 350 ? scrollUp.classList.add('show-scroll')
						: scrollUp.classList.remove('show-scroll')
}
window.addEventListener('scroll', scrollUp)
document.getElementById('signout-button').addEventListener('click', function() {
  // Redirect to sign-out page or handle sign out logic
  window.location.href = '/signout';
});


        
        
