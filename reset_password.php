<?php
session_start();

if (!isset($_SESSION["reset_user_id"])) {
    header("Location: forgot_password.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in both password fields.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $user_id = $_SESSION["reset_user_id"];

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);

        if ($stmt->execute()) {
            unset($_SESSION["reset_user_id"]);
            unset($_SESSION["reset_email"]);

            $success = "Your password has been successfully changed.";
        } else {
            $error = "Something went wrong. Please try again.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/logo.png">
    <title>Reset Password - ACES SBM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="./CSS/login.css">
    <script defer src="./JS/script.js"></script>
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <?php if (empty($success)): ?>
                <a href="forgot_password.php" class="back-button">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
                <h1>Reset Password</h1>
                <p class="forgot-description">
                    Enter your new password below.
                </p>
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="reset_password.php">
                    <div class="password-field">
                        <input
                            type="password"
                            name="new_password"
                            id="newPassword"
                            placeholder="New Password"
                            minlength="8"
                            required>

                        <button type="button" class="password-toggle" onclick="togglePassword('newPassword', 'newPasswordIcon')">
                            <i class="fa-solid fa-eye" id="newPasswordIcon"></i>
                        </button>
                    </div>

                    <div class="password-field">
                        <input
                            type="password"
                            name="confirm_password"
                            id="confirmPassword"
                            placeholder="Confirm Password"
                            minlength="8"
                            required>

                        <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', 'confirmPasswordIcon')">
                            <i class="fa-solid fa-eye" id="confirmPasswordIcon"></i>
                        </button>
                    </div>

                    <button type="submit">
                        Reset Password
                    </button>
                </form>
            <?php else: ?>
                <h1>Password Reset</h1>

                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>

                <a href="login.php" class="login-link">
                    Return to Login
                </a>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="socials">
            <a href="https://www.facebook.com/DepEdACES109582" class="fa-brands fa-facebook-f"></a>
            <a href="#" class="fa-solid fa-at"></a>
            <a href="#" class="fa-solid fa-phone"></a>
        </div>

        <div class="footerbottom">
            <p>&copy; <span id="current-year"></span> ACES. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>
