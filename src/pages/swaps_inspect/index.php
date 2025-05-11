<?php
// Include database connection and authentication
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// Get item ID from URL
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate item ID
if ($item_id <= 0) {
  // Redirect to swaps page if no valid ID provided
  header('Location: ../../pages/swaps/index.php');
  exit;
}

// Handle item removal
if (isset($_GET['action']) && $_GET['action'] === 'remove') {
  $user_id = getCurrentUserId();
  if ($user_id) {
    // Verify the user is the owner of the item
    $check_query = "SELECT user_id FROM swaps WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $item_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
      $item_owner = $check_result->fetch_assoc();

      if ($item_owner['user_id'] == $user_id) {
        // User is the owner, proceed with deletion

        // First check if there are any pending swap requests for this item
        $request_check_query = "SELECT id FROM swap_requests WHERE requested_item_id = ? OR offered_item_id = ?";
        $request_check_stmt = $conn->prepare($request_check_query);
        $request_check_stmt->bind_param("ii", $item_id, $item_id);
        $request_check_stmt->execute();
        $request_check_result = $request_check_stmt->get_result();

        if ($request_check_result->num_rows > 0) {
          // There are swap requests, delete them first
          $delete_requests_query = "DELETE FROM swap_requests WHERE requested_item_id = ? OR offered_item_id = ?";
          $delete_requests_stmt = $conn->prepare($delete_requests_query);
          $delete_requests_stmt->bind_param("ii", $item_id, $item_id);
          $delete_requests_stmt->execute();
        }

        // Delete any favorites for this item
        $delete_favorites_query = "DELETE FROM user_favourites WHERE item_id = ?";
        $delete_favorites_stmt = $conn->prepare($delete_favorites_query);
        $delete_favorites_stmt->bind_param("i", $item_id);
        $delete_favorites_stmt->execute();

        // Now delete the item
        $delete_query = "DELETE FROM swaps WHERE id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("ii", $item_id, $user_id);

        if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
          // Item successfully deleted, redirect to swaps page
          header('Location: ../../pages/swaps/index.php?removed=1');
          exit;
        }
      }
    }
  }
}

$user_id = getCurrentUserId();
if (isset($_GET['favourite']) && $user_id) {
  if ($_GET['favourite'] == '1') {
    $query = "INSERT INTO user_favourites (user_id, item_id) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $item_id);
    $stmt->execute();
  } else {
    $query = "DELETE FROM user_favourites WHERE user_id = ? AND item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $item_id);
    $stmt->execute();
  }
}

// Get user ID if logged in
$user_id = getCurrentUserId();

// Fetch item details
$query = "
    SELECT s.*, u.username AS author_name,
    CASE WHEN uf.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_favourited
    FROM swaps s
    INNER JOIN users u ON s.user_id = u.id
    LEFT JOIN user_favourites uf ON s.id = uf.item_id AND uf.user_id = ?
    WHERE s.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  // Item not found, redirect to swaps page
  header('Location: ../../pages/swaps/index.php');
  exit;
}

// Get item data
$item = $result->fetch_assoc();

// Check if current user is the owner
$is_owner = ($user_id && $user_id == $item['user_id']);

// Check if there are any pending swap requests for this item
$has_pending_request = false;
$is_exchanged = false;

$request_check_query = "SELECT id, status FROM swap_requests WHERE (requested_item_id = ? OR offered_item_id = ?) AND status != 'rejected'";
$request_check_stmt = $conn->prepare($request_check_query);
$request_check_stmt->bind_param("ii", $item_id, $item_id);
$request_check_stmt->execute();
$request_check_result = $request_check_stmt->get_result();

if ($request_check_result->num_rows > 0) {
  while ($request = $request_check_result->fetch_assoc()) {
    if ($request['status'] === 'pending') {
      $has_pending_request = true;
    } elseif ($request['status'] === 'accepted') {
      $is_exchanged = true;
      break; // If it's already exchanged, no need to check further
    }
  }
}

