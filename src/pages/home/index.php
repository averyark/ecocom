<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Homepage</title>
  <link rel="stylesheet" href="index.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/footer.css">
  <link rel="stylesheet" href="../../css/common.css">
  <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'>
  <script src="./index.js" defer></script>
</head>

<body>

  <!-- Header will be loaded here -->
  <?php include '../common/header.php'; ?>

  <!-- Main Image Section -->
  <section class="main-image">
    <img src="../../images/Image 1.png" alt="Main Banner" class="banner-image">
  </section>

  <!-- Free Shipping Banner -->
  <div class="bottom-banner">
    <span class="banner-text">THE BEST ECO SWAP SITE</span>
    <div class="stars">
      <img src="../../images/Star.png" alt="Star" class="star-icon">
      <img src="../../images/Star.png" alt="Star" class="star-icon">
      <img src="../../images/Star.png" alt="Star" class="star-icon">
      <img src="../../images/Star.png" alt="Star" class="star-icon">
      <img src="../../images/Star half.png" alt="Half Star" class="star-icon">
    </div>
    <span class="rating">4.8</span>
    <span class="review-text">4.8 out of 5 stars</span>
  </div>

  <!-- Programs Section -->
  <section class="programs">
    <div class="program-card">
      <img src="../../images/program 1.png" alt="Program 1">
      <div class="card-text">
        <h3>Discover local recycling programmes<br>and collection schedules</h3>
        <a href="../../pages/recycling/index.php" class="card-link">Discover Now</a>
      </div>
    </div>
    <div class="program-card">
      <img src="../../images/program 2.png" alt="Program 2">
      <div class="card-text">
        <h3>7 tips to reduce energy consumption in<br>your home or workplace</h3>
        <a href="../../pages/blogs/index.php" class="card-link">Read the blog</a>
      </div>
    </div>
    <div class="program-card">
      <img src="../../images/program 3.png" alt="Program 3">
      <div class="card-text">
        <h3>10 gardening tips to make your garden<br>flourish</h3>
        <a href="../../pages/blogs/index.php" class="card-link">Read the blog</a>
      </div>
    </div>
  </section>

  <!-- Top Swaps Section -->
  <section class="top-swaps">
    <h2>Recently Added Swaps</h2>

    <div class="swap-items">
      <?php
      // Fetch the most recent swap items that haven't been swapped yet
      $query = "
          SELECT s.*, u.username AS author_name
          FROM swaps s
          INNER JOIN users u ON s.user_id = u.id
          LEFT JOIN (
              SELECT requested_item_id, offered_item_id
              FROM swap_requests
              WHERE status = 'accepted'
          ) sr ON s.id = sr.requested_item_id OR s.id = sr.offered_item_id
          WHERE sr.requested_item_id IS NULL
          ORDER BY s.created_at DESC
          LIMIT 4
      ";
      $stmt = $conn->prepare($query);
      $stmt->execute();
      $result = $stmt->get_result();

      $recent_swaps = [];
      if ($result) {
        while ($row = $result->fetch_assoc()) {
          $recent_swaps[] = $row;
        }
      }

      // Display the recent swap items
      if (empty($recent_swaps)) {
        echo '<div class="no-items"><p>No swap items available at the moment.</p></div>';
      } else {
        foreach ($recent_swaps as $swap) {
          ?>
          <div class="swap-card">
            <a href="../../pages/swaps_inspect/index.php?id=<?php echo $swap['id']; ?>">
              <img class="img-responsive" src="<?php echo !empty($swap['image_url']) ? $swap['image_url'] : '../../images/Toothpaste.png'; ?>"
                alt="<?php echo htmlspecialchars($swap['item_name']); ?>">
            </a>
            <p class="product-name"><?php echo htmlspecialchars($swap['item_name']); ?></p>
          </div>
          <?php
        }
      }
      ?>
    </div>

    <!-- Other swaps button -->
    <div class="see-more-wrapper">
      <a href="../../pages/swaps/index.php" class="see-more-button">See Other Swaps</a>
    </div>
  </section>

  <!-- Recycling Map Section -->
  <section class="recycling-map">
    <h2>Find Recycling Programs Near You</h2>
    <div class="map-container">
      <img src="../../images/Map.png" alt="Map showing recycling locations" class="img-responsive">
    </div>
  </section>

  <!-- Footer will be loaded here -->
  <?php include '../common/footer.php'; ?>

</body>

</html>