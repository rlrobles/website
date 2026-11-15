<?php
session_start();

require_once __DIR__ . '/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

function redirectWithError($page, $message)
{
    $_SESSION['error'] = $message;
    header("Location: $page");
    exit();
}

$loginPage = 'login.php';

// CHECK REQUEST METHOD AND CSRF TOKEN
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['login']) ||
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    redirectWithError($loginPage, 'Invalid request. Please try again.');
}

// GET LOGIN INPUT
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    redirectWithError($loginPage, 'Please enter your email and password.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithError($loginPage, 'Please enter a valid email address.');
}

// GET USER ACCOUNT
try {

    $stmt = $conn->prepare("
        SELECT id, email, password, backup_email, role, first_name,
               failed_attempts, locked_until
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->bind_param('s', $email);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

} catch (Throwable $e) {

    error_log('Login query error: ' . $e->getMessage());

    redirectWithError(
        $loginPage,
        'A system error occurred. Please try again.'
    );
}

if (!$user) {
    redirectWithError($loginPage, 'Incorrect email or password.');
}

$userId = (int) $user['id'];

// CHECK ACCOUNT LOCKOUT
if (!empty($user['locked_until'])) {

    $lockedUntil = strtotime($user['locked_until']);

    if ($lockedUntil !== false && $lockedUntil > time()) {

        $remainingMinutes = ceil(($lockedUntil - time()) / 60);

        redirectWithError(
            $loginPage,
            'Too many failed login attempts. Please try again in ' .
            $remainingMinutes . ' minute(s).'
        );
    }

    // CLEAR EXPIRED LOCKOUT
    $clearLock = $conn->prepare("
        UPDATE users
        SET failed_attempts = 0,
            locked_until = NULL
        WHERE id = ?
    ");

    $clearLock->bind_param('i', $userId);
    $clearLock->execute();
    $clearLock->close();

    $user['failed_attempts'] = 0;
}

// VERIFY PASSWORD
if (!password_verify($password, $user['password'])) {

    $attempts = (int) $user['failed_attempts'] + 1;

    if ($attempts >= 5) {

        $lock = $conn->prepare("
            UPDATE users
            SET failed_attempts = 5,
                locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE)
            WHERE id = ?
        ");

        $lock->bind_param('i', $userId);
        $lock->execute();
        $lock->close();

        redirectWithError(
            $loginPage,
            'Too many failed login attempts. Your account is locked for 5 minutes.'
        );
    }

    $update = $conn->prepare("
        UPDATE users
        SET failed_attempts = ?
        WHERE id = ?
    ");

    $update->bind_param('ii', $attempts, $userId);
    $update->execute();
    $update->close();

    $remaining = 5 - $attempts;

    redirectWithError(
        $loginPage,
        "Incorrect email or password. You have $remaining attempt(s) remaining."
    );
}

// CHECK ACCOUNT ROLE
if (!in_array($user['role'], ['admin', 'teaching', 'non-teaching'], true)) {

    redirectWithError(
        $loginPage,
        'Invalid account role. Please contact the administrator.'
    );
}

// CHECK BACKUP EMAIL
$backupEmail = trim($user['backup_email'] ?? '');

if (
    $backupEmail === '' ||
    !filter_var($backupEmail, FILTER_VALIDATE_EMAIL)
) {
    redirectWithError(
        $loginPage,
        'Your backup email is missing or invalid. Please contact the administrator.'
    );
}

// CHECK OTP RESEND COOLDOWN
$cooldown = $conn->prepare("
    SELECT
        CASE
            WHEN otp_last_sent_at IS NOT NULL
            THEN TIMESTAMPDIFF(SECOND, otp_last_sent_at, NOW())
            ELSE 60
        END AS elapsed_seconds
    FROM users
    WHERE id = ?
");

$cooldown->bind_param('i', $userId);
$cooldown->execute();

$cooldownData = $cooldown->get_result()->fetch_assoc();
$cooldown->close();

$elapsed = (int) ($cooldownData['elapsed_seconds'] ?? 60);

if ($elapsed < 60) {

    redirectWithError(
        $loginPage,
        'Please wait ' . (60 - $elapsed) .
        ' seconds before requesting another OTP.'
    );
}

// GENERATE OTP
$otp = (string) random_int(100000, 999999);
$hashedOtp = password_hash($otp, PASSWORD_DEFAULT);
$otpExpiration = date('Y-m-d H:i:s', time() + 300);

try {

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'rubiusloren@gmail.com';
    $mail->Password = 'tavenkdmkyxnevwd';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom('rubiusloren@gmail.com', 'ACES SBM');
    $mail->addAddress($backupEmail);

    $mail->isHTML(true);
    $mail->Subject = 'ACES SBM Login Verification Code';

    $mail->Body = '
        <div style="font-family:Arial,sans-serif;max-width:500px;margin:auto;padding:25px;border:1px solid #ddd;border-radius:10px;">
            <h2>ACES SBM Login Verification</h2>
            <p>Hello,</p>
            <p>Your one-time password (OTP) for logging in to ACES SBM is:</p>
            <div style="font-size:32px;font-weight:bold;letter-spacing:8px;text-align:center;padding:20px 0;">
                ' . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . '
            </div>
            <p>This OTP will expire in <strong>5 minutes</strong>.</p>
            <p>If you did not attempt to log in, you can safely ignore this email.</p>
            <hr>
            <p style="font-size:12px;color:#777;">
                Alangilan Central Elementary School<br>
                ACES School Based Management System
            </p>
        </div>
    ';

    $mail->AltBody = "Your ACES SBM OTP is: $otp\nExpires in 5 minutes.";

    $mail->send();

    // SAVE OTP AFTER EMAIL DELIVERY
    $update = $conn->prepare("
        UPDATE users
        SET otp_code = ?,
            otp_expires_at = ?,
            otp_attempts = 0,
            otp_locked_until = NULL,
            otp_last_sent_at = NOW(),
            failed_attempts = 0,
            locked_until = NULL
        WHERE id = ?
    ");

    $update->bind_param('ssi', $hashedOtp, $otpExpiration, $userId);
    $update->execute();
    $update->close();

    // REGENERATE SESSION ID
    session_regenerate_id(true);

    // CLEAR PREVIOUS AUTHENTICATION DATA
    unset(
        $_SESSION['user_id'],
        $_SESSION['user_name'],
        $_SESSION['user_email'],
        $_SESSION['role'],
        $_SESSION['otp_user_id'],
        $_SESSION['otp_error']
    );

    // SET OTP SESSION
    $_SESSION['otp_user_id'] = $userId;
    $_SESSION['otp_sent_at'] = time();

    // ROTATE CSRF TOKEN
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: verify_otp.php');
    exit();

} catch (Throwable $e) {

    error_log('OTP email sending failed: ' . $e->getMessage());

    redirectWithError(
        $loginPage,
        'Unable to send the verification code. Please try again later.'
    );
}
?>
