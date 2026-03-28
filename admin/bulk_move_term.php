<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header('location: ../login.php');
    exit;
}
include('../dbcon.php');
require_once dirname(__DIR__) . '/schema_helpers.php';
require_once __DIR__ . '/role_helpers.php';
admin_sync_role_from_db($con);
require_admin_permission('bulk_term');
if (admin_is_class_scoped()) {
    header('Location: admindash.php?denied=1');
    exit;
}

$message = '';
$messageType = '';
$inserted = 0;
$skipped = 0;

/**
 * @param string $destClass Grade "1"–"7" or "ECD A" / "ECD B" (matches u_class varchar)
 */
function bulk_insert_blank_row(mysqli $con, array $row, string $destClass, int $destTerm, int $destYear): bool
{
    $chk = $con->prepare('SELECT `id` FROM `user_mark` WHERE `u_rollno` = ? AND `u_class` = ? AND `term` = ? AND `year` = ? LIMIT 1');
    if (!$chk) {
        return false;
    }
    $roll = (int) $row['u_rollno'];
    $chk->bind_param('isii', $roll, $destClass, $destTerm, $destYear);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        return false;
    }
    $chk->close();

    $name = $row['u_name'];
    $img = isset($row['u_image']) ? $row['u_image'] : 'default.jpg';
    $sqlIns = rfjs_user_mark_has_published_column($con)
        ? 'INSERT INTO `user_mark` (`u_name`, `u_rollno`, `u_class`, `term`, `year`, `u_mathematics_1`, `u_mathematics_2`, `u_english_1`, `u_english_2`, `u_shona_1`, `u_shona_2`, `u_social_science_1`, `u_social_science_2`, `u_physical_education_arts_1`, `u_physical_education_arts_2`, `u_science_technology_1`, `u_science_technology_2`, `u_total`, `u_position`, `u_image`, `published`) VALUES (?,?,?,?,?,0,0,0,0,0,0,0,0,0,0,0,0,0,0,?,0)'
        : 'INSERT INTO `user_mark` (`u_name`, `u_rollno`, `u_class`, `term`, `year`, `u_mathematics_1`, `u_mathematics_2`, `u_english_1`, `u_english_2`, `u_shona_1`, `u_shona_2`, `u_social_science_1`, `u_social_science_2`, `u_physical_education_arts_1`, `u_physical_education_arts_2`, `u_science_technology_1`, `u_science_technology_2`, `u_total`, `u_position`, `u_image`) VALUES (?,?,?,?,?,0,0,0,0,0,0,0,0,0,0,0,0,0,0,?)';
    $stmt = $con->prepare($sqlIns);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('sisiiis', $name, $roll, $destClass, $destTerm, $destYear, $img);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/** Valid grade/ECD value from form (GET/POST). */
function bulk_term_parse_class($v): ?string
{
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

if (isset($_POST['confirm_move_term']) && isset($_POST['u_class'], $_POST['year'], $_POST['term'])) {
    $classStr = bulk_term_parse_class($_POST['u_class']);
    $year = (int) $_POST['year'];
    $term = (int) $_POST['term'];
    $destTermMode = isset($_POST['dest_term_mode']) ? $_POST['dest_term_mode'] : 'auto';
    $destTermMode = ($destTermMode === 'custom') ? 'custom' : 'auto';

    if ($classStr === null || $year < 2000 || $year > 2100 || $term < 1 || $term > 3) {
        $message = 'Invalid selection.';
        $messageType = 'error';
    } else {
        $destTerm = 0;
        $destYear = 0;
        if ($destTermMode === 'custom') {
            $destTerm = isset($_POST['dest_term']) ? (int) $_POST['dest_term'] : 0;
            $destYear = isset($_POST['dest_year']) ? (int) $_POST['dest_year'] : 0;
            if ($destTerm < 1 || $destTerm > 3 || $destYear < 2000 || $destYear > 2100) {
                $message = 'Invalid destination term or year. Use Term 1–3 and a year between 2000 and 2100.';
                $messageType = 'error';
            }
        } else {
            if ($term < 3) {
                $destTerm = $term + 1;
                $destYear = $year;
            } else {
                $destTerm = 1;
                $destYear = $year + 1;
            }
        }

        if ($message === '') {
            $esc = mysqli_real_escape_string($con, $classStr);
            $sqlList = "SELECT * FROM `user_mark` WHERE `u_class` = '{$esc}' AND `term` = {$term} AND `year` = {$year}";
            $result = mysqli_query($con, $sqlList);

            mysqli_begin_transaction($con);
            try {
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        if (bulk_insert_blank_row($con, $row, $classStr, $destTerm, $destYear)) {
                            $inserted++;
                        } else {
                            $skipped++;
                        }
                    }
                }
                mysqli_commit($con);
                $modeNote = ($destTermMode === 'custom') ? ' (custom term/year)' : ' (automatic next term)';
                $message = "Done. Created {$inserted} new blank record(s) for Term {$destTerm}, {$destYear}{$modeNote}. Skipped {$skipped} (already existed or error). Original records were not changed.";
                $messageType = 'ok';
            } catch (Throwable $e) {
                mysqli_rollback($con);
                $message = 'Operation failed. No changes were saved.';
                $messageType = 'error';
            }
        }
    }
}

