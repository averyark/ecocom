<?php
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

requireLogin();

// Process notification actions if they're coming from this page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_action']) && isset($_POST['notification_id'])) {
    // Include the notification handler
    require_once realpath(__DIR__ . '/handle_notification.php');
}
?>

<div class="header-shipping-notice">
    Free shipping for all orders above 50 MYR!
</div>

<!-- Sidebar Navigation -->
<div id="mySidebar" class="header-sidebar">
    <a href="javascript:void(0)" class="closebtn">&times;</a>
    <a href="../../pages/home/index.php">Home</a>
    <a href="../../pages/recycling/index.php">Recycling</a>
    <a href="../../pages/blogs_all/index.php">All Blogs</a>
    <!-- <button class="community_gardening">Community Gardening
        <i class="fa fa-caret-down"></i>
    </button>
    <div class="gardening-container">
        <a href="#">All Community Gardening</a>
        <a href="#">My Garden</a>
    </div>

    <button class="swap">Eco-friendly Product Swap
        <i class="fa fa-caret-down"></i>
    </button>
    <div class="swap-container">
        <a href="#">All Swaps</a>
        <a href="#">My Swaps</a>
    </div> -->
    <a href="../../pages/community/index.php">Gardening</a>
    <a href="../../pages/swaps/index.php">Swaps</a>
</div>
</div>


