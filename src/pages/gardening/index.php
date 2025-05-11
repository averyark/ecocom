<?php
// Include database connection and authentication
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// Require user to be logged in
requireLogin();

// Get user ID and username
$user_id = getCurrentUserId();
$username = getCurrentUsername();

// Check if the request is a POST submission
$is_submitting = ($_SERVER['REQUEST_METHOD'] === 'POST');

if (!$is_submitting) {
  if (isset($_GET['garden_id'])) {
    $garden_id = $_GET['garden_id'];
    $gardens_query = "
    SELECT *
    FROM garden_participants gp
    WHERE gp.garden_id = ? AND gp.user_id = ?
";
    $stmt = $conn->prepare($gardens_query);
    $stmt->bind_param("ii", $garden_id, $user_id);
    $stmt->execute();
    $gardens_result = $stmt->get_result();
    $gardens = [];
    if ($gardens_result->num_rows == 0) {
      header('Location: ../../pages/community/index.php');
      exit;
    }
  } else {
    header('Location: ../../pages/community/index.php');
    exit;
  }
}

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_post') {
  $garden_id = $_GET['garden_id'];
  $content = trim($_POST['content']);
  $is_exchangeable = isset($_POST['exchangeable']) ? 1 : 0;
  $image_url = null;

  // Handle image upload if present
  if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../uploads/garden_posts/';

    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
      mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $file_extension = pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('garden_post_') . '.' . $file_extension;
    $target_file = $upload_dir . $filename;

    // Move uploaded file
    if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_file)) {
      $image_url = '../../uploads/garden_posts/' . $filename;
    }
  }

  // Insert post into database
  $stmt = $conn->prepare("INSERT INTO garden_posts (user_id, garden_id, content, image_url, is_exchangeable) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("iissi", $user_id, $garden_id, $content, $image_url, $is_exchangeable);

  if ($stmt->execute()) {
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
  } else {
    $error_message = "Error creating post: " . $conn->error;
  }
}

// Handle post editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_post') {
  $post_id = $_POST['post_id'];
  $content = trim($_POST['content']);
  $is_exchangeable = isset($_POST['exchangeable']) ? 1 : 0;

  // Verify the post belongs to the current user
  $check_stmt = $conn->prepare("SELECT user_id, image_url FROM garden_posts WHERE id = ?");
  $check_stmt->bind_param("i", $post_id);
  $check_stmt->execute();
  $result = $check_stmt->get_result();

  if ($row = $result->fetch_assoc()) {
    if ($row['user_id'] == $user_id) {
      // For inline editing, we're only updating the content and exchangeable status
      // Keep the existing image
      $image_url = $row['image_url'];

      // Update post in database
      $update_stmt = $conn->prepare("UPDATE garden_posts SET content = ?, is_exchangeable = ? WHERE id = ?");
      $update_stmt->bind_param("sii", $content, $is_exchangeable, $post_id);

      if ($update_stmt->execute()) {
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
      } else {
        $error_message = "Error updating post: " . $conn->error;
      }
    } else {
      $error_message = "You don't have permission to edit this post.";
    }
  }
}

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_post') {
  $post_id = $_POST['post_id'];

  // Verify the post belongs to the current user
  $check_stmt = $conn->prepare("SELECT user_id, image_url FROM garden_posts WHERE id = ?");
  $check_stmt->bind_param("i", $post_id);
  $check_stmt->execute();
  $result = $check_stmt->get_result();

  if ($row = $result->fetch_assoc()) {
    if ($row['user_id'] == $user_id) {
      // Delete the post
      $delete_stmt = $conn->prepare("DELETE FROM garden_posts WHERE id = ?");
      $delete_stmt->bind_param("i", $post_id);

      if ($delete_stmt->execute()) {
        // Delete the image file if it exists
        if (!empty($row['image_url'])) {
          $image_path = $_SERVER['DOCUMENT_ROOT'] . $row['image_url'];
          if (file_exists($image_path)) {
            unlink($image_path);
          }
        }

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
      } else {
        $error_message = "Error deleting post: " . $conn->error;
      }
    } else {
      $error_message = "You don't have permission to delete this post.";
    }
  }
}

