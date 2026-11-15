<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../index.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../database.php';
require_once __DIR__ . "/../notification/notification_helper.php";
$uploadDir = "../uploads/newsletter/";
$uploadPath = __DIR__ . "/../uploads/newsletter/";

if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$csrfToken = $_SESSION['csrf_token'];
$userId = (int) $_SESSION['user_id'];

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function setMessage($type, $message) {
    $_SESSION['newsletter_message'] = ['type' => $type, 'message' => $message];
}

function redirectPage() {
    header("Location: a_newsletter.php");
    exit();
}

function validateNewsletterRequest($csrfToken) {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        throw new Exception("Invalid request. Please refresh the page.");
    }
}

function deleteNewsletterImage($filename, $uploadPath) {
    $filename = basename((string) $filename);
    if ($filename && is_file($uploadPath . $filename)) {
        unlink($uploadPath . $filename);
    }
}

function uploadNewsletterImage($file, $uploadPath) {
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
            ? "Image exceeds the allowed upload size of 50 MB."
            : "Image upload failed.";
        throw new Exception($message);
    }

    if ($file['size'] > 50 * 1024 * 1024) {
        throw new Exception("Image must not exceed 50 MB.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedTypes[$mimeType])) {
        throw new Exception("Only JPG, PNG, and WEBP images are allowed.");
    }

    $filename = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mimeType];

    if (!move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
        throw new Exception("Unable to save the uploaded image.");
    }

    return [
        'file_name' => basename($file['name']),
        'file_path' => $filename,
        'file_type' => $mimeType,
        'file_size' => (int) $file['size']
    ];
}


/* ADD NEWSLETTER */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_newsletter'])) {
    $image = null;

    try {
        validateNewsletterRequest($csrfToken);

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $date = $_POST['newsletter_date'] ?? '';

        if ($title === '') {
            throw new Exception("Please enter a newsletter title.");
        }

        if (mb_strlen($title) > 255) {
            throw new Exception("Newsletter title must not exceed 255 characters.");
        }

        $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

        if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
            throw new Exception("Please select a valid publication date.");
        }

        if ($link !== '') {
            $scheme = strtolower(parse_url($link, PHP_URL_SCHEME) ?? '');

            if (!filter_var($link, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                throw new Exception("Please enter a valid external link.");
            }
        }

        $image = uploadNewsletterImage($_FILES['newsletter_image'] ?? null, $uploadPath);

        if (!$image) {
            throw new Exception("Please select a newsletter image.");
        }

        $stmt = $conn->prepare("
            INSERT INTO newsletter
            (file_name, title, description, link, newsletter_date, file_path, file_type, file_size, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssssssii",
            $image['file_name'],
            $title,
            $description,
            $link,
            $date,
            $image['file_path'],
            $image['file_type'],
            $image['file_size'],
            $userId
        );

        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception("Unable to publish newsletter.");
        }

        $stmt->close();

        // Notify all registered users
        $notificationSent = notifyAllUsersAboutNewsletter(
            $conn,
            $userId,
            $title
        );

        if (!$notificationSent) {
            error_log("Failed to send newsletter notifications: " . $title);
        }

        setMessage('success', 'Newsletter published successfully.');

    } catch (Exception $e) {
        if ($image) {
            deleteNewsletterImage($image['file_path'], $uploadPath);
        }

        setMessage('danger', $e->getMessage());
    }

    redirectPage();
}


