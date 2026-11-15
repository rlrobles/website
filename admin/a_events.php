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
$uploadDir = "../uploads/events/";
$uploadPath = __DIR__ . "/../uploads/events/";

if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$csrfToken = $_SESSION['csrf_token'];

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function setMessage($type, $message) {
    $_SESSION['event_message'] = ['type' => $type, 'message' => $message];
}

function redirectPage() {
    header("Location: a_events.php");
    exit();
}

function validateEventRequest($csrfToken) {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        throw new Exception("Invalid request. Please refresh the page.");
    }
}

function deleteEventImage($filename, $uploadPath) {
    $filename = basename((string) $filename);

    if ($filename && is_file($uploadPath . $filename)) {
        unlink($uploadPath . $filename);
    }
}

function uploadEventImage($file, $uploadPath) {
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

    $filename = 'event_' . bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];

    if (!move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
        throw new Exception("Unable to save the uploaded image.");
    }

    return $filename;
}

function validateEventDate($date) {
    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

    if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
        throw new Exception("Please select a valid event date.");
    }
}


/* ADD EVENT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $image = null;

    try {
        validateEventRequest($csrfToken);

        $title = trim($_POST['title'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $date = $_POST['event_date'] ?? '';

        if ($title === '') {
            throw new Exception("Please enter an event title.");
        }

        if (mb_strlen($title) > 255) {
            throw new Exception("Event title must not exceed 255 characters.");
        }

        validateEventDate($date);

        if ($link !== '') {
            $scheme = strtolower(parse_url($link, PHP_URL_SCHEME) ?? '');

            if (!filter_var($link, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                throw new Exception("Please enter a valid external link.");
            }
        }

        $image = uploadEventImage($_FILES['event_image'] ?? null, $uploadPath);

        if (!$image) {
            throw new Exception("Please select an event image.");
        }

        $stmt = $conn->prepare("
            INSERT INTO events (title, image, link, event_date, display_order)
            VALUES (?, ?, ?, ?, 0)
        ");

        $stmt->bind_param("ssss", $title, $image, $link, $date);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception("Unable to add event.");
        }

        $stmt->close();

        // Notify all registered users
        $notificationSent = notifyAllUsersAboutEvent(
            $conn,
            $_SESSION['user_id'],
            $title
        );

        if (!$notificationSent) {
            error_log("Failed to send event notifications: " . $title);
        }

        setMessage('success', 'Event added successfully.');

    } catch (Exception $e) {
        if ($image) {
            deleteEventImage($image, $uploadPath);
        }

        setMessage('danger', $e->getMessage());
    }

    redirectPage();
}


/* UPDATE EVENT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $newImage = null;

    try {
        validateEventRequest($csrfToken);

        $id = (int) ($_POST['event_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $date = $_POST['event_date'] ?? '';

        if ($id <= 0) throw new Exception("Invalid event.");
        if ($title === '') throw new Exception("Please enter an event title.");
        if (mb_strlen($title) > 255) throw new Exception("Event title must not exceed 255 characters.");

        validateEventDate($date);

        if ($link !== '') {
            $scheme = strtolower(parse_url($link, PHP_URL_SCHEME) ?? '');

            if (!filter_var($link, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                throw new Exception("Please enter a valid external link.");
            }
        }

        $check = $conn->prepare("SELECT image FROM events WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$existing) throw new Exception("Event not found.");

        $newImage = uploadEventImage($_FILES['event_image'] ?? null, $uploadPath);

        if ($newImage) {
            $stmt = $conn->prepare("
                UPDATE events
                SET title = ?, image = ?, link = ?, event_date = ?
                WHERE id = ?
            ");

            $stmt->bind_param("ssssi", $title, $newImage, $link, $date, $id);
        } else {
            $stmt = $conn->prepare("
                UPDATE events
                SET title = ?, link = ?, event_date = ?
                WHERE id = ?
            ");

            $stmt->bind_param("sssi", $title, $link, $date, $id);
        }

        $stmt->execute();
        $stmt->close();

        if ($newImage) {
            deleteEventImage($existing['image'], $uploadPath);
        }

        setMessage('success', 'Event updated successfully.');

    } catch (Exception $e) {
        if ($newImage) deleteEventImage($newImage, $uploadPath);
        setMessage('danger', $e->getMessage());
    }

    redirectPage();
}

/* DELETE EVENT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    try {
        validateEventRequest($csrfToken);

        $id = (int) ($_POST['event_id'] ?? 0);

        if ($id <= 0) throw new Exception("Invalid event.");

        $stmt = $conn->prepare("SELECT image FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$event) throw new Exception("Event not found.");

        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        deleteEventImage($event['image'], $uploadPath);

        setMessage('success', 'Event deleted successfully.');

    } catch (Exception $e) {
        setMessage('danger', $e->getMessage());
    }

    redirectPage();
}

/* SEARCH AND SORT */
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

$orderBy = $sort === 'oldest'
    ? 'event_date ASC, id ASC'
    : 'event_date DESC, id DESC';

$searchTerm = "%$search%";

