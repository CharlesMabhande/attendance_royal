<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header('location: ../login.php');
    exit;
}
include __DIR__ . '/../dbcon.php';
require_once __DIR__ . '/role_helpers.php';
require_once __DIR__ . '/report_helpers.php';
admin_sync_role_from_db($con);
require_admin_permission('reports');

$f = rfjs_report_get_filters_from_request($con);
$years = rfjs_report_distinct_years($con);
$staffStatus = isset($_GET['staff_status']) && $_GET['staff_status'] === 'all' ? 'all' : 'active';
$scoped = admin_class_scope();
$staffTable = rfjs_report_staff_table_exists($con);

function rfjs_school_report_url(string $type, string $format, array $f, string $staffStatus): string
{
    $p = ['type' => $type, 'format' => $format, 'year' => $f['year'], 'term' => $f['term']];
    if ($f['class'] !== '') {
        $p['class'] = $f['class'];
    }
    if ($type === 'top_students') {
        $p['top'] = $f['top'];
    }
    if ($type === 'staff_directory') {
        $p['staff_status'] = $staffStatus;
    }

    return 'report_run.php?' . http_build_query($p);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>School reports | Admin</title>
    <link rel="icon" href="../image/logo-rfjs.png" type="image/png">
    <link rel="stylesheet" href="../csss/rfjs-theme.css" type="text/css">
    <link rel="stylesheet" href="../csss/bulk-move-forms.css" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body.bulk-move-page { font-family: system-ui, Segoe UI, sans-serif; background: var(--rfjs-bg-page); min-height: 100vh; color: #333; padding: clamp(10px, 2vw, 20px); }
        .wrap { max-width: min(1680px, calc(100vw - 24px)); margin: 0 auto; }
        .nav a { color: #fff; font-weight: 600; text-decoration: none; }
        .card { background: rgba(255,255,255,.96); border-radius: 16px; padding: 22px 26px; box-shadow: 0 12px 36px rgba(0,0,0,.12); margin-bottom: 18px; }
        h1 { color: #4a2c7a; font-size: 1.35rem; margin-bottom: 8px; }
        .lead { color: #555; font-size: 0.95rem; line-height: 1.5; margin-bottom: 16px; }
        .filters { display: grid; gap: 12px 18px; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); align-items: end; margin-bottom: 8px; }
        .filters label { display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 4px; color: #444; }
        .filters select, .filters input { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 10px; font-size: 0.95rem; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; text-decoration: none; border: none; cursor: pointer; }
        .btn-primary { background: var(--rfjs-gradient-btn); color: #fff; }
        .btn-outline { background: #fff; color: #333; border: 1px solid #ccc; }
        .btn-sm { padding: 8px 12px; font-size: 0.82rem; }
        .report-grid { display: grid; gap: 14px; grid-template-columns: 1fr; }
        @media (min-width: 720px) { .report-grid { grid-template-columns: 1fr 1fr; } }
        .report-card { border: 1px solid #e9ecef; border-radius: 12px; padding: 16px; background: #fafafa; }
        .report-card h2 { font-size: 1.05rem; color: #1a2a52; margin: 0 0 8px; }
        .report-card p { font-size: 0.88rem; color: #555; line-height: 1.45; margin: 0 0 12px; }
        .export-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .hint { font-size: 0.82rem; color: #666; margin-top: 12px; }
        .scope-note { background: #e7f3ff; border: 1px solid #b8daff; color: #004085; padding: 10px 12px; border-radius: 10px; font-size: 0.88rem; margin-bottom: 14px; }
    </style>
</head>
<body class="bulk-move-page">
    <div class="wrap">
        <div class="nav" style="margin-bottom:14px;">
            <a href="admindash.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>

        <div class="card">
            <div class="card-logo-wrap" style="text-align:center;margin-bottom:14px;">
                <img src="../image/logo-rfjs.png" alt="Royal Family Junior School" class="rfjs-logo-img rfjs-logo-crest" width="100" height="100" style="display:block;margin:0 auto;">
            </div>
            <h1><i class="fas fa-chart-bar"></i> School reports</h1>
            <p class="lead">
                Build academic and administrative exports from your marks database. Choose filters, then open a <strong>printable</strong> page (use your browser’s <em>Print</em> dialog to print or save as PDF), or download <strong>CSV</strong> / <strong>PDF</strong> directly.
            </p>

            <?php if ($scoped !== null): ?>
                <div class="scope-note">
                    <i class="fas fa-lock"></i> Your account is limited to <strong><?php echo htmlspecialchars(admin_class_scope_label()); ?></strong> — class filter is fixed.
                </div>
            <?php endif; ?>

            <form method="get" action="">
                <div class="filters">
                    <div>
                        <label for="year">Year</label>
                        <select name="year" id="year">
                            <?php foreach ($years as $y): ?>
                                <option value="<?php echo (int) $y; ?>" <?php echo $f['year'] === (int) $y ? 'selected' : ''; ?>><?php echo (int) $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="term">Term</label>
                        <select name="term" id="term">
                            <option value="" <?php echo $f['term'] === 0 ? 'selected' : ''; ?>>All terms</option>
                            <option value="1" <?php echo $f['term'] === 1 ? 'selected' : ''; ?>>Term 1</option>
                            <option value="2" <?php echo $f['term'] === 2 ? 'selected' : ''; ?>>Term 2</option>
                            <option value="3" <?php echo $f['term'] === 3 ? 'selected' : ''; ?>>Term 3</option>
                        </select>
                    </div>
                    <?php if ($scoped === null): ?>
                    <div>
                        <label for="class">Class</label>
                        <select name="class" id="class">
                            <option value="" <?php echo $f['class'] === '' ? 'selected' : ''; ?>>All classes</option>
                            <option value="ECD A" <?php echo $f['class'] === 'ECD A' ? 'selected' : ''; ?>>ECD A</option>
                            <option value="ECD B" <?php echo $f['class'] === 'ECD B' ? 'selected' : ''; ?>>ECD B</option>
                            <?php for ($g = 1; $g <= 7; $g++): ?>
                                <option value="<?php echo $g; ?>" <?php echo $f['class'] === (string) $g ? 'selected' : ''; ?>>Grade <?php echo $g; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="class" value="<?php echo htmlspecialchars($scoped); ?>">
                    <?php endif; ?>
                    <div>
                        <label for="top">Top N (leaderboard)</label>
                        <input type="number" name="top" id="top" min="1" max="500" value="<?php echo (int) $f['top']; ?>">
                    </div>
                    <div>
                        <label for="staff_status">Staff list</label>
                        <select name="staff_status" id="staff_status">
                            <option value="active" <?php echo $staffStatus === 'active' ? 'selected' : ''; ?>>Active only</option>
                            <option value="all" <?php echo $staffStatus === 'all' ? 'selected' : ''; ?>>All statuses</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply filters</button>
                    </div>
                </div>
            </form>

            <p class="hint">
                <i class="fas fa-info-circle"></i> Academic reports use the selected <strong>year</strong>, <strong>term</strong>, and <strong>class</strong>. The staff report uses the <strong>Staff list</strong> option only.
            </p>
        </div>

        <div class="report-grid">
            <div class="report-card">
                <h2><i class="fas fa-list-ol"></i> Student marks summary</h2>
                <p>Name, admission number, class, term, year, total, position — good for class lists and filing.</p>
                <div class="export-row">
                    <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('marks_summary', 'html', $f, $staffStatus)); ?>" target="_blank" rel="noopener">Print / view</a>
                    <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('marks_summary', 'csv', $f, $staffStatus)); ?>">CSV</a>
                    <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('marks_summary', 'pdf', $f, $staffStatus)); ?>">PDF</a>
                </div>
            </div>

            <div class="report-card">
                <h2><i class="fas fa-table"></i> Full marks (all subjects)</h2>
                <p>Every subject column plus totals — use CSV for spreadsheets; PDF may use small text in landscape.</p>
                <div class="export-row">
                    <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('marks_full', 'html', $f, $staffStatus)); ?>" target="_blank" rel="noopener">Print / view</a>
                    <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('marks_full', 'csv', $f, $staffStatus)); ?>">CSV</a>
                    <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('marks_full', 'pdf', $f, $staffStatus)); ?>">PDF</a>
                </div>
            </div>

            <div class="report-card">
                <h2><i class="fas fa-chart-pie"></i> Class performance summary</h2>
                <p>Per-class student counts and average / min / max totals for the filter.</p>
                <div class="export-row">
                    <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('class_statistics', 'html', $f, $staffStatus)); ?>" target="_blank" rel="noopener">Print / view</a>
                    <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('class_statistics', 'csv', $f, $staffStatus)); ?>">CSV</a>
                    <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('class_statistics', 'pdf', $f, $staffStatus)); ?>">PDF</a>
                </div>
            </div>

            <div class="report-card">
                <h2><i class="fas fa-flask"></i> Subject averages by class</h2>
                <p>Average combined marks per learning area for each class (same filters).</p>
                <div class="export-row">
                    <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('subject_averages', 'html', $f, $staffStatus)); ?>" target="_blank" rel="noopener">Print / view</a>
                    <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('subject_averages', 'csv', $f, $staffStatus)); ?>">CSV</a>
                    <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('subject_averages', 'pdf', $f, $staffStatus)); ?>">PDF</a>
                </div>
            </div>

            <div class="report-card">
                <h2><i class="fas fa-trophy"></i> Top students</h2>
                <p>Highest total marks (up to <strong><?php echo (int) $f['top']; ?></strong> rows) for the current filters.</p>
                <div class="export-row">
                    <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('top_students', 'html', $f, $staffStatus)); ?>" target="_blank" rel="noopener">Print / view</a>
                    <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('top_students', 'csv', $f, $staffStatus)); ?>">CSV</a>
                    <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('top_students', 'pdf', $f, $staffStatus)); ?>">PDF</a>
                </div>
            </div>

            <div class="report-card">
                <h2><i class="fas fa-id-badge"></i> Staff directory</h2>
                <?php if ($staffTable && admin_can('staff_directory')): ?>
                    <p>Teachers and ancillary staff (from the staff directory). Uses “Staff list” filter above.</p>
                    <div class="export-row">
                        <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('staff_directory', 'html', $f, $staffStatus)); ?>" target="_blank" rel="noopener">Print / view</a>
                        <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('staff_directory', 'csv', $f, $staffStatus)); ?>">CSV</a>
                        <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars(rfjs_school_report_url('staff_directory', 'pdf', $f, $staffStatus)); ?>">PDF</a>
                    </div>
                <?php elseif (!$staffTable): ?>
                    <p style="color:#856404;">Run <code>sql/staff_directory.sql</code> to enable staff exports.</p>
                <?php else: ?>
                    <p style="color:#721c24;">You do not have permission to export staff data.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
