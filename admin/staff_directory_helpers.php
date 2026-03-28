<?php
/**
 * Staff directory — upload paths, PDF certificates, and staff photos.
 * Include after dbcon.php when handling staff files.
 */

if (defined('RFJS_STAFF_DIR_HELPERS')) {
    return;
}
define('RFJS_STAFF_DIR_HELPERS', true);

function rfjs_staff_upload_dir(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'staff_certificates';
}

function rfjs_staff_photo_dir(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'staff_photos';
}

function rfjs_staff_ensure_photo_dir(): bool
{
    $d = rfjs_staff_photo_dir();
    if (is_dir($d)) {
        return true;
    }
    return @mkdir($d, 0755, true);
}

function rfjs_staff_ensure_upload_dir(): bool
{
    $d = rfjs_staff_upload_dir();
    if (is_dir($d)) {
        return true;
    }
    return @mkdir($d, 0755, true);
}

/** @return string|null error message or null if OK */
function rfjs_staff_validate_pdf_upload(array $file): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'Upload failed (error code ' . (int) $file['error'] . ').';
    }
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return 'Invalid upload.';
    }
    $max = 12 * 1024 * 1024;
    if (isset($file['size']) && (int) $file['size'] > $max) {
        return 'File too large (max 12 MB).';
    }
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        return 'Only PDF files are allowed.';
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if ($mime !== false && $mime !== 'application/pdf' && $mime !== 'application/octet-stream') {
            return 'File must be a valid PDF document.';
        }
    }
    return null;
}

/** @return string|null error message or null if OK */
function rfjs_staff_validate_photo_upload(array $file): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'Photo upload failed (error code ' . (int) $file['error'] . ').';
    }
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return 'Invalid photo upload.';
    }
    $max = 5 * 1024 * 1024;
    if (isset($file['size']) && (int) $file['size'] > $max) {
        return 'Photo too large (max 5 MB).';
    }
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowedExt, true)) {
        return 'Photo must be JPG, PNG, or WebP.';
    }
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if ($mime !== false && !in_array($mime, $allowedMime, true)) {
            return 'File must be a valid image (JPG, PNG, or WebP).';
        }
    }
    $probe = @getimagesize($file['tmp_name']);
    if ($probe === false) {
        return 'Could not read image file.';
    }
    return null;
}

/**
 * Save or replace staff photo. Returns null on success, error string on failure.
 */
function rfjs_staff_save_staff_photo(mysqli $con, int $staffId, array $file): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $err = rfjs_staff_validate_photo_upload($file);
    if ($err !== null) {
        return $err;
    }
    if (!rfjs_staff_ensure_photo_dir()) {
        return 'Could not create upload folder for photos.';
    }
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpeg') {
        $ext = 'jpg';
    }
    $newName = 'p' . $staffId . '_' . bin2hex(random_bytes(10)) . '.' . $ext;
    $dest = rfjs_staff_photo_dir() . DIRECTORY_SEPARATOR . $newName;

    $st = $con->prepare('SELECT `photo_filename` FROM `school_staff` WHERE `id` = ? LIMIT 1');
    if (!$st) {
        return 'Database error.';
    }
    $st->bind_param('i', $staffId);
    $st->execute();
    $res = $st->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $st->close();
    $oldName = $row && !empty($row['photo_filename']) ? basename((string) $row['photo_filename']) : '';

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return 'Could not save photo file.';
    }

    $upd = $con->prepare('UPDATE `school_staff` SET `photo_filename` = ? WHERE `id` = ? LIMIT 1');
    if (!$upd) {
        @unlink($dest);
        return 'Database error.';
    }
    $upd->bind_param('si', $newName, $staffId);
    if (!$upd->execute()) {
        @unlink($dest);
        $upd->close();
        return 'Could not save photo reference.';
    }
    $upd->close();

    if ($oldName !== '') {
        $oldPath = rfjs_staff_photo_dir() . DIRECTORY_SEPARATOR . $oldName;
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }
    return null;
}

