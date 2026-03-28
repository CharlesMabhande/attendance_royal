<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header('location: ../login.php');
    exit;
}
include('../dbcon.php');
require_once __DIR__ . '/role_helpers.php';
admin_sync_role_from_db($con);
require_admin_permission('staff_directory');

$filter = isset($_GET['status']) ? $_GET['status'] : 'active';
if (!in_array($filter, ['all', 'active', 'left'], true)) {
    $filter = 'active';
}

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

$rows = [];
if ($tablesOk) {
    $where = '1=1';
    if ($filter === 'active') {
        $where = "`status` = 'active'";
    } elseif ($filter === 'left') {
        $where = "`status` = 'left'";
    }
    $q = mysqli_query($con, "SELECT * FROM `school_staff` WHERE $where ORDER BY `full_name` ASC");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $rows[] = $r;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Staff directory | Admin</title>
    <link rel="icon" href="../image/logo-rfjs.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../csss/rfjs-theme.css" type="text/css">
    <link rel="stylesheet" href="../csss/bulk-move-forms.css" type="text/css">
    <style>
        body.bulk-move-page { font-family: system-ui, Segoe UI, sans-serif; background: var(--rfjs-bg-page); min-height: 100vh; color: #333; padding: clamp(10px, 2vw, 20px); }
        .wrap { max-width: min(1200px, 100%); margin: 0 auto; }
        .nav a { color: #fff; font-weight: 600; text-decoration: none; }
        .nav a:hover { text-decoration: underline; }
        .card { background: rgba(255,255,255,.96); border-radius: 16px; padding: 22px; box-shadow: 0 12px 36px rgba(0,0,0,.12); }
        h1 { color: #4a2c7a; font-size: 1.25rem; margin-bottom: 12px; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 16px; }
        .toolbar select { padding: 8px 12px; border-radius: 8px; border: 1px solid #ccc; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; text-decoration: none; font-size: 0.95rem; }
        .btn-primary { background: var(--rfjs-gradient-btn); color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 0.85rem; }
        .btn-danger { background: #c0392b; color: #fff; }
        .btn-outline { background: #fff; color: #333; border: 1px solid #ccc; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th, td { border: 1px solid #e9ecef; padding: 10px 12px; text-align: left; }
        th { background: #f8f9fa; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-left { background: #f8d7da; color: #721c24; }
        .msg { padding: 12px; border-radius: 10px; margin-bottom: 14px; }
        .msg.warn { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .staff-thumb { width: 44px; height: 44px; object-fit: cover; border-radius: 8px; border: 1px solid #e9ecef; vertical-align: middle; background: #f5f5f5; }
        .staff-thumb-ph { display: inline-flex; width: 44px; height: 44px; align-items: center; justify-content: center; border-radius: 8px; background: #f0f0f0; color: #aaa; font-size: 1.1rem; vertical-align: middle; }
    </style>
</head>
<body class="bulk-move-page">
    <div class="wrap">
        <div class="nav" style="margin-bottom:14px;">
            <a href="admindash.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>
        <div class="card">
            <div style="text-align:center;margin-bottom:14px;">
                <img src="../image/logo-rfjs.png" alt="Royal Family Junior School" class="rfjs-logo-img rfjs-logo-crest" width="100" height="100" style="display:block;margin:0 auto;">
            </div>
            <h1><i class="fas fa-id-badge"></i> Teachers &amp; ancillary staff</h1>
            <p style="color:#666;font-size:0.92rem;margin-bottom:14px;line-height:1.5;">
                Record qualifications, roles, level of work, profile photos, and PDF certificates. Use <strong>Edit</strong> to update details anytime. Remove staff who have left (their files are deleted).
            </p>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="msg warn" style="background:#d4edda;border-color:#c3e6cb;color:#155724;">
                    Staff member and their certificate files were removed.
                </div>
            <?php endif; ?>

            <?php if (!$tablesOk): ?>
                <div class="msg warn">
                    <strong>Database tables missing.</strong> Open phpMyAdmin, select your database, and run the script
                    <code>sql/staff_directory.sql</code>, then refresh this page.
                </div>
            <?php else: ?>
                <div class="toolbar">
                    <a class="btn btn-primary" href="staff_edit.php"><i class="fas fa-user-plus"></i> Add staff</a>
                    <form method="get" action="" style="display:flex;align-items:center;gap:8px;">
                        <label for="status">Show</label>
                        <select name="status" id="status" onchange="this.form.submit()">
                            <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active only</option>
                            <option value="left" <?php echo $filter === 'left' ? 'selected' : ''; ?>>Left / former</option>
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Everyone</option>
                        </select>
                    </form>
                </div>

                <?php if (count($rows) === 0): ?>
                    <p style="color:#666;">No staff in this list. Use <strong>Add staff</strong> to begin.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <?php if ($staffPhotoEnabled): ?><th style="width:56px;">Photo</th><?php endif; ?>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Job title</th>
                                    <th>Role</th>
                                    <th>Level</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <?php if ($staffPhotoEnabled): ?>
                                        <td>
                                            <?php if (!empty($r['photo_filename'])): ?>
                                                <img class="staff-thumb" src="staff_photo_get.php?id=<?php echo (int) $r['id']; ?>" alt="" width="44" height="44" loading="lazy">
                                            <?php else: ?>
                                                <span class="staff-thumb-ph" aria-hidden="true"><i class="fas fa-user"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                        <td><strong><?php echo htmlspecialchars((string) $r['full_name']); ?></strong></td>
                                        <td><?php echo $r['staff_category'] === 'teacher' ? 'Teacher' : 'Ancillary'; ?></td>
                                        <td><?php echo htmlspecialchars((string) $r['job_title']); ?></td>
                                        <td><?php echo htmlspecialchars((string) $r['role_at_school']); ?></td>
                                        <td><?php echo htmlspecialchars((string) $r['work_level']); ?></td>
                                        <td>
                                            <?php if ($r['status'] === 'active'): ?>
                                                <span class="badge badge-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-left">Left</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline btn-sm" href="staff_edit.php?id=<?php echo (int) $r['id']; ?>">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
