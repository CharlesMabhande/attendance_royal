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
require_admin_permission('bulk_ecd');
if (admin_is_class_scoped()) {
    header('Location: admindash.php?denied=1');
    exit;
}

$message = '';
$messageType = '';
$inserted = 0;
$skipped = 0;

/**
 * New row: blank marks/position; u_class as string (ECD B, ECD A, or 1).
 */
function bulk_insert_blank_ecd(mysqli $con, array $row, string $destClass, int $destTerm, int $destYear): bool
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
    $stmt->bind_param('sisiis', $name, $roll, $destClass, $destTerm, $destYear, $img);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function ecd_dest_class(string $src): ?string
{
    if ($src === 'ECD A') {
        return 'ECD B';
    }
    if ($src === 'ECD B') {
        return '1';
    }
    return null;
}

if (isset($_POST['confirm_ecd_year']) && isset($_POST['ecd_src'], $_POST['year'], $_POST['term'])) {
    $ecdSrc = $_POST['ecd_src'];
    $sourceYear = (int) $_POST['year'];
    $term = (int) $_POST['term'];
    $destYearMode = isset($_POST['dest_year_mode']) ? $_POST['dest_year_mode'] : 'next';
    if (!in_array($destYearMode, ['next', 'same', 'custom'], true)) {
        $destYearMode = 'next';
    }
    $destYear = $sourceYear + 1;
    if ($destYearMode === 'same') {
        $destYear = $sourceYear;
    } elseif ($destYearMode === 'custom' && isset($_POST['dest_year'])) {
        $destYear = (int) $_POST['dest_year'];
    }

    $destTermMode = isset($_POST['dest_term_mode']) ? $_POST['dest_term_mode'] : 'term1';
    if (!in_array($destTermMode, ['term1', 'same', 'custom'], true)) {
        $destTermMode = 'term1';
    }
    $destTerm = 1;
    if ($destTermMode === 'same') {
        $destTerm = $term;
    } elseif ($destTermMode === 'custom') {
        $destTerm = isset($_POST['dest_term']) ? (int) $_POST['dest_term'] : 0;
    }

    if (!in_array($ecdSrc, ['ECD A', 'ECD B'], true) || $sourceYear < 2000 || $sourceYear > 2100 || $term < 1 || $term > 3) {
        $message = 'Invalid selection.';
        $messageType = 'error';
    } elseif ($destYearMode === 'custom' && !isset($_POST['dest_year'])) {
        $message = 'Please provide the year for new records.';
        $messageType = 'error';
    } elseif ($destYear < 2000 || $destYear > 2100) {
        $message = 'Invalid destination year.';
        $messageType = 'error';
    } elseif ($destTerm < 1 || $destTerm > 3) {
        $message = 'Invalid destination term. Choose Term 1, 2, or 3.';
        $messageType = 'error';
    } else {
        $destClass = ecd_dest_class($ecdSrc);

        $esc = mysqli_real_escape_string($con, $ecdSrc);
        $sqlList = "SELECT * FROM `user_mark` WHERE `u_class` = '{$esc}' AND `term` = {$term} AND `year` = {$sourceYear}";
        $result = mysqli_query($con, $sqlList);

        mysqli_begin_transaction($con);
        try {
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    if (bulk_insert_blank_ecd($con, $row, $destClass, $destTerm, $destYear)) {
                        $inserted++;
                    } else {
                        $skipped++;
                    }
                }
            }
            mysqli_commit($con);
            $destLabel = $destClass === '1' ? 'Grade 1' : $destClass;
            if ($destYearMode === 'same') {
                $yearNote = 'destination year same as source (' . $sourceYear . ')';
            } elseif ($destYearMode === 'custom') {
                $yearNote = 'destination year ' . $destYear . ' (source list was ' . $sourceYear . ')';
            } else {
                $yearNote = 'destination year next calendar year (' . $destYear . ', source ' . $sourceYear . ')';
            }
            if ($destTermMode === 'same') {
                $termNote = 'destination term same as source (Term ' . $term . ')';
            } elseif ($destTermMode === 'custom') {
                $termNote = 'destination Term ' . $destTerm;
            } else {
                $termNote = 'destination Term 1 (default promotion)';
            }
            $message = "Done. Created {$inserted} new blank record(s) for {$destLabel}, Term {$destTerm}, {$destYear} ({$termNote}; {$yearNote}). Skipped {$skipped}. Original records were not changed.";
            $messageType = 'ok';
        } catch (Throwable $e) {
            mysqli_rollback($con);
            $message = 'Operation failed. No changes were saved.';
            $messageType = 'error';
        }
    }
}

