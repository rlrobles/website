<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$uploadsPath = realpath(__DIR__ . '/../uploads');

if (!$uploadsPath || !is_dir($uploadsPath)) {
    http_response_code(404);
    exit("Uploads folder not found.");
}

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit("PHP ZipArchive extension is not enabled.");
}

$zipName = 'ACES_SBM_Uploads_Backup_' . date('Y-m-d_H-i-s') . '.zip';
$tempFile = tempnam(sys_get_temp_dir(), 'aces_backup_');

if ($tempFile === false) {
    http_response_code(500);
    exit("Unable to create backup file.");
}

$zip = new ZipArchive();

if ($zip->open($tempFile, ZipArchive::OVERWRITE) !== true) {
    unlink($tempFile);
    http_response_code(500);
    exit("Unable to create ZIP archive.");
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $uploadsPath,
        FilesystemIterator::SKIP_DOTS
    ),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$fileCount = 0;

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->isLink()) {
        continue;
    }

    $filePath = $file->getRealPath();

    if ($filePath === false) {
        continue;
    }

    $relativePath = 'uploads/' . str_replace(
        DIRECTORY_SEPARATOR,
        '/',
        substr($filePath, strlen($uploadsPath) + 1)
    );

    if ($zip->addFile($filePath, $relativePath)) {
        $fileCount++;
    }
}

$zip->close();

if ($fileCount === 0) {
    unlink($tempFile);
    http_response_code(404);
    exit("No uploaded files were found to back up.");
}

if (!file_exists($tempFile) || filesize($tempFile) === 0) {
    unlink($tempFile);
    http_response_code(500);
    exit("Backup generation failed.");
}

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($tempFile));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($tempFile);
unlink($tempFile);
exit();
