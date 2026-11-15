<?php
session_start();
require_once 'database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'phpmailer/src/Exception.php';
require_once 'phpmailer/src/PHPMailer.php';
require_once 'phpmailer/src/SMTP.php';

if (!isset($_SESSION['otp_user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['otp_user_id'];

function otpRedirect($message)
{
    $_SESSION['otp_error'] = $message;
    header("Location: verify_otp.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT id, first_name, last_name, backup_email,
           otp_attempts, otp_locked_until, otp_last_sent_at
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

// CHECK OTP COOLDOWN
if (
    !empty($user['otp_locked_until']) &&
    strtotime($user['otp_locked_until']) > time()
) {
    $remaining = strtotime($user['otp_locked_until']) - time();

    otpRedirect(
        "Too many incorrect OTP attempts. Please wait " .
        ceil($remaining / 60) . " minute(s) before requesting another OTP."
    );
}

// CHECK RESEND COOLDOWN
if (!empty($user['otp_last_sent_at'])) {
    $elapsed = time() - strtotime($user['otp_last_sent_at']);

    if ($elapsed < 60) {
        otpRedirect(
            "Please wait " . (60 - $elapsed) .
            " seconds before requesting another OTP."
        );
    }
}

// CHECK BACKUP EMAIL
if (
    empty($user['backup_email']) ||
    !filter_var($user['backup_email'], FILTER_VALIDATE_EMAIL)
) {
    otpRedirect("Your backup email is missing or invalid.");
}

// GENERATE OTP
$otp = (string) random_int(100000, 999999);
$otpHash = password_hash($otp, PASSWORD_DEFAULT);
$expires = date("Y-m-d H:i:s", time() + 300);

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
    $mail->Subject = 'ACES SBM New Login Verification Code';

    $mail->Body = '
        <div style="font-family:Arial,sans-serif;max-width:500px;margin:auto;padding:25px;border:1px solid #ddd;border-radius:10px;">
            <h2>ACES SBM Login Verification</h2>
            <p>Hello ' . htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') . ',</p>
            <p>Your new verification code is:</p>
            <div style="font-size:32px;font-weight:bold;letter-spacing:8px;text-align:center;padding:20px 0;">
                ' . $otp . '
            </div>
            <p>This OTP expires in <strong>5 minutes</strong>.</p>
            <p>If you did not attempt to log in, ignore this email.</p>
        </div>
    ';

    $mail->AltBody =
        "Your ACES SBM verification code is: " . $otp .
        "\nThis OTP expires in 5 minutes.";

    $mail->send();

    // SAVE NEW OTP AFTER SUCCESSFUL EMAIL DELIVERY
    $update = $conn->prepare("
        UPDATE users
        SET otp_code = ?,
            otp_expires_at = ?,
            otp_attempts = 0,
            otp_locked_until = NULL,
            otp_last_sent_at = NOW()
        WHERE id = ?
    ");

    $update->bind_param("ssi", $otpHash, $expires, $user_id);
    $update->execute();
    $update->close();

    otpRedirect("A new OTP has been sent to your backup email.");

} catch (Exception $e) {
    error_log("OTP resend failed: " . $mail->ErrorInfo);
    otpRedirect("Unable to send OTP. Please try again later.");
}
?>

