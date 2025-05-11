<?php
// Include database connection and authentication helper
require_once realpath(__DIR__ . '/../../includes/db_conn.php');
require_once realpath(__DIR__ . '/../../includes/auth.php');

// Initialize variables
$email = '';
$username = '';
$birthdate = '';
$error_message = '';
$success_message = '';

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect to home page if already logged in
    header("Location: ../../pages/home/index.php");
    exit;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $terms_agreed = isset($_POST['terms_agreed']);

    // Validate input
    if (empty($email) || empty($username) || empty($password) || empty($birthdate)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{}|;:\'",.<>\/?])/', $password)) {
        $error_message = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
    } elseif (!$terms_agreed) {
        $error_message = "You must agree to the Terms of Use.";
    } else {
        // Check if email already exists
        $check_email_query = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_email_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "Email address is already registered.";
        } else {
            // Check if username already exists
            $check_username_query = "SELECT id FROM users WHERE username = ?";
            $stmt = $conn->prepare($check_username_query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error_message = "Username is already taken.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $insert_query = "INSERT INTO users (email, username, password, birthdate) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("ssss", $email, $username, $hashed_password, $birthdate);

                if ($stmt->execute()) {
                    // Get the new user ID
                    $user_id = $stmt->insert_id;

                    // Set session variables
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;

                    // Redirect to home page
                    header("Location: ../../pages/home/index.php");
                    exit;
                } else {
                    $error_message = "Registration failed. Please try again later.";
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
    <title>Signup</title>
    <link rel="stylesheet" href="index.css">
    <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'> <!-- Allows me to use Public Sans -->
    <script src="./index.js"></script>
    <style>
        .error-message {
            color: #e74c3c;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .success-message {
            color: #2ecc71;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .password-requirements {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
            margin-bottom: 10px;
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            margin-bottom: 10px;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        .strength-weak {
            background-color: #e74c3c;
            width: 30%;
        }
        .strength-medium {
            background-color: #f39c12;
            width: 60%;
        }
        .strength-strong {
            background-color: #2ecc71;
            width: 100%;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const strengthIndicator = document.createElement('div');
            strengthIndicator.className = 'password-strength';
            const form = document.querySelector('form');

            // Insert strength indicator after password input
            passwordInput.parentNode.insertBefore(strengthIndicator, passwordInput.nextSibling);

            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;

                // Check length
                if (password.length >= 8) strength += 1;

                // Check for lowercase letters
                if (/[a-z]/.test(password)) strength += 1;

                // Check for uppercase letters
                if (/[A-Z]/.test(password)) strength += 1;

                // Check for numbers
                if (/\d/.test(password)) strength += 1;

                // Check for special characters
                if (/[!@#$%^&*()_+\-=\[\]{}|;:\'",.<>\/?]/.test(password)) strength += 1;

                // Update strength indicator
                strengthIndicator.className = 'password-strength';
                if (strength <= 2) {
                    strengthIndicator.classList.add('strength-weak');
                } else if (strength <= 4) {
                    strengthIndicator.classList.add('strength-medium');
                } else {
                    strengthIndicator.classList.add('strength-strong');
                }
            });

            // Form validation
            form.addEventListener('submit', function(event) {
                const password = passwordInput.value;
                const termsCheckbox = document.getElementById('terms_agreed');
                let isValid = true;
                let errorMessage = '';

                // Validate password complexity
                if (password.length < 8) {
                    errorMessage = 'Password must be at least 8 characters long.';
                    isValid = false;
                } else if (!/[a-z]/.test(password)) {
                    errorMessage = 'Password must contain at least one lowercase letter.';
                    isValid = false;
                } else if (!/[A-Z]/.test(password)) {
                    errorMessage = 'Password must contain at least one uppercase letter.';
                    isValid = false;
                } else if (!/\d/.test(password)) {
                    errorMessage = 'Password must contain at least one number.';
                    isValid = false;
                } else if (!/[!@#$%^&*()_+\-=\[\]{}|;:\'",.<>\/?]/.test(password)) {
                    errorMessage = 'Password must contain at least one special character.';
                    isValid = false;
                }

                // Validate terms agreement
                if (!termsCheckbox.checked) {
                    errorMessage = 'You must agree to the Terms of Use.';
                    isValid = false;
                }

                if (!isValid) {
                    event.preventDefault();
                    alert(errorMessage);
                }
            });
        });
    </script>
</head>
<body>
    <header class="header">
    <img src="../../images/Ellipse 1.svg" alt="" style="padding-top: 20px;"> <Strong>ECOCOM</Strong>
    </header>
    <div class="container">
        <h2>Join us now</h2>
        <p class="subtext">Please insert your details</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" style="width: 330px;">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required />
            <br />
            <label for="name">Username</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required />
            <br />
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required />
            <div class="password-requirements">
                Password must be at least 8 characters and include uppercase, lowercase, number, and special character.
            </div>
            <br />
            <label for="birthdate">Birthday</label>
            <input type="date" name="birthdate" id="birthdate" value="<?php echo htmlspecialchars($birthdate); ?>" required />
            <br />

            <label class="checkbox">
                <input type="checkbox" name="terms_agreed" id="terms_agreed" style="width: 15px; height: 20px; margin-bottom: 5px;" required>
                I agree to the <a href="#" target="_blank">Terms of use</a>
            </label>
            <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !$terms_agreed): ?>
                <div class="error-message">You must agree to the Terms of Use to continue.</div>
            <?php endif; ?>

            <button type="submit" class="btn">Continue</button>

            <p class="signin">Have an account? <a href="../../pages/signin/index.php">Sign in</a></p>
        </form>
    </div>
    <div class="image-container">
    <img src="../../images/Frame 1.png" style="height: 920px; margin-left: auto;">
    </div>
</body>
</html>
