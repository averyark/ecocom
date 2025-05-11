<?php
// Include database connection and authentication
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// Get user ID if logged in
$user_id = isLoggedIn() ? getCurrentUserId() : null;

// Get selected category from URL parameter if any
$selected_category = $_GET['category'] ?? '';

// Fetch unique categories from the database
$categories_query = "
    SELECT DISTINCT category
    FROM swaps
    WHERE category IS NOT NULL AND category != ''
    ORDER BY category
";

$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories_result = $categories_stmt->get_result();

$categories = [];
if ($categories_result) {
  while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
  }
}

// Fetch all swap items from the database, filtered by category or user if selected
// Exclude items that have been exchanged (have an accepted swap request)
if (isset($_GET['my_swaps']) && $user_id) {
  // Show only user's swaps
  $query = "
        SELECT s.*, u.username AS author_name
        FROM swaps s
        INNER JOIN users u ON s.user_id = u.id
        LEFT JOIN (
            SELECT requested_item_id, offered_item_id
            FROM swap_requests
            WHERE status = 'accepted'
        ) sr ON s.id = sr.requested_item_id OR s.id = sr.offered_item_id
        WHERE s.user_id = ? AND sr.requested_item_id IS NULL
        ORDER BY s.created_at DESC
    ";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $user_id);
} elseif (!empty($selected_category)) {
  // Filter by category
  if ($selected_category === 'Recommended') {
    // For Recommended, show random items
    $query = "
        SELECT s.*, u.username AS author_name
        FROM swaps s
        INNER JOIN users u ON s.user_id = u.id
        LEFT JOIN (
            SELECT requested_item_id, offered_item_id
            FROM swap_requests
            WHERE status = 'accepted'
        ) sr ON s.id = sr.requested_item_id OR s.id = sr.offered_item_id
        WHERE sr.requested_item_id IS NULL AND s.user_id != ?
        ORDER BY RAND()
        LIMIT 10
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
  } else {
    $query = "
        SELECT s.*, u.username AS author_name
        FROM swaps s
        INNER JOIN users u ON s.user_id = u.id
        LEFT JOIN (
            SELECT requested_item_id, offered_item_id
            FROM swap_requests
            WHERE status = 'accepted'
        ) sr ON s.id = sr.requested_item_id OR s.id = sr.offered_item_id
        WHERE s.category = ? AND sr.requested_item_id IS NULL
        ORDER BY s.created_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $selected_category);
  }
} else {
  // Show all swaps
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
    ";
  $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();

$swaps = [];
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $swaps[] = $row;
  }
}

// Get total count of all swaps (excluding exchanged items)
$total_swaps_count = 0;
$total_count_query = "
    SELECT COUNT(*) as count
    FROM swaps s
    LEFT JOIN (
        SELECT requested_item_id, offered_item_id
        FROM swap_requests
        WHERE status = 'accepted'
    ) sr ON s.id = sr.requested_item_id OR s.id = sr.offered_item_id
    WHERE sr.requested_item_id IS NULL
";
$total_count_stmt = $conn->prepare($total_count_query);
$total_count_stmt->execute();
$total_count_result = $total_count_stmt->get_result();

if ($total_count_result && $row = $total_count_result->fetch_assoc()) {
  $total_swaps_count = $row['count'];
}

// Count items per category for display (excluding exchanged items)
$category_counts = [];
$count_query = "
    SELECT s.category, COUNT(*) as count
    FROM swaps s
    LEFT JOIN (
        SELECT requested_item_id, offered_item_id
        FROM swap_requests
        WHERE status = 'accepted'
    ) sr ON s.id = sr.requested_item_id OR s.id = sr.offered_item_id
    WHERE sr.requested_item_id IS NULL
    GROUP BY s.category
    ORDER BY s.category
";

$count_stmt = $conn->prepare($count_query);
$count_stmt->execute();
$count_result = $count_stmt->get_result();

if ($count_result) {
  while ($row = $count_result->fetch_assoc()) {
    $category_counts[$row['category']] = $row['count'];
  }
}

