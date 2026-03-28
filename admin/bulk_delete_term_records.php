<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header('location: ../login.php');
    exit;
}
include('../dbcon.php');
require_once __DIR__ . '/role_helpers.php';
admin_sync_role_from_db($con);
require_full_admin_role();
if (admin_is_class_scoped()) {
    header('Location: admindash.php?denied=1');
    exit;
}

/**
 * @return string[] e.g. ['1','2','7','ECD A','ECD B'] sorted for display
 */
function rfjs_class_filter_options(): array
{
    $o = ['ECD A', 'ECD B'];
    for ($g = 1; $g <= 7; $g++) {
        $o[] = (string) $g;
    }
    return $o;
}

/** @return null = all classes; string = single u_class value */
function rfjs_parse_bulk_class_filter($v): ?string
{
    if ($v === null || $v === '' || $v === 'all') {
        return null;
    }
    $v = trim((string) $v);
    if ($v === 'ECD A' || $v === 'ECD B') {
        return $v;
    }
    if (preg_match('/^[1-7]$/', $v)) {
        return (string) (int) $v;
    }
    return null;
}

/** @return int[] 1–3 */
function rfjs_parse_terms_from_request(array $src): array
{
    $terms = [];
    foreach ([1, 2, 3] as $t) {
        if (!empty($src['term_' . $t])) {
            $terms[] = $t;
        }
    }
    sort($terms);
    return array_values(array_unique($terms));
}

$message = '';
$messageType = '';
$deletedCount = null;
$previewTotal = null;
$previewBreakdown = [];
$lastYear = null;
$lastTerms = [];
$lastClassFilter = null;

// POST: permanent delete
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === '1') {
    $year = isset($_POST['year']) ? (int) $_POST['year'] : 0;
    $terms = rfjs_parse_terms_from_request($_POST);
    $cfRaw = isset($_POST['class_filter']) ? (string) $_POST['class_filter'] : 'all';
    $classFilter = rfjs_parse_bulk_class_filter($cfRaw);

    if ($year < 2000 || $year > 2100 || count($terms) === 0) {
        $message = 'Invalid year or no terms selected.';
        $messageType = 'error';
    } elseif ($classFilter === null && $cfRaw !== 'all' && trim($cfRaw) !== '') {
        $message = 'Invalid class filter.';
        $messageType = 'error';
    } else {
        $in = implode(',', array_map('intval', $terms));
        $sql = "DELETE FROM `user_mark` WHERE `year` = " . (int) $year . " AND `term` IN ($in)";
        if ($classFilter !== null) {
            $esc = mysqli_real_escape_string($con, $classFilter);
            $sql .= " AND `u_class` = '$esc'";
        }

        mysqli_begin_transaction($con);
        try {
            mysqli_query($con, $sql);
            if (mysqli_errno($con)) {
                throw new RuntimeException(mysqli_error($con));
            }
            $deletedCount = mysqli_affected_rows($con);
            mysqli_commit($con);
            $message = "Deleted {$deletedCount} row(s) from user_mark for year {$year}, term(s) " . implode(', ', $terms) . '.';
            if ($classFilter !== null) {
                $message .= ' Class filter: ' . htmlspecialchars($classFilter) . '.';
            }
            $messageType = 'ok';
        } catch (Throwable $e) {
            mysqli_rollback($con);
            $message = 'Delete failed. No changes were saved.';
            $messageType = 'error';
        }
    }
}

