<?php
// Include database connection and authentication helper
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// Require user to be logged in
requireLogin();

// Get user ID and username
$user_id = getCurrentUserId();
$current_username = getCurrentUsername();

// Initialize variables for form processing
$error_message = '';
$success_message = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $new_username = trim($_POST['new-username'] ?? '');
    $current_password = $_POST['current-password'] ?? '';
    $new_password = $_POST['new-password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';

    // Validate input
    if (empty($new_username)) {
        $error_message = "New username is required.";
    } elseif (empty($current_password)) {
        $error_message = "Current password is required.";
    } else {
        // Verify current password
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($current_password, $user['password'])) {
                // Check if username already exists (if it's different from current)
                if ($new_username !== $current_username) {
                    $check_username_query = "SELECT id FROM users WHERE username = ? AND id != ?";
                    $stmt = $conn->prepare($check_username_query);
                    $stmt->bind_param("si", $new_username, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $error_message = "Username is already taken.";
                    }
                }

                // If no error so far, proceed with updates
                if (empty($error_message)) {
                    // Prepare update query
                    $update_fields = [];
                    $update_types = "";
                    $update_params = [];

                    // Add username to update if it changed
                    if ($new_username !== $current_username) {
                        $update_fields[] = "username = ?";
                        $update_types .= "s";
                        $update_params[] = $new_username;
                    }

                    // Add password to update if provided
                    if (!empty($new_password)) {
                        // Validate new password
                        if (strlen($new_password) < 8) {
                            $error_message = "Password must be at least 8 characters long.";
                          } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{}|;:\'",.<>\/?])/', $new_password)) {
                            $error_message = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
                        } elseif ($new_password !== $confirm_password) {
                            $error_message = "Passwords do not match.";
                        } else {
                            // Hash new password
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_fields[] = "password = ?";
                            $update_types .= "s";
                            $update_params[] = $hashed_password;
                        }
                    }

                    // If there are fields to update and no errors
                    if (!empty($update_fields) && empty($error_message)) {
                        $update_query = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
                        $update_types .= "i";
                        $update_params[] = $user_id;

                        $stmt = $conn->prepare($update_query);

                        // Dynamically bind parameters
                        $bind_params = [$update_types];
                        foreach ($update_params as $key => $value) {
                            $bind_params[] = &$update_params[$key];
                        }
                        call_user_func_array([$stmt, 'bind_param'], $bind_params);

                        if ($stmt->execute()) {
                            $success_message = "Account information updated successfully!";

                            // Update session username if it was changed
                            if ($new_username !== $current_username) {
                                $_SESSION['username'] = $new_username;
                                $current_username = $new_username;
                            }
                        } else {
                            $error_message = "Failed to update account information. Please try again.";
                        }
                    }
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        } else {
            $error_message = "User not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile</title>
  <link rel="stylesheet" href="index.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/footer.css">
  <link rel="stylesheet" href="../../css/common.css">
  <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'>
  <script src="index.js" defer></script>
</head>

<body>
  <!-- Header will be loaded here -->
  <?php include '../common/header.php'; ?>

  <!-- Page content -->
  <main class="content">
    <div class="profile-container">
      <h1 class="profile-title">My Profile</h1>

      <div class="profile-section">
        <h2>Account Settings</h2>

        <!-- Combined Account Settings Form -->
        <div class="profile-form-container">
          <h3>Update Account Information</h3>

          <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
          <?php endif; ?>

          <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
          <?php endif; ?>

          <form id="accountForm" class="profile-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <!-- Username Section -->
            <div class="form-group">
              <label for="current-username">Current Username</label>
              <input type="text" id="current-username" name="current-username" value="<?php echo htmlspecialchars($current_username); ?>" readonly>
            </div>

            <div class="form-group">
              <label for="new-username">New Username</label>
              <input type="text" id="new-username" name="new-username">
              <div class="error-message" id="username-error"></div>
            </div>

            <!-- Password Section -->
            <div class="form-group">
              <label for="current-password">Current Password</label>
              <input type="password" id="current-password" name="current-password" required>
            </div>

            <div class="form-group">
              <label for="new-password">New Password</label>
              <input type="password" id="new-password" name="new-password">
              <div class="error-message" id="password-error"></div>
              <div class="password-hint">Password must contain letters, numbers, and at least one special character</div>
            </div>

            <div class="form-group">
              <label for="confirm-password">Confirm New Password</label>
              <input type="password" id="confirm-password" name="confirm-password">
              <div class="error-message" id="confirm-password-error"></div>
            </div>

            <button type="submit" class="button button-primary">Update Account</button>
            <div class="success-message" id="form-success"></div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer will be loaded here -->
  <?php include '../common/footer.php'; ?>
</body>

</html>
