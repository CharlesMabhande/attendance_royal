<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}
include('../dbcon.php');
require_once __DIR__ . '/role_helpers.php';
require_once __DIR__ . '/staff_directory_helpers.php';
admin_sync_role_from_db($con);
if (!admin_can('staff_directory')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id < 1) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$st = $con->prepare('SELECT `photo_filename` FROM `school_staff` WHERE `id` = ? LIMIT 1');
if (!$st) {
    header('HTTP/1.0 500 Internal Server Error');
    exit;
}
$st->bind_param('i', $id);
$st->execute();
$res = $st->get_result();
$row = $res ? $res->fetch_assoc() : null;
$st->close();

if (!$row || empty($row['photo_filename'])) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$fn = basename((string) $row['photo_filename']);
$path = rfjs_staff_photo_dir() . DIRECTORY_SEPARATOR . $fn;
if (!is_file($path) || !is_readable($path)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
$types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
];
$mime = $types[$ext] ?? 'application/octet-stream';
$finfo = finfo_open(FILEINFO_MIME_TYPE);
if ($finfo) {
    $m = finfo_file($finfo, $path);
    finfo_close($finfo);
    if ($m !== false && strpos($m, 'image/') === 0) {
        $mime = $m;
    }
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, max-age=3600');
header('Content-Disposition: inline; filename="' . str_replace('"', '', $fn) . '"');
readfile($path);
exit;
