
<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['reset_otp_user_id'])) {
    header("Location: forgot_password.php");
    exit();
}

$user_id = (int) $_SESSION['reset_otp_user_id'];

if (isset($_POST['verify_otp'])) {
    $otp = trim($_POST['otp'] ?? '');

    if (!preg_match('/^[0-9]{6}$/', $otp)) {
        $_SESSION['reset_otp_error'] = "Please enter a valid 6-digit OTP.";
        header("Location: verify_reset_otp.php");
        exit();
    }

    $stmt = $conn->prepare("
        SELECT id, reset_otp_code, reset_otp_expires_at, reset_otp_attempts
        FROM users
        WHERE id = ?
    ");

    if (!$stmt) {
        error_log("Reset OTP query preparation failed: " . $conn->error);
        $_SESSION['reset_otp_error'] = "A system error occurred. Please try again.";
        header("Location: verify_reset_otp.php");
        exit();
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        $stmt->close();
        unset($_SESSION['reset_otp_user_id']);
        header("Location: forgot_password.php");
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    if ((int) $user['reset_otp_attempts'] >= 5) {
        $_SESSION['reset_otp_error'] = "Too many incorrect attempts. Please request a new OTP.";
        header("Location: verify_reset_otp.php");
        exit();
    }

    if (empty($user['reset_otp_code']) || empty($user['reset_otp_expires_at'])) {
        $_SESSION['reset_otp_error'] = "Your OTP is no longer valid. Please request a new one.";
        header("Location: verify_reset_otp.php");
        exit();
    }

    if (strtotime($user['reset_otp_expires_at']) < time()) {
        $_SESSION['reset_otp_error'] = "Your OTP has expired. Please request a new one.";
        header("Location: verify_reset_otp.php");
        exit();
    }

    if (!password_verify($otp, $user['reset_otp_code'])) {
        $attempts = (int) $user['reset_otp_attempts'] + 1;

        $update = $conn->prepare("
            UPDATE users
            SET reset_otp_attempts = ?
            WHERE id = ?
        ");

        if (!$update) {
            error_log("Reset OTP attempts update preparation failed: " . $conn->error);
            $_SESSION['reset_otp_error'] = "A system error occurred. Please try again.";
            header("Location: verify_reset_otp.php");
            exit();
        }

        $update->bind_param("ii", $attempts, $user_id);
        $update->execute();
        $update->close();

        $remaining = 5 - $attempts;

        $_SESSION['reset_otp_error'] = $remaining > 0
            ? "Incorrect OTP. You have $remaining attempt(s) remaining."
            : "Too many incorrect attempts. Please request a new OTP.";

        header("Location: verify_reset_otp.php");
        exit();
    }

    $clear = $conn->prepare("
        UPDATE users
        SET reset_otp_code = NULL,
            reset_otp_expires_at = NULL,
            reset_otp_attempts = 0
        WHERE id = ?
    ");

    if (!$clear) {
        error_log("Reset OTP clearing preparation failed: " . $conn->error);
        $_SESSION['reset_otp_error'] = "A system error occurred. Please try again.";
        header("Location: verify_reset_otp.php");
        exit();
    }

    $clear->bind_param("i", $user_id);

    if (!$clear->execute()) {
        error_log("Reset OTP clearing failed: " . $clear->error);
        $clear->close();

        $_SESSION['reset_otp_error'] = "Unable to complete verification. Please try again.";
        header("Location: verify_reset_otp.php");
        exit();
    }

    $clear->close();

    session_regenerate_id(true);

    $_SESSION['reset_user_id'] = $user_id;

    unset(
        $_SESSION['reset_otp_user_id'],
        $_SESSION['reset_otp_error']
    );

    header("Location: reset_password.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT first_name, backup_email, reset_otp_expires_at
    FROM users
    WHERE id = ?
");

if (!$stmt) {
    error_log("Reset OTP display query preparation failed: " . $conn->error);
    unset($_SESSION['reset_otp_user_id']);
    header("Location: forgot_password.php");
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    unset($_SESSION['reset_otp_user_id']);
    header("Location: forgot_password.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

$error = $_SESSION['reset_otp_error'] ?? '';
unset($_SESSION['reset_otp_error']);

$maskedEmail = $user['backup_email'] ?? '';

if (strpos($maskedEmail, '@') !== false) {
    [$emailName, $emailDomain] = explode('@', $maskedEmail, 2);

    if (strlen($emailName) > 2) {
        $maskedEmail = substr($emailName, 0, 2) .
            str_repeat('*', max(2, strlen($emailName) - 2)) .
            '@' . $emailDomain;
    }
}

$remainingSeconds = !empty($user['reset_otp_expires_at'])
    ? max(0, strtotime($user['reset_otp_expires_at']) - time())
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/logo.png">
    <title>Verify OTP - ACES SBM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="./CSS/login.css">

    <style>
        .otp-info {
            text-align: center;
            margin-bottom: 20px;
            color: #555;
            line-height: 1.5;
        }

        .otp-email {
            font-weight: 600;
            color: #0e8708;
        }

        .otp-input {
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            font-size: 24px;
            letter-spacing: 8px;
            font-weight: 600;
        }

        .otp-error {
            background: #ffe5e5;
            color: #c62828;
            padding: 10px 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

        .otp-actions {
            text-align: center;
            margin-top: 15px;
        }

        .otp-actions a {
            color: #0e8708;
            text-decoration: none;
            font-size: 14px;
        }

        .otp-actions a:hover {
            text-decoration: underline;
        }

        .timer {
            text-align: center;
            color: #777;
            font-size: 13px;
            margin-top: 8px;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <a href="forgot_password.php" class="back-button">
                <i class="fa-solid fa-arrow-left"></i>
            </a>

            <h1>Verify OTP</h1>

            <div class="otp-info">
                <p>We sent a 6-digit password reset code to:</p>
                <div class="otp-email">
                    <?= htmlspecialchars($maskedEmail, ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="otp-error">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="verify_reset_otp.php" method="post">
                <input
                    type="text"
                    name="otp"
                    class="otp-input"
                    placeholder="000000"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    required
                >

                <button type="submit" name="verify_otp">Verify OTP</button>
            </form>

            <div class="timer">
                OTP expires in <span id="timer">05:00</span>
            </div>

            <div class="otp-actions">
                <a href="forgot_password.php">Request a new OTP</a>
            </div>
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
        let seconds = <?= (int) $remainingSeconds ?>;
        const timer = document.getElementById("timer");

        function updateTimer() {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;

            timer.textContent =
                String(minutes).padStart(2, "0") + ":" +
                String(remainingSeconds).padStart(2, "0");

            if (seconds > 0) {
                seconds--;
            } else {
                timer.textContent = "Expired";
            }
        }

        updateTimer();
        setInterval(updateTimer, 1000);
    </script>
</body>
</html>
