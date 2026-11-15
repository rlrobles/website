<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../db.php';

$error = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    if ($new_password === "" || $confirm_password === "") {
        $error = "Please fill in all fields.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $_SESSION["user_id"]);

        if ($stmt->execute()) {
            header("Location: admin/a_updated.php");
            exit();
        } else {
            $error = "Failed to change password.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow p-4" style="max-width: 450px; width: 100%;">
            <h3 class="mb-2">Change Your Password</h3>
            <p class="text-muted">For security, you must create a new password before continuing.</p>

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" minlength="8" required>
                    <small class="text-muted">Password must be at least 8 characters.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                </div>

                <button type="submit" class="btn btn-success w-100">
                    Change Password
                </button>
            </form>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>