$previewCount = null;
$previewDest = null;
$previewClassLabel = null;
if (isset($_GET['preview']) && isset($_GET['u_class'], $_GET['year'], $_GET['term'])) {
    $classStr = bulk_term_parse_class($_GET['u_class']);
    $py = (int) $_GET['year'];
    $pt = (int) $_GET['term'];
    $destTermModeGet = isset($_GET['dest_term_mode']) ? $_GET['dest_term_mode'] : 'auto';
    $destTermModeGet = ($destTermModeGet === 'custom') ? 'custom' : 'auto';
    if ($classStr !== null && $py >= 2000 && $py <= 2100 && $pt >= 1 && $pt <= 3) {
        if ($destTermModeGet === 'custom') {
            $dt = isset($_GET['dest_term']) ? (int) $_GET['dest_term'] : 0;
            $dy = isset($_GET['dest_year']) ? (int) $_GET['dest_year'] : 0;
            if ($dt >= 1 && $dt <= 3 && $dy >= 2000 && $dy <= 2100) {
                $previewDest = ['term' => $dt, 'year' => $dy, 'mode' => 'custom'];
            }
        } else {
            if ($pt < 3) {
                $previewDest = ['term' => $pt + 1, 'year' => $py, 'mode' => 'auto'];
            } else {
                $previewDest = ['term' => 1, 'year' => $py + 1, 'mode' => 'auto'];
            }
        }
        if ($previewDest !== null) {
            $previewClassLabel = ($classStr === 'ECD A' || $classStr === 'ECD B') ? $classStr : 'Grade ' . $classStr;
            $esc = mysqli_real_escape_string($con, $classStr);
            $qr = mysqli_query($con, "SELECT COUNT(*) AS c FROM `user_mark` WHERE `u_class` = '{$esc}' AND `term` = {$pt} AND `year` = {$py}");
            if ($qr) {
                $previewCount = (int) mysqli_fetch_assoc($qr)['c'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Bulk move — next term | Admin</title>
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
        .nav a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .nav a:hover { text-decoration: underline; }
        .bulk-move-card {
            background: rgba(255,255,255,.95);
            border-radius: 16px;
            box-shadow: 0 15px 40px rgba(0,0,0,.15);
        }
        h1 { color: #4a2c7a; }
        .sub { color: #666; }
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
        .hint { color: #555; }
        .preview { background: #fff5f0; }
        .card-logo-wrap { text-align: center; }
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
            <h1><i class="fas fa-calendar-alt"></i> Bulk move to next term</h1>
            <p class="sub">
                For the <strong>grade or ECD group</strong>, year, and <strong>source term</strong> you choose, the system <strong>creates new rows</strong> with the same student name, admission number, and class.
                All marks and positions are left at <strong>0</strong>. Existing rows are <strong>not</strong> modified.
                By default, new rows go to the <strong>automatic next term</strong> (Term 1→2→3, then Term 1 in the next year).
                You can instead set a <strong>specific destination term and year</strong> for the new rows.
            </p>

            <?php if ($message): ?>
                <div class="msg <?php echo $messageType === 'ok' ? 'ok' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php
            $term_class_sel = isset($_GET['u_class']) ? (string) $_GET['u_class'] : '1';
            $g_term_mode = isset($_GET['dest_term_mode']) ? $_GET['dest_term_mode'] : 'auto';
            $g_term_mode = ($g_term_mode === 'custom') ? 'custom' : 'auto';
            $srcYf = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
            $srcTf = isset($_GET['term']) ? (int) $_GET['term'] : 1;
            if ($srcTf < 1 || $srcTf > 3) {
                $srcTf = 1;
            }
            if ($srcTf < 3) {
                $autoNextT = $srcTf + 1;
                $autoNextY = $srcYf;
            } else {
                $autoNextT = 1;
                $autoNextY = $srcYf + 1;
            }
            $g_dest_term_val = isset($_GET['dest_term']) ? (int) $_GET['dest_term'] : $autoNextT;
            $g_dest_year_val = isset($_GET['dest_year']) ? (int) $_GET['dest_year'] : $autoNextY;
            if ($g_dest_term_val < 1 || $g_dest_term_val > 3) {
                $g_dest_term_val = $autoNextT;
            }
            ?>
            <form method="get" action="" id="previewFormTerm">
                <input type="hidden" name="preview" value="1">
                <div class="bulk-sources-row">
                    <div class="bulk-field">
                        <label for="termClassSel">Grade / ECD</label>
                        <select name="u_class" id="termClassSel" required>
                            <option value="ECD A" <?php echo ($term_class_sel === 'ECD A') ? 'selected' : ''; ?>>ECD A</option>
                            <option value="ECD B" <?php echo ($term_class_sel === 'ECD B') ? 'selected' : ''; ?>>ECD B</option>
                            <?php for ($g = 1; $g <= 7; $g++): ?>
                                <option value="<?php echo $g; ?>" <?php echo ($term_class_sel === (string) $g) ? 'selected' : ''; ?>>Grade <?php echo $g; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="bulk-field">
                        <label for="sourceYearTerm">Source year</label>
                        <input type="number" name="year" id="sourceYearTerm" min="2020" max="2100" value="<?php echo isset($_GET['year']) ? (int)$_GET['year'] : (int) date('Y'); ?>" required>
                    </div>
                    <div class="bulk-field">
                        <label for="sourceTermSel">Source term</label>
                        <select name="term" id="sourceTermSel" required>
                            <option value="1" <?php echo (isset($_GET['term']) && (int)$_GET['term'] === 1) ? 'selected' : ''; ?>>Term 1</option>
                            <option value="2" <?php echo (isset($_GET['term']) && (int)$_GET['term'] === 2) ? 'selected' : ''; ?>>Term 2</option>
                            <option value="3" <?php echo (isset($_GET['term']) && (int)$_GET['term'] === 3) ? 'selected' : ''; ?>>Term 3</option>
                        </select>
                    </div>
                </div>
                <div class="bulk-dest-full">
                <fieldset class="term-dest">
                    <legend>Term and year on new records</legend>
                    <label>
                        <input type="radio" name="dest_term_mode" value="auto" <?php echo $g_term_mode === 'auto' ? 'checked' : ''; ?>>
                        Automatic next term (Term 1→2→3; after Term 3 → Term 1 next year)
                    </label>
                    <label>
                        <input type="radio" name="dest_term_mode" value="custom" <?php echo $g_term_mode === 'custom' ? 'checked' : ''; ?>>
                        Set destination term and year…
                    </label>
                    <label class="dest-input" for="destTermTerm">Destination term</label>
                    <select name="dest_term" id="destTermTerm">
                        <option value="1" <?php echo $g_dest_term_val === 1 ? 'selected' : ''; ?>>Term 1</option>
                        <option value="2" <?php echo $g_dest_term_val === 2 ? 'selected' : ''; ?>>Term 2</option>
                        <option value="3" <?php echo $g_dest_term_val === 3 ? 'selected' : ''; ?>>Term 3</option>
                    </select>
                    <label class="dest-input" for="destYearTerm">Year for new records</label>
                    <input type="number" name="dest_year" id="destYearTerm" min="2020" max="2100" value="<?php echo $g_dest_year_val; ?>">
                </fieldset>
                </div>
                <div class="bulk-actions-row">
                <button type="submit" class="btn btn-primary"><i class="fas fa-eye"></i> Preview count</button>
                </div>
            </form>

            <?php if ($previewCount !== null && $previewDest && $previewClassLabel !== null): ?>
                <div class="preview">
                    <strong><?php echo (int) $previewCount; ?></strong> student record(s) in <strong><?php echo htmlspecialchars($previewClassLabel); ?></strong>, source year <?php echo (int) $_GET['year']; ?>, source Term <?php echo (int) $_GET['term']; ?>.
                    New blank records will be created for <strong>Term <?php echo (int) $previewDest['term']; ?>, year <?php echo (int) $previewDest['year']; ?></strong> (same class)<?php if (($previewDest['mode'] ?? '') === 'custom'): ?> — <em>custom destination</em><?php endif; ?>.
                </div>
                <?php if ($previewCount > 0): ?>
                    <form method="post" action="" onsubmit="return confirm('Create <?php echo (int) $previewCount; ?> new blank record(s) for Term <?php echo (int) $previewDest['term']; ?>, <?php echo (int) $previewDest['year']; ?>? Original data will stay unchanged.');">
                        <input type="hidden" name="u_class" value="<?php echo htmlspecialchars($_GET['u_class']); ?>">
                        <input type="hidden" name="year" value="<?php echo (int) $_GET['year']; ?>">
                        <input type="hidden" name="term" value="<?php echo (int) $_GET['term']; ?>">
                        <input type="hidden" name="dest_term_mode" value="<?php echo htmlspecialchars($g_term_mode); ?>">
                        <?php if ($g_term_mode === 'custom'): ?>
                            <input type="hidden" name="dest_term" value="<?php echo (int) $previewDest['term']; ?>">
                            <input type="hidden" name="dest_year" value="<?php echo (int) $previewDest['year']; ?>">
                        <?php endif; ?>
                        <div class="bulk-actions-row">
                        <button type="submit" name="confirm_move_term" value="1" class="btn btn-danger">
                            <i class="fas fa-check"></i> Create new term records
                        </button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <p class="hint">
                If a student already has a row for the destination term and year (same grade and admission number), that student is skipped so duplicates are not created.
            </p>
    <script>
(function () {
    var form = document.getElementById('previewFormTerm');
    if (!form) return;
    var sourceYear = document.getElementById('sourceYearTerm');
    var sourceTerm = document.getElementById('sourceTermSel');
    var destTerm = document.getElementById('destTermTerm');
    var destYear = document.getElementById('destYearTerm');
    function autoNext() {
        var t = parseInt(sourceTerm && sourceTerm.value, 10) || 1;
        var y = parseInt(sourceYear && sourceYear.value, 10) || 0;
        if (t < 3) return { term: t + 1, year: y };
        return { term: 1, year: y + 1 };
    }
    function sync() {
        var custom = form.querySelector('input[name="dest_term_mode"][value="custom"]').checked;
        if (destTerm) destTerm.disabled = !custom;
        if (destYear) destYear.disabled = !custom;
        if (!custom && destTerm && destYear && sourceYear) {
            var a = autoNext();
            destTerm.value = String(a.term);
            destYear.value = String(a.year);
        }
    }
    form.querySelectorAll('input[name="dest_term_mode"]').forEach(function (r) { r.addEventListener('change', sync); });
    if (sourceYear) sourceYear.addEventListener('input', sync);
    if (sourceTerm) sourceTerm.addEventListener('change', sync);
    sync();
})();
    </script>
        </div>
    </div>
</body>
</html>
