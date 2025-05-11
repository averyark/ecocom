<?php
// Include database connection and authentication
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// Require login to access this page
requireLogin();

// Get user ID
$user_id = getCurrentUserId();

// Initialize variables
$error_message = '';
$success_message = '';
$is_edit_mode = false;
$item = null;

// Check if we're in edit mode (id parameter in URL)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $item_id = intval($_GET['id']);
    $is_edit_mode = true;

    // Fetch the existing item data
    $query = "SELECT * FROM swaps WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Item not found
        header("Location: ../../pages/swaps/index.php");
        exit;
    }

    $item = $result->fetch_assoc();

    // Verify the current user is the owner of this item
    if ($item['user_id'] != $user_id) {
        // Not the owner, redirect to swaps page
        header("Location: ../../pages/swaps/index.php");
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $item_name = isset($_POST['item_name']) ? trim($_POST['item_name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $wish_list = isset($_POST['wish_list']) ? trim($_POST['wish_list']) : '';
    $user_notes = isset($_POST['user_notes']) ? trim($_POST['user_notes']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

    // Validate form data
    if (empty($item_name)) {
        $error_message = "Item name is required.";
    } elseif (empty($description)) {
        $error_message = "Description is required.";
    } elseif (empty($wish_list)) {
        $error_message = "Wishlist is required.";
    } elseif (empty($category)) {
        $error_message = "Category is required.";
    } elseif (
        (!$is_edit_mode || $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) &&
        (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK)
    ) {
        // Image is required for new items
        // For edit mode, only validate if a file was actually selected (error != 4)
        $error_message = "Image upload is required.";
    } else {
        // Handle image upload if present
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/swaps/';

            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('swap_') . '.' . $file_extension;
            $target_file = $upload_dir . $filename;

            // Move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = '../../uploads/swaps/' . $filename;
            } else {
                $error_message = "Failed to upload image.";
            }
        }

        // If no errors, insert or update database
        if (empty($error_message)) {
            if ($is_edit_mode && $item_id > 0) {
                // Update existing item

                // If no new image was uploaded, keep the existing one
                if ($image_url === null) {
                    // In edit mode with no new image uploaded, we need to ensure there's an existing image
                    $check_query = "SELECT image_url FROM swaps WHERE id = ? AND user_id = ?";
                    $check_stmt = $conn->prepare($check_query);
                    $check_stmt->bind_param("ii", $item_id, $user_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    $existing_item = $check_result->fetch_assoc();

                    if (empty($existing_item['image_url'])) {
                        $error_message = "An image is required. Please upload an image.";
                    } else {
                        $stmt = $conn->prepare("UPDATE swaps SET item_name = ?, description = ?, category = ?, wish_list = ?, user_notes = ? WHERE id = ? AND user_id = ?");
                        $stmt->bind_param("sssssii", $item_name, $description, $category, $wish_list, $user_notes, $item_id, $user_id);
                    }
                } else {
                    // Update with new image
                    $stmt = $conn->prepare("UPDATE swaps SET item_name = ?, description = ?, category = ?, image_url = ?, wish_list = ?, user_notes = ? WHERE id = ? AND user_id = ?");
                    $stmt->bind_param("ssssssii", $item_name, $description, $category, $image_url, $wish_list, $user_notes, $item_id, $user_id);
                }

                if (empty($error_message) && $stmt->execute()) {
                    // Redirect to the item's detail page
                    header("Location: ../../pages/swaps_inspect/index.php?id=" . $item_id);
                    exit;
                } elseif (empty($error_message)) {
                    $error_message = "Error updating swap: " . $conn->error;
                }
            } else {
                // Insert new item
                $stmt = $conn->prepare("INSERT INTO swaps (user_id, item_name, description, category, image_url, wish_list, user_notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssss", $user_id, $item_name, $description, $category, $image_url, $wish_list, $user_notes);

                if ($stmt->execute()) {
                    // Redirect to swaps page
                    header("Location: ../../pages/swaps/index.php");
                    exit;
                } else {
                    $error_message = "Error creating swap: " . $conn->error;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit_mode ? 'Edit' : 'Create'; ?> Swap</title>
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

    <?php if (!empty($error_message)): ?>
        <div class="error-message" style="color: red; text-align: center; margin-top: 20px;">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?><?php echo $is_edit_mode ? '?id=' . $item['id'] : ''; ?>"
        method="post" enctype="multipart/form-data">
        <main>
            <div>
                <div class="image-upload-box">
                    <div class="image-upload">
                        <label for="image-input" class="image-label">
                            <img id="preview-image"
                                src="<?php echo $is_edit_mode && !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : '../../images/placeholder.png'; ?>"
                                alt="Upload your image here..." class="garden-img">
                        </label>
                        <input type="file" name="image" id="image-input" accept="image/*" style="display: none;">
                    </div>
                </div>
                <span id="image-upload-required">Image upload is required</span>
            </div>
            <div class="form">
                <?php if ($is_edit_mode): ?>
                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                <?php endif; ?>
                <section class="input-box name-box">
                    <p><strong>Item Name</strong></p>
                    <input type="text" name="item_name" placeholder="Item Name here"
                        value="<?php echo $is_edit_mode ? htmlspecialchars($item['item_name']) : ''; ?>" required>
                </section>
                <section class="input-box description-box">
                    <p><strong>Description</strong></p>
                    <textarea name="description" placeholder="Description here" required><?php echo $is_edit_mode ? htmlspecialchars($item['description']) : ''; ?></textarea>
                </section>

                <section class="input-box wishlist-box">
                    <p><strong>My Wishlist</strong></p>
                    <input type="text" name="wish_list" placeholder="Wishlist here"
                        value="<?php echo $is_edit_mode ? htmlspecialchars($item['wish_list']) : ''; ?>" required>
                </section>
                <section class="input-box usernotes-box">
                    <p><strong>User Notes</strong></p>
                    <input type="text" name="user_notes" placeholder="User Notes Here"
                        value="<?php echo $is_edit_mode ? htmlspecialchars($item['user_notes']) : ''; ?>">
                </section>
                <section class="input-box category-box">
                    <p><strong>Genre</strong></p>
                    <select name="category" id="category" required>
                        <option value="" disabled <?php echo !$is_edit_mode ? 'selected' : ''; ?>>Select a category
                        </option>
                        <option value="Home & Kitchen" <?php echo $is_edit_mode && $item['category'] == 'Home & Kitchen' ? 'selected' : ''; ?>>Home & Kitchen</option>
                        <option value="Cleaning" <?php echo $is_edit_mode && $item['category'] == 'Cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                        <option value="Bathroom" <?php echo $is_edit_mode && $item['category'] == 'Bathroom' ? 'selected' : ''; ?>>Bathroom</option>
                        <option value="Beauty" <?php echo $is_edit_mode && $item['category'] == 'Beauty' ? 'selected' : ''; ?>>Beauty</option>
                        <option value="Baby & Kids" <?php echo $is_edit_mode && $item['category'] == 'Baby & Kids' ? 'selected' : ''; ?>>Baby & Kids</option>
                        <option value="Pets" <?php echo $is_edit_mode && $item['category'] == 'Pets' ? 'selected' : ''; ?>>Pets</option>
                    </select>
                </section>
                <button type="submit"
                    class="button button-primary create-swap"><?php echo $is_edit_mode ? 'Update' : 'Create'; ?>
                    Swap</button>
            </div>
        </main>
    </form>

    <!-- Footer will be loaded here -->
    <?php include realpath(__DIR__ . '/../../pages/common/footer.php'); ?>
</body>

</html>