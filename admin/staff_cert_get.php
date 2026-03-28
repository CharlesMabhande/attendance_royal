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

$st = $con->prepare(
    'SELECT c.`stored_filename`, c.`original_filename` FROM `staff_certificate` c ' .
    'INNER JOIN `school_staff` s ON s.`id` = c.`staff_id` WHERE c.`id` = ? LIMIT 1'
);
if (!$st) {
    header('HTTP/1.0 500 Internal Server Error');
    exit;
}
$st->bind_param('i', $id);
$st->execute();
$res = $st->get_result();
$row = $res ? $res->fetch_assoc() : null;
$st->close();

if (!$row) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$fn = basename((string) $row['stored_filename']);
$path = rfjs_staff_upload_dir() . DIRECTORY_SEPARATOR . $fn;
if (!is_file($path) || !is_readable($path)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$download = isset($_GET['download']) && $_GET['download'] === '1';
$orig = (string) $row['original_filename'];
if ($orig === '' || preg_match('/[\r\n\0"]/', $orig)) {
    $orig = 'certificate.pdf';
}

header('Content-Type: application/pdf');
header('Content-Length: ' . (string) filesize($path));
if ($download) {
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $orig) . '"');
} else {
    header('Content-Disposition: inline; filename="' . str_replace('"', '', $orig) . '"');
}
readfile($path);
exit;