/* UPDATE NEWSLETTER */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_newsletter'])) {
    try {
        validateNewsletterRequest($csrfToken);

        $id = (int) ($_POST['newsletter_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $date = $_POST['newsletter_date'] ?? '';

        if ($id <= 0) throw new Exception("Invalid newsletter.");
        if ($title === '') throw new Exception("Please enter a newsletter title.");
        if (mb_strlen($title) > 255) throw new Exception("Newsletter title must not exceed 255 characters.");

        $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
        if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
            throw new Exception("Please select a valid publication date.");
        }

        if ($link !== '') {
            $scheme = strtolower(parse_url($link, PHP_URL_SCHEME) ?? '');
            if (!filter_var($link, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                throw new Exception("Please enter a valid external link.");
            }
        }

        $check = $conn->prepare("SELECT file_path FROM newsletter WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$existing) throw new Exception("Newsletter not found.");

        $newImage = uploadNewsletterImage($_FILES['newsletter_image'] ?? null, $uploadPath);

        if ($newImage) {
            $stmt = $conn->prepare("
                UPDATE newsletter
                SET file_name = ?, title = ?, description = ?, link = ?, newsletter_date = ?,
                    file_path = ?, file_type = ?, file_size = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "sssssssii",
                $newImage['file_name'],
                $title,
                $description,
                $link,
                $date,
                $newImage['file_path'],
                $newImage['file_type'],
                $newImage['file_size'],
                $id
            );
        } else {
            $stmt = $conn->prepare("
                UPDATE newsletter
                SET title = ?, description = ?, link = ?, newsletter_date = ?
                WHERE id = ?
            ");

            $stmt->bind_param("ssssi", $title, $description, $link, $date, $id);
        }

        $stmt->execute();
        $stmt->close();

        if ($newImage) {
            deleteNewsletterImage($existing['file_path'], $uploadPath);
        }

        setMessage('success', 'Newsletter updated successfully.');

    } catch (Exception $e) {
        if (isset($newImage) && $newImage) {
            deleteNewsletterImage($newImage['file_path'], $uploadPath);
        }
        setMessage('danger', $e->getMessage());
    }

    redirectPage();
}

/* DELETE NEWSLETTER */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_newsletter'])) {
    try {
        validateNewsletterRequest($csrfToken);

        $id = (int) ($_POST['newsletter_id'] ?? 0);
        if ($id <= 0) throw new Exception("Invalid newsletter.");

        $stmt = $conn->prepare("SELECT file_path FROM newsletter WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $newsletter = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$newsletter) throw new Exception("Newsletter not found.");

        $stmt = $conn->prepare("DELETE FROM newsletter WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        deleteNewsletterImage($newsletter['file_path'], $uploadPath);

        setMessage('success', 'Newsletter deleted successfully.');

    } catch (Exception $e) {
        setMessage('danger', $e->getMessage());
    }

    redirectPage();
}

/* SEARCH AND SORT */
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

$orderBy = $sort === 'oldest'
    ? 'newsletter_date ASC, id ASC'
    : 'newsletter_date DESC, id DESC';

$searchTerm = "%$search%";

$stmt = $conn->prepare("
    SELECT * FROM newsletter
    WHERE title LIKE ? OR file_name LIKE ? OR description LIKE ?
    ORDER BY $orderBy
");

$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();

$result = $stmt->get_result();
$newsletters = [];

while ($row = $result->fetch_assoc()) {
    $newsletters[] = $row;
}

$stmt->close();

/* CALENDAR DATA */
$calendarResult = $conn->query("
    SELECT id, title, description, newsletter_date
    FROM newsletter
    WHERE newsletter_date IS NOT NULL
    ORDER BY newsletter_date ASC
");

$calendarEvents = [];

while ($row = $calendarResult->fetch_assoc()) {
    $calendarEvents[] = [
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'date' => $row['newsletter_date']
    ];
}

$calendarJson = json_encode(
    $calendarEvents,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

if ($calendarJson === false) $calendarJson = '[]';

$message = $_SESSION['newsletter_message'] ?? null;
unset($_SESSION['newsletter_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Management</title>
    
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../CSS/a_schedule.css">
    <link rel="stylesheet" href="../CSS/newsletter.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js" defer></script>
    <script src="../JS/newsletter.js" defer></script>
</head>

<body>
    <div class="flex-grow-1 min-vh-100">
        <!-- TOP NAVIGATION -->
        <div class="d-flex align-items-center justify-content-end gap-3 p-3 border-bottom">
            <nav>
                <div class="events-nav">
                    <a href="a_updated.php">
                        <i class="fa-solid fa-angle-left left"></i>
                    </a>
                </div>
            </nav>

            <!-- NOTIFICATIONS -->
            <div class="dropdown notification-icon">
                <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-bell"></i>
                    <span id="notificationCount" class="count-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">0</span>
                </button>

                <div class="notification-panel dropdown-menu dropdown-menu-end shadow p-2" style="width:350px;max-height:450px;overflow-y:auto;">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <strong>Notifications</strong>
                        <span id="notificationTotal" class="text-muted small">0</span>
                    </div>

                    <div id="notificationList">
                        <div class="text-center text-muted p-4">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                            <div>Loading notifications...</div>
                        </div>
                    </div>

                    <div class="border-top p-2 text-center">
                        <button type="button" id="markAllRead" class="btn btn-link text-success text-decoration-none">
                            Mark all as read
                        </button>
                    </div>
                </div>
            </div>

            <!-- ACCOUNT -->
            <div class="dropdown-acc">
                <button class="btn btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-circle-user"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <div class="dropdown-item">
                            <img src="../images/user1.png" class="user-profile" alt="User profile">
                            <?= escape($_SESSION['user_name'] ?? 'Admin') ?>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="a_us.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
                    <li><a class="dropdown-item" href="a_aboutus.php"><i class="fa-solid fa-circle-info"></i> About</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="../logout.php">
                            <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <section class="dashboard-section">
            <div class="container-fluid px-3">
                <div class="dashboard-grid">
                    <div class="container-fluid p-0">

                        <!-- PAGE NAVIGATION -->
                        <div class="dashboard-overview d-grid gap-1 d-md-flex justify-content-md-end">
                            <a class="btn btn-welcome" href="a_updated.php">
                                <i class="fa-solid fa-handshake"></i> Dashboard
                            </a>

                            <a class="btn btn-announcement" href="a_announcement.php">
                                <i class="fa-solid fa-bullhorn"></i> Announcement
                            </a>

                            <a class="btn btn-events" href="a_events.php">
                                <i class="fa-solid fa-calendar-week"></i> Events
                            </a>

                            <a class="btn btn-newsletter active" href="a_newsletter.php">
                                <i class="fa-solid fa-envelopes-bulk"></i> Newsletter
                            </a>
                        </div>

                        <!-- NEWSLETTER MANAGEMENT -->
                        <div class="newsletter-admin">
                            <div class="container-fluid">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                                    <div>
                                        <h1 class="page-heading mb-1">Newsletter Management</h1>
                                        <p class="page-subtitle mb-0">Manage and publish school newsletters.</p>
                                    </div>
                                    <button type="button" class="btn btn-primary-custom rounded-pill px-4" onclick="prepareAddModal()">
                                        <i class="fa-solid fa-plus me-2"></i> Publish Newsletter
                                    </button>
                                </div>

                                <?php if ($message): ?>
                                    <div class="alert alert-<?= escape($message['type']) ?> alert-dismissible fade show" role="alert">
                                        <?= escape($message['message']) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <div class="row g-4">
                                    <!-- NEWSLETTER LIST -->
                                    <div class="col-lg-8 col-xl-9">
                                        <div class="card admin-card">
                                            <div class="card-body">
                                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                                                    <div>
                                                        <h5 class="fw-bold mb-1">Published Newsletters</h5>
                                                        <small class="text-muted"><?= count($newsletters) ?> newsletter(s) found</small>
                                                    </div>
                                                    <form method="GET" class="d-flex flex-wrap gap-2">
                                                        <input type="search" name="search" class="form-control" placeholder="Search newsletter..." value="<?= escape($search) ?>" style="width:190px;">
                                                        <select name="sort" class="form-select" style="width:125px;">
                                                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                                                            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                                                        </select>
                                                        <button type="submit" class="btn btn-primary-custom">
                                                            <i class="fa-solid fa-search"></i>
                                                        </button>
                                                    </form>
                                                </div>

                                                <hr>

                                                <?php if (empty($newsletters)): ?>
                                                    <div class="text-center py-5">
                                                        <i class="fa-solid fa-newspaper fa-3x text-secondary mb-3"></i>
                                                        <h6 class="fw-bold">No newsletters found</h6>
                                                        <p class="text-muted small mb-0">Publish a newsletter to display it here.</p>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="row g-3">
                                                        <?php foreach ($newsletters as $newsletter):
                                                            $imageName = basename($newsletter['file_path']);
                                                            $imageUrl = $uploadDir . rawurlencode($imageName);
                                                        ?>
                                                            <div class="col-md-6 col-xl-4">
                                                                <div class="newsletter-card">
                                                                    <img src="<?= escape($imageUrl) ?>" alt="<?= escape($newsletter['title']) ?>" onerror="this.style.display='none';">
                                                                    <div class="card-body p-3">
                                                                        <h6 class="card-title mb-2"><?= escape($newsletter['title']) ?></h6>
                                                                        <p class="newsletter-date mb-2">
                                                                            <i class="fa-regular fa-calendar me-1"></i>
                                                                            <?= $newsletter['newsletter_date'] ? date('F d, Y', strtotime($newsletter['newsletter_date'])) : 'No publication date' ?>
                                                                        </p>
                                                                        <p class="card-text"><?= escape($newsletter['description'] ?: 'No description provided.') ?></p>

                                                                        <?php if (!empty($newsletter['link'])): ?>
                                                                            <a href="<?= escape($newsletter['link']) ?>" target="_blank" rel="noopener noreferrer" class="small text-success text-decoration-none">
                                                                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open external link
                                                                            </a>
                                                                        <?php endif; ?>

                                                                        <hr>
                                                                        <div class="d-flex justify-content-between gap-2">
                                                                            <button type="button" class="btn btn-sm btn-outline-success flex-fill"
                                                                                onclick='prepareEditModal(<?= json_encode([
                                                                                    "id" => $newsletter["id"],
                                                                                    "title" => $newsletter["title"],
                                                                                    "description" => $newsletter["description"],
                                                                                    "link" => $newsletter["link"],
                                                                                    "date" => $newsletter["newsletter_date"],
                                                                                    "image" => $imageUrl
                                                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                                                            </button>
                                                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this newsletter?');">
                                                                                <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                                                                                <input type="hidden" name="newsletter_id" value="<?= (int) $newsletter['id'] ?>">
                                                                                <button type="submit" name="delete_newsletter" class="btn btn-sm btn-outline-danger">
                                                                                    <i class="fa-solid fa-trash"></i>
                                                                                </button>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- NEWSLETTER CALENDAR -->
                                    <div class="col-lg-4 col-xl-3">
                                        <div class="card admin-card calendar-card">
                                            <div class="card-body">
                                                <div class="calendar-header mb-3">
                                                    <button type="button" onclick="changeMonth(-1)">
                                                        <i class="fa-solid fa-chevron-left"></i>
                                                    </button>

                                                    <div class="text-center">
                                                        <h6 class="fw-bold mb-0" id="calendarMonth"></h6>
                                                        <small class="text-muted" id="calendarYear"></small>
                                                    </div>

                                                    <button type="button" onclick="changeMonth(1)">
                                                        <i class="fa-solid fa-chevron-right"></i>
                                                    </button>
                                                </div>

                                                <div class="calendar-weekdays">
                                                    <div>Sun</div><div>Mon</div><div>Tue</div>
                                                    <div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                                                </div>

                                                <div class="calendar-days" id="calendarDays"></div>

                                                <hr>

                                                <h6 class="fw-bold small mb-3" id="selectedDateTitle">
                                                    Select a date
                                                </h6>

                                                <div id="calendarEventList">
                                                    <p class="text-muted small mb-0">
                                                        Select a highlighted date to view its events.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- ADD / EDIT NEWSLETTER MODAL -->
    <div class="modal fade" id="newsletterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="newsletterForm">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="newsletterModalTitle">Publish Newsletter</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                        <input type="hidden" name="newsletter_id" id="newsletterId">

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                Newsletter Image <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="newsletter_image" id="newsletterImage" class="form-control"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <small class="text-muted">JPG, PNG, or WEBP. Maximum 50 MB. Leave empty when editing to keep the current image.</small>
                            <small id="imageError" class="text-danger d-block mt-1"></small>
                            <img src="" id="imagePreview" class="image-preview" alt="Image preview" style="display:none;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                Newsletter Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" id="newsletterTitle" class="form-control"
                                placeholder="Enter newsletter title..." maxlength="255" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Description</label>
                            <textarea name="description" id="newsletterDescription" class="form-control" rows="4" placeholder="Enter newsletter description..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                Publication Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="newsletter_date" id="newsletterDate" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                External Link <span class="text-muted">(Optional)</span>
                            </label>
                            <input type="url" name="link" id="newsletterLink" class="form-control" placeholder="https://example.com">
                            <small class="text-muted">Add a link where visitors can read more.</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_newsletter" id="newsletterSubmit" class="btn btn-primary-custom rounded-pill px-4">
                            <i class="fa-solid fa-upload me-1"></i> Publish
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.calendarEvents = <?= $calendarJson ?>;
    </script>
</body>
</html>
