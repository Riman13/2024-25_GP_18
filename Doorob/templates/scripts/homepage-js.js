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
    delay: 2500,
});
ScrollReveal().reveal(".intro-image-card3", {
    ...scrollRevealOrtion,
    duration: 1000,
    interval: 500,
    delay: 2500,
});
ScrollReveal().reveal(".intro-image-card4", {
    ...scrollRevealOrtion,
    duration: 1000,
    interval: 500,
    delay: 2500,
});
ScrollReveal().reveal(".intro-image-card5", {
    ...scrollRevealOrtion,
    duration: 1000,
    interval: 500,
    delay: 2500,
});
