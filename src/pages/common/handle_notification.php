<?php
/**
 * Handle Notification Actions
 *
 * This file processes notification actions (accept/reject) for different notification types
 * such as swap_request and garden_exchange.
 */

require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// Ensure user is logged in
requireLogin();
$user_id = $_SESSION['user_id'];

// Process notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_action'])) {
    $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    $action = isset($_POST['notification_action']) ? $_POST['notification_action'] : '';
    $type = isset($_POST['notification_type']) ? $_POST['notification_type'] : '';

    // Validate input
    if ($notification_id <= 0 || !in_array($action, ['accept', 'reject']) || empty($type)) {
        $_SESSION['notification_error'] = 'Invalid notification parameters.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Get the notification details
    $notification_query = "SELECT * FROM notifications WHERE id = ? AND user_id = ? AND type = ?";
    $notification_stmt = $conn->prepare($notification_query);
    $notification_stmt->bind_param("iis", $notification_id, $user_id, $type);
    $notification_stmt->execute();
    $notification_result = $notification_stmt->get_result();

    if ($notification_result->num_rows === 0) {
        $_SESSION['notification_error'] = 'Notification not found or you do not have permission to handle it.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    $notification = $notification_result->fetch_assoc();
    $related_id = $notification['related_id'];

    // Handle different notification types
    if ($type === 'swap_request') {
        handleSwapRequest($conn, $user_id, $notification_id, $related_id, $action);
    } elseif ($type === 'garden_exchange') {
        handleGardenExchange($conn, $user_id, $notification_id, $related_id, $action);
    } else {
        $_SESSION['notification_error'] = 'Unsupported notification type.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

/**
 * Handle swap request notifications
 */
function handleSwapRequest($conn, $user_id, $notification_id, $related_id, $action) {
    // Get the swap request details
    $request_query = "SELECT * FROM swap_requests WHERE id = ?";
    $request_stmt = $conn->prepare($request_query);
    $request_stmt->bind_param("i", $related_id);
    $request_stmt->execute();
    $request_result = $request_stmt->get_result();

    if ($request_result->num_rows === 0) {
        $_SESSION['notification_error'] = 'Swap request not found.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    $request = $request_result->fetch_assoc();

    // Verify the user is the owner of the requested item
    $item_query = "SELECT user_id FROM swaps WHERE id = ?";
    $item_stmt = $conn->prepare($item_query);
    $item_stmt->bind_param("i", $request['requested_item_id']);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();

    if ($item_result->num_rows === 0) {
        $_SESSION['notification_error'] = 'Requested item not found.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    $item = $item_result->fetch_assoc();

    if ($item['user_id'] != $user_id) {
        $_SESSION['notification_error'] = 'You do not have permission to handle this swap request.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update the swap request status
        $new_status = ($action === 'accept') ? 'accepted' : 'rejected';
        $update_query = "UPDATE swap_requests SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $related_id);
        $update_stmt->execute();

        // Mark the notification as read
        $read_query = "UPDATE notifications SET is_read = 1 WHERE id = ?";
        $read_stmt = $conn->prepare($read_query);
        $read_stmt->bind_param("i", $notification_id);
        $read_stmt->execute();

        // If accepted, create a notification for the requester
        if ($action === 'accept') {
            // Get the item names for the notification message
            $items_query = "SELECT s1.item_name AS requested_item, s2.item_name AS offered_item, s1.user_id AS owner_id, u.username AS owner_name
                            FROM swap_requests sr
                            JOIN swaps s1 ON sr.requested_item_id = s1.id
                            JOIN swaps s2 ON sr.offered_item_id = s2.id
                            JOIN users u ON s1.user_id = u.id
                            WHERE sr.id = ?";
            $items_stmt = $conn->prepare($items_query);
            $items_stmt->bind_param("i", $related_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            $items = $items_result->fetch_assoc();

            // Create notification for requester
            $notification_message = "Your swap request for " . htmlspecialchars($items['requested_item']) . " has been accepted by " . htmlspecialchars($items['owner_name']) . ".";
            $notification_type = 'request_accepted';

            $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, related_id, type, message) VALUES (?, ?, ?, ?)");
            $notification_stmt->bind_param("iiss", $request['requester_id'], $related_id, $notification_type, $notification_message);
            $notification_stmt->execute();
        } else {
            // If rejected, create a rejection notification
            // Get the item names for the notification message
            $items_query = "SELECT s1.item_name AS requested_item, s1.user_id AS owner_id, u.username AS owner_name
                            FROM swap_requests sr
                            JOIN swaps s1 ON sr.requested_item_id = s1.id
                            JOIN users u ON s1.user_id = u.id
                            WHERE sr.id = ?";
            $items_stmt = $conn->prepare($items_query);
            $items_stmt->bind_param("i", $related_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            $items = $items_result->fetch_assoc();

            // Create notification for requester
            $notification_message = "Your swap request for " . htmlspecialchars($items['requested_item']) . " has been rejected by " . htmlspecialchars($items['owner_name']) . ".";
            $notification_type = 'request_rejected';

            $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, related_id, type, message) VALUES (?, ?, ?, ?)");
            $notification_stmt->bind_param("iiss", $request['requester_id'], $related_id, $notification_type, $notification_message);
            $notification_stmt->execute();
        }

        // Commit transaction
        $conn->commit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['notification_error'] = 'Error processing swap request: ' . $e->getMessage();
    }

    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

/**
 * Handle garden exchange notifications
 */
function handleGardenExchange($conn, $user_id, $notification_id, $related_id, $action) {
    // Get the garden exchange details
    $exchange_query = "SELECT * FROM garden_exchange WHERE id = ?";
    $exchange_stmt = $conn->prepare($exchange_query);
    $exchange_stmt->bind_param("i", $related_id);
    $exchange_stmt->execute();
    $exchange_result = $exchange_stmt->get_result();

    if ($exchange_result->num_rows === 0) {
        $_SESSION['notification_error'] = 'Garden exchange request not found.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    $exchange = $exchange_result->fetch_assoc();

    // Verify the user is the owner of the requested post
    $post_query = "SELECT gp.*, u.username FROM garden_posts gp JOIN users u ON gp.user_id = u.id WHERE gp.id = ?";
    $post_stmt = $conn->prepare($post_query);
    $post_stmt->bind_param("i", $exchange['requested_post_id']);
    $post_stmt->execute();
    $post_result = $post_stmt->get_result();

    if ($post_result->num_rows === 0) {
        $_SESSION['notification_error'] = 'Requested garden post not found.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    $requested_post = $post_result->fetch_assoc();

    if ($requested_post['user_id'] != $user_id) {
        $_SESSION['notification_error'] = 'You do not have permission to handle this garden exchange request.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update the garden exchange status
        $new_status = ($action === 'accept') ? 'accepted' : 'rejected';
        $update_query = "UPDATE garden_exchange SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $related_id);
        $update_stmt->execute();

        // Mark the notification as read
        $read_query = "UPDATE notifications SET is_read = 1 WHERE id = ?";
        $read_stmt = $conn->prepare($read_query);
        $read_stmt->bind_param("i", $notification_id);
        $read_stmt->execute();

        // Get the offered post details
        $offered_post_query = "SELECT gp.*, u.username FROM garden_posts gp JOIN users u ON gp.user_id = u.id WHERE gp.id = ?";
        $offered_post_stmt = $conn->prepare($offered_post_query);
        $offered_post_stmt->bind_param("i", $exchange['offered_post_id']);
        $offered_post_stmt->execute();
        $offered_post_result = $offered_post_stmt->get_result();
        $offered_post = $offered_post_result->fetch_assoc();

        // Create notification for requester
        if ($action === 'accept') {
            $notification_message = "Your garden exchange request for " . htmlspecialchars($requested_post['content']) . " has been accepted by " . htmlspecialchars($requested_post['username']) . ".";
            $notification_type = 'request_accepted';
        } else {
            $notification_message = "Your garden exchange request for " . htmlspecialchars($requested_post['content']) . " has been rejected by " . htmlspecialchars($requested_post['username']) . ".";
            $notification_type = 'request_rejected';
        }

        $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, related_id, type, message) VALUES (?, ?, ?, ?)");
        $notification_stmt->bind_param("iiss", $exchange['requester_id'], $related_id, $notification_type, $notification_message);
        $notification_stmt->execute();

        // Commit transaction
        $conn->commit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['notification_error'] = 'Error processing garden exchange request: ' . $e->getMessage();
    }

    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
