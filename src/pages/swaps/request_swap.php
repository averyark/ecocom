<?php
// Include database connection and authentication
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// Check if user is logged in
$user_id = getCurrentUserId();
if (!$user_id) {
    // Redirect to login page if not logged in
    header('Location: ../../pages/login/index.php');
    exit;
}

// Handle POST request for creating a swap request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $requested_item_id = isset($_POST['requested_item_id']) ? intval($_POST['requested_item_id']) : 0;
    $offered_item_id = isset($_POST['swap_item_id']) ? intval($_POST['swap_item_id']) : 0;

    // Validate input
    if ($requested_item_id <= 0 || $offered_item_id <= 0) {
        // Invalid input, redirect back with error
        header('Location: ../../pages/swaps/index.php?error=invalid_input');
        exit;
    }

    // Verify the requested item exists and is not owned by the current user
    $check_query = "SELECT id, user_id, item_name FROM swaps WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $requested_item_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        // Item not found, redirect back with error
        header('Location: ../../pages/swaps/index.php?error=item_not_found');
        exit;
    }

    $requested_item = $check_result->fetch_assoc();
    
    // Check if the user is trying to swap with their own item
    if ($requested_item['user_id'] == $user_id) {
        // Cannot swap with own item, redirect back with error
        header('Location: ../../pages/swaps/index.php?error=own_item');
        exit;
    }

    // Verify the offered item exists and is owned by the current user
    $offered_check_query = "SELECT id, item_name FROM swaps WHERE id = ? AND user_id = ?";
    $offered_check_stmt = $conn->prepare($offered_check_query);
    $offered_check_stmt->bind_param("ii", $offered_item_id, $user_id);
    $offered_check_stmt->execute();
    $offered_check_result = $offered_check_stmt->get_result();

    if ($offered_check_result->num_rows === 0) {
        // Offered item not found or not owned by user, redirect back with error
        header('Location: ../../pages/swaps/index.php?error=invalid_offered_item');
        exit;
    }

    $offered_item = $offered_check_result->fetch_assoc();

    // Check if there are any existing pending requests for either item
    $existing_query = "SELECT id FROM swap_requests 
                      WHERE (requested_item_id = ? OR offered_item_id = ? OR requested_item_id = ? OR offered_item_id = ?) 
                      AND status = 'pending'";
    $existing_stmt = $conn->prepare($existing_query);
    $existing_stmt->bind_param("iiii", $requested_item_id, $requested_item_id, $offered_item_id, $offered_item_id);
    $existing_stmt->execute();
    $existing_result = $existing_stmt->get_result();

    if ($existing_result->num_rows > 0) {
        // There's already a pending request for one of these items
        header('Location: ../../pages/swaps/index.php?error=existing_request');
        exit;
    }

    // Get the username of the requester
    $username_query = "SELECT username FROM users WHERE id = ?";
    $username_stmt = $conn->prepare($username_query);
    $username_stmt->bind_param("i", $user_id);
    $username_stmt->execute();
    $username_result = $username_stmt->get_result();
    $username_row = $username_result->fetch_assoc();
    $username = $username_row['username'];

    // Create the swap request
    $insert_query = "INSERT INTO swap_requests (requester_id, requested_item_id, offered_item_id, status) 
                    VALUES (?, ?, ?, 'pending')";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iii", $user_id, $requested_item_id, $offered_item_id);
    
    if ($insert_stmt->execute()) {
        // Get the swap request ID
        $swap_request_id = $conn->insert_id;
        
        // Create notification for the item owner
        $notification_type = 'swap_request';
        
        $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, related_id, type) 
                                           VALUES (?, ?, ?)");
        $notification_stmt->bind_param("iis", $requested_item['user_id'], $swap_request_id, $notification_type);
        $notification_stmt->execute();
        
        // Redirect to swaps page with success message
        header('Location: ../../pages/swaps/index.php?success=request_sent');
        exit;
    } else {
        // Error creating swap request
        header('Location: ../../pages/swaps/index.php?error=request_failed');
        exit;
    }
} else {
    // Not a POST request, redirect to swaps page
    header('Location: ../../pages/swaps/index.php');
    exit;
}
