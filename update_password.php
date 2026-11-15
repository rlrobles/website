<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "database.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "phpmailer/src/Exception.php";
require "phpmailer/src/PHPMailer.php";
require "phpmailer/src/SMTP.php";

if (!isset($_POST['update_password'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    die("Please fill in all password fields.");
}

if (strlen($newPassword) < 8) {
    die("New password must be at least 8 characters.");
}

if ($newPassword !== $confirmPassword) {
    die("New password and confirm password do not match.");
}

$stmt = $conn->prepare("
    SELECT first_name, last_name, password, backup_email
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("User account not found.");
}

$user = $result->fetch_assoc();

if (!password_verify($currentPassword, $user['password'])) {
    die("Current password is incorrect.");
}

if (empty($user['backup_email']) || !filter_var($user['backup_email'], FILTER_VALIDATE_EMAIL)) {
    die("No valid backup email is configured for this account.");
}

$otp = random_int(100000, 999999);
$otpHash = password_hash((string)$otp, PASSWORD_DEFAULT);
$expires = date("Y-m-d H:i:s", time() + 300);

$stmt = $conn->prepare("
    UPDATE users
    SET otp_code = ?,
        otp_expires_at = ?,
        otp_attempts = 0
    WHERE id = ?
");
$stmt->bind_param("ssi", $otpHash, $expires, $userId);

if (!$stmt->execute()) {
    die("Failed to create verification code.");
}

$_SESSION['password_change_user_id'] = $userId;
$_SESSION['password_change_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'rubiusloren@gmail.com';
    $mail->Password = 'tavenkdmkyxnevwd';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom('rubiusloren@gmail.com', 'ACES SBM');
    $mail->addAddress(
        $user['backup_email'],
        $user['first_name'] . ' ' . $user['last_name']
    );

    $mail->isHTML(true);
    $mail->Subject = 'ACES SBM Password Change Verification';

    $mail->Body = '
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;">
            <h2>ACES SBM Password Change</h2>
            <p>Hello ' . htmlspecialchars($user['first_name']) . ',</p>
            <p>A request was made to change the password of your ACES SBM account.</p>
            <p>Your One-Time Password is:</p>
            <h1 style="font-size:32px;letter-spacing:8px;">' . $otp . '</h1>
            <p>This verification code will expire in <strong>5 minutes</strong>.</p>
            <p>If you did not request a password change, you can safely ignore this email.</p>
            <hr>
            <small>ACES School Based Management System</small>
        </div>
    ';

    $mail->AltBody =
        "ACES SBM Password Change\n\n" .
        "Hello " . $user['first_name'] . ",\n\n" .
        "Your One-Time Password is: " . $otp . "\n\n" .
        "This verification code will expire in 5 minutes.\n\n" .
        "If you did not request a password change, you can safely ignore this email.";

    $mail->send();

    header("Location: verify_password_otp.php");
    exit();

} catch (Exception $e) {
    $stmt = $conn->prepare("
        UPDATE users
        SET otp_code = NULL,
            otp_expires_at = NULL,
            otp_attempts = 0
        WHERE id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    unset($_SESSION['password_change_user_id']);
    unset($_SESSION['password_change_hash']);

    die("Email error: " . htmlspecialchars($mail->ErrorInfo));
}
?>