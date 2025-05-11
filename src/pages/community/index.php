<?php
// Include database connection and authentication helper
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// Require user to be logged in
requireLogin();

// Get user ID and username
$user_id = getCurrentUserId();

// Fetch gardens from the database
$gardens_query = "
    SELECT g.*, u.username
    FROM gardens g
    JOIN users u ON g.user_id = u.id
    ORDER BY g.created_at DESC
";
$gardens_result = $conn->query($gardens_query);
$gardens = [];
if ($gardens_result) {
    while ($row = $gardens_result->fetch_assoc()) {
        $gardens[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="../../css/header.css">
    <link rel="stylesheet" href="../../css/footer.css">
    <link rel="stylesheet" href="../../css/common.css">
    <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'>
    <script src="./index.js" defer></script>
</head>
<body>

    <!-- Include header -->
    <?php include '../common/header.php'; ?>

    <div class="card-container">
        <?php if (empty($gardens)): ?>
            <div class="no-gardens-message">
                <p>No community gardens available yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($gardens as $garden): ?>
                <div class="card">
                    <a href="../../pages/gardening_join/index.php?id=<?php echo $garden['id']; ?>">
                        <img src="../../images/image (2).png" alt="garden image">
                    </a>
                    <div class="card-content">
                        <h2 class="card-title"><?php echo htmlspecialchars($garden['title']); ?></h2>
                        <p class="card-date">by <strong class="card-date-real"><?php echo htmlspecialchars($garden['username']); ?></strong></p>
                        <p class="card-location"><?php echo htmlspecialchars($garden['address']); ?></p>
                        <div class="btn-container">
                            <button class="navigate-btn" style="width: 110px; height: 37px;" onclick="window.location.href = '../../pages/gardening_join/index.php?id=<?php echo $garden['id']; ?>'">
                                <img src="../../images/Directions.png" alt="navigate">Navigate
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Include footer -->
    <?php include '../common/footer.php'; ?>
</body>
</html>
