/**
 * Profile Page JavaScript
 * Handles form validation and interactions for the profile page
 */

document.addEventListener('DOMContentLoaded', function() {
  // Initialize the profile page
  initializeProfilePage();
});

/**
 * Initialize the profile page
 */
function initializeProfilePage() {
  // Get the current username from session and populate the field
  populateCurrentUsername();

  // Set up event listeners for the account form
  setupAccountForm();
}

/**
 * Populate the current username field
 */
function populateCurrentUsername() {
  // In a real application, this would fetch the username from the server
  // For now, we'll use a placeholder or localStorage if available
  const currentUsername = localStorage.getItem('username') || 'CurrentUser';
  document.getElementById('current-username').value = currentUsername;
}

/**
 * Check if a password contains alphanumeric characters and at least one symbol
 * @param {string} password - The password to validate
 * @returns {boolean} - True if password meets requirements, false otherwise
 */
function isValidPassword(password) {
  // Check for at least one letter
  const hasLetter = /[a-zA-Z]/.test(password);

  // Check for at least one number
  const hasNumber = /[0-9]/.test(password);

  // Check for at least one special character
  const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);

  return hasLetter && hasNumber && hasSpecial;
}

/**
 * Set up the account form
 */
function setupAccountForm() {
  const accountForm = document.getElementById('accountForm');
  const usernameError = document.getElementById('username-error');
  const passwordError = document.getElementById('password-error');
  const confirmPasswordError = document.getElementById('confirm-password-error');
  const formSuccess = document.getElementById('form-success');

  accountForm.addEventListener('submit', function(e) {
    e.preventDefault();

    // Clear previous messages
    usernameError.textContent = '';
    passwordError.textContent = '';
    confirmPasswordError.textContent = '';
    formSuccess.textContent = '';

    // Get form values
    const newUsername = document.getElementById('new-username').value.trim();
    const currentPassword = document.getElementById('current-password').value;
    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;

    // Flag to track if validation passes
    let isValid = true;

    // Validate username (only required)
    if (!newUsername) {
      usernameError.textContent = 'Please enter a new username';
      isValid = false;
    }

    // Validate current password
    if (!currentPassword) {
      passwordError.textContent = 'Please enter your current password';
      isValid = false;
    }

    // Validate new password
    if (newPassword) {
      // Check if password meets requirements
      if (!isValidPassword(newPassword)) {
        passwordError.textContent = 'Password must contain letters, numbers, and at least one special character';
        isValid = false;
      }

      // Check if passwords match
      if (newPassword !== confirmPassword) {
        confirmPasswordError.textContent = 'Passwords do not match';
        isValid = false;
      }
    }

    // If validation fails, stop here
    if (!isValid) {
      return;
    }

    // In a real application, this would send an AJAX request to the server
    // For now, we'll simulate a successful update
    setTimeout(() => {
      // Update the current username field if a new username was provided
      if (newUsername) {
        document.getElementById('current-username').value = newUsername;
        localStorage.setItem('username', newUsername);
      }

      // Show success message
      formSuccess.textContent = 'Account information updated successfully!';

      // Clear the form fields
      document.getElementById('new-username').value = '';
      document.getElementById('current-password').value = '';
      document.getElementById('new-password').value = '';
      document.getElementById('confirm-password').value = '';
    }, 500);
  });
}