// Count user's own swaps (excluding exchanged items)
$user_swaps_count = 0;
if ($user_id) {
  $user_count_query = "
      SELECT COUNT(*) as count
      FROM swaps s
      LEFT JOIN (
          SELECT requested_item_id, offered_item_id
          FROM swap_requests
          WHERE status = 'accepted'
      ) sr ON s.id = sr.requested_item_id OR s.id = sr.offered_item_id
      WHERE s.user_id = ? AND sr.requested_item_id IS NULL
  ";
  $user_count_stmt = $conn->prepare($user_count_query);
  $user_count_stmt->bind_param("i", $user_id);
  $user_count_stmt->execute();
  $user_count_result = $user_count_stmt->get_result();

  if ($user_count_result && $row = $user_count_result->fetch_assoc()) {
    $user_swaps_count = $row['count'];
  }
}


// Get total count of recommended items (excluding exchanged items) - we'll show up to 10 random items
$recommended_swaps_count = 0;
$recommended_count_query = "
    SELECT COUNT(*) as count
    FROM swaps s
    LEFT JOIN (
        SELECT requested_item_id, offered_item_id
        FROM swap_requests
        WHERE status = 'accepted'
    ) sr ON s.id = sr.requested_item_id OR s.id = sr.offered_item_id
    WHERE sr.requested_item_id IS NULL AND s.user_id != ?
";
$recommended_count_stmt = $conn->prepare($recommended_count_query);
$recommended_count_stmt->bind_param("i", $user_id);
$recommended_count_stmt->execute();
$recommended_count_result = $recommended_count_stmt->get_result();