$stmt = $conn->prepare("
    SELECT * FROM events
    WHERE title LIKE ? OR image LIKE ? OR link LIKE ?
    ORDER BY $orderBy
");

$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();

$result = $stmt->get_result();
$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

$stmt->close();

/* CALENDAR DATA */
$calendarResult = $conn->query("
    SELECT id, title, link, event_date
    FROM events
    WHERE event_date IS NOT NULL
    ORDER BY event_date ASC
");

$calendarEvents = [];

while ($row = $calendarResult->fetch_assoc()) {
    $calendarEvents[] = [
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'link' => $row['link'], 
        'date' => $row['event_date']
    ];
}

$calendarJson = json_encode(
    $calendarEvents,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

if ($calendarJson === false) $calendarJson = '[]';

$message = $_SESSION['event_message'] ?? null;
unset($_SESSION['event_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management</title>

    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../CSS/a_schedule.css">
    <link rel="stylesheet" href="../CSS/event_new.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js" defer></script>
    <script src="../JS/event_new.js" defer></script>
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

                            <a class="btn btn-events active" href="a_events.php">
                                <i class="fa-solid fa-calendar-week"></i> Events
                            </a>

                            <a class="btn btn-newsletter" href="a_newsletter.php">
                                <i class="fa-solid fa-envelopes-bulk"></i> Newsletter
                            </a>
                        </div>
                        <!-- EVENT MANAGEMENT -->
                        <div class="event-admin">
                            <div class="container-fluid">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                                    <div>
                                        <h1 class="page-heading mb-1">Event Management</h1>
                                        <p class="page-subtitle mb-0">Manage and publish school events.</p>
                                    </div>
                                    <button type="button" class="btn btn-primary-custom rounded-pill px-4" onclick="prepareAddModal()">
                                        <i class="fa-solid fa-plus me-2"></i> Add Event
                                    </button>
                                </div>

                                <?php if ($message): ?>
                                    <div class="alert alert-<?= escape($message['type']) ?> alert-dismissible fade show" role="alert">
                                        <?= escape($message['message']) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <div class="row g-4">
                                    <!-- EVENT LIST -->
                                    <div class="col-lg-8 col-xl-9">
                                        <div class="card admin-card">
                                            <div class="card-body">
                                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                                                    <div>
                                                        <h5 class="fw-bold mb-1">Published Events</h5>
                                                        <small class="text-muted"><?= count($events) ?> event(s) found</small>
                                                    </div>

                                                    <form method="GET" class="d-flex flex-wrap gap-2">
                                                        <input type="search" name="search" class="form-control" placeholder="Search event..." value="<?= escape($search) ?>" style="width:190px;">
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

                                                <?php if (empty($events)): ?>
                                                    <div class="text-center py-5">
                                                        <i class="fa-solid fa-calendar-xmark fa-3x text-secondary mb-3"></i>
                                                        <h6 class="fw-bold">No events found</h6>
                                                        <p class="text-muted small mb-0">Add an event to display it here.</p>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="row g-3">
                                                        <?php foreach ($events as $event):
                                                            $imageName = basename($event['image']);
                                                            $imageUrl = $uploadDir . rawurlencode($imageName);
                                                        ?>
                                                            <div class="col-md-6 col-xl-4">
                                                                <div class="event-card">
                                                                    <img src="<?= escape($imageUrl) ?>" alt="<?= escape($event['title']) ?>" onerror="this.style.display='none';">

                                                                    <div class="card-body p-3">
                                                                        <h6 class="card-title mb-2"><?= escape($event['title']) ?></h6>

                                                                        <p class="event-date mb-2">
                                                                            <i class="fa-regular fa-calendar me-1"></i>
                                                                            <?= $event['event_date'] ? date('F d, Y', strtotime($event['event_date'])) : 'No event date' ?>
                                                                        </p>

                                                                        <?php if (!empty($event['link'])): ?>
                                                                            <a href="<?= escape($event['link']) ?>" target="_blank" rel="noopener noreferrer" class="small text-success text-decoration-none">
                                                                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open external link
                                                                            </a>
                                                                        <?php endif; ?>

                                                                        <hr>

                                                                        <div class="d-flex justify-content-between gap-2">
                                                                            <button type="button" class="btn btn-sm btn-outline-success flex-fill"
                                                                                onclick='prepareEditModal(<?= json_encode([
                                                                                    "id" => $event["id"],
                                                                                    "title" => $event["title"],
                                                                                    "link" => $event["link"],
                                                                                    "date" => $event["event_date"],
                                                                                    "image" => $imageUrl
                                                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                                                            </button>

                                                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                                                                <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                                                                                <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                                                                <button type="submit" name="delete_event" class="btn btn-sm btn-outline-danger">
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

                                    <!-- EVENT CALENDAR -->
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

    <!-- ADD / EDIT EVENT MODAL -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="eventForm">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="eventModalTitle">Add Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                        <input type="hidden" name="event_id" id="eventId">

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                Event Image <span class="text-danger" id="imageRequired">*</span>
                            </label>
                            <input type="file" name="event_image" id="eventImage" class="form-control"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <small class="text-muted">JPG, PNG, or WEBP. Maximum 50 MB. Leave empty when editing to keep the current image.</small>
                            <small id="imageError" class="text-danger d-block mt-1"></small>
                            <img src="" id="imagePreview" class="image-preview" alt="Image preview" style="display:none;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                Event Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" id="eventTitle" class="form-control"
                                placeholder="Enter event title..." maxlength="255" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                Event Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="event_date" id="eventDate" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                External Link <span class="text-muted">(Optional)</span>
                            </label>
                            <input type="url" name="link" id="eventLink" class="form-control" placeholder="https://example.com">
                            <small class="text-muted">Add a link where visitors can learn more about the event.</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_event" id="eventSubmit" class="btn btn-primary-custom rounded-pill px-4">
                            <i class="fa-solid fa-upload me-1"></i> Save Event
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