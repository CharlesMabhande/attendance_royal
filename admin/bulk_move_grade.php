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
require_admin_permission('bulk_grade');
if (admin_is_class_scoped()) {
    header('Location: admindash.php?denied=1');
    exit;
}

$message = '';
$messageType = '';
$inserted = 0;
$skipped = 0;

function bulk_insert_blank_row_grade(mysqli $con, array $row, int $destClass, int $term, int $year): bool
{
    $chk = $con->prepare('SELECT `id` FROM `user_mark` WHERE `u_rollno` = ? AND `u_class` = ? AND `term` = ? AND `year` = ? LIMIT 1');
    if (!$chk) {
        return false;
    }
    $roll = (int) $row['u_rollno'];
    $chk->bind_param('iiii', $roll, $destClass, $term, $year);
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
    $stmt->bind_param('siiiis', $name, $roll, $destClass, $term, $year, $img);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

if (isset($_POST['confirm_move_grade']) && isset($_POST['u_class'], $_POST['year'], $_POST['term'])) {
    $u_class = (int) $_POST['u_class'];
    $sourceYear = (int) $_POST['year'];
    $sourceTerm = (int) $_POST['term'];
    $destYearMode = isset($_POST['dest_year_mode']) ? $_POST['dest_year_mode'] : 'same';
    $destYear = $sourceYear;
    if ($destYearMode === 'custom' && isset($_POST['dest_year'])) {
        $destYear = (int) $_POST['dest_year'];
    }
    $destTermMode = isset($_POST['dest_term_mode']) ? $_POST['dest_term_mode'] : 'same';
    $destTerm = $sourceTerm;
    if ($destTermMode === 'custom' && isset($_POST['dest_term'])) {
        $destTerm = (int) $_POST['dest_term'];
    }

    if ($u_class < 1 || $u_class > 6 || $sourceYear < 2000 || $sourceYear > 2100 || $sourceTerm < 1 || $sourceTerm > 3) {
        $message = 'Invalid selection. Grade must be 1–6 (Grade 7 cannot be promoted to a higher grade).';
        $messageType = 'error';
    } elseif ($destYear < 2000 || $destYear > 2100) {
        $message = 'Invalid destination year.';
        $messageType = 'error';
    } elseif ($destTerm < 1 || $destTerm > 3) {
        $message = 'Invalid destination term. Choose Term 1, 2, or 3.';
        $messageType = 'error';
    } else {
        $destClass = $u_class + 1;

        $sqlList = "SELECT * FROM `user_mark` WHERE `u_class` = {$u_class} AND `term` = {$sourceTerm} AND `year` = {$sourceYear}";
        $result = mysqli_query($con, $sqlList);

        mysqli_begin_transaction($con);
        try {
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    if (bulk_insert_blank_row_grade($con, $row, $destClass, $destTerm, $destYear)) {
                        $inserted++;
                    } else {
                        $skipped++;
                    }
                }
            }
            mysqli_commit($con);
            $yearNote = ($destYear === $sourceYear)
                ? 'same year as source list'
                : 'year on new records: ' . $destYear . ' (source list year was ' . $sourceYear . ')';
            $termNote = ($destTerm === $sourceTerm)
                ? 'same term as source'
                : 'Term ' . $destTerm . ' (source list was Term ' . $sourceTerm . ')';
            $message = "Done. Created {$inserted} new blank record(s) in Grade {$destClass}, Term {$destTerm}, year {$destYear} ({$termNote}; {$yearNote}). Skipped {$skipped}. Original records were not changed.";
            $messageType = 'ok';
        } catch (Throwable $e) {
            mysqli_rollback($con);
            $message = 'Operation failed. No changes were saved.';
            $messageType = 'error';
        }
    }
}

