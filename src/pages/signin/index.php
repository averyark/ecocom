<?php
// Include database connection and authentication helper
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// Initialize variables
$email = '';
$error_message = '';
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '../../pages/home/index.php';

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect to home page if already logged in
    header("Location: " . $redirect_url);
    exit;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error_message = "Email and password are required.";
    } else {
        // Check if user exists
        $query = "SELECT id, username, password FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Set cookie if remember me is checked
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (86400 * 30); // 30 days
                    
                    // Store token in database (you would need to create a remember_tokens table)
                    // This is a simplified example - in a real app, you'd want to store the token securely
                    setcookie('remember_token', $token, $expires, '/');
                }
                
                // Redirect to home page or requested page
                header("Location: " . $redirect_url);
                exit;
            } else {
                $error_message = "Invalid email or password.";
            }
        } else {
            $error_message = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in</title>
    <link rel="stylesheet" href="index.css" />
    <style>
        .error-message {
            color: #e74c3c;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <header class="header">
        <img src="../../images/Ellipse 1.svg" alt="Description of image" style="padding-top: 20px;"> <Strong>ECOCOM</Strong>
    </header>
    <div class="container">
        <h2>Welcome back</h2>
        <p class="subtext">Please insert your details</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?><?php echo !empty($_GET['redirect']) ? '?redirect=' . htmlspecialchars($_GET['redirect']) : ''; ?>" method="post" style="width: 330px;">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required />
            <br />
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required />
        
            <div class="forget-pass">
                <label class="checkbox">
                    <input type="checkbox" name="remember_me" style="width: 15px; height: 20px; margin-bottom: 5px;"> Remember me
                </label>
                <!-- <a href="#" class="forgot-pass">Forgot password</a> -->
            </div>
        
            <button type="submit" class="btn">Continue</button>
        
            <p class="signin">Don't have an account? <a href="../../pages/signup/index.php">Sign up</a></p>
        </form>
    </div>
    <div class="image-container">
        <img src="../../images/Frame 1.png" style="height: 920px; margin-left: auto;">
    </div>
</body>
</html>
