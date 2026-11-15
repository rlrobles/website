<?php
session_start();

$errors = [
    'login' => $_SESSION['error'] ?? '',
];

unset($_SESSION['error']);

function displayError($field) {
    global $errors;

    if (!empty($errors[$field])) {
        echo '<div class="error-message">' .
            htmlspecialchars($errors[$field], ENT_QUOTES, 'UTF-8') .
            '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/logo.png">
    <title>ACES SBM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="CSS/login.css">
    <script defer src="JS/script.js"></script>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box" id="login-form">
            <a href="index.php" class="back-button">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <h1>Login</h1>
            <form action="login_register.php" method="post">
                <?php displayError('login'); ?>
                <input type="email" name="email" placeholder="Email" required>
                <div class="password-field">
                    <input type="password" name="password" id="loginPassword" placeholder="Password" required>
                    <button type="button" class="password-toggle" id="toggleLoginPassword" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
                <button type="submit" class="login-btn" name="login">Login</button>
            </form>
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
        const loginPassword = document.getElementById("loginPassword");
        const toggleLoginPassword = document.getElementById("toggleLoginPassword");
        const loginPasswordIcon = toggleLoginPassword.querySelector("i");

        toggleLoginPassword.addEventListener("click", function() {
            if (loginPassword.type === "password") {
                loginPassword.type = "text";
                loginPasswordIcon.classList.replace("fa-eye", "fa-eye-slash");
                toggleLoginPassword.setAttribute("aria-label", "Hide password");
            } else {
                loginPassword.type = "password";
                loginPasswordIcon.classList.replace("fa-eye-slash", "fa-eye");
                toggleLoginPassword.setAttribute("aria-label", "Show password");
            }
        });
    </script>
</body>
</html>
