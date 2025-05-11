<?php
// Include database connection and authentication
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// Require user to be logged in
requireLogin();

// Get user ID and username
$user_id = getCurrentUserId();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Calendar</title>
  <link rel="stylesheet" href="index.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/footer.css">
  <link rel="stylesheet" href="../../css/common.css">
  <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'>

  <!-- FullCalendar Core CSS -->
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />

  <!-- FullCalendar JS -->
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
  <script>
    const recurringEvents = [
      <?php

      // Get gardens the user has joined
      $stmt = $conn->prepare("SELECT g.* FROM gardens g 
                              JOIN garden_participants gp ON g.id = gp.garden_id 
                              WHERE gp.user_id = ?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();

      $first = true;
      while ($garden = $result->fetch_assoc()) {
        if (!$first) {
          echo ",";
        }
        $first = false;

        $dayOfWeek = date('w', strtotime($garden['recurring_day']));
        echo "{
          title: '" . addslashes($garden['title']) . "',
          daysOfWeek: [" . $dayOfWeek . "],
          startTime: '" . $garden['recurring_start_time'] . "',
          endTime: '" . $garden['recurring_end_time'] . "',
          startRecur: '" . $garden['start_date'] . "',
          endRecur: '" . $garden['end_date'] . "',
          extendedProps: {
            id: '" . $garden['id'] . "',
            type: 'gardening',
          }
        }";
      }

      // Get recycling events the user has joined
      $stmt = $conn->prepare("SELECT r.* FROM recycling r 
                              JOIN recycling_participants rp ON r.id = rp.recycling_id 
                              WHERE rp.user_id = ?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();

      while ($recycling = $result->fetch_assoc()) {
        if (!$first) {
          echo ",";
        }
        $first = false;

        echo "{
          title: '" . addslashes($recycling['title']) . "',
          start: '" . $recycling['event_date'] . "',
          end: '" . $recycling['event_end_date'] . "',
          allDay: false,
          extendedProps: {
            id: '" . $recycling['id'] . "',
            type: 'recycling',
          }
        }";
      }
      ?>
    ];
  </script>
  <script src="index.js" defer></script>
</head>

<body>
  <!-- Header will be loaded here -->
  <?php include realpath(__DIR__ . '/../../pages/common/header.php'); ?>

  <!-- Page content -->
  <main class="content">
    <h1>Community Events Calendar</h1>
    <p>View all upcoming recycling programs, gardening events, and community activities.</p>

    <!-- Calendar container -->
    <div id="calendar-container">
      <div id="calendar"></div>
    </div>

    <!-- Legend for event types -->
    <div class="calendar-legend">
      <div class="legend-item">
        <span class="legend-color gardening"></span>
        <span class="legend-text">Gardening Events</span>
      </div>
      <div class="legend-item">
        <span class="legend-color recycling"></span>
        <span class="legend-text">Recycling Events</span>
      </div>
    </div>
  </main>

  <!-- Footer will be loaded here -->
  <?php include realpath(__DIR__ . '/../../pages/common/footer.php'); ?>
</body>

</html>