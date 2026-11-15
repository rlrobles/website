<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (
    !isset($_SESSION['password_change_user_id']) ||
    !isset($_SESSION['password_change_hash'])
) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin/a_us.php");
    } else {
        header("Location: employee/staff_us.php");
    }
    exit();
}

require_once "database.php";

$userId = $_SESSION['password_change_user_id'];
$error = '';

$stmt = $conn->prepare("
    SELECT otp_code, otp_expires_at, otp_attempts, backup_email
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');

    if (!preg_match('/^\d{6}$/', $otp)) {
        $error = "Please enter the 6-digit OTP.";
    } elseif (empty($user['otp_code'])) {
        $error = "No OTP is available. Please request a new one.";
    } elseif (
        empty($user['otp_expires_at']) ||
        strtotime($user['otp_expires_at']) < time()
    ) {
        $error = "This OTP has expired. Please start the password change again.";
    } elseif ((int)$user['otp_attempts'] >= 5) {
        $error = "Too many incorrect attempts. Please start the password change again.";
    } elseif (!password_verify($otp, $user['otp_code'])) {
        $newAttempts = (int)$user['otp_attempts'] + 1;

        $stmt = $conn->prepare("
            UPDATE users
            SET otp_attempts = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $newAttempts, $userId);
        $stmt->execute();

        $error = "Incorrect OTP.";
    } else {
        $newPasswordHash = $_SESSION['password_change_hash'];

        $stmt = $conn->prepare("
            UPDATE users
            SET password = ?,
                otp_code = NULL,
                otp_expires_at = NULL,
                otp_attempts = 0,
                must_change_password = 0
            WHERE id = ?
        ");
        $stmt->bind_param("si", $newPasswordHash, $userId);

        if (!$stmt->execute()) {
            die("Failed to update password.");
        }

        unset($_SESSION['password_change_user_id']);
        unset($_SESSION['password_change_hash']);

        $_SESSION['password_success'] =
            "Your password has been updated successfully.";

        if ($_SESSION['role'] === 'admin') {
            header("Location: admin/a_us.php");
        } else {
            header("Location: employee/staff_us.php");
        }

        exit();
    }
}

function maskEmail($email) {
    if (
        empty($email) ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        return "your backup email";
    }

    [$name, $domain] = explode('@', $email, 2);

    if (strlen($name) <= 2) {
        $maskedName = substr($name, 0, 1) . '***';
    } else {
        $maskedName = substr($name, 0, 2) . '***';
    }

    return $maskedName . '@' . $domain;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Password Change</title>
    <link rel="icon" type="image/png" href="images/logo.png">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body>

<div class="container d-flex justify-content-center align-items-center min-vh-100">

    <div
        class="bg-white border rounded p-4 shadow-sm"
        style="width:100%;max-width:450px;"
    >

        <div class="text-center mb-4">

            <i class="fa-solid fa-shield-halved fs-1 text-success mb-3"></i>

            <h4>Verify Password Change</h4>

            <p class="text-muted mb-1">
                Enter the 6-digit verification code sent to:
            </p>

            <strong>
                <?php echo htmlspecialchars(maskEmail($user['backup_email'])); ?>
            </strong>

        </div>

        <?php if (!empty($error)): ?>

            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">

                <label for="otp" class="form-label">
                    One-Time Password
                </label>

                <input
                    type="text"
                    name="otp"
                    id="otp"
                    class="form-control text-center"
                    maxlength="6"
                    minlength="6"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    placeholder="Enter 6-digit OTP"
                    required
                >

            </div>

            <button
                type="submit"
                class="btn btn-success w-100"
            >
                Verify
            </button>

        </form>

        <div class="text-center mt-3">

            <?php if ($_SESSION['role'] === 'admin'): ?>

                <a href="admin/a_us.php" class="text-decoration-none">
                    Cancel
                </a>

            <?php else: ?>

                <a href="employee/staff_us.php" class="text-decoration-none">
                    Cancel
                </a>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>
</html>