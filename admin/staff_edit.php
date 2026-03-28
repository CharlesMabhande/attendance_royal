<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header('location: ../login.php');
    exit;
}
include('../dbcon.php');
require_once __DIR__ . '/role_helpers.php';
require_once __DIR__ . '/staff_directory_helpers.php';
admin_sync_role_from_db($con);
require_admin_permission('staff_directory');

$message = '';
$messageType = '';
$staff = null;
$certs = [];

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isNew = $id < 1;

$tablesOk = false;
$tq = mysqli_query($con, "SHOW TABLES LIKE 'school_staff'");
if ($tq && mysqli_num_rows($tq) > 0) {
    $tablesOk = true;
}

$staffPhotoEnabled = false;
if ($tablesOk) {
    $pc = mysqli_query($con, "SHOW COLUMNS FROM `school_staff` LIKE 'photo_filename'");
    if ($pc && mysqli_num_rows($pc) > 0) {
        $staffPhotoEnabled = true;
    }
}

if (!$tablesOk) {
    header('Location: staff_list.php');
    exit;
}

// POST: delete staff member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    $sid = (int) ($_POST['staff_id'] ?? 0);
    if ($sid < 1) {
        $message = 'Invalid staff.';
        $messageType = 'error';
    } else {
        rfjs_staff_delete_all_for_staff($con, $sid);
        $st = $con->prepare('DELETE FROM `school_staff` WHERE `id` = ? LIMIT 1');
        if ($st) {
            $st->bind_param('i', $sid);
            $st->execute();
            $st->close();
        }
        header('Location: staff_list.php?deleted=1');
        exit;
    }
}

// POST: delete one certificate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cert'])) {
    $cid = (int) ($_POST['cert_id'] ?? 0);
    $sid = (int) ($_POST['staff_id'] ?? 0);
    if ($cid < 1 || $sid < 1) {
        $message = 'Invalid certificate.';
        $messageType = 'error';
    } else {
        $chk = $con->prepare('SELECT `id` FROM `staff_certificate` WHERE `id` = ? AND `staff_id` = ? LIMIT 1');
        if ($chk) {
            $chk->bind_param('ii', $cid, $sid);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                rfjs_staff_delete_certificate_file($con, $cid);
                $message = 'Certificate removed.';
                $messageType = 'ok';
            }
            $chk->close();
        }
        header('Location: staff_edit.php?id=' . $sid);
        exit;
    }
}

