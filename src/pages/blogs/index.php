<?php
// Include database connection and authentication helper
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// No login required for viewing blog posts
// Get user ID if logged in (for potential future features like favorites)
$user_id = isLoggedIn() ? getCurrentUserId() : null;

// Check if blog post ID is provided
$blog_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If no ID provided, redirect to blogs list
if ($blog_id <= 0) {
    header('Location: ../../pages/blogs_all/index.php');
    exit;
}

// Fetch the specific blog post
$blog_query = "
    SELECT b.*, u.username as author_name,
           DATE_FORMAT(b.created_at, '%M %d') as formatted_date
    FROM blog_posts b
    JOIN users u ON b.author_id = u.id
    WHERE b.id = ?
";

$stmt = $conn->prepare($blog_query);
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$result = $stmt->get_result();

// If blog post not found, redirect to blogs list
if ($result->num_rows === 0) {
    header('Location: ../../pages/blogs_all/index.php');
    exit;
}

$blog_post = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($blog_post['title']); ?></title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="../../css/header.css">
    <link rel="stylesheet" href="../../css/footer.css">
    <link rel="stylesheet" href="../../css/common.css">
    <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'>
</head>
<body>
    <?php include '../common/header.php'; ?>

    <!-- Blog Content -->
    <main>
        <div class="container">
            <header>
                <h1><?php echo htmlspecialchars($blog_post['title']); ?></h1>
                <p class="meta">Created by <?php echo htmlspecialchars($blog_post['author_name']); ?> · Updated on <?php echo $blog_post['formatted_date']; ?></p>
                <br>
                <img src="<?php echo !empty($blog_post['image_url']) ? $blog_post['image_url'] : '../../frontend/images/blogpic1.jpg'; ?>" alt="<?php echo htmlspecialchars($blog_post['title']); ?>" class="featured-image">
            </header>
            <article>
                <?php
                // Split content by paragraphs and output each as a separate <p> tag
                $paragraphs = explode("\n", $blog_post['content']);
                foreach ($paragraphs as $paragraph) {
                    if (trim($paragraph) !== '') {
                        echo '<p>' . htmlspecialchars($paragraph) . '</p>';
                    }
                }
                ?>
            </article>
        </div>
    </main>

    <?php include '../common/footer.php'; ?>
</body>
</html>