if ($recommended_count_result && $row = $recommended_count_result->fetch_assoc()) {
  // If there are fewer than 10 total items, show the actual count
  $recommended_count = min(10, $row['count']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>
    <?php
    if (isset($_GET['my_swaps'])) {
      echo 'My Swaps';
    } elseif (!empty($selected_category)) {
      echo htmlspecialchars($selected_category) . ' Swaps';
    } else {
      echo 'All Swaps';
    }
    ?>
  </title>
  <link rel="stylesheet" href="index.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/footer.css">
  <link rel="stylesheet" href="../../css/common.css">
  <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'>
  <script src="./index.js" defer></script>
</head>

<body>
  <!-- Header will be loaded here -->
  <?php include realpath(__DIR__ . '/../../pages/common/header.php'); ?>

  <?php if (isset($_GET['removed']) && $_GET['removed'] == '1'): ?>
    <div class="success-message" style="background-color: #d4edda; color: #155724; text-align: center; padding: 10px; margin: 10px 0;">
      Item successfully removed.
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['success']) && $_GET['success'] == 'request_sent'): ?>
    <div class="success-message" style="background-color: #d4edda; color: #155724; text-align: center; padding: 10px; margin: 10px 0;">
      Swap request sent successfully! The item owner will be notified.
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['error'])): ?>
    <div class="error-message" style="background-color: #f8d7da; color: #721c24; text-align: center; padding: 10px; margin: 10px 0;">
      <?php
        $error = $_GET['error'];
        switch($error) {
          case 'invalid_input':
            echo 'Invalid input. Please try again.';
            break;
          case 'item_not_found':
            echo 'The requested item was not found.';
            break;
          case 'own_item':
            echo 'You cannot swap with your own item.';
            break;
          case 'invalid_offered_item':
            echo 'The item you offered is not valid or does not belong to you.';
            break;
          case 'existing_request':
            echo 'There is already a pending swap request for one of these items.';
            break;
          case 'request_failed':
            echo 'Failed to create swap request. Please try again.';
            break;
          default:
            echo 'An error occurred. Please try again.';
        }
      ?>
    </div>
  <?php endif; ?>

  <main class="product-page">
    <!-- Button Row Above Products -->
    <div class="button-row">
      <div class="button-container">
        <a href="../../pages/swaps_create/index.php">
          <button class="button button-primary create-button">Create new Swaps</button>
        </a>
      </div>
    </div>

    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="filter-section">
        <h3>Swaps by Genre</h3>
        <ul>
          <li <?php echo empty($selected_category) ? 'class="active"' : ''; ?>>
            <a href="../../pages/swaps/index.php">All Swaps <span class="count">(<?php echo $total_swaps_count; ?>)</span></a>
          </li>
          <li class="<?php echo ($selected_category === 'Recommended') ? 'active' : ''; ?>"><a
              href="../../pages/swaps/index.php?category=Recommended">Recommended <span
                class="count">(<?php echo $recommended_count; ?>)</span></a></li>
          <li class="has-arrow <?php echo ($selected_category === 'Home & Kitchen') ? 'active' : ''; ?>"><a
              href="../../pages/swaps/index.php?category=Home%20%26%20Kitchen">Home &
              Kitchen <span class="count">(<?php echo $category_counts['Home & Kitchen'] ?? '0'; ?>)</span></a><span
              class="arrow">&gt;</span></li>
          <li class="has-arrow <?php echo ($selected_category === 'Cleaning') ? 'active' : ''; ?>"><a
              href="../../pages/swaps/index.php?category=Cleaning">Cleaning <span
                class="count">(<?php echo $category_counts['Cleaning'] ?? '0'; ?>)</span></a><span class="arrow">&gt;</span>
          </li>
          <li class="has-arrow <?php echo ($selected_category === 'Bathroom') ? 'active' : ''; ?>"><a
              href="../../pages/swaps/index.php?category=Bathroom">Bathroom <span
                class="count">(<?php echo $category_counts['Bathroom'] ?? '0'; ?>)</span></a><span class="arrow">&gt;</span>
          </li>
          <li class="has-arrow <?php echo ($selected_category === 'Beauty') ? 'active' : ''; ?>"><a
              href="../../pages/swaps/index.php?category=Beauty">Beauty <span
                class="count">(<?php echo $category_counts['Beauty'] ?? '0'; ?>)</span></a><span class="arrow">&gt;</span>
          </li>
          <li class="has-arrow <?php echo ($selected_category === 'Baby & Kids') ? 'active' : ''; ?>"><a
              href="../../pages/swaps/index.php?category=Baby%20%26%20Kids">Baby & Kids <span
                class="count">(<?php echo $category_counts['Baby & Kids'] ?? '0'; ?>)</span></a>
            <span class="arrow">&gt;</span>
          </li>
          <li class="has-arrow <?php echo ($selected_category === 'Pets') ? 'active' : ''; ?>"><a
              href="../../pages/swaps/index.php?category=Pets">Pets <span
              class="count">(<?php echo $category_counts['Pets'] ?? '0'; ?>)</span></a><span class="arrow">&gt;</span>
          </li>
          <hr />

          <li class="has-arrow <?php echo isset($_GET['my_swaps']) ? 'active' : ''; ?>">
            <a href="../../pages/swaps/index.php?my_swaps=1">
              Your Swaps
              <span class="count">(<?php echo $user_swaps_count; ?>)</span>
            </a>
            <span class="arrow">&gt;</span>
          </li>
        </ul>
      </div>
    </aside>

    <!-- Products Grid -->
    <section class="products-grid">
      <?php if (empty($swaps)): ?>
        <div class="no-items">
          <p>No swap items available at the moment.</p>
        </div>
      <?php else: ?>
        <?php foreach ($swaps as $swap): ?>
          <div class="product-card">
            <a href="../../pages/swaps_inspect/index.php?id=<?php echo $swap['id']; ?>">
              <img src="<?php echo !empty($swap['image_url']) ? $swap['image_url'] : '../../images/Toothpaste.png'; ?>"
                alt="<?php echo htmlspecialchars($swap['item_name']); ?>">
            </a>
            <h4><?php echo htmlspecialchars($swap['item_name']); ?></h4>
            <p class="price">By <?php echo htmlspecialchars($swap['author_name']); ?></p>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </main>

  <!-- Footer will be loaded here -->
  <?php include realpath(__DIR__ . '/../../pages/common/footer.php'); ?>

</body>

</html>