// POST: save staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_staff'])) {
    $sid = (int) ($_POST['staff_id'] ?? 0);
    $full_name = trim((string) ($_POST['full_name'] ?? ''));
    $cat = $_POST['staff_category'] ?? 'teacher';
    $cat = ($cat === 'ancillary') ? 'ancillary' : 'teacher';
    $job_title = trim((string) ($_POST['job_title'] ?? ''));
    $role_at = trim((string) ($_POST['role_at_school'] ?? ''));
    $work_level = trim((string) ($_POST['work_level'] ?? ''));
    $qual = trim((string) ($_POST['qualifications'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $date_started = trim((string) ($_POST['date_started'] ?? ''));
    $date_left = trim((string) ($_POST['date_left'] ?? ''));
    $status = $_POST['status'] ?? 'active';
    $status = ($status === 'left') ? 'left' : 'active';
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($full_name === '' || strlen($full_name) > 200) {
        $message = 'Enter a full name (required).';
        $messageType = 'error';
    } elseif (strlen($email) > 120 || strlen($phone) > 40) {
        $message = 'Email or phone too long.';
        $messageType = 'error';
    } else {
        $ds = null;
        if ($date_started !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_started)) {
            $ds = $date_started;
        }
        $dl = null;
        if ($date_left !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_left)) {
            $dl = $date_left;
        }
        $now = date('Y-m-d H:i:s');
        if ($sid < 1) {
            $st = $con->prepare('INSERT INTO `school_staff` (`full_name`,`staff_category`,`job_title`,`role_at_school`,`work_level`,`qualifications`,`email`,`phone`,`date_started`,`date_left`,`status`,`notes`,`created_at`,`updated_at`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            if ($st) {
                $st->bind_param(
                    'ssssssssssssss',
                    $full_name,
                    $cat,
                    $job_title,
                    $role_at,
                    $work_level,
                    $qual,
                    $email,
                    $phone,
                    $ds,
                    $dl,
                    $status,
                    $notes,
                    $now,
                    $now
                );
                if ($st->execute()) {
                    $newId = (int) mysqli_insert_id($con);
                    $st->close();
                    $st = null;
                    $sid = $newId;
                    if ($staffPhotoEnabled && isset($_FILES['staff_photo'])) {
                        $perr = rfjs_staff_save_staff_photo($con, $sid, $_FILES['staff_photo']);
                        if ($perr !== null) {
                            $_SESSION['staff_photo_error'] = $perr;
                        }
                    }
                    if (!empty($_FILES['certs']) && is_array($_FILES['certs']['name'])) {
                        $n = count($_FILES['certs']['name']);
                        for ($i = 0; $i < $n; $i++) {
                            $file = [
                                'name' => $_FILES['certs']['name'][$i],
                                'type' => $_FILES['certs']['type'][$i],
                                'tmp_name' => $_FILES['certs']['tmp_name'][$i],
                                'error' => $_FILES['certs']['error'][$i],
                                'size' => $_FILES['certs']['size'][$i],
                            ];
                            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                                continue;
                            }
                            $err = rfjs_staff_validate_pdf_upload($file);
                            if ($err !== null) {
                                $message = $err;
                                $messageType = 'error';
                                break;
                            }
                            rfjs_staff_store_pdf($con, $sid, $file);
                        }
                    }
                    if ($messageType !== 'error') {
                        header('Location: staff_edit.php?id=' . $sid . '&saved=1');
                        exit;
                    }
                } else {
                    $message = 'Could not save staff.';
                    $messageType = 'error';
                    $st->close();
                }
            }
        } else {
            $st = $con->prepare('UPDATE `school_staff` SET `full_name`=?,`staff_category`=?,`job_title`=?,`role_at_school`=?,`work_level`=?,`qualifications`=?,`email`=?,`phone`=?,`date_started`=?,`date_left`=?,`status`=?,`notes`=?,`updated_at`=? WHERE `id`=? LIMIT 1');
            if ($st) {
                $st->bind_param(
                    'sssssssssssssi',
                    $full_name,
                    $cat,
                    $job_title,
                    $role_at,
                    $work_level,
                    $qual,
                    $email,
                    $phone,
                    $ds,
                    $dl,
                    $status,
                    $notes,
                    $now,
                    $sid
                );
                if ($st->execute()) {
                    $st->close();
                    $st = null;
                    if ($staffPhotoEnabled && isset($_FILES['staff_photo'])) {
                        $perr = rfjs_staff_save_staff_photo($con, $sid, $_FILES['staff_photo']);
                        if ($perr !== null) {
                            $_SESSION['staff_photo_error'] = $perr;
                        }
                    }
                    if (!empty($_FILES['certs']) && is_array($_FILES['certs']['name'])) {
                        $n = count($_FILES['certs']['name']);
                        for ($i = 0; $i < $n; $i++) {
                            $file = [
                                'name' => $_FILES['certs']['name'][$i],
                                'type' => $_FILES['certs']['type'][$i],
                                'tmp_name' => $_FILES['certs']['tmp_name'][$i],
                                'error' => $_FILES['certs']['error'][$i],
                                'size' => $_FILES['certs']['size'][$i],
                            ];
                            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                                continue;
                            }
                            $err = rfjs_staff_validate_pdf_upload($file);
                            if ($err !== null) {
                                $message = $err;
                                $messageType = 'error';
                                break;
                            }
                            rfjs_staff_store_pdf($con, $sid, $file);
                        }
                    }
                    if ($messageType !== 'error') {
                        header('Location: staff_edit.php?id=' . $sid . '&saved=1');
                        exit;
                    }
                } else {
                    $message = 'Could not update.';
                    $messageType = 'error';
                    $st->close();
                }
            }
        }
    }
}

// POST: remove staff photo (same form as save; must run after save block)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_staff_photo'])) {
    $sid = (int) ($_POST['staff_id'] ?? 0);
    if ($sid < 1) {
        $message = 'Invalid staff.';
        $messageType = 'error';
    } elseif ($staffPhotoEnabled) {
        rfjs_staff_clear_staff_photo($con, $sid);
        header('Location: staff_edit.php?id=' . $sid . '&saved=1');
        exit;
    } else {
        header('Location: staff_edit.php?id=' . $sid);
        exit;
    }
}

