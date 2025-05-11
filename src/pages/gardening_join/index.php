<?php
// Include database connection and authentication helper
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// Require user to be logged in
requireLogin();

// Get user ID and username
$user_id = getCurrentUserId();
$username = getCurrentUsername();

// Initialize variables
$garden = null;
$is_joined = false;
$error_message = '';
$success_message = '';

// Get garden ID from URL parameter
$garden_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch garden details from database
if ($garden_id > 0) {
    $garden_query = "
        SELECT g.*, u.username as creator_name
        FROM gardens g
        JOIN users u ON g.user_id = u.id
        WHERE g.id = ?
    ";
    $stmt = $conn->prepare($garden_query);
    $stmt->bind_param("i", $garden_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $garden = $result->fetch_assoc();

        // Check if user has already joined this garden
        $joined_query = "
            SELECT * FROM garden_participants
            WHERE garden_id = ? AND user_id = ?
        ";
        $joined_stmt = $conn->prepare($joined_query);
        $joined_stmt->bind_param("ii", $garden_id, $user_id);
        $joined_stmt->execute();
        $joined_result = $joined_stmt->get_result();
        $is_joined = ($joined_result->num_rows > 0);
    } else {
        $error_message = "Garden not found.";
    }
} else {
    $error_message = "Invalid garden ID.";
}

// Handle join/unjoin garden actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'join' && $garden_id > 0) {
            // Check if user is already a participant
            if (!$is_joined) {
                // Add user to garden participants
                $join_query = "
                    INSERT INTO garden_participants (garden_id, user_id)
                    VALUES (?, ?)
                ";
                $join_stmt = $conn->prepare($join_query);
                $join_stmt->bind_param("ii", $garden_id, $user_id);

                if ($join_stmt->execute()) {
                    $success_message = "You have successfully joined this garden!";
                    $is_joined = true;
                } else {
                    $error_message = "Failed to join garden. Please try again.";
                }
            } else {
                $error_message = "You are already a participant in this garden.";
            }
        } elseif ($_POST['action'] === 'unjoin' && $garden_id > 0) {
            // Check if user is a participant
            if ($is_joined) {
                // Remove user from garden participants
                $unjoin_query = "
                    DELETE FROM garden_participants
                    WHERE garden_id = ? AND user_id = ?
                ";
                $unjoin_stmt = $conn->prepare($unjoin_query);
                $unjoin_stmt->bind_param("ii", $garden_id, $user_id);

                if ($unjoin_stmt->execute()) {
                    $success_message = "You have left this garden.";
                    $is_joined = false;
                } else {
                    $error_message = "Failed to leave garden. Please try again.";
                }
            } else {
                $error_message = "You are not a participant in this garden.";
            }
        }
    }
}

// Format recurring time for display
$recurring_day = $garden ? $garden['recurring_day'] : '';
$recurring_start_time = $garden ? date('g:i a', strtotime($garden['recurring_start_time'])) : '';
$recurring_end_time = $garden ? date('g:i a', strtotime($garden['recurring_end_time'])) : '';
$recurring_schedule = $garden ? "Every {$recurring_day}, {$recurring_start_time} - {$recurring_end_time}" : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gardening With You</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="../../css/header.css">
    <link rel="stylesheet" href="../../css/footer.css">
    <link rel="stylesheet" href="../../css/common.css">
    <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'>
</head>

<body>
    <!-- Header will be loaded here -->
    <?php include '../common/header.php'; ?>

    <main>
        <img src="../../images/garden.png" alt="garden" class="garden-img">

        <?php if ($garden): ?>
            <!-- Gardening Info -->
            <div class="gardening-info">
                <div>
                    <?php if (!empty($error_message)): ?>
                        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                        <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>
                </div>
                
                <h1><?php echo htmlspecialchars($garden['title']); ?></h1>
                <p class="author">by <span><?php echo htmlspecialchars($garden['creator_name']); ?></span></p>

                <!-- Venue & Time Box -->
                <div class="box venue">
                    <strong>Venue & Time</strong>
                    <ul>
                        <p><?php echo htmlspecialchars($garden['address']); ?></p>
                        <p><?php echo htmlspecialchars($recurring_schedule); ?></p>
                    </ul>
                </div>

                <!-- Description Box -->
                <div class="box description">
                    <strong>Description</strong>
                    <ul>
                        <p><?php echo htmlspecialchars($garden['description']); ?></p>
                    </ul>
                </div>

                <div class="garden-buttons">
                    <?php if (!$is_joined): ?>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                            <input type="hidden" name="action" value="join">
                            <button type="submit" class="button button-primary join-garden">Join Gardening</button>
                        </form>
                    <?php else: ?>
                        <div class="action-buttons post-join-buttons">
                            <a href="../../pages/gardening/index.php?garden_id=<?php echo $garden_id; ?>" style="text-decoration: none;display:flex;flex-grow:1;">
                                <button class="button button-primary view-garden">
                                    View Garden
                                </button>
                            </a>
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                <input type="hidden" name="action" value="unjoin">
                                <button type="submit" class="button unjoin-garden">Unjoin Garden</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="error-container">
                <p>Garden information not available. Please go back to the <a
                        href="../../pages/community/index.php">Community Gardens</a> page.</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer will be loaded here -->
    <?php include '../common/footer.php'; ?>
</body>

</html>