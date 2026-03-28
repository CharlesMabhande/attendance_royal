<?php
/**
 * Publish or unpublish marks for the public results portal (result.php).
 * Super administrators (role full) only.
 */
session_start();
if (!isset($_SESSION['uid'])) {
    header('location: ../login.php');
    exit;
}
include('../dbcon.php');
require_once dirname(__DIR__) . '/schema_helpers.php';
require_once __DIR__ . '/role_helpers.php';
admin_sync_role_from_db($con);
require_full_admin_role();

$schema_has_published = rfjs_user_mark_has_published_column($con);

$message = '';
$messageType = '';
if (isset($_SESSION['publish_flash_ok'])) {
    $message = $_SESSION['publish_flash_ok'];
    $messageType = 'ok';
    unset($_SESSION['publish_flash_ok']);
}

function publish_parse_class($v): ?string
{
    if ($v === '' || $v === '__all__') {
        return '';
    }
    if ($v === 'ECD A' || $v === 'ECD B') {
        return $v;
    }
    if (is_numeric($v)) {
        $g = (int) $v;
        if ($g >= 1 && $g <= 7) {
            return (string) $g;
        }
    }
    return null;
}

if ($schema_has_published && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = (string) $_POST['action'];

    if ($action === 'toggle_one') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $pub = isset($_POST['publish_to']) ? (int) $_POST['publish_to'] : 0;
        $pub = $pub === 1 ? 1 : 0;
        $rq = isset($_POST['redirect_query']) ? (string) $_POST['redirect_query'] : '';
        if ($id > 0) {
            $st = $con->prepare('UPDATE `user_mark` SET `published` = ? WHERE `id` = ?');
            if ($st) {
                $st->bind_param('ii', $pub, $id);
                $st->execute();
                $st->close();
                $_SESSION['publish_flash_ok'] = $pub === 1
                    ? 'That learner\'s result is now published on the public portal.'
                    : 'That learner\'s result is now hidden from the public portal.';
            }
        }
        header('Location: publish_results.php' . ($rq !== '' ? '?' . $rq : ''));
        exit;
    }

    $year = isset($_POST['year']) ? (int) $_POST['year'] : 0;
    $term = isset($_POST['term']) ? (int) $_POST['term'] : 0;
    $classRaw = isset($_POST['u_class']) ? (string) $_POST['u_class'] : '';
    $classStr = publish_parse_class($classRaw);

    if ($classStr === null || $year < 2000 || $year > 2100 || $term < 1 || $term > 3) {
        $message = 'Invalid year, term, or grade selection.';
        $messageType = 'error';
    } elseif ($action === 'publish' || $action === 'unpublish') {
        $pub = $action === 'publish' ? 1 : 0;
        $st = null;
        if ($classStr === '') {
            $st = $con->prepare('UPDATE `user_mark` SET `published` = ? WHERE `year` = ? AND `term` = ?');
            if ($st) {
                $st->bind_param('iii', $pub, $year, $term);
            }
        } else {
            $st = $con->prepare('UPDATE `user_mark` SET `published` = ? WHERE `year` = ? AND `term` = ? AND `u_class` = ?');
            if ($st) {
                $st->bind_param('iiis', $pub, $year, $term, $classStr);
            }
        }
        if ($st && $st->execute()) {
            $n = $st->affected_rows;
            $st->close();
            $message = ($action === 'publish' ? 'Published' : 'Unpublished') . " {$n} record(s) for Term {$term}, {$year}" . ($classStr !== '' ? ' (' . htmlspecialchars($classStr) . ')' : ' (all grades).');
            $messageType = 'ok';
        } else {
            if ($st) {
                $st->close();
            }
            $message = 'Update failed.';
            $messageType = 'error';
        }
    }
}

$selYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$selTerm = isset($_GET['term']) ? (int) $_GET['term'] : 1;
$selClass = isset($_GET['u_class']) ? (string) $_GET['u_class'] : '';
$searchQ = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$searchRoll = isset($_GET['roll']) ? trim((string) $_GET['roll']) : '';

