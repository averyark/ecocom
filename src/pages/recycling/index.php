<?php
// Include database connection and authentication helper
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

requireLogin();

// Get user ID if logged in
$user_id = isLoggedIn() ? getCurrentUserId() : null;

// Fetch recycling programs from the database with participation status
$recycling_query = "
    SELECT r.*,
           CASE WHEN rp.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_participating
    FROM recycling r
    LEFT JOIN recycling_participants rp ON r.id = rp.recycling_id AND rp.user_id = ?
    ORDER BY r.event_date ASC
";

$stmt = $conn->prepare($recycling_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recycling_result = $stmt->get_result();

$recycling_programs = [];
if ($recycling_result) {
    while ($row = $recycling_result->fetch_assoc()) {
        // Format the activity date
        if (!empty($row['event_date'])) {
            $event_date = new DateTime($row['event_date']);
            $now = new DateTime();
            $interval = $now->diff($event_date);

            if ($interval->days == 0 && $event_date > $now) {
                $row['next_program'] = "Today";
            } elseif ($interval->days == 1 && $event_date > $now) {
                $row['next_program'] = "Tomorrow";
            } else {
                $row['next_program'] = $event_date->format('F j, Y');
            }
        } else {
            $row['next_program'] = "Not scheduled";
        }

        $recycling_programs[] = $row;
    }
}

// Handle join/leave recycling program
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['recycling_id'])) {
    $recycling_id = intval($_POST['recycling_id']);

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
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recycling</title>
  <link rel="stylesheet" href="index.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/footer.css">
  <link rel="stylesheet" href="../../css/common.css">
  <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'>
</head>

<body>

  <!-- Include header -->
  <?php include '../common/header.php'; ?>

  <div class="card-container">
    <?php if (empty($recycling_programs)): ?>
      <div class="no-recycling-message">
        <p>No recycling programs available yet.</p>
      </div>
    <?php else: ?>
      <?php foreach ($recycling_programs as $program): ?>
        <div class="card">
          <a href="../../pages/recycling_info/index.php?id=<?php echo $program['id']; ?>">
            <img src="../../images/image.png" alt="recycling center">
          </a>
          <div class="card-content">
            <h2 class="card-title"><?php echo htmlspecialchars($program['title']); ?></h2>
            <p class="card-date">Next Program <strong class="card-date-real"><?php echo htmlspecialchars($program['next_program']); ?></strong></p>
            <p class="card-location"><?php echo htmlspecialchars($program['location']); ?></p>
            <div class="btn-container">
              <?php if (isLoggedIn()): ?>
                <form method="post" style="display: inline;">
                  <input type="hidden" name="recycling_id" value="<?php echo $program['id']; ?>">
                  <?php if ($program['is_participating']): ?>
                    <input type="hidden" name="action" value="leave">
                    <button type="submit" class="calender-btn" style="width: 40px; height: 37px; border-radius: 10px; border: none; background-color: #e74c3c;">
                      <img src="../../images/Calendar month.png" class="card-calender" alt="Leave Program" style="width: 34px" title="Leave Program">
                    </button>
                  <?php else: ?>
                    <input type="hidden" name="action" value="join">
                    <button type="submit" class="calender-btn" style="width: 40px; height: 37px; border-radius: 10px; border: none; background-color: #2ecc71;">
                      <img src="../../images/Calendar month.png" class="card-calender" alt="Join Program" style="width: 34px" title="Join Program">
                    </button>
                  <?php endif; ?>
                </form>
              <?php else: ?>
                <button class="calender-btn" style="width: 40px; height: 37px; border-radius: 10px; border: none;" onclick="window.location.href = '../../pages/signin/index.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>'">
                  <img src="../../images/Calendar month.png" class="card-calender" alt="Login to Join" style="width: 34px" title="Login to Join">
                </button>
              <?php endif; ?>
              <button class="navigate-btn" style="width: 110px; height: 37px;"
                onclick="window.location.href = '../../pages/recycling_info/index.php?id=<?php echo $program['id']; ?>'">
                <img src="../../images/Directions.png" alt="navigate">Navigate
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (empty($recycling_programs)): ?>
      <!-- Display static example cards if no data in database -->
      <div class="card">
        <a href="../../pages/recycling_info/index.php">
          <img src="../../images/image.png" alt="recycling center">
        </a>
        <div class="card-content">
          <h2 class="card-title">Klang Valley Recycling</h2>
          <p class="card-date">Next Program <strong class="card-date-real">Tomorrow</strong></p>
          <p class="card-location">Jalan Teknologi 5, Taman Teknologi Malaysia, Kuala Lumpur, 57000 Kuala Lumpur, Wilayah
            Persekutuan Kuala Lumpur</p>
          <div class="btn-container">
            <button class="calender-btn" style="width: 40px; height: 37px; border-radius: 10px; border: none;" onclick="window.location.href = '../../pages/signin/index.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>'">
              <img src="../../images/Calendar month.png" class="card-calender" alt="Login to Join" style="width: 34px" title="Login to Join">
            </button>
            <button class="navigate-btn" style="width: 110px; height: 37px;"
              onclick="window.location.href = '../../pages/recycling_info/index.php'">
              <img src="../../images/Directions.png" alt="navigate">Navigate
            </button>
          </div>
        </div>
      </div>
      <div class="card">
        <img src="../../images/image.png" alt="recycling center">
        <div class="card-content">
          <h2 class="card-title">Klang Valley Recycling</h2>
          <p class="card-date">Next Program <strong class="card-date-real">Tomorrow</strong></p>
          <p class="card-location">Jalan Teknologi 5, Taman Teknologi Malaysia, Kuala Lumpur, 57000 Kuala Lumpur, Wilayah
            Persekutuan Kuala Lumpur</p>
          <div class="btn-container">
            <button class="calender-btn" style="width: 40px; height: 37px; border-radius: 10px; border: none;" onclick="window.location.href = '../../pages/signin/index.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>'">
              <img src="../../images/Calendar month.png" class="card-calender" alt="Login to Join" style="width: 34px" title="Login to Join">
            </button>
            <button class="navigate-btn" style="width: 110px; height: 37px;"><img src="../../images/Directions.png"
                alt="navigate">Navigate</button>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Include footer -->
  <?php include '../common/footer.php'; ?>

</body>

</html>