// Handle exchange request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_exchange') {
  $post_id = $_POST['post_id']; // The post the user wants to exchange with
  $exchange_item_id = $_POST['exchange_item']; // The user's item they want to exchange
  $message = isset($_POST['message']) ? trim($_POST['message']) : '';

  // Verify the target post exists and is exchangeable
  $check_stmt = $conn->prepare("SELECT id, user_id, is_exchangeable, content FROM garden_posts WHERE id = ?");
  $check_stmt->bind_param("i", $post_id);
  $check_stmt->execute();
  $result = $check_stmt->get_result();

  if ($target_post = $result->fetch_assoc()) {
    if ($target_post['is_exchangeable']) {
      // Don't allow users to request exchange with their own posts
      if ($target_post['user_id'] == $user_id) {
        $error_message = "You cannot request an exchange with your own post.";
      } else {
        // Check if the requested post already has pending or accepted exchange requests
        $requested_check_stmt = $conn->prepare("SELECT
          SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
          SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_count
          FROM garden_exchange
          WHERE requested_post_id = ? OR offered_post_id = ?");
        $requested_check_stmt->bind_param("ii", $post_id, $post_id);
        $requested_check_stmt->execute();
        $requested_result = $requested_check_stmt->get_result();
        $requested_row = $requested_result->fetch_assoc();

        if ($requested_row['accepted_count'] > 0) {
          $error_message = "This item has already been exchanged.";
        } elseif ($requested_row['pending_count'] > 0) {
          $error_message = "This item already has a pending exchange request.";
        } else {
          // Check if the offered item is already involved in an exchange
          $offered_check_stmt = $conn->prepare("SELECT
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_count
            FROM garden_exchange
            WHERE requested_post_id = ? OR offered_post_id = ?");
          $offered_check_stmt->bind_param("ii", $exchange_item_id, $exchange_item_id);
          $offered_check_stmt->execute();
          $offered_result = $offered_check_stmt->get_result();
          $offered_row = $offered_result->fetch_assoc();

          if ($offered_row['accepted_count'] > 0) {
            $error_message = "The item you selected for exchange has already been exchanged.";
          } elseif ($offered_row['pending_count'] > 0) {
            $error_message = "The item you selected for exchange already has a pending exchange request.";
          } else {
            // Verify the user's exchange item exists and belongs to them
            $item_check_stmt = $conn->prepare("SELECT id, content FROM garden_posts WHERE id = ? AND user_id = ? AND is_exchangeable = 1");
            $item_check_stmt->bind_param("ii", $exchange_item_id, $user_id);
            $item_check_stmt->execute();
            $item_result = $item_check_stmt->get_result();

            if ($user_item = $item_result->fetch_assoc()) {
              // Create the garden exchange request
              $status = 'pending'; // Initial status is pending

              $exchange_stmt = $conn->prepare("INSERT INTO garden_exchange (requester_id, requested_post_id, offered_post_id, status) VALUES (?, ?, ?, ?)");
              $exchange_stmt->bind_param("iiis", $user_id, $post_id, $exchange_item_id, $status);

              if ($exchange_stmt->execute()) {
                // Get the exchange request ID
                $exchange_id = $conn->insert_id;

                // Create notification for the post owner
                $notification_message = htmlspecialchars($username) . " wants to exchange garden produce with you.";
                $notification_type = 'garden_exchange';

                $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, related_id, type, message) VALUES (?, ?, ?, ?)");
                $notification_stmt->bind_param("iiss", $target_post['user_id'], $exchange_id, $notification_type, $message);
                $notification_stmt->execute();

                // Store exchange request details in session for confirmation message
                $_SESSION['exchange_request_sent'] = true;

                // Redirect to prevent form resubmission
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
              } else {
                $error_message = "Error sending exchange request: " . $conn->error;
              }
            } else {
              $error_message = "The item you selected for exchange is not valid or does not belong to you.";
            }
          }
        }
      }
    } else {
      $error_message = "This post is not available for exchange.";
    }
  } else {
    $error_message = "Post not found.";
  }
}