if (!empty($_SESSION['staff_photo_error'])) {
    $message = 'Details saved, but photo upload failed: ' . htmlspecialchars((string) $_SESSION['staff_photo_error'], ENT_QUOTES, 'UTF-8');
    $messageType = 'error';
    unset($_SESSION['staff_photo_error']);
} elseif (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $message = 'Saved.';
    $messageType = 'ok';
}

if (!$isNew && $id > 0) {
    $st = $con->prepare('SELECT * FROM `school_staff` WHERE `id` = ? LIMIT 1');
    if ($st) {
        $st->bind_param('i', $id);
        $st->execute();
        $res = $st->get_result();
        $staff = $res ? $res->fetch_assoc() : null;
        $st->close();
    }
    if (!$staff) {
        header('Location: staff_list.php');
        exit;
    }
    $st2 = $con->prepare('SELECT * FROM `staff_certificate` WHERE `staff_id` = ? ORDER BY `uploaded_at` DESC');
    if ($st2) {
        $st2->bind_param('i', $id);
        $st2->execute();
        $cres = $st2->get_result();
        while ($cres && ($row = $cres->fetch_assoc())) {
            $certs[] = $row;
        }
        $st2->close();
    }
}

if ($isNew) {
    $staff = [
        'full_name' => '',
        'staff_category' => 'teacher',
        'job_title' => '',
        'role_at_school' => '',
        'work_level' => '',
        'qualifications' => '',
        'email' => '',
        'phone' => '',
        'date_started' => '',
        'date_left' => '',
        'status' => 'active',
        'notes' => '',
        'photo_filename' => '',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $isNew ? 'Add staff' : 'Edit staff'; ?> | Admin</title>
    <link rel="icon" href="../image/logo-rfjs.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../csss/rfjs-theme.css" type="text/css">
    <link rel="stylesheet" href="../csss/bulk-move-forms.css" type="text/css">
    <style>
        body.bulk-move-page { font-family: system-ui, Segoe UI, sans-serif; background: var(--rfjs-bg-page); min-height: 100vh; color: #333; padding: clamp(10px, 2vw, 20px); }
        .wrap { max-width: min(1200px, calc(100vw - 24px)); margin: 0 auto; }
        .nav a { color: #fff; font-weight: 600; text-decoration: none; }
        .card { background: rgba(255,255,255,.96); border-radius: 16px; padding: 22px 26px; box-shadow: 0 12px 36px rgba(0,0,0,.12); }
        h1 { color: #4a2c7a; font-size: 1.2rem; margin-bottom: 12px; }
        label { display: block; font-weight: 600; margin: 14px 0 6px; color: #444; font-size: 0.92rem; }
        .staff-edit-layout > .staff-edit-col label:first-child { margin-top: 0; }
        input[type="text"], input[type="email"], input[type="date"], textarea, select {
            width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 10px; font-size: 1rem;
        }
        textarea { min-height: 88px; resize: vertical; }
        #qualifications { min-height: 140px; }
        #notes { min-height: 72px; }
        .staff-edit-layout {
            display: grid;
            gap: 8px 28px;
            grid-template-columns: 1fr;
            align-items: start;
        }
        @media (min-width: 960px) {
            .staff-edit-layout { grid-template-columns: 1fr 1fr; }
        }
        .row2 { display: grid; gap: 12px; grid-template-columns: 1fr; }
        @media (min-width: 520px) { .row2 { grid-template-columns: 1fr 1fr; } }
        .row3 { display: grid; gap: 12px; grid-template-columns: 1fr; }
        @media (min-width: 700px) { .row3 { grid-template-columns: repeat(3, 1fr); } }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 0.95rem; }
        .btn-primary { background: var(--rfjs-gradient-btn); color: #fff; }
        .btn-danger { background: #c0392b; color: #fff; }
        .btn-outline { background: #fff; color: #333; border: 1px solid #ccc; text-decoration: none; }
        .msg { padding: 12px; border-radius: 10px; margin-bottom: 14px; font-size: 0.92rem; }
        .msg.ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .msg.warn { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .subtitle { color: #666; font-size: 0.92rem; margin: -4px 0 14px; line-height: 1.45; }
        .staff-photo-row { display: flex; flex-wrap: wrap; gap: 16px 22px; align-items: flex-start; margin-bottom: 14px; padding-bottom: 16px; border-bottom: 1px solid #eee; }
        .staff-photo-preview { flex-shrink: 0; }
        .staff-photo-preview img { width: 120px; height: 120px; object-fit: cover; border-radius: 12px; border: 1px solid #e0e0e0; display: block; background: #f5f5f5; }
        .staff-photo-placeholder { width: 120px; height: 120px; border-radius: 12px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #bbb; font-size: 2.5rem; background: #f8f9fa; }
        .staff-photo-actions { flex: 1; min-width: 200px; }
        .staff-photo-actions .hint { margin-bottom: 10px; }
        .cert-list { margin: 10px 0; padding: 0; list-style: none; }
        .cert-list li { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; padding: 10px; border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 8px; background: #fafafa; }
        .hint { font-size: 0.88rem; color: #666; margin-top: 6px; }
        .staff-form-actions { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; padding-top: 14px; border-top: 1px solid #eee; align-items: center; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; align-items: center; }
        .btn-sm { padding: 6px 12px; font-size: 0.85rem; }
    </style>
</head>
<body class="bulk-move-page">
    <div class="wrap">
        <div class="nav" style="margin-bottom:14px;">
            <a href="staff_list.php"><i class="fas fa-arrow-left"></i> Staff list</a>
        </div>
        <div class="card">
            <div style="text-align:center;margin-bottom:14px;">
                <img src="../image/logo-rfjs.png" alt="Royal Family Junior School" class="rfjs-logo-img rfjs-logo-crest" width="100" height="100" style="display:block;margin:0 auto;">
            </div>
            <h1><i class="fas fa-user-edit"></i> <?php echo $isNew ? 'Add staff member' : 'Edit staff member'; ?></h1>
            <p class="subtitle"><?php echo $isNew
                ? 'Enter their details and optional photo. After saving you can attach PDF certificates.'
                : 'Update their information, photo, and certificates below, then click Save.'; ?></p>

            <?php if ($message): ?>
                <div class="msg <?php echo $messageType === 'ok' ? 'ok' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if (!$staffPhotoEnabled): ?>
                <div class="msg warn">
                    <strong>Photo uploads disabled.</strong> Run <code>sql/staff_directory_photo.sql</code> in phpMyAdmin (adds the <code>photo_filename</code> column), then refresh this page.
                </div>
            <?php endif; ?>

            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="staff_id" value="<?php echo $isNew ? '0' : (int) $staff['id']; ?>">

                <?php if ($staffPhotoEnabled): ?>
                <div class="staff-photo-row">
                    <div class="staff-photo-preview">
                        <?php if (!$isNew && !empty($staff['photo_filename'])): ?>
                            <img src="staff_photo_get.php?id=<?php echo (int) $staff['id']; ?>" alt="" width="120" height="120">
                        <?php else: ?>
                            <div class="staff-photo-placeholder" aria-hidden="true"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="staff-photo-actions">
                        <label for="staff_photo">Staff photo</label>
                        <input type="file" name="staff_photo" id="staff_photo" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
                        <p class="hint">Optional. JPG, PNG or WebP, max 5 MB. Choose a file to add or replace the current picture.</p>
                        <?php if (!$isNew && !empty($staff['photo_filename'])): ?>
                            <button type="submit" name="remove_staff_photo" value="1" formnovalidate class="btn btn-outline btn-sm" onclick="return confirm('Remove this photo from the directory?');"><i class="fas fa-times"></i> Remove photo</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="staff-edit-layout">
                    <div class="staff-edit-col staff-edit-col-primary">
                        <label for="full_name">Full name *</label>
                        <input type="text" name="full_name" id="full_name" required maxlength="200" value="<?php echo htmlspecialchars((string) $staff['full_name']); ?>">

                        <div class="row3">
                            <div>
                                <label for="staff_category">Category</label>
                                <select name="staff_category" id="staff_category">
                                    <option value="teacher" <?php echo (isset($staff['staff_category']) && $staff['staff_category'] === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                                    <option value="ancillary" <?php echo (isset($staff['staff_category']) && $staff['staff_category'] === 'ancillary') ? 'selected' : ''; ?>>Ancillary staff</option>
                                </select>
                            </div>
                            <div>
                                <label for="status">Employment status</label>
                                <select name="status" id="status">
                                    <option value="active" <?php echo (isset($staff['status']) && $staff['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="left" <?php echo (isset($staff['status']) && $staff['status'] === 'left') ? 'selected' : ''; ?>>Left / no longer here</option>
                                </select>
                            </div>
                            <div>
                                <label for="job_title">Job title</label>
                                <input type="text" name="job_title" id="job_title" maxlength="150" placeholder="e.g. Grade 5 Teacher" value="<?php echo htmlspecialchars((string) $staff['job_title']); ?>">
                            </div>
                        </div>

                        <div class="row2">
                            <div>
                                <label for="role_at_school">Role at school</label>
                                <input type="text" name="role_at_school" id="role_at_school" maxlength="200" placeholder="e.g. HOD, Sports coach" value="<?php echo htmlspecialchars((string) $staff['role_at_school']); ?>">
                            </div>
                            <div>
                                <label for="work_level">Level of work</label>
                                <input type="text" name="work_level" id="work_level" maxlength="120" placeholder="e.g. Senior, Level 1" value="<?php echo htmlspecialchars((string) $staff['work_level']); ?>">
                            </div>
                        </div>

                        <div class="row2">
                            <div>
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" maxlength="120" value="<?php echo htmlspecialchars((string) $staff['email']); ?>">
                            </div>
                            <div>
                                <label for="phone">Phone</label>
                                <input type="text" name="phone" id="phone" maxlength="40" value="<?php echo htmlspecialchars((string) $staff['phone']); ?>">
                            </div>
                        </div>

                        <div class="row2">
                            <div>
                                <label for="date_started">Date started</label>
                                <input type="date" name="date_started" id="date_started" value="<?php echo $staff['date_started'] ? htmlspecialchars((string) $staff['date_started']) : ''; ?>">
                            </div>
                            <div>
                                <label for="date_left">Date left (if applicable)</label>
                                <input type="date" name="date_left" id="date_left" value="<?php echo !empty($staff['date_left']) ? htmlspecialchars((string) $staff['date_left']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="staff-edit-col staff-edit-col-secondary">
                        <label for="qualifications">Qualifications &amp; training</label>
                        <textarea name="qualifications" id="qualifications" placeholder="Degrees, diplomas, professional development…"><?php echo htmlspecialchars((string) $staff['qualifications']); ?></textarea>

                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="Internal notes (not shown on public site)"><?php echo htmlspecialchars((string) $staff['notes']); ?></textarea>

                        <label for="certs">Upload certificates (PDF)</label>
                        <input type="file" name="certs[]" id="certs" accept="application/pdf,.pdf" multiple>
                        <p class="hint">You can select several PDFs (max 12 MB each). Save the staff record first, then add more files on the same page after saving.</p>
                    </div>

                    <div class="staff-form-actions">
                        <button type="submit" name="save_staff" value="1" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                        <a class="btn btn-outline" href="staff_list.php">Cancel</a>
                    </div>
                </div>
            </form>

            <?php if (!$isNew && !empty($certs)): ?>
                <h2 style="font-size:1.05rem;margin:24px 0 10px;color:#4a2c7a;">Certificates</h2>
                <ul class="cert-list">
                    <?php foreach ($certs as $c): ?>
                        <li>
                            <span><i class="fas fa-file-pdf" style="color:#c0392b;"></i> <?php echo htmlspecialchars((string) $c['original_filename']); ?></span>
                            <span style="font-size:0.85rem;color:#666;"><?php echo htmlspecialchars((string) $c['uploaded_at']); ?></span>
                            <span>
                                <a class="btn btn-outline btn-sm" href="staff_cert_get.php?id=<?php echo (int) $c['id']; ?>" target="_blank" rel="noopener">View</a>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Remove this file?');">
                                    <input type="hidden" name="staff_id" value="<?php echo (int) $staff['id']; ?>">
                                    <input type="hidden" name="cert_id" value="<?php echo (int) $c['id']; ?>">
                                    <button type="submit" name="delete_cert" value="1" class="btn btn-danger btn-sm">Remove</button>
                                </form>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!$isNew): ?>
                <form method="post" style="margin-top:28px;padding-top:20px;border-top:1px solid #eee;" onsubmit="return confirm('Permanently delete this staff member, their profile photo, and all uploaded certificates?');">
                    <input type="hidden" name="staff_id" value="<?php echo (int) $staff['id']; ?>">
                    <button type="submit" name="delete_staff" value="1" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete staff member entirely</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