$previewCount = null;
$previewDestClass = null;
$previewDestYear = null;
$previewDestTerm = null;
if (isset($_GET['preview']) && isset($_GET['u_class'], $_GET['year'], $_GET['term'])) {
    $pc = (int) $_GET['u_class'];
    $py = (int) $_GET['year'];
    $pt = (int) $_GET['term'];
    $destMode = isset($_GET['dest_year_mode']) ? $_GET['dest_year_mode'] : 'same';
    $destY = $py;
    if ($destMode === 'custom' && isset($_GET['dest_year'])) {
        $destY = (int) $_GET['dest_year'];
    }
    $destTermMode = isset($_GET['dest_term_mode']) ? $_GET['dest_term_mode'] : 'same';
    $destT = $pt;
    if ($destTermMode === 'custom' && isset($_GET['dest_term'])) {
        $destT = (int) $_GET['dest_term'];
    }
    if ($pc >= 1 && $pc <= 6 && $py >= 2000 && $py <= 2100 && $pt >= 1 && $pt <= 3 && $destY >= 2000 && $destY <= 2100 && $destT >= 1 && $destT <= 3) {
        $previewDestClass = $pc + 1;
        $previewDestYear = $destY;
        $previewDestTerm = $destT;
        $qr = mysqli_query($con, "SELECT COUNT(*) AS c FROM `user_mark` WHERE `u_class` = {$pc} AND `term` = {$pt} AND `year` = {$py}");
        if ($qr) {
            $previewCount = (int) mysqli_fetch_assoc($qr)['c'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Bulk move — next grade | Admin</title>
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
        .warn { background: #fff3cd; border: 1px solid #ffc107; color: #856404; }
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
            <h1><i class="fas fa-level-up-alt"></i> Bulk move to next grade</h1>
            <p class="sub">
                Choose a <strong>source grade</strong> (Grades 1–6), <strong>source term</strong>, and <strong>source year</strong> (which records to read from). The system creates <strong>new rows</strong> in the <strong>next grade up</strong> with the same name and admission number, and <strong>no marks or positions</strong> (all zero).
                You can set the <strong>destination term</strong> on the new rows (for example move everyone into <strong>Term 1</strong> of the next grade/year) or keep the <strong>same term</strong> as the source list.
                You can keep the <strong>same year</strong> on the new rows or set a <strong>different year</strong> (for example promoting into the next academic year).
                <strong>Grade 7</strong> is not listed: those learners are not promoted to another grade here.
            </p>
            <div class="warn">
                <i class="fas fa-info-circle"></i> Original records stay unchanged. This only adds blank placeholder rows for the new grade.
            </div>

            <?php if ($message): ?>
                <div class="msg <?php echo $messageType === 'ok' ? 'ok' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php
            $g_dest_mode = isset($_GET['dest_year_mode']) ? $_GET['dest_year_mode'] : 'same';
            $g_dest_mode = ($g_dest_mode === 'custom') ? 'custom' : 'same';
            $g_dest_year_val = isset($_GET['dest_year']) ? (int) $_GET['dest_year'] : (isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y'));
            $g_dest_term_mode = isset($_GET['dest_term_mode']) ? $_GET['dest_term_mode'] : 'same';
            $g_dest_term_mode = ($g_dest_term_mode === 'custom') ? 'custom' : 'same';
            $srcTermForDefault = isset($_GET['term']) ? (int) $_GET['term'] : 1;
            if ($srcTermForDefault < 1 || $srcTermForDefault > 3) {
                $srcTermForDefault = 1;
            }
            $g_dest_term_val = isset($_GET['dest_term']) ? (int) $_GET['dest_term'] : $srcTermForDefault;
            if ($g_dest_term_val < 1 || $g_dest_term_val > 3) {
                $g_dest_term_val = $srcTermForDefault;
            }
            ?>
            <form method="get" action="" id="previewForm">
                <input type="hidden" name="preview" value="1">
                <div class="bulk-sources-row">
                    <div class="bulk-field">
                        <label for="sourceGrade">Source grade</label>
                        <select name="u_class" id="sourceGrade" required>
                            <?php for ($g = 1; $g <= 6; $g++): ?>
                                <option value="<?php echo $g; ?>" <?php echo (isset($_GET['u_class']) && (int)$_GET['u_class'] === $g) ? 'selected' : ''; ?>>Grade <?php echo $g; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="bulk-field">
                        <label for="sourceTerm">Source term</label>
                        <select name="term" id="sourceTerm" required>
                            <option value="1" <?php echo (isset($_GET['term']) && (int)$_GET['term'] === 1) ? 'selected' : ''; ?>>Term 1</option>
                            <option value="2" <?php echo (isset($_GET['term']) && (int)$_GET['term'] === 2) ? 'selected' : ''; ?>>Term 2</option>
                            <option value="3" <?php echo (isset($_GET['term']) && (int)$_GET['term'] === 3) ? 'selected' : ''; ?>>Term 3</option>
                        </select>
                    </div>
                    <div class="bulk-field">
                        <label for="sourceYear">Source year</label>
                        <input type="number" name="year" id="sourceYear" min="2020" max="2100" value="<?php echo isset($_GET['year']) ? (int)$_GET['year'] : (int) date('Y'); ?>" required>
                    </div>
                </div>
                <div class="bulk-dest-row">
                <fieldset class="year-choice">
                    <legend>Year on new grade records</legend>
                    <label>
                        <input type="radio" name="dest_year_mode" value="same" <?php echo $g_dest_mode === 'same' ? 'checked' : ''; ?>>
                        Same as source year (no change to year on new rows)
                    </label>
                    <label>
                        <input type="radio" name="dest_year_mode" value="custom" <?php echo $g_dest_mode === 'custom' ? 'checked' : ''; ?>>
                        Use a different year for the new rows…
                    </label>
                    <label class="dest-year-input" for="destYear">Year for new records (when using a different year)</label>
                    <input class="dest-year-input" type="number" name="dest_year" id="destYear" min="2020" max="2100" value="<?php echo $g_dest_year_val; ?>">
                </fieldset>
                <fieldset class="year-choice term-choice">
                    <legend>Term on new grade records</legend>
                    <label>
                        <input type="radio" name="dest_term_mode" value="same" <?php echo $g_dest_term_mode === 'same' ? 'checked' : ''; ?>>
                        Same as source term
                    </label>
                    <label>
                        <input type="radio" name="dest_term_mode" value="custom" <?php echo $g_dest_term_mode === 'custom' ? 'checked' : ''; ?>>
                        Set a different term for the new rows…
                    </label>
                    <label class="dest-year-input" for="destTermSelect">Destination term (Term 1–3)</label>
                    <select name="dest_term" id="destTermSelect">
                        <option value="1" <?php echo $g_dest_term_val === 1 ? 'selected' : ''; ?>>Term 1</option>
                        <option value="2" <?php echo $g_dest_term_val === 2 ? 'selected' : ''; ?>>Term 2</option>
                        <option value="3" <?php echo $g_dest_term_val === 3 ? 'selected' : ''; ?>>Term 3</option>
                    </select>
                </fieldset>
                </div>
                <div class="bulk-actions-row">
                <button type="submit" class="btn btn-primary"><i class="fas fa-eye"></i> Preview count</button>
                </div>
            </form>

            <?php if ($previewCount !== null && $previewDestClass !== null && $previewDestYear !== null && $previewDestTerm !== null): ?>
                <div class="preview">
                    <strong><?php echo (int) $previewCount; ?></strong> student record(s) in Grade <?php echo (int) $_GET['u_class']; ?>, Term <?php echo (int) $_GET['term']; ?>, source year <?php echo (int) $_GET['year']; ?>.
                    New blank records will be created in <strong>Grade <?php echo (int) $previewDestClass; ?></strong>, Term <strong><?php echo (int) $previewDestTerm; ?></strong>, year <strong><?php echo (int) $previewDestYear; ?></strong><?php if ((int) $_GET['year'] !== (int) $previewDestYear): ?> (source year was <?php echo (int) $_GET['year']; ?>)<?php endif; ?><?php if ((int) $_GET['term'] !== (int) $previewDestTerm): ?> (source term was <?php echo (int) $_GET['term']; ?>)<?php endif; ?>.
                </div>
                <?php if ($previewCount > 0): ?>
                    <form method="post" action="" onsubmit="return confirm('Create <?php echo (int) $previewCount; ?> new blank record(s) in Grade <?php echo (int) $previewDestClass; ?>, Term <?php echo (int) $previewDestTerm; ?>, year <?php echo (int) $previewDestYear; ?>?');">
                        <input type="hidden" name="u_class" value="<?php echo (int) $_GET['u_class']; ?>">
                        <input type="hidden" name="year" value="<?php echo (int) $_GET['year']; ?>">
                        <input type="hidden" name="term" value="<?php echo (int) $_GET['term']; ?>">
                        <input type="hidden" name="dest_year_mode" value="<?php echo htmlspecialchars($g_dest_mode); ?>">
                        <input type="hidden" name="dest_term_mode" value="<?php echo htmlspecialchars($g_dest_term_mode); ?>">
                        <?php if ($g_dest_mode === 'custom'): ?>
                            <input type="hidden" name="dest_year" value="<?php echo (int) $previewDestYear; ?>">
                        <?php endif; ?>
                        <?php if ($g_dest_term_mode === 'custom'): ?>
                            <input type="hidden" name="dest_term" value="<?php echo (int) $previewDestTerm; ?>">
                        <?php endif; ?>
                        <div class="bulk-actions-row">
                        <button type="submit" name="confirm_move_grade" value="1" class="btn btn-danger">
                            <i class="fas fa-check"></i> Create next-grade records
                        </button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <p class="hint">
                Skips students who already have a row for the same admission number, destination grade, destination term, and year (on the new records).
            </p>
        </div>
    </div>
    <script>
(function () {
    var form = document.getElementById('previewForm');
    if (!form) return;
    var yearRadios = form.querySelectorAll('input[name="dest_year_mode"]');
    var termRadios = form.querySelectorAll('input[name="dest_term_mode"]');
    var sourceYear = document.getElementById('sourceYear');
    var destYear = document.getElementById('destYear');
    var sourceTerm = document.getElementById('sourceTerm');
    var destTermSelect = document.getElementById('destTermSelect');
    function syncYear() {
        var custom = form.querySelector('input[name="dest_year_mode"][value="custom"]').checked;
        if (destYear) {
            destYear.disabled = !custom;
            if (!custom && sourceYear) destYear.value = sourceYear.value;
        }
    }
    function syncTerm() {
        var custom = form.querySelector('input[name="dest_term_mode"][value="custom"]').checked;
        if (destTermSelect) {
            destTermSelect.disabled = !custom;
            if (!custom && sourceTerm) destTermSelect.value = sourceTerm.value;
        }
    }
    function syncAll() {
        syncYear();
        syncTerm();
    }
    yearRadios.forEach(function (r) { r.addEventListener('change', syncAll); });
    termRadios.forEach(function (r) { r.addEventListener('change', syncAll); });
    if (sourceYear) sourceYear.addEventListener('input', function () {
        if (!form.querySelector('input[name="dest_year_mode"][value="custom"]').checked && destYear) {
            destYear.value = sourceYear.value;
        }
    });
    if (sourceTerm) sourceTerm.addEventListener('change', function () {
        if (!form.querySelector('input[name="dest_term_mode"][value="custom"]').checked && destTermSelect) {
            destTermSelect.value = sourceTerm.value;
        }
    });
    syncAll();
})();
    </script>
</body>
</html>