// Fetch garden posts with exchange request information
$posts_query = "
    SELECT gp.*, u.username,
    (SELECT COUNT(*) FROM garden_exchange ge
     WHERE (ge.requested_post_id = gp.id OR ge.offered_post_id = gp.id)
     AND ge.status = 'pending') as has_pending_requests,
    (SELECT COUNT(*) FROM garden_exchange ge
     WHERE (ge.requested_post_id = gp.id OR ge.offered_post_id = gp.id)
     AND ge.status = 'accepted') as has_accepted_requests
    FROM garden_posts gp
    JOIN users u ON gp.user_id = u.id
    WHERE gp.garden_id = ?
    ORDER BY gp.created_at DESC
";

$stmt = $conn->prepare($posts_query);
$stmt->bind_param("i", $garden_id);
$stmt->execute();
$posts_result = $stmt->get_result();
$posts = [];
if ($posts_result) {
  while ($row = $posts_result->fetch_assoc()) {
    $posts[] = $row;
  }
}

// Format timestamp function
function formatTimeAgo($timestamp)
{
  $time_ago = strtotime($timestamp);
  $current_time = time();
  $time_difference = $current_time - $time_ago;

  if ($time_difference < 60) {
    return 'Just now';
  } elseif ($time_difference < 3600) {
    $minutes = round($time_difference / 60);
    return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
  } elseif ($time_difference < 86400) {
    $hours = round($time_difference / 3600);
    return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
  } elseif ($time_difference < 604800) {
    $days = round($time_difference / 86400);
    return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
  } else {
    return date('M j, Y', $time_ago);
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gardening Community</title>
  <link rel="stylesheet" href="index.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/footer.css">
  <link rel="stylesheet" href="../../css/common.css">
  <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="index.js" defer></script>
</head>

<body>
  <!-- Include Header -->
  <?php include '../common/header.php'; ?>

  <!-- Page content -->
  <main class="content">
    <div class="gardening-container">
      <!-- Post Creation Section -->
      <div class="post-creation-card">
        <div class="post-header">
          <h2>Share with the Community</h2>
        </div>
        <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>" enctype="multipart/form-data">
          <input type="hidden" name="action" value="create_post">
          <div class="post-input-container">
            <textarea id="post-content" name="content"
              placeholder="Write your garden tips or post your home grown produce..." required></textarea>
            <div id="post-image-preview-container" class="post-image-preview hidden">
              <img id="post-image-preview" src="#" alt="Image Preview">
              <button type="button" id="remove-post-image-btn" class="remove-btn">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <div class="post-options">
              <div class="post-attachments">
                <button type="button" class="attachment-btn" id="photo-btn">
                  <i class="fas fa-image"></i> Photo
                </button>
                <input type="file" id="post-image-input" name="post_image" accept="image/*" class="hidden">
                <div class="exchangeable-checkbox">
                  <input type="checkbox" id="exchangeable-check" name="exchangeable">
                  <label for="exchangeable-check">Mark as Home Grown Produce</label>
                </div>
              </div>
              <button type="submit" class="button button-primary" id="post-btn">Post</button>
            </div>
          </div>
        </form>
      </div>

      <!-- Feed Section -->
      <div class="feed-container">
        <h3>Community Garden Feed</h3>

        <?php if (isset($error_message)): ?>
          <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
          <div class="no-posts-message">
            <p>No garden posts yet. Be the first to share your gardening experience!</p>
          </div>
        <?php else: ?>
          <?php foreach ($posts as $post): ?>
            <div class="post-card">
              <div class="post-header">
                <div class="post-user-info">
                  <img src="../../images/profile-placeholder.png" alt="User Profile" class="user-avatar">
                  <div class="post-user-details">
                    <h4><?php echo htmlspecialchars($post['username']); ?></h4>
                    <p class="post-time"><?php echo formatTimeAgo($post['created_at']); ?></p>
                  </div>
                </div>
                <div class="post-actions-top">
                  <?php if ($post['is_exchangeable'] && $post['user_id'] != $user_id): ?>
                    <?php if ($post['has_accepted_requests'] > 0): ?>
                      <!-- Hide button completely if item has been exchanged -->
                    <?php elseif ($post['has_pending_requests'] > 0): ?>
                      <!-- Show disabled button if item has pending requests -->
                      <button class="action-btn-top exchange-btn-disabled" title="This item already has a pending exchange request" disabled>
                        <i class="fas fa-exchange-alt"></i>
                      </button>
                    <?php else: ?>
                      <!-- Show normal button if item is available for exchange -->
                      <button class="action-btn-top exchange-btn" title="Request Exchange"
                        data-post-id="<?php echo $post['id']; ?>">
                        <i class="fas fa-exchange-alt"></i>
                      </button>
                    <?php endif; ?>
                  <?php endif; ?>

                  <?php if ($post['user_id'] == $user_id && $post['has_pending_requests'] == 0 && $post['has_accepted_requests'] == 0): ?>
                    <button class="action-btn-top edit-btn" title="Edit Post" data-post-id="<?php echo $post['id']; ?>">
                      <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>" class="delete-form"
                      style="display: inline;">
                      <input type="hidden" name="action" value="delete_post">
                      <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                      <button type="submit" class="action-btn-top delete-btn" title="Delete Post"
                        onclick="return confirm('Are you sure you want to delete this post?');">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
              <div class="post-content">
                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <?php if (!empty($post['image_url'])): ?>
                  <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post image" class="post-image">
                <?php endif; ?>
              </div>
              <?php if ($post['is_exchangeable']): ?>
                <?php if ($post['has_accepted_requests'] > 0): ?>
                  <div class="exchangeable-tag accepted">
                    <i class="fas fa-check-circle"></i> Exchanged
                  </div>
                <?php elseif ($post['has_pending_requests'] > 0): ?>
                  <div class="exchangeable-tag pending">
                    <i class="fas fa-clock"></i> Exchange In Progress
                  </div>
                <?php else: ?>
                  <div class="exchangeable-tag">
                    <i class="fas fa-exchange-alt"></i> Exchangeable
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <!-- Include Footer -->
  <?php include '../common/footer.php'; ?>



  <!-- Exchange Request Modal -->
  <div id="exchange-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Request Exchange</h3>
        <span class="close-modal" id="close-exchange-modal">&times;</span>
      </div>
      <div class="modal-body">
        <form id="exchange-form" method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>"
          enctype="multipart/form-data">
          <input type="hidden" name="action" value="request_exchange">
          <input type="hidden" name="post_id" id="exchange-post-id" value="">

          <div class="form-group">
            <label for="exchange-item">Select Item to Exchange</label>
            <select id="exchange-item" name="exchange_item" required>
              <option value="">-- Select an item --</option>
              <?php
              // Fetch user's items that can be exchanged and are not already in an exchange
              $items_query = "
                  SELECT gp.id, gp.content
                  FROM garden_posts gp
                  WHERE gp.user_id = ?
                  AND gp.is_exchangeable = 1
                  AND NOT EXISTS (
                      SELECT 1 FROM garden_exchange ge
                      WHERE (ge.requested_post_id = gp.id OR ge.offered_post_id = gp.id)
                      AND (ge.status = 'pending' OR ge.status = 'accepted')
                  )
              ";
              $items_stmt = $conn->prepare($items_query);
              $items_stmt->bind_param("i", $user_id);
              $items_stmt->execute();
              $items_result = $items_stmt->get_result();

              while ($item = $items_result->fetch_assoc()) {
                echo '<option value="' . $item['id'] . '">' . htmlspecialchars($item['content']) . '</option>';
              }
              ?>
            </select>
          </div>

          <div class="form-group">
            <label for="exchange-message">Message (Optional)</label>
            <textarea id="exchange-message" name="message"
              placeholder="Add any additional details about the exchange..."></textarea>
          </div>

          <div class="form-actions">
            <button type="button" id="cancel-exchange-btn" class="button button-secondary">Cancel</button>
            <button type="submit" class="button button-primary">Send Request</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php if (isset($_SESSION['exchange_request_sent']) && $_SESSION['exchange_request_sent']): ?>
    <script>
      alert('Exchange request sent successfully!');
    </script>
    <?php
    // Clear the session variable
    $_SESSION['exchange_request_sent'] = false;
  endif;
  ?>
</body>

</html>