<header class="header-sticky-navbar">
    <div class="header-navbar">
        <div class="header-navbar-left">
            <img src="../../images/burgerMenu.png" alt="Menu Icon" class="icon" id="menuBtn">
        </div>

        <div class="header-logo">
            <a href="../../pages/home/index.php">
                <img src="../../images/Ellipse 1.svg" alt="Logo" class="header-logo-icon">
                <span class="header-logo-text"> ECOCOM</span>
            </a>
        </div>

        <div class="header-navbar-right">
            <div class="header-notification-container">
                <div class="notification-icon-wrapper">
                    <img src="../../images/notification.svg" alt="Notification Icon"
                        style="width: 30px; height: 30px; position: relative; top: -1px;" class="icon"
                        id="notificationBtn">
                    <!-- PHP will render the actual count here -->
                    <span class="notification-badge" id="notificationBadge">0</span>
                </div>
                <div class="header-notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <!-- Only show error messages, not success messages -->
                        <?php if (isset($_SESSION['notification_error'])): ?>
                            <div class="notification-error">
                                <?php
                                echo $_SESSION['notification_error'];
                                unset($_SESSION['notification_error']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <!-- PHP will render notifications here -->
                        <?php
                        // Get user ID from the session
                        $user_id = $_SESSION['user_id'];

                        // Get notifications from the database with more detailed information
                        $query = "SELECT n.*, u.username FROM notifications n
                                  LEFT JOIN users u ON u.id = n.user_id
                                  WHERE n.user_id = ? AND n.is_read = 0
                                  ORDER BY n.created_at DESC LIMIT 10";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();



                        // Render the notifications
                        $unread_count = 0;
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $notification_id = $row['id'];
                                $type = $row['type'];
                                $related_id = $row['related_id'];
                                $detailed_message = $row['message'];
                                $created_at = $row['created_at'];

                                // Get more detailed information based on notification type
                                $message = '';

                                if ($type == 'swap_request') {
                                    // Get swap request details
                                    $swap_query = "SELECT sr.*,
                                                  s1.item_name AS requested_item, s2.item_name AS offered_item,
                                                  u.username AS requester_name
                                                  FROM swap_requests sr
                                                  JOIN swaps s1 ON sr.requested_item_id = s1.id
                                                  JOIN swaps s2 ON sr.offered_item_id = s2.id
                                                  JOIN users u ON sr.requester_id = u.id
                                                  WHERE sr.id = ?";
                                    $swap_stmt = $conn->prepare($swap_query);
                                    $swap_stmt->bind_param("i", $related_id);
                                    $swap_stmt->execute();
                                    $swap_result = $swap_stmt->get_result();

                                    if ($swap_row = $swap_result->fetch_assoc()) {
                                        $message = "<strong>{$swap_row['requester_name']}</strong> wants to swap <strong>{$swap_row['offered_item']}</strong> for your <strong>{$swap_row['requested_item']}.</strong>";
                                    }
                                } elseif ($type == 'garden_exchange') {
                                    // Get garden exchange details
                                    $exchange_query = "SELECT ge.*,
                                                     gp1.content AS requested_content, gp2.content AS offered_content,
                                                     u.username AS requester_name
                                                     FROM garden_exchange ge
                                                     JOIN garden_posts gp1 ON ge.requested_post_id = gp1.id
                                                     JOIN garden_posts gp2 ON ge.offered_post_id = gp2.id
                                                     JOIN users u ON ge.requester_id = u.id
                                                     WHERE ge.id = ?";
                                    $exchange_stmt = $conn->prepare($exchange_query);
                                    $exchange_stmt->bind_param("i", $related_id);
                                    $exchange_stmt->execute();
                                    $exchange_result = $exchange_stmt->get_result();

                                    if ($exchange_row = $exchange_result->fetch_assoc()) {
                                        $message = "<strong>{$exchange_row['requester_name']}</strong> wants to exchange <strong>{$exchange_row['offered_content']}</strong> for your <strong>{$exchange_row['requested_content']}.</strong>";
                                    }
                                } else {
                                    $message = $detailed_message;
                                    $detailed_message = '';
                                }

                                if ($message == '') {
                                    // Skip this notification if we couldn't get detailed information
                                    continue;
                                }

                                // Count unread notifications for badge
                                $unread_count++;

                                echo "<div class='notification-item' data-id='{$notification_id}' data-type='{$type}'>";
                                echo "<div class='notification-content'>";
                                echo "<p>{$message}</p>";
                                if ($detailed_message != '') {
                                    echo "<br />";
                                    echo "<p>Extra message: {$detailed_message}</p>";
                                }
                                echo "<span class='notification-time'>{$created_at}</span>";
                                echo "</div>";

                                // Only show action buttons for pending requests
                                if (($type == 'swap_request' || $type == 'garden_exchange') && !$row['is_read']) {
                                    // For swap_request, check if the request is still pending
                                    $is_pending = true;

                                    if ($type == 'swap_request') {
                                        $status_query = "SELECT status FROM swap_requests WHERE id = ?";
                                        $status_stmt = $conn->prepare($status_query);
                                        $status_stmt->bind_param("i", $related_id);
                                        $status_stmt->execute();
                                        $status_result = $status_stmt->get_result();
                                        if ($status_row = $status_result->fetch_assoc()) {
                                            $is_pending = ($status_row['status'] == 'pending');
                                        }
                                    } elseif ($type == 'garden_exchange') {
                                        $status_query = "SELECT status FROM garden_exchange WHERE id = ?";
                                        $status_stmt = $conn->prepare($status_query);
                                        $status_stmt->bind_param("i", $related_id);
                                        $status_stmt->execute();
                                        $status_result = $status_stmt->get_result();
                                        if ($status_row = $status_result->fetch_assoc()) {
                                            $is_pending = ($status_row['status'] == 'pending');
                                        }
                                    }

                                    // Only show buttons if the request is still pending
                                    if ($is_pending) {
                                        echo "<div class='notification-actions'>";
                                        echo "<form method='post' class='notification-form' action=''>";
                                        echo "<input type='hidden' name='notification_id' value='{$notification_id}'>";
                                        echo "<input type='hidden' name='notification_type' value='{$type}'>";
                                        echo "<button type='submit' name='notification_action' value='accept' class='accept-btn'>Yes</button>";
                                        echo "<button type='submit' name='notification_action' value='reject' class='reject-btn'>No</button>";
                                        echo "</form>";
                                        echo "</div>";
                                    }
                                }

                                echo "</div>";
                            }
                        }
                        
                        if ($unread_count == 0) {
                            echo "<div class='notification-empty'>No new notifications</div>";
                        }

                        // Update the notification badge count with PHP
                        echo "<script>document.getElementById('notificationBadge').textContent = '{$unread_count}';</script>";
                        if ($unread_count == 0) {
                            echo "<script>document.getElementById('notificationBadge').classList.add('hidden');</script>";
                        } else {
                            echo "<script>document.getElementById('notificationBadge').classList.remove('hidden');</script>";
                        }
                        ?>
                    </div>
                    <div class="notification-empty hidden">
                        <p>No new notifications</p>
                    </div>
                </div>
            </div>
            <div class="header-profile-container">
                <img src="../../images/profile.png" alt="Person Icon" class="icon" id="profileBtn">
                <div class="header-profile-dropdown" id="profileDropdown">
                    <a href="../../pages/profile/index.php">My Profile</a>
                    <a href="../../pages/calendar/index.php">Calendar</a>
                    <a href="../../pages/favourite/index.php">Favourite</a>
                    <a href="../../pages/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>

<script src="../../pages/common/header.js"></script>