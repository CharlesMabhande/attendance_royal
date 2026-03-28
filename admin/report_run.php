<?php
/**
 * Run a school report: ?type=...&format=html|csv|pdf
 * (Print: open HTML format and use browser Print / Save as PDF.)
 */
session_start();
if (!isset($_SESSION['uid'])) {
    header('Location: ../login.php');
    exit;
}
include __DIR__ . '/../dbcon.php';
require_once __DIR__ . '/role_helpers.php';
require_once __DIR__ . '/report_helpers.php';
admin_sync_role_from_db($con);
require_admin_permission('reports');

$type = isset($_REQUEST['type']) ? (string) $_REQUEST['type'] : '';
$format = isset($_REQUEST['format']) ? strtolower(trim((string) $_REQUEST['format'])) : 'html';

$allowed = ['marks_summary', 'marks_full', 'class_statistics', 'subject_averages', 'top_students', 'staff_directory'];
if (!in_array($type, $allowed, true)) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Invalid report type.';
    exit;
}

if ($type === 'staff_directory' && !admin_can('staff_directory')) {
    header('Location: admindash.php?denied=1');
    exit;
}

$f = rfjs_report_get_filters_from_request($con);
$data = null;

switch ($type) {
    case 'marks_summary':
        $data = rfjs_report_data_marks_summary($con, $f);
        break;
    case 'marks_full':
        $data = rfjs_report_data_marks_full($con, $f);
        break;
    case 'class_statistics':
        $data = rfjs_report_data_class_statistics($con, $f);
        break;
    case 'subject_averages':
        $data = rfjs_report_data_subject_averages($con, $f);
        break;
    case 'top_students':
        $data = rfjs_report_data_top_students($con, $f);
        break;
    case 'staff_directory':
        $data = rfjs_report_data_staff_directory($con, $f);
        break;
}

if ($data === null) {
    header('HTTP/1.0 404 Not Found');
    echo 'Report data not available (e.g. staff tables missing).';
    exit;
}

$title = rfjs_report_title_for_type($type, $f);
$slug = preg_replace('/[^a-z0-9_-]+/i', '_', $type);
$filenameBase = $type === 'staff_directory'
    ? 'staff_directory'
    : ($slug . '_' . $f['year'] . ($f['term'] > 0 ? '_t' . $f['term'] : ''));

if ($format === 'csv') {
    rfjs_report_send_csv($filenameBase . '.csv', $data['headers'], $data['rows']);
    exit;
}

if ($format === 'pdf') {
    $orient = ($type === 'marks_full') ? 'L' : 'L';
    rfjs_report_send_pdf_simple($title, $filenameBase . '.pdf', $data['headers'], $data['rows'], $orient);
    exit;
}

// HTML (printable)
$autoprint = isset($_GET['autoprint']) && $_GET['autoprint'] === '1';
$genTime = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> | RFJS</title>
    <link rel="icon" href="../image/logo-rfjs.png" type="image/png">
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, Segoe UI, sans-serif; margin: 0; padding: 16px; color: #222; background: #f5f5f5; overflow-x: auto; }
        .sheet { max-width: none; width: 100%; margin: 0 auto; background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.08); box-sizing: border-box; }
        .head { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #1a2a52; }
        .head .rfjs-report-logo { max-height: 100px; width: auto; object-fit: contain; flex-shrink: 0; }
        .head h1 { margin: 0; font-size: 1.25rem; color: #1a2a52; flex: 1; min-width: 200px; }
        .meta { font-size: 0.88rem; color: #555; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; }
        .toolbar a, .toolbar button {
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 8px;
            font-weight: 600; font-size: 0.9rem; text-decoration: none; cursor: pointer; border: none;
        }
        .btn-print { background: #1a2a52; color: #fff; }
        .btn-back { background: #e9ecef; color: #333; }
        table { width: max-content; min-width: 100%; border-collapse: collapse; font-size: 0.82rem; table-layout: auto; }
        th, td { border: 1px solid #dee2e6; padding: 8px 10px; text-align: left; white-space: nowrap; word-break: normal; }
        th { background: #f1f3f5; font-weight: 700; color: #1a2a52; }
        tr:nth-child(even) td { background: #fafafa; }
        .wrap-table { overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none !important; }
            .sheet { box-shadow: none; border-radius: 0; padding: 12px; }
            .head .rfjs-report-logo { max-height: 95px; width: auto; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head">
            <img src="../image/logo-rfjs.png" alt="Royal Family Junior School" class="rfjs-report-logo">
            <div>
            <h1><?php echo htmlspecialchars($title); ?></h1>
            <p style="margin:4px 0 0;font-size:0.95rem;color:#764ba2;">Royal Family Junior School</p>
            </div>
        </div>
        <p class="meta">Generated <?php echo htmlspecialchars($genTime); ?> — Royal Family Junior School</p>
        <div class="toolbar no-print">
            <button type="button" class="btn-print" onclick="window.print()"><span>Print</span></button>
            <a class="btn-back" href="school_reports.php">← Back to reports</a>
        </div>
        <div class="wrap-table">
            <table>
                <thead>
                    <tr>
                        <?php foreach ($data['headers'] as $h): ?>
                            <th><?php echo htmlspecialchars((string) $h); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($data['rows']) === 0): ?>
                        <tr><td colspan="<?php echo count($data['headers']); ?>">No rows for the selected filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data['rows'] as $row): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
                                    <td><?php echo htmlspecialchars((string) $cell); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($autoprint): ?>
    <script>window.addEventListener('load', function () { window.print(); });</script>
    <?php endif; ?>
</body>
</html>
