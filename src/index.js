// Sticky navbar scroll effect
window.addEventListener("scroll", function () {
    const navbar = document.querySelector(".nav-bar");
    const scrollY = window.scrollY;

    if (scrollY > 60) {
      navbar.classList.add("sticky");
    } else {
      navbar.classList.remove("sticky");
    }
});

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any required functionality for the index page
    console.log('Index page loaded');

    // Add any index-specific JavaScript here
});