function rfjs_staff_clear_staff_photo(mysqli $con, int $staffId): bool
{
    $st = $con->prepare('SELECT `photo_filename` FROM `school_staff` WHERE `id` = ? LIMIT 1');
    if (!$st) {
        return false;
    }
    $st->bind_param('i', $staffId);
    $st->execute();
    $res = $st->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $st->close();
    if (!$row || empty($row['photo_filename'])) {
        return true;
    }
    $fn = basename((string) $row['photo_filename']);
    $path = rfjs_staff_photo_dir() . DIRECTORY_SEPARATOR . $fn;
    $upd = $con->prepare('UPDATE `school_staff` SET `photo_filename` = NULL WHERE `id` = ? LIMIT 1');
    if (!$upd) {
        return false;
    }
    $upd->bind_param('i', $staffId);
    $ok = $upd->execute();
    $upd->close();
    if ($ok && is_file($path)) {
        @unlink($path);
    }
    return $ok;
}

/** Store PDF; returns [stored_filename, original_name] or null on failure */
function rfjs_staff_store_pdf(mysqli $con, int $staffId, array $file): ?array
{
    $err = rfjs_staff_validate_pdf_upload($file);
    if ($err !== null) {
        return null;
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (!rfjs_staff_ensure_upload_dir()) {
        return null;
    }
    $orig = basename((string) $file['name']);
    if (strlen($orig) > 200) {
        $orig = substr($orig, 0, 200);
    }
    $safe = 's' . $staffId . '_' . bin2hex(random_bytes(12)) . '.pdf';
    $dest = rfjs_staff_upload_dir() . DIRECTORY_SEPARATOR . $safe;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    $now = date('Y-m-d H:i:s');
    $st = $con->prepare('INSERT INTO `staff_certificate` (`staff_id`, `stored_filename`, `original_filename`, `uploaded_at`) VALUES (?, ?, ?, ?)');
    if (!$st) {
        @unlink($dest);
        return null;
    }
    $st->bind_param('isss', $staffId, $safe, $orig, $now);
    if (!$st->execute()) {
        @unlink($dest);
        $st->close();
        return null;
    }
    $st->close();
    return [$safe, $orig];
}

function rfjs_staff_delete_certificate_file(mysqli $con, int $certId): bool
{
    $st = $con->prepare('SELECT `stored_filename` FROM `staff_certificate` WHERE `id` = ? LIMIT 1');
    if (!$st) {
        return false;
    }
    $st->bind_param('i', $certId);
    if (!$st->execute()) {
        $st->close();
        return false;
    }
    $res = $st->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $st->close();
    if (!$row) {
        return false;
    }
    $fn = basename((string) $row['stored_filename']);
    $path = rfjs_staff_upload_dir() . DIRECTORY_SEPARATOR . $fn;
    $del = $con->prepare('DELETE FROM `staff_certificate` WHERE `id` = ? LIMIT 1');
    if (!$del) {
        return false;
    }
    $del->bind_param('i', $certId);
    $ok = $del->execute();
    $del->close();
    if ($ok && is_file($path)) {
        @unlink($path);
    }
    return $ok;
}

function rfjs_staff_delete_all_for_staff(mysqli $con, int $staffId): void
{
    $sid = (int) $staffId;
    $q0 = mysqli_query($con, "SELECT `photo_filename` FROM `school_staff` WHERE `id` = {$sid} LIMIT 1");
    if ($q0 && mysqli_num_rows($q0) > 0) {
        $row0 = mysqli_fetch_assoc($q0);
        if ($row0 && !empty($row0['photo_filename'])) {
            $pf = rfjs_staff_photo_dir() . DIRECTORY_SEPARATOR . basename((string) $row0['photo_filename']);
            if (is_file($pf)) {
                @unlink($pf);
            }
        }
    }

    $st = $con->prepare('SELECT `id`, `stored_filename` FROM `staff_certificate` WHERE `staff_id` = ?');
    if (!$st) {
        return;
    }
    $st->bind_param('i', $staffId);
    $st->execute();
    $res = $st->get_result();
    $dir = rfjs_staff_upload_dir();
    while ($row = $res->fetch_assoc()) {
        $p = $dir . DIRECTORY_SEPARATOR . basename((string) $row['stored_filename']);
        if (is_file($p)) {
            @unlink($p);
        }
    }
    $st->close();
    $d2 = $con->prepare('DELETE FROM `staff_certificate` WHERE `staff_id` = ?');
    if ($d2) {
        $d2->bind_param('i', $staffId);
        $d2->execute();
        $d2->close();
    }
}
