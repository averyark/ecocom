<?php
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

$user_id = getCurrentUserId();
$success_message = '';
$error_message = '';

// Handle unfavourite action
if (isset($_POST['unfavourite']) && isset($_POST['item_id'])) {
    $item_id = intval($_POST['item_id']);

    // Delete the favourite record
    $query = "DELETE FROM user_favourites WHERE user_id = ? AND item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $item_id);

    if ($stmt->execute()) {
        $success_message = "Item removed from favourites";
    } else {
        $error_message = "Failed to remove item from favourites";
    }
}

// Fetch active favourite items (not exchanged)
$active_items_query = "
    SELECT s.*, uf.user_id AS favourited
    FROM swaps s
    INNER JOIN user_favourites uf ON s.id = uf.item_id
    LEFT JOIN (
        SELECT requested_item_id, offered_item_id
        FROM swap_requests
        WHERE status = 'accepted'
    ) sr ON s.id = sr.requested_item_id OR s.id = sr.offered_item_id
    WHERE uf.user_id = ? AND sr.requested_item_id IS NULL
    ORDER BY s.id DESC
";

$active_stmt = $conn->prepare($active_items_query);
$active_stmt->bind_param("i", $user_id);
$active_stmt->execute();
$active_result = $active_stmt->get_result();
$active_items = [];
while ($row = $active_result->fetch_assoc()) {
    $active_items[] = $row;
}

// Fetch expired favourite items (already exchanged)
$expired_items_query = "
    SELECT s.*, uf.user_id AS favourited
    FROM swaps s
    INNER JOIN user_favourites uf ON s.id = uf.item_id
    INNER JOIN (
        SELECT requested_item_id, offered_item_id
        FROM swap_requests
        WHERE status = 'accepted'
    ) sr ON s.id = sr.requested_item_id OR s.id = sr.offered_item_id
    WHERE uf.user_id = ?
    ORDER BY s.id DESC
";

$expired_stmt = $conn->prepare($expired_items_query);
$expired_stmt->bind_param("i", $user_id);
$expired_stmt->execute();
$expired_result = $expired_stmt->get_result();
$expired_items = [];
while ($row = $expired_result->fetch_assoc()) {
    $expired_items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Favourites</title>
  <link rel="stylesheet" href="index.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/footer.css">
  <link rel="stylesheet" href="../../css/common.css">
  <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
  <!-- Include header -->
  <?php include '../common/header.php'; ?>

  <!-- Page content -->
  <main class="content">
    <?php if (!empty($success_message)): ?>
      <div class="message success">
        <?php echo htmlspecialchars($success_message); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
      <div class="message error">
        <?php echo htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>

    <h1>My Favourites</h1>

    <!-- Tabs for different states -->
    <div class="tabs-container">
      <button class="tab-btn active" data-tab="active">Active Items</button>
      <button class="tab-btn" data-tab="expired">Expired Items</button>
    </div>

    <!-- Active Items Tab Content -->
    <div class="tab-content active" id="active-tab">
      <div class="items-grid" id="active-items-grid">
        <?php if (count($active_items) > 0): ?>
          <?php foreach ($active_items as $item): ?>
            <div class="item-card" data-id="<?php echo $item['id']; ?>">
              <div class="item-image">
                <?php if (!empty($item['image_url'])): ?>
                  <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                <?php else: ?>
                  <div class="no-image">No Image</div>
                <?php endif; ?>
                <form method="post" action="" class="unfavourite-form">
                  <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                  <input type="hidden" name="unfavourite" value="1">
                  <button type="submit" class="unfavourite-btn" title="Remove from favourites">
                    <i class="fas fa-heart"></i>
                  </button>
                </form>
              </div>
              <div class="item-details">
                <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
                <p class="item-category"><span class="category-tag"><?php echo htmlspecialchars($item['category']); ?></span></p>
                <p class="item-description"><?php echo htmlspecialchars(substr($item['description'], 0, 100)) . (strlen($item['description']) > 100 ? '...' : ''); ?></p>
                <div class="item-actions">
                  <button class="view-btn" onclick="location.href='../../pages/swaps_inspect/index.php?id=<?php echo $item['id']; ?>'">View Details</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-items-message" id="no-active-items">
            <p>You don't have any favourite items.</p>
            <p>Browse items and click the FAVOURITE button to add them here.</p>
            <a href="../../pages/swaps/index.php" class="browse-btn">Browse Items</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Expired Items Tab Content -->
    <div class="tab-content" id="expired-tab">
      <div class="items-grid" id="expired-items-grid">
        <?php if (count($expired_items) > 0): ?>
          <?php foreach ($expired_items as $item): ?>
            <div class="item-card expired" data-id="<?php echo $item['id']; ?>">
              <div class="item-image">
                <?php if (!empty($item['image_url'])): ?>
                  <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                <?php else: ?>
                  <div class="no-image">No Image</div>
                <?php endif; ?>
                <div class="expired-badge">Exchanged</div>
              </div>
              <div class="item-details">
                <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
                <p class="item-category"><span class="category-tag"><?php echo htmlspecialchars($item['category']); ?></span></p>
                <p class="item-description"><?php echo htmlspecialchars(substr($item['description'], 0, 100)) . (strlen($item['description']) > 100 ? '...' : ''); ?></p>
                <div class="item-actions">
                  <button class="view-btn" onclick="location.href='../../pages/swaps_inspect/index.php?id=<?php echo $item['id']; ?>'">View Details</button>
                  <form method="post" action="" class="unfavourite-form">
                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                    <input type="hidden" name="unfavourite" value="1">
                    <button type="submit" class="unfavourite-btn" title="Remove from favourites">
                      <i class="fas fa-heart"></i>
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-items-message" id="no-expired-items">
            <p>You don't have any expired items.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <!-- Include footer -->
  <?php include '../common/footer.php'; ?>

  <!-- JavaScript for tab switching -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Tab switching functionality
      const tabButtons = document.querySelectorAll('.tab-btn');
      const tabContents = document.querySelectorAll('.tab-content');

      tabButtons.forEach(button => {
        button.addEventListener('click', () => {
          // Remove active class from all buttons and contents
          tabButtons.forEach(btn => btn.classList.remove('active'));
          tabContents.forEach(content => content.classList.remove('active'));

          // Add active class to clicked button and corresponding content
          button.classList.add('active');
          const tabName = button.getAttribute('data-tab');
          document.getElementById(`${tabName}-tab`).classList.add('active');
        });
      });

      // Auto-hide messages after 3 seconds
      const messages = document.querySelectorAll('.message');
      if (messages.length > 0) {
        setTimeout(() => {
          messages.forEach(message => {
            message.style.opacity = '0';
            setTimeout(() => {
              message.style.display = 'none';
            }, 300);
          });
        }, 3000);
      }
    });
  </script>
</body>

</html>
