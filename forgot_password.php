
<?php
session_start();
require_once 'database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'phpmailer/src/Exception.php';
require_once 'phpmailer/src/PHPMailer.php';
require_once 'phpmailer/src/SMTP.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if ($email === '') {
        $error = "Please enter your registered email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("
            SELECT id, first_name, last_name, email, backup_email
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        if (!$stmt) {
            error_log("Forgot password query failed: " . $conn->error);
            $error = "A system error occurred. Please try again.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user) {
                $error = "No account was found with that email address.";
            } elseif (
                empty($user['backup_email']) ||
                !filter_var($user['backup_email'], FILTER_VALIDATE_EMAIL)
            ) {
                $error = "Your backup email is missing or invalid. Please contact the administrator.";
            } else {
                $otp = (string) random_int(100000, 999999);
                $hashedOtp = password_hash($otp, PASSWORD_DEFAULT);
                $expiration = date('Y-m-d H:i:s', time() + 300);

                $update = $conn->prepare("
                    UPDATE users
                    SET reset_otp_code = ?,
                        reset_otp_expires_at = ?,
                        reset_otp_attempts = 0
                    WHERE id = ?
                ");

                if (!$update) {
                    error_log("Reset OTP update failed: " . $conn->error);
                    $error = "Unable to start password recovery. Please try again.";
                } else {
                    $update->bind_param(
                        "ssi",
                        $hashedOtp,
                        $expiration,
                        $user['id']
                    );

                    if (!$update->execute()) {
                        error_log("Reset OTP execution failed: " . $update->error);
                        $error = "Unable to start password recovery. Please try again.";
                    }

                    $update->close();

                    if ($error === '') {
                        $mail = new PHPMailer(true);

                        try {
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'rubiusloren@gmail.com';
                            $mail->Password = 'tavenkdmkyxnevwd';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                            $mail->Port = 465;

                            $mail->setFrom(
                                'rubiusloren@gmail.com',
                                'ACES SBM'
                            );

                            $mail->addAddress($user['backup_email']);

                            $mail->isHTML(true);
                            $mail->Subject = 'ACES SBM Password Reset Verification';

                            $mail->Body = '
                                <div style="font-family:Arial,sans-serif;max-width:500px;margin:auto;padding:25px;border:1px solid #ddd;border-radius:10px;">
                                    <h2>ACES SBM Password Reset</h2>
                                    <p>Hello ' .
                                    htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') .
                                    ',</p>

                                    <p>Your password reset verification code is:</p>

                                    <div style="font-size:32px;font-weight:bold;letter-spacing:8px;text-align:center;padding:20px 0;">
                                        ' . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . '
                                    </div>

                                    <p>This OTP will expire in <strong>5 minutes</strong>.</p>

                                    <p>If you did not request a password reset, please ignore this email.</p>

                                    <hr>

                                    <p style="font-size:12px;color:#777;">
                                        Alangilan Central Elementary School<br>
                                        ACES School Based Management System
                                    </p>
                                </div>
                            ';

                            $mail->AltBody =
                                "Hello " . $user['first_name'] . ",\n\n" .
                                "Your ACES SBM password reset code is: " . $otp . "\n\n" .
                                "This OTP will expire in 5 minutes.";

                            $mail->send();

                            session_regenerate_id(true);

                            unset(
                                $_SESSION['reset_user_id'],
                                $_SESSION['reset_email']
                            );

                            $_SESSION['reset_otp_user_id'] = (int) $user['id'];

                            header("Location: verify_reset_otp.php");
                            exit();

                        } catch (Exception $e) {
                            error_log("Password reset email failed: " . $mail->ErrorInfo);

                            $clear = $conn->prepare("
                                UPDATE users
                                SET reset_otp_code = NULL,
                                    reset_otp_expires_at = NULL,
                                    reset_otp_attempts = 0
                                WHERE id = ?
                            ");

                            if ($clear) {
                                $clear->bind_param("i", $user['id']);
                                $clear->execute();
                                $clear->close();
                            }

                            $error = "Unable to send the verification code. Please try again later.";
                        }
                    }
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
    <link rel="icon" type="image/png" href="images/logo.png">
    <title>Forgot Password - ACES SBM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="CSS/login.css">
    <script defer src="JS/script.js"></script>

    <style>
        input[name="email"] {
            border: 1px solid #0e8708;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input[name="email"]:focus {
            border-color: #0e8708;
            box-shadow: 0 0 5px rgba(14, 135, 8, 0.4);
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <a href="login.php" class="back-button">
                <i class="fa-solid fa-arrow-left"></i>
            </a>

            <h1>Forgot Password?</h1>

            <p class="forgot-description">
                Enter your registered email address to recover your account.
                A verification code will be sent to your backup email.
            </p>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="post" action="forgot_password.php">
                <input
                    type="email"
                    name="email"
                    placeholder="Registered Email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                >

                <button type="submit">Continue</button>
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
</body>
</html>
