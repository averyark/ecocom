/**
 * Header component functionality
 * This file contains all JavaScript related to the header component
 */

// Initialize header functionality
function initializeHeader() {
    // Get the sidebar elements
    const sidebar = document.getElementById('mySidebar');
    const menuBtn = document.getElementById('menuBtn');
    const closeBtn = document.querySelector('.header-sidebar .closebtn');

    if (sidebar && menuBtn && closeBtn) {
        // Toggle sidebar when menu button is clicked
        menuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when close button is clicked
        closeBtn.addEventListener('click', function() {
            sidebar.classList.remove('open');
        });

        // Close sidebar when clicking outside of it
        document.addEventListener('click', function(event) {
            if (!sidebar.contains(event.target) &&
                event.target !== menuBtn &&
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });

        // Stop propagation on sidebar clicks to prevent close-when-clicking-outside behavior
        sidebar.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    }

    // Profile dropdown functionality
    const profileBtn = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdown');

    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });

        // Close dropdown when clicking elsewhere on the page
        document.addEventListener('click', function(e) {
            if (profileDropdown.classList.contains('show') && !e.target.closest('.header-profile-container')) {
                profileDropdown.classList.remove('show');
            }
        });
    }

    // Notification functionality
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');

    if (notificationBtn && notificationDropdown) {
        // Toggle notification dropdown
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking elsewhere on the page
        document.addEventListener('click', function(e) {
            if (notificationDropdown.classList.contains('active') && !e.target.closest('.header-notification-container')) {
                notificationDropdown.classList.remove('active');
            }
        });
    }
}

/* Set the width of the sidebar to 250px and the left margin of the page content to 250px */
function openNav() {
    document.getElementById("mySidebar").style.width = "250px";
    document.body.style.marginLeft = "250px";
}

/* Set the width of the sidebar to 0 and the left margin of the page content to 0 */
function closeNav() {
    document.getElementById("mySidebar").style.width = "0";
    document.body.style.marginLeft = "0";
}

// If this script is loaded directly (not through the component loader)
// initialize the header when the DOM is loaded
if (typeof headerLoaded === 'undefined') {
    document.addEventListener('DOMContentLoaded', initializeHeader);
}
// initializeHeader()