$individualRows = [];
$individualListLimit = 400;

$countPub = 0;
$countUnpub = 0;
$classStr = publish_parse_class(($selClass === '' || $selClass === '__all__') ? '__all__' : $selClass);
if ($schema_has_published && $classStr !== null && $selYear >= 2000 && $selYear <= 2100 && $selTerm >= 1 && $selTerm <= 3) {
    if ($classStr === '') {
        $q1 = $con->prepare('SELECT COUNT(*) AS c FROM `user_mark` WHERE `year` = ? AND `term` = ? AND `published` = 1');
        $q2 = $con->prepare('SELECT COUNT(*) AS c FROM `user_mark` WHERE `year` = ? AND `term` = ? AND `published` = 0');
        if ($q1) {
            $q1->bind_param('ii', $selYear, $selTerm);
            $q1->execute();
            $r = $q1->get_result()->fetch_assoc();
            $countPub = (int) ($r['c'] ?? 0);
            $q1->close();
        }
        if ($q2) {
            $q2->bind_param('ii', $selYear, $selTerm);
            $q2->execute();
            $r = $q2->get_result()->fetch_assoc();
            $countUnpub = (int) ($r['c'] ?? 0);
            $q2->close();
        }
    } else {
        $q1 = $con->prepare('SELECT COUNT(*) AS c FROM `user_mark` WHERE `year` = ? AND `term` = ? AND `u_class` = ? AND `published` = 1');
        $q2 = $con->prepare('SELECT COUNT(*) AS c FROM `user_mark` WHERE `year` = ? AND `term` = ? AND `u_class` = ? AND `published` = 0');
        if ($q1) {
            $q1->bind_param('iis', $selYear, $selTerm, $classStr);
            $q1->execute();
            $r = $q1->get_result()->fetch_assoc();
            $countPub = (int) ($r['c'] ?? 0);
            $q1->close();
        }
        if ($q2) {
            $q2->bind_param('iis', $selYear, $selTerm, $classStr);
            $q2->execute();
            $r = $q2->get_result()->fetch_assoc();
            $countUnpub = (int) ($r['c'] ?? 0);
            $q2->close();
        }
    }
}

