function scrollToDestinations() {
    document.getElementById('destinations').scrollIntoView({
        behavior: 'smooth'
    });
}
const scrollRevealOrtion = {
    duration: 1000,    
    distance: '50px',  
    origin: 'bottom',  
    opacity: 0         
};

ScrollReveal().reveal(".intro-image-card1", {
    ...scrollRevealOrtion,
    duration: 1000,
    interval: 500,
    delay: 700,
});
ScrollReveal().reveal(".intro-content h1", {
    ...scrollRevealOrtion,
    delay: 400,
});
ScrollReveal().reveal(".intro-content p", {
    ...scrollRevealOrtion,
    delay: 1500,
});
ScrollReveal().reveal(".intro-content .cta-btn", {
    ...scrollRevealOrtion,
    delay: 2000,      
});

ScrollReveal().reveal(".intro-image-card2", {
    ...scrollRevealOrtion,
    duration: 1000,
    interval: 500,
    delay: 2400,
});
ScrollReveal().reveal(".intro-image-card3", {
    ...scrollRevealOrtion,
    duration: 1000,
    interval: 500,
    delay: 2400,
});
ScrollReveal().reveal(".intro-image-card4", {
    ...scrollRevealOrtion,
    duration: 1000,
    interval: 500,
    delay: 2400,
});
ScrollReveal().reveal(".intro-image-card5", {
    ...scrollRevealOrtion,
    duration: 1000,
    interval: 500,
    delay: 3000,
});

// To show a specific section dynamically
document.getElementById("context-section").style.display = "none"; 
document.getElementById("content-section").style.display = "none";  
document.getElementById("cf-section").style.display = "none"; 
document.getElementById("hybrid-section").style.display = "none"; 
