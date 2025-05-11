<?php
// Include database connection and authentication helper
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// Get user ID if logged in
$user_id = isLoggedIn() ? getCurrentUserId() : null;

// Get recycling ID from URL parameter
$recycling_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If no ID provided, redirect to recycling list
if ($recycling_id === 0) {
    header("Location: ../../pages/recycling/index.php");
    exit;
}

// Fetch recycling program details with participation status
$recycling_query = "
    SELECT r.*,
           CASE WHEN rp.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_participating
    FROM recycling r
    LEFT JOIN recycling_participants rp ON r.id = rp.recycling_id AND rp.user_id = ?
    WHERE r.id = ?
";

$stmt = $conn->prepare($recycling_query);
$stmt->bind_param("ii", $user_id, $recycling_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if recycling program exists
if ($result->num_rows === 0) {
    header("Location: ../../pages/recycling/index.php");
    exit;
}

// Get recycling program data
$program = $result->fetch_assoc();

// Format the event date and time
$event_date = new DateTime($program['event_date']);
$event_end_date = new DateTime($program['event_end_date']);
$formatted_date = $event_date->format('l, F j, Y');
$formatted_time = $event_date->format('g:i a') . ' - ' . $event_end_date->format('g:i a');

// Handle join/leave recycling program
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        // Redirect to login page if not logged in
        header("Location: ../../pages/signin/index.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    if ($_POST['action'] === 'join') {
        // Check if already participating
        $check_query = "SELECT * FROM recycling_participants WHERE recycling_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $recycling_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            // Add user to participants
            $join_query = "INSERT INTO recycling_participants (recycling_id, user_id) VALUES (?, ?)";
            $join_stmt = $conn->prepare($join_query);
            $join_stmt->bind_param("ii", $recycling_id, $user_id);
            $join_stmt->execute();
        }
    } elseif ($_POST['action'] === 'leave') {
        // Remove user from participants
        $leave_query = "DELETE FROM recycling_participants WHERE recycling_id = ? AND user_id = ?";
        $leave_stmt = $conn->prepare($leave_query);
        $leave_stmt->bind_param("ii", $recycling_id, $user_id);
        $leave_stmt->execute();
    }
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $recycling_id);
    exit;
}

// Get the list of recyclable items
$recyclable_items = [];
if (!empty($program['item_to_recycle'])) {
    $recyclable_items = explode(',', $program['item_to_recycle']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($program['title']); ?> - Recycling Info</title>
  <link rel="stylesheet" href="index.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/footer.css">
  <link rel="stylesheet" href="../../css/common.css">
  <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'>
</head>
<body>
  <!-- Include header -->
  <?php include '../common/header.php'; ?>

  <div class="recycling-detail-container">
    <!-- Recycling Image -->
    <div class="recycling-image">
      <img src="../../images/image.png" alt="Recycling" class="img-responsive"/>
    </div>

    <!-- Recycling Info -->
    <div class="recycling-info">
        <h1><?php echo htmlspecialchars($program['title']); ?></h1>
        
        <!-- Join/Leave Button -->
        <div class="action-buttons" style="margin-bottom: 20px;">
          <?php if (isLoggedIn()): ?>
            <form method="post" style="display: inline;">
              <?php if ($program['is_participating']): ?>
                <input type="hidden" name="action" value="leave">
                <button type="submit" class="button button-primary" style="background-color: #e74c3c;">Leave Program</button>
              <?php else: ?>
                <input type="hidden" name="action" value="join">
                <button type="submit" class="button button-primary">Join Program</button>
              <?php endif; ?>
            </form>
          <?php else: ?>
            <a href="../../pages/signin/index.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="button button-primary">Login to Join</a>
          <?php endif; ?>
        </div>

        <ul class="recycling-description">
            <p class="description">
                <?php echo nl2br(htmlspecialchars($program['description'])); ?>
            </p>
        </ul>

        <!-- Venue & Time Box -->
        <div class="box info">
          <strong>Venue & Time</strong>
          <ul>
            <p><?php echo htmlspecialchars($program['location']); ?></p>
            <p><?php echo $formatted_date; ?>, <?php echo $formatted_time; ?></p>
          </ul>
        </div>

        <!-- Recycling Notes Box -->
        <div class="box notes">
          <strong>What You Can Recycle</strong>
          <?php if (!empty($recyclable_items)): ?>
            <?php foreach ($recyclable_items as $item): ?>
              <li><?php echo htmlspecialchars(trim($item)); ?></li>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No specific items listed. Please contact the organizer for details.</p>
          <?php endif; ?>
        </div>

        <!-- How To Participate -->
        <div class="box participate">
            <strong>How to Participate</strong>
            <ul>
              <p>Contact <strong><?php echo htmlspecialchars($program['contact']); ?></strong> to register and join the program</p>
            </ul>
        </div>
    </div>
  </div>

  <!-- Include footer -->
  <?php include '../common/footer.php'; ?>

</body>
</html>