// If user is logged in, fetch their items for swap
$user_items = [];
if ($user_id) {
  $items_query = "SELECT * FROM swaps WHERE user_id = ? AND id != ? AND NOT EXISTS (SELECT 1 FROM swap_requests WHERE (requested_item_id = swaps.id OR offered_item_id = swaps.id) AND status = 'accepted')";
  $items_stmt = $conn->prepare($items_query);
  $items_stmt->bind_param("ii", $user_id, $item_id);
  $items_stmt->execute();
  $items_result = $items_stmt->get_result();

  while ($row = $items_result->fetch_assoc()) {
    $user_items[] = $row;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($item['item_name']); ?> - Swap Details</title>
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

  <div class="product-detail-container">
    <!-- Product Image -->
    <div class="product-image">
      <?php if (!empty($item['image_url'])): ?>
        <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
          alt="<?php echo htmlspecialchars($item['item_name']); ?>" />
      <?php else: ?>
        <div class="no-image">No Image Available</div>
      <?php endif; ?>
    </div>

    <!-- Product Info -->
    <div class="product-info">
      <div class="title-container">
        <h1><?php echo htmlspecialchars($item['item_name']); ?></h1>
        <?php if ($is_owner): ?>
          <button class="edit-title"
            onclick="window.location.href = '../../pages/swaps_create/index.php?id=<?php echo $item['id']; ?>'">
            <img src="../../images/edit.png" alt="Edit" class="edit-icon">
          </button>
        <?php endif; ?>
      </div>
      <p class="author">by <span><?php echo htmlspecialchars($item['author_name']); ?></span></p>

      <div class="product-description">
        <?php echo nl2br(htmlspecialchars($item['description'])); ?>
      </div>

      <div class="box wishlist">
        <p class="category"><strong>Category</strong>: <?php echo htmlspecialchars($item['category']); ?></p>
      </div>

      <!-- Wishlist Box -->
      <?php if (!empty($item['wish_list'])): ?>
        <div class="box wishlist">
          <strong><?php echo htmlspecialchars($item['author_name']); ?>'s wishlist</strong>
          <?php
          // Convert wishlist to array if it's a comma-separated string
          $wishlist_items = explode(',', $item['wish_list']);
          if (count($wishlist_items) > 0):
            ?>
            <ul>
              <?php foreach ($wishlist_items as $wish_item): ?>
                <li><?php echo htmlspecialchars(trim($wish_item)); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p><?php echo htmlspecialchars($item['wish_list']); ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- User Notes Box -->
      <?php if (!empty($item['user_notes'])): ?>
        <div class="box notes">
          <strong>User notes</strong>
          <p><?php echo nl2br(htmlspecialchars($item['user_notes'])); ?></p>
        </div>
      <?php endif; ?>

      <!-- Action Buttons -->
      <?php if (!$is_owner && $user_id): ?>
        <div class="action-buttons">
          <a href="?id=<?php echo $item['id']; ?>&favourite=<?php echo $item['is_favourited'] ? '0' : '1'; ?>">
            <button class="fav-btn <?php echo $item['is_favourited'] ? 'active' : ''; ?>" id="favourite-btn"
              data-id="<?php echo $item['id']; ?>">
              <?php echo $item['is_favourited'] ? 'FAVOURITED' : 'FAVOURITE'; ?>
            </button>
          </a>
          <?php if (!$is_exchanged): ?>
            <?php if ($has_pending_request): ?>
              <button class="swap-btn" disabled style="opacity: 0.6; cursor: not-allowed;">PENDING SWAP</button>
            <?php else: ?>
              <button class="swap-btn">REQUEST SWAP</button>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php elseif ($is_owner && !$has_pending_request): ?>
        <div class="action-buttons">
          <button class="swap-btn"
            onclick="if(confirm('Are you sure you want to remove this item?')) window.location.href='?id=<?php echo $item['id']; ?>&action=remove'">REMOVE
            ITEM</button>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Swap Request Modal -->
  <?php if (!$is_owner && $user_id && !$is_exchanged && !$has_pending_request): ?>
    <div id="swap-request-modal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Select Item to Swap</h3>
          <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
          <form id="swap-request-form" action="../../pages/swaps/request_swap.php" method="post">
            <div class="form-group">
              <p>Please select one of your items to swap with this item:</p>
              <div class="items-grid" id="user-items-grid">
                <?php if (count($user_items) > 0): ?>
                  <?php foreach ($user_items as $user_item): ?>
                    <div class="item-card selectable" data-id="<?php echo $user_item['id']; ?>">
                      <div class="item-image">
                        <?php if (!empty($user_item['image_url'])): ?>
                          <img src="<?php echo htmlspecialchars($user_item['image_url']); ?>"
                            alt="<?php echo htmlspecialchars($user_item['item_name']); ?>">
                        <?php else: ?>
                          <div class="no-image">No Image</div>
                        <?php endif; ?>
                      </div>
                      <div class="item-details">
                        <h3><?php echo htmlspecialchars($user_item['item_name']); ?></h3>
                        <?php if (!empty($user_item['category'])): ?>
                          <p class="item-category"><?php echo htmlspecialchars($user_item['category']); ?></p>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="no-items-message">
                    <p>You don't have any items to swap.</p>
                  </div>
                <?php endif; ?>
              </div>
              <p class="form-note">Only items you've created will appear here. <a
                  href="../../pages/swaps_create/index.php">Create a new item</a> if you don't have any to swap.</p>
            </div>
            <input type="hidden" name="swap_item_id" id="selected-item-id" value="">
            <input type="hidden" name="requested_item_id" value="<?php echo $item['id']; ?>">
            <div class="form-actions">
              <button type="button" id="cancel-swap-btn" class="btn-secondary">Cancel</button>
              <button type="submit" id="confirm-swap-btn" class="btn-primary" <?php echo count($user_items) === 0 ? 'disabled' : ''; ?>>Confirm Swap</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Include footer -->
  <?php include '../common/footer.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Modal elements
      const swapModal = document.getElementById('swap-request-modal');
      const closeModalBtn = document.querySelector('.close-modal');
      const swapForm = document.getElementById('swap-request-form');
      const swapBtn = document.querySelector('.swap-btn');
      const favouriteBtn = document.getElementById('favourite-btn');
      const cancelSwapBtn = document.getElementById('cancel-swap-btn');
      const confirmSwapBtn = document.getElementById('confirm-swap-btn');
      const userItemsGrid = document.getElementById('user-items-grid');
      const selectedItemInput = document.getElementById('selected-item-id');

      // Open modal when swap button is clicked
      if (swapBtn) {
        swapBtn.addEventListener('click', function () {
          swapModal.style.display = 'block';
        });
      }

      // Close modal when close button is clicked
      if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function () {
          swapModal.style.display = 'none';
        });
      }

      // Close modal when cancel button is clicked
      if (cancelSwapBtn) {
        cancelSwapBtn.addEventListener('click', function () {
          swapModal.style.display = 'none';
        });
      }

      // Close modal when clicking outside of it
      window.addEventListener('click', function (event) {
        if (event.target === swapModal) {
          swapModal.style.display = 'none';
        }
      });

      // Handle item selection in the modal
      if (userItemsGrid) {
        const itemCards = userItemsGrid.querySelectorAll('.item-card.selectable');
        itemCards.forEach(card => {
          card.addEventListener('click', function () {
            // Remove selected class from all cards
            itemCards.forEach(c => c.classList.remove('selected'));

            // Add selected class to clicked card
            this.classList.add('selected');

            // Set the selected item ID in the hidden input
            const itemId = this.getAttribute('data-id');
            selectedItemInput.value = itemId;

            // Enable the confirm button
            confirmSwapBtn.disabled = false;
          });
        });
      }
    });
  </script>
</body>

</html>