$previewCount = null;
$previewDest = null;
if (isset($_GET['preview']) && isset($_GET['ecd_src'], $_GET['year'], $_GET['term'])) {
    $es = $_GET['ecd_src'];
    $py = (int) $_GET['year'];
    $pt = (int) $_GET['term'];
    $destYearMode = isset($_GET['dest_year_mode']) ? $_GET['dest_year_mode'] : 'next';
    if (!in_array($destYearMode, ['next', 'same', 'custom'], true)) {
        $destYearMode = 'next';
    }
    $destY = $py + 1;
    if ($destYearMode === 'same') {
        $destY = $py;
    } elseif ($destYearMode === 'custom' && isset($_GET['dest_year'])) {
        $destY = (int) $_GET['dest_year'];
    }
    $destTermModePv = isset($_GET['dest_term_mode']) ? $_GET['dest_term_mode'] : 'term1';
    if (!in_array($destTermModePv, ['term1', 'same', 'custom'], true)) {
        $destTermModePv = 'term1';
    }
    $destTermPv = 1;
    if ($destTermModePv === 'same') {
        $destTermPv = $pt;
    } elseif ($destTermModePv === 'custom') {
        $destTermPv = isset($_GET['dest_term']) ? (int) $_GET['dest_term'] : 0;
    }
    if (in_array($es, ['ECD A', 'ECD B'], true) && $py >= 2000 && $py <= 2100 && $pt >= 1 && $pt <= 3 && $destY >= 2000 && $destY <= 2100 && $destTermPv >= 1 && $destTermPv <= 3) {
        $previewDest = [
            'class' => ecd_dest_class($es),
            'year' => $destY,
            'term' => $destTermPv,
            'source_year' => $py,
            'mode' => $destYearMode,
            'term_mode' => $destTermModePv,
        ];
        $esc = mysqli_real_escape_string($con, $es);
        $qr = mysqli_query($con, "SELECT COUNT(*) AS c FROM `user_mark` WHERE `u_class` = '{$esc}' AND `term` = {$pt} AND `year` = {$py}");
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
    <title>Bulk ECD — new year | Admin</title>
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
        .preview { background: #f0ebff; }
        ul.rules { color: #555; }
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
            <h1><i class="fas fa-child"></i> ECD — promote to new year</h1>
            <p class="sub">
                Copies a cohort into <strong>ECD B</strong> or <strong>Grade 1</strong> (see below) with <strong>blank marks</strong> (original rows stay unchanged).
                Choose which <strong>calendar year</strong> appears on the new rows: default is <strong>next year</strong> (source + 1); you can use the <strong>same year</strong> as the source list or any <strong>custom year</strong>.
                Choose which <strong>term</strong> the new rows use: default is <strong>Term 1</strong>, or match the <strong>source term</strong>, or pick <strong>Term 1–3</strong>.
            </p>
            <ul class="rules">
                <li><strong>ECD A</strong> → new records in <strong>ECD B</strong> (term and year: your choices below).</li>
                <li><strong>ECD B</strong> → new records in <strong>Grade 1</strong> (term and year: your choices below).</li>
            </ul>

            <?php if ($message): ?>
                <div class="msg <?php echo $messageType === 'ok' ? 'ok' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php
            $g_dest_mode = isset($_GET['dest_year_mode']) ? $_GET['dest_year_mode'] : 'next';
            if (!in_array($g_dest_mode, ['next', 'same', 'custom'], true)) {
                $g_dest_mode = 'next';
            }
            $srcY = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
            $g_dest_year_val = isset($_GET['dest_year']) ? (int) $_GET['dest_year'] : ($srcY + 1);
            $g_dest_term_mode = isset($_GET['dest_term_mode']) ? $_GET['dest_term_mode'] : 'term1';
            if (!in_array($g_dest_term_mode, ['term1', 'same', 'custom'], true)) {
                $g_dest_term_mode = 'term1';
            }
            $srcTermEcd = isset($_GET['term']) ? (int) $_GET['term'] : 1;
            if ($srcTermEcd < 1 || $srcTermEcd > 3) {
                $srcTermEcd = 1;
            }
            $g_dest_term_val = isset($_GET['dest_term']) ? (int) $_GET['dest_term'] : 1;
            if ($g_dest_term_val < 1 || $g_dest_term_val > 3) {
                $g_dest_term_val = 1;
            }
            ?>
            <form method="get" action="" id="previewFormEcd">
                <input type="hidden" name="preview" value="1">
                <div class="bulk-sources-row">
                    <div class="bulk-field">
                        <label for="ecd_src_select">Source group</label>
                        <select name="ecd_src" id="ecd_src_select" required>
                            <option value="ECD A" <?php echo (isset($_GET['ecd_src']) && $_GET['ecd_src'] === 'ECD A') ? 'selected' : ''; ?>>ECD A → promotes to ECD B</option>
                            <option value="ECD B" <?php echo (isset($_GET['ecd_src']) && $_GET['ecd_src'] === 'ECD B') ? 'selected' : ''; ?>>ECD B → promotes to Grade 1</option>
                        </select>
                    </div>
                    <div class="bulk-field">
                        <label for="sourceYearEcd">Source year (records to copy from)</label>
                        <input type="number" name="year" id="sourceYearEcd" min="2020" max="2100" value="<?php echo isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y'); ?>" required>
                    </div>
                    <div class="bulk-field">
                        <label for="sourceTermEcd">Source term</label>
                        <select name="term" id="sourceTermEcd" required>
                            <option value="1" <?php echo (isset($_GET['term']) && (int)$_GET['term'] === 1) ? 'selected' : ''; ?>>Term 1</option>
                            <option value="2" <?php echo (isset($_GET['term']) && (int)$_GET['term'] === 2) ? 'selected' : ''; ?>>Term 2</option>
                            <option value="3" <?php echo (isset($_GET['term']) && (int)$_GET['term'] === 3) ? 'selected' : ''; ?>>Term 3</option>
                        </select>
                    </div>
                </div>
                <div class="bulk-dest-row">
                <fieldset class="year-choice">
                    <legend>Year on new records</legend>
                    <label>
                        <input type="radio" name="dest_year_mode" value="next" <?php echo $g_dest_mode === 'next' ? 'checked' : ''; ?>>
                        Next calendar year (source year + 1) — default
                    </label>
                    <label>
                        <input type="radio" name="dest_year_mode" value="same" <?php echo $g_dest_mode === 'same' ? 'checked' : ''; ?>>
                        Same as source year
                    </label>
                    <label>
                        <input type="radio" name="dest_year_mode" value="custom" <?php echo $g_dest_mode === 'custom' ? 'checked' : ''; ?>>
                        Other year…
                    </label>
                    <label class="dest-year-input" for="destYearEcd">Year on new records (when using “Other year”)</label>
                    <input class="dest-year-input" type="number" name="dest_year" id="destYearEcd" min="2020" max="2100" value="<?php echo $g_dest_year_val; ?>">
                </fieldset>
                <fieldset class="year-choice term-choice">
                    <legend>Term on new records</legend>
                    <label>
                        <input type="radio" name="dest_term_mode" value="term1" <?php echo $g_dest_term_mode === 'term1' ? 'checked' : ''; ?>>
                        Term 1 — default promotion (recommended)
                    </label>
                    <label>
                        <input type="radio" name="dest_term_mode" value="same" <?php echo $g_dest_term_mode === 'same' ? 'checked' : ''; ?>>
                        Same as source term
                    </label>
                    <label>
                        <input type="radio" name="dest_term_mode" value="custom" <?php echo $g_dest_term_mode === 'custom' ? 'checked' : ''; ?>>
                        Other term…
                    </label>
                    <label class="dest-year-input" for="destTermEcd">Destination term (Term 1–3)</label>
                    <select name="dest_term" id="destTermEcd">
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

            <?php if ($previewCount !== null && $previewDest): ?>
                <div class="preview">
                    <strong><?php echo (int) $previewCount; ?></strong> student record(s) in this cohort (source year <?php echo (int) $_GET['year']; ?>).
                    New blank records will be created for
                    <strong><?php echo htmlspecialchars($previewDest['class'] === '1' ? 'Grade 1' : $previewDest['class']); ?></strong>,
                    <strong>Term <?php echo (int) $previewDest['term']; ?>, year <?php echo (int) $previewDest['year']; ?></strong><?php if ((int) $_GET['year'] !== (int) $previewDest['year']): ?> (source year was <?php echo (int) $_GET['year']; ?>)<?php endif; ?>.
                </div>
                <?php if ($previewCount > 0): ?>
                    <form method="post" action="" class="bulk-confirm-form" onsubmit="return confirm('Create <?php echo (int) $previewCount; ?> new blank record(s) for Term <?php echo (int) $previewDest['term']; ?>, year <?php echo (int) $previewDest['year']; ?>?');">
                        <input type="hidden" name="ecd_src" value="<?php echo htmlspecialchars($_GET['ecd_src']); ?>">
                        <input type="hidden" name="year" value="<?php echo (int) $_GET['year']; ?>">
                        <input type="hidden" name="term" value="<?php echo (int) $_GET['term']; ?>">
                        <input type="hidden" name="dest_year_mode" value="<?php echo htmlspecialchars($g_dest_mode); ?>">
                        <input type="hidden" name="dest_term_mode" value="<?php echo htmlspecialchars($g_dest_term_mode); ?>">
                        <?php if ($g_dest_mode === 'custom'): ?>
                            <input type="hidden" name="dest_year" value="<?php echo (int) $previewDest['year']; ?>">
                        <?php endif; ?>
                        <?php if ($g_dest_term_mode === 'custom'): ?>
                            <input type="hidden" name="dest_term" value="<?php echo (int) $previewDest['term']; ?>">
                        <?php endif; ?>
                        <div class="bulk-actions-row">
                        <button type="submit" name="confirm_ecd_year" value="1" class="btn btn-danger">
                            <i class="fas fa-check"></i> Create new-year records
                        </button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <p class="hint">
                Skips a learner if a row already exists for the same admission number, destination class, destination term, and destination year.
            </p>
        </div>
    </div>
    <script>
(function () {
    var form = document.getElementById('previewFormEcd');
    if (!form) return;
    var source = document.getElementById('sourceYearEcd');
    var dest = document.getElementById('destYearEcd');
    var sourceTerm = document.getElementById('sourceTermEcd');
    var destTerm = document.getElementById('destTermEcd');
    function mode() {
        var n = form.querySelector('input[name="dest_year_mode"][value="next"]');
        return n && n.checked ? 'next' : (form.querySelector('input[name="dest_year_mode"][value="same"]').checked ? 'same' : 'custom');
    }
    function syncYear() {
        var m = mode();
        if (!dest || !source) return;
        if (m === 'custom') {
            dest.disabled = false;
            return;
        }
        dest.disabled = true;
        var sy = parseInt(source.value, 10) || 0;
        if (m === 'next') dest.value = sy + 1;
        else dest.value = sy;
    }
    function syncTerm() {
        var custom = form.querySelector('input[name="dest_term_mode"][value="custom"]');
        if (!custom || !destTerm || !sourceTerm) return;
        var isCustom = custom.checked;
        destTerm.disabled = !isCustom;
        if (!isCustom) {
            if (form.querySelector('input[name="dest_term_mode"][value="same"]').checked) {
                destTerm.value = sourceTerm.value;
            } else {
                destTerm.value = '1';
            }
        }
    }
    function syncAll() {
        syncYear();
        syncTerm();
    }
    form.querySelectorAll('input[name="dest_year_mode"]').forEach(function (r) { r.addEventListener('change', syncAll); });
    form.querySelectorAll('input[name="dest_term_mode"]').forEach(function (r) { r.addEventListener('change', syncAll); });
    if (source) source.addEventListener('input', syncAll);
    if (sourceTerm) sourceTerm.addEventListener('change', syncAll);
    syncAll();
})();
    </script>
</body>
</html>