// GET preview
if (isset($_GET['preview']) && $_GET['preview'] === '1') {
    $year = isset($_GET['year']) ? (int) $_GET['year'] : 0;
    $terms = rfjs_parse_terms_from_request($_GET);
    $cfRaw = isset($_GET['class_filter']) ? $_GET['class_filter'] : 'all';
    $classFilter = rfjs_parse_bulk_class_filter($cfRaw);

    if ($year < 2000 || $year > 2100 || count($terms) === 0) {
        $message = 'Choose a year and at least one term.';
        $messageType = 'error';
    } elseif ($classFilter === null && $cfRaw !== 'all' && trim((string) $cfRaw) !== '') {
        $message = 'Invalid class filter.';
        $messageType = 'error';
    } else {
        $in = implode(',', array_map('intval', $terms));
        $sql = "SELECT `u_class`, COUNT(*) AS `c` FROM `user_mark` WHERE `year` = " . (int) $year . " AND `term` IN ($in)";
        if ($classFilter !== null) {
            $esc = mysqli_real_escape_string($con, $classFilter);
            $sql .= " AND `u_class` = '$esc'";
        }
        $sql .= ' GROUP BY `u_class` ORDER BY `u_class`';

        $res = mysqli_query($con, $sql);
        if (!$res) {
            $message = 'Could not load preview: ' . htmlspecialchars(mysqli_error($con));
            $messageType = 'error';
        } else {
            $previewTotal = 0;
            while ($row = mysqli_fetch_assoc($res)) {
                $previewBreakdown[] = ['class' => $row['u_class'], 'count' => (int) $row['c']];
                $previewTotal += (int) $row['c'];
            }
            mysqli_free_result($res);
            $lastYear = $year;
            $lastTerms = $terms;
            $lastClassFilter = $cfRaw;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Bulk delete term records | Admin</title>
    <link rel="icon" href="../image/logo-rfjs.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../csss/rfjs-theme.css" type="text/css">
    <link rel="stylesheet" href="../csss/bulk-move-forms.css" type="text/css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body.bulk-move-page {
            font-family: system-ui, Segoe UI, sans-serif;
            background: var(--rfjs-bg-page);
            min-height: 100vh;
            min-height: 100dvh;
            color: #333;
        }
        .nav { margin-bottom: 12px; }
        .nav a { color: #fff; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
        .nav a:hover { text-decoration: underline; }
        .bulk-move-card {
            background: rgba(255,255,255,.95);
            border-radius: 16px;
            box-shadow: 0 15px 40px rgba(0,0,0,.15);
        }
        h1 { color: #4a2c7a; font-size: 1.22rem; margin-bottom: 0.4rem; }
        .sub { color: #666; font-size: 0.9rem; line-height: 1.45; margin-bottom: 12px; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 20px; border: none; border-radius: 10px; font-weight: 700;
            cursor: pointer; font-size: 0.98rem;
        }
        .btn-primary { background: var(--rfjs-gradient-btn); color: #fff; }
        .btn-danger { background: #c0392b; color: #fff; }
        .msg { padding: 12px; border-radius: 10px; margin-bottom: 12px; font-size: 0.92rem; }
        .msg.ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warn { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 12px; border-radius: 10px; margin-bottom: 14px; font-size: 0.88rem; line-height: 1.45; }
        .preview-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; margin-top: 10px; }
        .preview-table th, .preview-table td { border: 1px solid #dee2e6; padding: 8px 10px; text-align: left; }
        .preview-table th { background: #f8f9fa; }
        .term-check { display: flex; flex-wrap: wrap; gap: 16px; margin-top: 6px; }
        .term-check label { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer; }
        .card-logo-wrap { text-align: center; margin-bottom: 10px; }
        .bulk-move-card label { display: block; font-weight: 600; margin: 12px 0 6px; color: #444; font-size: 0.92rem; }
        .bulk-move-card select, .bulk-move-card input[type="number"] {
            width: 100%; max-width: 320px; padding: 10px 12px; border: 1px solid #ccc; border-radius: 10px; font-size: 1rem;
        }
        .bulk-actions-row { margin-top: 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    </style>
</head>
<body class="bulk-move-page">
    <div class="wrap bulk-move-wrap">
        <div class="nav">
            <a href="admindash.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>
        <div class="card bulk-move-card">
            <div class="card-logo-wrap">
                <img src="../image/logo-rfjs.png" alt="Royal Family Junior School" class="rfjs-logo-img rfjs-logo-crest" width="100" height="100" style="display:block;margin:0 auto 10px;">
            </div>
            <h1><i class="fas fa-calendar-times"></i> Bulk delete term records</h1>
            <p class="sub">
                <strong>Super administrators only.</strong> Permanently remove rows from <code>user_mark</code> that match the calendar <strong>year</strong>, selected <strong>term(s)</strong>, and optional <strong>class</strong>.
                This cannot be undone. Use preview to see counts before deleting.
            </p>
            <div class="warn">
                <i class="fas fa-exclamation-triangle"></i>
                Deleting removes marks, positions, and all subject data for matching students for those term(s). Parents will no longer see those results online.
            </div>

            <?php if ($message): ?>
                <div class="msg <?php echo $messageType === 'ok' ? 'ok' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php
            $gy = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
            $gcf = isset($_GET['class_filter']) ? (string) $_GET['class_filter'] : 'all';
            ?>

            <form method="get" action="">
                <input type="hidden" name="preview" value="1">
                <label for="year_in">Year</label>
                <input type="number" name="year" id="year_in" min="2000" max="2100" value="<?php echo $gy; ?>" required>

                <label>Term(s) to include</label>
                <div class="term-check">
                    <label><input type="checkbox" name="term_1" value="1" <?php echo (!isset($_GET['preview']) || !empty($_GET['term_1'])) ? 'checked' : ''; ?>> Term 1</label>
                    <label><input type="checkbox" name="term_2" value="1" <?php echo (!isset($_GET['preview']) || !empty($_GET['term_2'])) ? 'checked' : ''; ?>> Term 2</label>
                    <label><input type="checkbox" name="term_3" value="1" <?php echo (!isset($_GET['preview']) || !empty($_GET['term_3'])) ? 'checked' : ''; ?>> Term 3</label>
                </div>

                <label for="class_filter_in">Class</label>
                <select name="class_filter" id="class_filter_in">
                    <option value="all" <?php echo $gcf === 'all' ? 'selected' : ''; ?>>All classes</option>
                    <?php foreach (rfjs_class_filter_options() as $copt): ?>
                        <option value="<?php echo htmlspecialchars($copt); ?>" <?php echo $gcf === $copt ? 'selected' : ''; ?>><?php echo htmlspecialchars($copt === 'ECD A' || $copt === 'ECD B' ? $copt : 'Grade ' . $copt); ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="bulk-actions-row">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Preview counts</button>
                </div>
            </form>

            <?php if ($previewTotal !== null && $lastYear !== null && count($lastTerms) > 0): ?>
                <div style="margin-top: 18px; padding: 14px; background: #f8f9fa; border-radius: 10px; border: 1px solid #e9ecef;">
                    <strong>Preview</strong> — Year <strong><?php echo (int) $lastYear; ?></strong>,
                    term(s) <strong><?php echo htmlspecialchars(implode(', ', $lastTerms)); ?></strong>
                    <?php if ($lastClassFilter !== null && $lastClassFilter !== 'all'): ?>
                        — class <strong><?php echo htmlspecialchars($lastClassFilter); ?></strong>
                    <?php else: ?>
                        — <em>all classes</em>
                    <?php endif; ?>
                    <p style="margin: 10px 0 6px; font-size: 1.1rem;"><strong>Total rows: <?php echo (int) $previewTotal; ?></strong></p>
                    <?php if (count($previewBreakdown) > 0): ?>
                        <table class="preview-table">
                            <tr><th>Class (u_class)</th><th>Rows</th></tr>
                            <?php foreach ($previewBreakdown as $br): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $br['class']); ?></td>
                                    <td><?php echo (int) $br['count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>

                <?php if ($previewTotal > 0): ?>
                    <form method="post" action="" style="margin-top: 16px;" onsubmit="return confirm('Permanently DELETE <?php echo (int) $previewTotal; ?> row(s)? This cannot be undone.');">
                        <input type="hidden" name="year" value="<?php echo (int) $lastYear; ?>">
                        <?php foreach ($lastTerms as $t): ?>
                            <input type="hidden" name="term_<?php echo (int) $t; ?>" value="1">
                        <?php endforeach; ?>
                        <input type="hidden" name="class_filter" value="<?php echo htmlspecialchars((string) $lastClassFilter); ?>">
                        <input type="hidden" name="confirm_delete" value="1">
                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete <?php echo (int) $previewTotal; ?> row(s) permanently</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