$redirectQuery = http_build_query($_GET);
if ($schema_has_published && $classStr !== null && $selYear >= 2000 && $selYear <= 2100 && $selTerm >= 1 && $selTerm <= 3) {
    $where = ['`year` = ?', '`term` = ?'];
    $types = 'ii';
    $bind = [$selYear, $selTerm];
    if ($classStr !== '') {
        $where[] = '`u_class` = ?';
        $types .= 's';
        $bind[] = $classStr;
    }
    if ($searchQ !== '') {
        $where[] = '`u_name` LIKE ?';
        $types .= 's';
        $bind[] = '%' . $searchQ . '%';
    }
    if ($searchRoll !== '' && ctype_digit($searchRoll)) {
        $where[] = '`u_rollno` = ?';
        $types .= 'i';
        $bind[] = (int) $searchRoll;
    }
    $sqlList = 'SELECT `id`, `u_name`, `u_rollno`, `u_class`, `term`, `year`, `published` FROM `user_mark` WHERE ' . implode(' AND ', $where)
        . ' ORDER BY `u_class` ASC, `u_name` ASC, `u_rollno` ASC LIMIT ' . (int) $individualListLimit;
    $stList = $con->prepare($sqlList);
    if ($stList) {
        rfjs_mysqli_stmt_bind_params($stList, $types, $bind);
        $stList->execute();
        $resList = $stList->get_result();
        if ($resList) {
            while ($row = $resList->fetch_assoc()) {
                $individualRows[] = $row;
            }
        }
        $stList->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Publish results — Admin</title>
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
        h1 { color: #4a2c7a; }
        .sub { color: #666; margin-bottom: 16px; line-height: 1.5; }
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
        .stats { display: flex; gap: 16px; flex-wrap: wrap; margin: 16px 0; }
        .stat { background: #f8f9fa; padding: 12px 16px; border-radius: 10px; font-size: 0.95rem; }
        .card-logo-wrap { text-align: center; }
        label { display: block; font-weight: 600; margin: 12px 0 6px; color: #444; font-size: 0.92rem; }
        select, input { padding: 10px 12px; border-radius: 8px; border: 1px solid #ccc; font-size: 1rem; max-width: 100%; }
        h2 { font-size: 1.15rem; color: #4a2c7a; margin: 28px 0 12px; }
        .ind-table-wrap { overflow-x: auto; margin-top: 12px; border-radius: 10px; border: 1px solid #e0e0e0; }
        table.ind-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        table.ind-table th, table.ind-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        table.ind-table th { background: #f5f5f5; font-weight: 600; }
        table.ind-table tr:hover { background: #fafafa; }
        .btn-sm { padding: 6px 12px; font-size: 0.85rem; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; }
        .btn-sm-pub { background: #27ae60; color: #fff; }
        .btn-sm-unpub { background: #c0392b; color: #fff; }
        .badge-pub { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; background: #d4edda; color: #155724; }
        .badge-hid { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; background: #f8d7da; color: #721c24; }
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
            <h1><i class="fas fa-globe"></i> Publish results (public portal)</h1>
            <p class="sub">
                Only <strong>super administrators</strong> can use this page. Parents and students see marks on the home portal only when records are <strong>published</strong> for that term and year.
                New marks are <strong>not</strong> public until you publish them. Use <strong>Publish all</strong> for a whole grade/term, or the <strong>individual list</strong> below to publish or unpublish one learner at a time.
            </p>

            <?php if (!$schema_has_published): ?>
                <div class="msg error" style="line-height:1.6;">
                    <strong>Database update required.</strong> The <code>published</code> column is not on <code>user_mark</code> yet.
                    In <strong>phpMyAdmin</strong>, select your database, open the SQL tab, and run the contents of
                    <code>sql/alter_user_mark_published.sql</code> in your project folder (or paste the <code>ALTER TABLE</code> statement below), then reload this page.
                    <pre style="margin-top:12px;padding:12px;background:#fff;border-radius:8px;overflow:auto;font-size:0.85rem;">ALTER TABLE `user_mark`
  ADD COLUMN `published` tinyint(1) NOT NULL DEFAULT 0
  COMMENT '1 = visible on public result.php; super-admins only may publish'
  AFTER `u_image`;
</pre>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="msg <?php echo $messageType === 'ok' ? 'ok' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="get" class="bulk-move-form" style="margin-bottom: 20px;">
                <label for="year">Year</label>
                <select name="year" id="year">
                    <?php for ($y = (int) date('Y') + 1; $y >= 2020; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $selYear === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
                <label for="term">Term</label>
                <select name="term" id="term">
                    <option value="1" <?php echo $selTerm === 1 ? 'selected' : ''; ?>>Term 1</option>
                    <option value="2" <?php echo $selTerm === 2 ? 'selected' : ''; ?>>Term 2</option>
                    <option value="3" <?php echo $selTerm === 3 ? 'selected' : ''; ?>>Term 3</option>
                </select>
                <label for="u_class">Grade / cohort</label>
                <select name="u_class" id="u_class">
                    <option value="__all__" <?php echo ($selClass === '' || $selClass === '__all__') ? 'selected' : ''; ?>>All grades</option>
                    <option value="ECD A" <?php echo $selClass === 'ECD A' ? 'selected' : ''; ?>>ECD A</option>
                    <option value="ECD B" <?php echo $selClass === 'ECD B' ? 'selected' : ''; ?>>ECD B</option>
                    <?php for ($g = 1; $g <= 7; $g++): ?>
                        <option value="<?php echo $g; ?>" <?php echo $selClass === (string) $g ? 'selected' : ''; ?>>Grade <?php echo $g; ?></option>
                    <?php endfor; ?>
                </select>
                <label for="q">Search learner name (optional)</label>
                <input type="text" name="q" id="q" value="<?php echo htmlspecialchars($searchQ); ?>" placeholder="Part of name" autocomplete="off">
                <label for="roll">Admission number (optional)</label>
                <input type="text" name="roll" id="roll" value="<?php echo htmlspecialchars($searchRoll); ?>" placeholder="Exact number" inputmode="numeric" autocomplete="off">
                <div style="margin-top: 14px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-sync"></i> Apply filters &amp; refresh list</button>
                </div>
            </form>

            <?php if ($schema_has_published): ?>
            <div class="stats">
                <div class="stat"><strong>Published</strong> (visible on portal): <?php echo (int) $countPub; ?></div>
                <div class="stat"><strong>Not published</strong> (hidden): <?php echo (int) $countUnpub; ?></div>
            </div>

            <form method="post" onsubmit="return confirm('Publish all matching marks for this year, term, and grade filter?');" style="display:inline-block;margin-right:10px;">
                <input type="hidden" name="u_class" value="<?php echo htmlspecialchars($selClass === '' ? '__all__' : $selClass); ?>">
                <input type="hidden" name="year" value="<?php echo (int) $selYear; ?>">
                <input type="hidden" name="term" value="<?php echo (int) $selTerm; ?>">
                <input type="hidden" name="action" value="publish">
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Publish all in filter</button>
            </form>
            <form method="post" onsubmit="return confirm('Unpublish all matching marks? They will disappear from the public portal.');" style="display:inline-block;">
                <input type="hidden" name="u_class" value="<?php echo htmlspecialchars($selClass === '' ? '__all__' : $selClass); ?>">
                <input type="hidden" name="year" value="<?php echo (int) $selYear; ?>">
                <input type="hidden" name="term" value="<?php echo (int) $selTerm; ?>">
                <input type="hidden" name="action" value="unpublish">
                <button type="submit" class="btn btn-danger"><i class="fas fa-eye-slash"></i> Unpublish all in filter</button>
            </form>

            <?php if ($classStr !== null): ?>
            <h2><i class="fas fa-user"></i> Individuals (publish or unpublish one at a time)</h2>
            <p class="sub" style="margin-bottom:8px;">
                The table uses the same year, term, grade, and optional name/admission filters as above. Up to <strong><?php echo (int) $individualListLimit; ?></strong> rows are shown.
                You can also change visibility per learner on <strong>Add / update marks</strong> (<code>addmark.php</code>).
            </p>
            <?php if (count($individualRows) === 0): ?>
                <p class="hint" style="color:#666;">No records match these filters.</p>
            <?php else: ?>
                <div class="ind-table-wrap">
                    <table class="ind-table">
                        <thead>
                            <tr>
                                <th>Learner</th>
                                <th>Admission</th>
                                <th>Grade</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($individualRows as $row): ?>
                                <?php
                                $rid = (int) $row['id'];
                                $isPub = isset($row['published']) ? (int) $row['published'] === 1 : false;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $row['u_name']); ?></td>
                                    <td><?php echo (int) $row['u_rollno']; ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['u_class']); ?></td>
                                    <td><?php echo $isPub ? '<span class="badge-pub">Published</span>' : '<span class="badge-hid">Not published</span>'; ?></td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_one">
                                            <input type="hidden" name="id" value="<?php echo $rid; ?>">
                                            <input type="hidden" name="publish_to" value="<?php echo $isPub ? '0' : '1'; ?>">
                                            <input type="hidden" name="redirect_query" value="<?php echo htmlspecialchars($redirectQuery); ?>">
                                            <?php if ($isPub): ?>
                                                <button type="submit" class="btn-sm btn-sm-unpub" onclick="return confirm('Hide this learner\'s results from the public portal?');">Unpublish</button>
                                            <?php else: ?>
                                                <button type="submit" class="btn-sm btn-sm-pub" onclick="return confirm('Show this learner\'s results on the public portal?');">Publish</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
