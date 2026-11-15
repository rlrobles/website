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
require_once __DIR__ . '/../database.php';
require_once __DIR__ . "/../notification/notification_helper.php";
$uploadDir = "../uploads/announcements/";
$uploadPath = __DIR__ . "/../uploads/announcements/";

if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$csrfToken = $_SESSION['csrf_token'];
$userId = (int) $_SESSION['user_id'];

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function setMessage($type, $message) {
    $_SESSION['announcement_message'] = ['type' => $type, 'message' => $message];
}

function redirectPage() {
    header("Location: a_announcement.php");
    exit();
}

function validateRequest($csrfToken) {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        throw new Exception("Invalid request. Please refresh the page.");
    }
}

function deleteAnnouncementImage($filename, $uploadPath) {
    $filename = basename((string) $filename);

    if ($filename && is_file($uploadPath . $filename)) {
        unlink($uploadPath . $filename);
    }
}

function uploadAnnouncementImage($file, $uploadPath) {
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Image upload failed.");
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("Image must not exceed 5 MB.");
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

function validateAnnouncementDate($date) {
    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

    if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
        throw new Exception("Please select a valid announcement date.");
    }
}

function validateAnnouncementLink($link) {
    if ($link !== '') {
        $scheme = strtolower(parse_url($link, PHP_URL_SCHEME) ?? '');

        if (!filter_var($link, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
            throw new Exception("Please enter a valid external link.");
        }
    }
}

 

/* UPDATE ANNOUNCEMENT */

/* ADD ANNOUNCEMENT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    try {
        validateRequest($csrfToken);

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $date = $_POST['announcement_date'] ?? '';

        if ($title === '') {
            throw new Exception("Please enter an announcement title.");
        }

        validateAnnouncementDate($date);
        validateAnnouncementLink($link);

        $image = uploadAnnouncementImage($_FILES['announcement_image'] ?? null, $uploadPath);

        if (!$image) {
            throw new Exception("Please select an announcement image.");
        }

        $stmt = $conn->prepare("
            INSERT INTO announcements
            (title, description, link, announcement_date, file_name, file_path, file_type, file_size, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssssssii",
            $title,
            $description,
            $link,
            $date,
            $image['file_name'],
            $image['file_path'],
            $image['file_type'],
            $image['file_size'],
            $userId
        );

        if (!$stmt->execute()) {
            $stmt->close();
            deleteAnnouncementImage($image['file_path'], $uploadPath);
            throw new Exception("Unable to publish announcement.");
        }

        $stmt->close();

        // Notify all registered users using their respective page links
        $notificationSent = notifyAllUsers(
            $conn,
            $userId,
            $title
        );

        if (!$notificationSent) {
            error_log("Failed to send announcement notifications: " . $title);
        }

        setMessage('success', 'Announcement published successfully.');

        } catch (mysqli_sql_exception $e) {
            error_log("Announcement database error: " . $e->getMessage());
            setMessage('danger', 'Something went wrong. Please try again.');
        } catch (Exception $e) {
            setMessage('danger', $e->getMessage());
        }

    redirectPage();
}


/* DELETE ANNOUNCEMENT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    try {
        validateRequest($csrfToken);

        $id = (int) ($_POST['announcement_id'] ?? 0);

        if ($id <= 0) throw new Exception("Invalid announcement.");

        $stmt = $conn->prepare("SELECT file_path FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $announcement = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$announcement) throw new Exception("Announcement not found.");

        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception("Unable to delete announcement.");
        }

        $stmt->close();
        deleteAnnouncementImage($announcement['file_path'], $uploadPath);

        setMessage('success', 'Announcement deleted successfully.');

    } catch (Exception $e) {
        setMessage('danger', $e->getMessage());
    }

    redirectPage();
}

/* SEARCH AND SORT */
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

$orderBy = $sort === 'oldest'
    ? 'announcement_date ASC, id ASC'
    : 'announcement_date DESC, id DESC';

$searchTerm = "%$search%";

$stmt = $conn->prepare("
    SELECT * FROM announcements
    WHERE title LIKE ? OR description LIKE ?
    ORDER BY $orderBy
");

$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();

$result = $stmt->get_result();
$announcements = [];

while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
}

$stmt->close();

/* CALENDAR DATA */
$calendarResult = $conn->query("
    SELECT id, title, description, announcement_date
    FROM announcements
    ORDER BY announcement_date ASC
");

$calendarEvents = [];

while ($row = $calendarResult->fetch_assoc()) {
    $calendarEvents[] = [
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'date' => $row['announcement_date']
    ];
}

$calendarJson = json_encode(
    $calendarEvents,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

if ($calendarJson === false) $calendarJson = '[]';

$message = $_SESSION['announcement_message'] ?? null;
unset($_SESSION['announcement_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcement Management - ACES SBM</title>

    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../CSS/a_schedule.css">
    <link rel="stylesheet" href="../CSS/announcement.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js" defer></script>
    <script src="../JS/announcement.js" defer></script>
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

                            <a class="btn btn-announcement active" href="a_announcement.php">
                                <i class="fa-solid fa-bullhorn"></i> Announcement
                            </a>

                            <a class="btn btn-events" href="a_events.php">
                                <i class="fa-solid fa-calendar-week"></i> Events
                            </a>

                            <a class="btn btn-newsletter" href="a_newsletter.php">
                                <i class="fa-solid fa-envelopes-bulk"></i> Newsletter
                            </a>
                        </div>

                        <!-- ANNOUNCEMENT MANAGEMENT -->
                        <div class="announcement-admin">
                            <div class="container-fluid">

                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                                    <div>
                                        <h1 class="page-heading mb-1">Announcement Management</h1>
                                        <p class="page-subtitle mb-0">Manage and publish school announcements.</p>
                                    </div>

                                    <button type="button" class="btn btn-primary-custom rounded-pill px-4" onclick="prepareAddModal()">
                                        <i class="fa-solid fa-plus me-2"></i> Publish Announcement
                                    </button>
                                </div>

                                <?php if ($message): ?>
                                    <div class="alert alert-<?= escape($message['type']) ?> alert-dismissible fade show" role="alert">
                                        <?= escape($message['message']) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <div class="row g-4">

                                    <!-- ANNOUNCEMENT LIST -->
                                    <div class="col-lg-8 col-xl-9">
                                        <div class="card admin-card">
                                            <div class="card-body">

                                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                                                    <div>
                                                        <h5 class="fw-bold mb-1">Published Announcements</h5>
                                                        <small class="text-muted"><?= count($announcements) ?> announcement(s) found</small>
                                                    </div>

                                                    <form method="GET" class="d-flex flex-wrap gap-2">
                                                        <input type="search" name="search" class="form-control" placeholder="Search announcement..." value="<?= escape($search) ?>" style="width:190px;">

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

                                                <?php if (empty($announcements)): ?>
                                                    <div class="text-center py-5">
                                                        <i class="fa-solid fa-bullhorn fa-3x text-secondary mb-3"></i>
                                                        <h6 class="fw-bold">No announcements found</h6>
                                                        <p class="text-muted small mb-0">Publish an announcement to display it here.</p>
                                                    </div>
                                                <?php else: ?>

                                                    <div class="row g-3">
                                                        <?php foreach ($announcements as $announcement):
                                                            $imageName = basename($announcement['file_path']);
                                                            $imageUrl = $uploadDir . rawurlencode($imageName);
                                                        ?>

                                                            <div class="col-md-6 col-xl-4">
                                                                <div class="announcement-card">

                                                                    <img src="<?= escape($imageUrl) ?>" alt="<?= escape($announcement['title']) ?>" onerror="this.style.display='none';">

                                                                    <div class="card-body p-3">
                                                                        <h6 class="card-title mb-2"><?= escape($announcement['title']) ?></h6>

                                                                        <p class="announcement-date mb-2">
                                                                            <i class="fa-regular fa-calendar me-1"></i>
                                                                            <?= date('F d, Y', strtotime($announcement['announcement_date'])) ?>
                                                                        </p>

                                                                        <p class="card-text"><?= escape($announcement['description'] ?: 'No description provided.') ?></p>

                                                                        <?php if (!empty($announcement['link'])): ?>
                                                                            <a href="<?= escape($announcement['link']) ?>" target="_blank" rel="noopener noreferrer" class="small text-success text-decoration-none">
                                                                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Read more
                                                                            </a>
                                                                        <?php endif; ?>

                                                                        <hr>

                                                                        <div class="d-flex justify-content-between gap-2">
                                                                            <button type="button" class="btn btn-sm btn-outline-success flex-fill"
                                                                                onclick='prepareEditModal(<?= json_encode([
                                                                                    "id" => $announcement["id"],
                                                                                    "title" => $announcement["title"],
                                                                                    "description" => $announcement["description"],
                                                                                    "link" => $announcement["link"],
                                                                                    "date" => $announcement["announcement_date"],
                                                                                    "image" => $imageUrl
                                                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                                                            </button>

                                                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                                                                <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                                                                                <input type="hidden" name="announcement_id" value="<?= (int) $announcement['id'] ?>">

                                                                                <button type="submit" name="delete_announcement" class="btn btn-sm btn-outline-danger">
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

                                    <!-- ANNOUNCEMENT CALENDAR -->
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
                                                    <div>Sun</div>
                                                    <div>Mon</div>
                                                    <div>Tue</div>
                                                    <div>Wed</div>
                                                    <div>Thu</div>
                                                    <div>Fri</div>
                                                    <div>Sat</div>
                                                </div>

                                                <div class="calendar-days" id="calendarDays"></div>

                                                <hr>

                                                <h6 class="fw-bold small mb-3" id="selectedDateTitle">Select a date</h6>

                                                <div id="calendarEventList">
                                                    <p class="text-muted small mb-0">Select a highlighted date to view its announcements.</p>
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

    <!-- ADD / EDIT ANNOUNCEMENT MODAL -->
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="announcementModalTitle">Publish Announcement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                        <input type="hidden" name="announcement_id" id="announcementId">

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                Announcement Image <span class="text-danger" id="imageRequired">*</span>
                            </label>
                            <input type="file" name="announcement_image" id="announcementImage" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                            <label class="form-label fw-semibold small">
                                Announcement Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" id="announcementTitle" class="form-control" maxlength="255" placeholder="Enter announcement title..." required>
                            <small class="text-muted">JPG, PNG, or WEBP. Maximum 5 MB. Leave empty when editing to keep the current image.</small>
                            <img src="" id="imagePreview" class="image-preview" alt="Image preview">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Description</label>
                            <textarea name="description" id="announcementDescription" class="form-control" rows="4" placeholder="Enter announcement details..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                Announcement Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="announcement_date" id="announcementDate" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                External Link <span class="text-muted">(Optional)</span>
                            </label>
                            <input type="url" name="link" id="announcementLink" class="form-control" placeholder="https://example.com">
                            <small class="text-muted">Add a link where visitors can read more.</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>

                        <button type="submit" name="add_announcement" id="announcementSubmit" class="btn btn-primary-custom rounded-pill px-4">
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

<?php
$conn->close();
?>