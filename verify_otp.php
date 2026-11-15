<?php
session_start();
require_once 'database.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['otp_user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['otp_user_id'];

function otpError($message)
{
    $_SESSION['otp_error'] = $message;
    header("Location: verify_otp.php");
    exit();
}

// GENERATE CSRF TOKEN
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {

    // GET OTP USER
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, email, role,
               must_change_password, otp_code, otp_expires_at,
               otp_attempts, otp_locked_until
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        session_unset();
        session_destroy();

        header("Location: login.php");
        exit();
    }

    // PROCESS OTP VERIFICATION
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {

        // CHECK CSRF TOKEN
        if (
            !isset($_POST['csrf_token']) ||
            !hash_equals(
                $_SESSION['csrf_token'] ?? '',
                $_POST['csrf_token']
            )
        ) {
            otpError("Invalid request. Please try again.");
        }

        $otp = trim($_POST['otp'] ?? '');

        // CHECK OTP COOLDOWN
        if (
            !empty($user['otp_locked_until']) &&
            strtotime($user['otp_locked_until']) > time()
        ) {
            $remaining = strtotime($user['otp_locked_until']) - time();

            otpError(
                "Too many incorrect OTP attempts. Please wait " .
                ceil($remaining / 60) . " minute(s)."
            );
        }

        // CLEAR EXPIRED LOCKOUT
        if (!empty($user['otp_locked_until'])) {

            $clearLock = $conn->prepare("
                UPDATE users
                SET otp_attempts = 0,
                    otp_locked_until = NULL
                WHERE id = ?
            ");

            $clearLock->bind_param("i", $user_id);
            $clearLock->execute();
            $clearLock->close();

            $user['otp_attempts'] = 0;
            $user['otp_locked_until'] = null;
        }

        // VALIDATE OTP FORMAT
        if (!preg_match('/^[0-9]{6}$/', $otp)) {
            otpError("Please enter a valid 6-digit OTP.");
        }

        // CHECK OTP EXISTENCE
        if (empty($user['otp_code']) || empty($user['otp_expires_at'])) {
            otpError("Your OTP is no longer valid. Please request a new one.");
        }

        // CHECK OTP EXPIRATION
        if (strtotime($user['otp_expires_at']) <= time()) {
            otpError("Your OTP has expired. Please request a new one.");
        }

        // VERIFY OTP
        if (!password_verify($otp, $user['otp_code'])) {

            $attempts = (int) $user['otp_attempts'] + 1;

            // LOCK ACCOUNT AFTER 5 INCORRECT ATTEMPTS
            if ($attempts >= 5) {

                $lock = $conn->prepare("
                    UPDATE users
                    SET otp_attempts = 5,
                        otp_locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE),
                        otp_code = NULL,
                        otp_expires_at = NULL
                    WHERE id = ?
                ");

                $lock->bind_param("i", $user_id);
                $lock->execute();
                $lock->close();

                otpError(
                    "Too many incorrect OTP attempts. Verification is locked for 5 minutes. Please request a new OTP afterward."
                );
            }

            // UPDATE FAILED ATTEMPTS
            $update = $conn->prepare("
                UPDATE users
                SET otp_attempts = ?
                WHERE id = ?
            ");

            $update->bind_param("ii", $attempts, $user_id);
            $update->execute();
            $update->close();

            $remaining = 5 - $attempts;

            otpError(
                "Incorrect OTP. You have $remaining attempt(s) remaining."
            );
        }

        // CHECK ACCOUNT ROLE
        if (!in_array($user['role'], ['admin', 'teaching', 'non-teaching'], true)) {
            otpError("Invalid account role. Please contact the administrator.");
        }

        // SUCCESSFUL OTP VERIFICATION
        session_regenerate_id(true);

        // CLEAR OTP DATA
        $clear = $conn->prepare("
            UPDATE users
            SET otp_code = NULL,
                otp_expires_at = NULL,
                otp_attempts = 0,
                otp_locked_until = NULL,
                otp_last_sent_at = NULL
            WHERE id = ?
        ");

        $clear->bind_param("i", $user_id);
        $clear->execute();
        $clear->close();

        // SET USER SESSION
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['role'] = $user['role'];

        unset(
            $_SESSION['otp_user_id'],
            $_SESSION['otp_error'],
            $_SESSION['otp_sent_at'],
            $_SESSION['csrf_token']
        );

        // FORCE PASSWORD CHANGE
        if ((int) $user['must_change_password'] === 1) {
            header("Location: change_password.php");
            exit();
        }

        // ROLE-BASED REDIRECTION
        if ($user['role'] === 'admin') {
            header("Location: admin/a_updated.php");
        } else {
            header("Location: employee/staff.php");
        }

        exit();
    }

    // LOAD OTP PAGE
    $stmt = $conn->prepare("
        SELECT first_name, backup_email, otp_expires_at
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $displayUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$displayUser) {
        session_unset();
        session_destroy();

        header("Location: login.php");
        exit();
    }

    // GET ERROR MESSAGE
    $error = $_SESSION['otp_error'] ?? '';
    unset($_SESSION['otp_error']);

    // MASK BACKUP EMAIL
    $maskedEmail = $displayUser['backup_email'] ?? '';

    if (strpos($maskedEmail, '@') !== false) {

        [$emailName, $emailDomain] = explode('@', $maskedEmail, 2);

        if (strlen($emailName) > 2) {
            $maskedEmail = substr($emailName, 0, 2) .
                str_repeat('*', max(2, strlen($emailName) - 2)) .
                '@' . $emailDomain;
        }
    }

    // CALCULATE ACTUAL OTP REMAINING TIME
    $remainingSeconds = 0;

    if (!empty($displayUser['otp_expires_at'])) {
        $remainingSeconds = max(
            0,
            strtotime($displayUser['otp_expires_at']) - time()
        );
    }

} catch (Throwable $e) {

    error_log('OTP verification error: ' . $e->getMessage());

    $_SESSION['otp_error'] = 'A system error occurred. Please try again.';

    header("Location: verify_otp.php");
    exit();
}
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

        .otp-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

        .otp-actions {
            text-align: center;
            margin-top: 15px;
        }

        .otp-actions button {
            border: none;
            background: none;
            color: #0e8708;
            cursor: pointer;
            font-size: 14px;
        }

        .otp-actions button:hover {
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

            <a href="index.php" class="back-button">
                <i class="fa-solid fa-arrow-left"></i>
            </a>

            <h1>Verify OTP</h1>

            <div class="otp-info">
                <p>We sent a 6-digit verification code to:</p>

                <div class="otp-email">
                    <?= htmlspecialchars($maskedEmail, ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="<?= str_contains($error, 'sent') ? 'otp-success' : 'otp-error' ?>">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="verify_otp.php" method="post">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                >

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

                <button type="submit" name="verify_otp">
                    Verify OTP
                </button>

            </form>

            <div class="timer">
                OTP expires in <span id="timer">05:00</span>
            </div>

            <div class="otp-actions">
                <form action="resend_otp.php" method="post">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <button type="submit" name="resend_otp">
                        Resend OTP
                    </button>

                </form>
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

        document.getElementById("current-year").textContent =
            new Date().getFullYear();
    </script>

</body>
</html>