<?php
/**
 * Authentication Helper for EcoCom Project
 *
 * This file provides authentication-related functions and session management.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Redirect to login page if user is not logged in
 * @param string $redirect_url Optional URL to redirect to after login
 */
function requireLogin($redirect_url = '') {
    if (!isLoggedIn()) {
        $redirect = empty($redirect_url) ? '' : '?redirect=' . urlencode($redirect_url);
        header("Location: ../../pages/signin/index.php" . $redirect);
        exit;
    }
}

/**
 * Get current user ID
 * @return int|null User ID if logged in, null otherwise
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Get current username
 * @return string|null Username if logged in, null otherwise
 */
function getCurrentUsername() {
    return isset($_SESSION['username']) ? $_SESSION['username'] : null;
}

function getCurrentUserIsAdmin() {
    return isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : null;
}
?>