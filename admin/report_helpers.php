<?php
/**
 * School reports — shared data + CSV/PDF output helpers.
 * Requires dbcon + role_helpers (session, admin_can).
 */

if (defined('RFJS_REPORT_HELPERS')) {
    return;
}
define('RFJS_REPORT_HELPERS', true);

/** @return array{year:int,term:int,class:string,top:int} */
function rfjs_report_get_filters_from_request(mysqli $con): array
{
    $year = isset($_REQUEST['year']) ? (int) $_REQUEST['year'] : (int) date('Y');
    if ($year < 2000 || $year > 2100) {
        $year = (int) date('Y');
    }
    $term = isset($_REQUEST['term']) && $_REQUEST['term'] !== '' ? (int) $_REQUEST['term'] : 0;
    if ($term < 0 || $term > 3) {
        $term = 0;
    }
    $class = isset($_REQUEST['class']) ? trim((string) $_REQUEST['class']) : '';
    $top = isset($_REQUEST['top']) ? (int) $_REQUEST['top'] : 50;
    if ($top < 1 || $top > 500) {
        $top = 50;
    }

    $scoped = admin_class_scope();
    if ($scoped !== null) {
        $class = $scoped;
    }

    return ['year' => $year, 'term' => $term, 'class' => $class, 'top' => $top];
}

/** Build SQL WHERE fragment for user_mark (returns string with leading ANDs or empty). */
function rfjs_report_user_mark_where_sql(mysqli $con, array $f): string
{
    $parts = [];
    if ($f['year'] > 0) {
        $parts[] = '`year` = ' . (int) $f['year'];
    }
    if ($f['term'] >= 1 && $f['term'] <= 3) {
        $parts[] = '`term` = ' . (int) $f['term'];
    }
    if ($f['class'] !== '') {
        $esc = mysqli_real_escape_string($con, $f['class']);
        $parts[] = "`u_class` = '$esc'";
    }
    if ($parts === []) {
        return '1=1';
    }
    return implode(' AND ', $parts);
}

function rfjs_report_pdf_text(string $s): string
{
    $s = (string) $s;
    if (function_exists('iconv')) {
        $x = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
        if ($x !== false) {
            return $x;
        }
    }
    return $s;
}

function rfjs_report_send_csv(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $filename) . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
}

/** PDF: one line per row, fixed cell height (landscape for wide tables). */
function rfjs_report_send_pdf_simple(string $title, string $filename, array $headers, array $rows, string $orientation = 'L'): void
{
    $path = __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'fpdf.php';
    if (!is_readable($path)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'PDF library missing (admin/lib/fpdf.php).';
        exit;
    }
    require_once $path;

    $pdf = new FPDF($orientation, 'mm', 'A4');
    $pdf->SetTitle(rfjs_report_pdf_text($title));
    $pdf->AddPage();
    $logoFs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'logo-rfjs.png';
    $headerY = 10;
    if (is_readable($logoFs)) {
        $pdf->Image($logoFs, 10, $headerY, 22);
        $headerY = 34;
    }
    $pdf->SetXY(10, $headerY);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, rfjs_report_pdf_text($title), 0, 1);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 5, rfjs_report_pdf_text('Royal Family Junior School'), 0, 1);
    $pdf->Ln(1);
    $pdf->SetFont('Arial', '', 7);

    $nc = count($headers);
    $w = $orientation === 'L' ? 277 : 190;
    $usable = $w - 20;
    $colW = $nc > 0 ? $usable / $nc : $usable;
    // Narrow min width so many columns fit on one row (was max(14,…) which overflowed wide tables)
    $colW = max(5.5, min(95, $colW));

    $clip = static function (string $s, int $max): string {
        if (function_exists('mb_substr')) {
            return mb_substr($s, 0, $max);
        }
        return substr($s, 0, $max);
    };
    $pdf->SetFont('Arial', 'B', 7);
    foreach ($headers as $h) {
        $pdf->Cell($colW, 5, rfjs_report_pdf_text($clip((string) $h, 500)), 1, 0);
    }
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 7);
    foreach ($rows as $row) {
        if ($pdf->GetY() > ($orientation === 'L' ? 185 : 270)) {
            $pdf->AddPage();
        }
        foreach ($row as $cell) {
            $pdf->Cell($colW, 5, rfjs_report_pdf_text($clip((string) $cell, 500)), 1, 0);
        }
        $pdf->Ln();
    }
    $pdf->Output('D', $filename);
    exit;
}

function rfjs_report_distinct_years(mysqli $con): array
{
    $years = [];
    $q = mysqli_query($con, 'SELECT DISTINCT `year` FROM `user_mark` ORDER BY `year` DESC');
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $years[] = (int) $r['year'];
        }
    }
    if ($years === []) {
        $years[] = (int) date('Y');
    }
    return $years;
}

function rfjs_report_staff_table_exists(mysqli $con): bool
{
    $q = mysqli_query($con, "SHOW TABLES LIKE 'school_staff'");
    return $q && mysqli_num_rows($q) > 0;
}

/** @return array{headers: string[], rows: array<int, array<int, string>>} */
function rfjs_report_data_marks_summary(mysqli $con, array $f): array
{
    $where = rfjs_report_user_mark_where_sql($con, $f);
    $sql = "SELECT `u_name`,`u_rollno`,`u_class`,`term`,`year`,`u_total`,`u_position` FROM `user_mark` WHERE $where ORDER BY `u_class` ASC, `u_position` ASC, `u_name` ASC";
    $q = mysqli_query($con, $sql);
    $headers = ['Student name', 'Admission no.', 'Class', 'Term', 'Year', 'Total marks', 'Position'];
    $rows = [];
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $rows[] = [
                (string) $r['u_name'],
                (string) $r['u_rollno'],
                (string) $r['u_class'],
                (string) $r['term'],
                (string) $r['year'],
                (string) $r['u_total'],
                (string) $r['u_position'],
            ];
        }
    }
    return ['headers' => $headers, 'rows' => $rows];
}

/** Full marks (all subject columns). */
function rfjs_report_data_marks_full(mysqli $con, array $f): array
{
    $where = rfjs_report_user_mark_where_sql($con, $f);
    $sql = "SELECT * FROM `user_mark` WHERE $where ORDER BY `u_class` ASC, `u_position` ASC, `u_name` ASC";
    $q = mysqli_query($con, $sql);
    $headers = [
        'Student', 'Admission', 'Class', 'Term', 'Year',
        'Math 1', 'Math 2', 'Eng 1', 'Eng 2', 'Shona 1', 'Shona 2',
        'Soc Sci 1', 'Soc Sci 2', 'PE Arts 1', 'PE Arts 2', 'Sci/Tech 1', 'Sci/Tech 2',
        'Total', 'Position',
    ];
    $rows = [];
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $rows[] = [
                (string) $r['u_name'],
                (string) $r['u_rollno'],
                (string) $r['u_class'],
                (string) $r['term'],
                (string) $r['year'],
                (string) $r['u_mathematics_1'],
                (string) $r['u_mathematics_2'],
                (string) $r['u_english_1'],
                (string) $r['u_english_2'],
                (string) $r['u_shona_1'],
                (string) $r['u_shona_2'],
                (string) $r['u_social_science_1'],
                (string) $r['u_social_science_2'],
                (string) $r['u_physical_education_arts_1'],
                (string) $r['u_physical_education_arts_2'],
                (string) $r['u_science_technology_1'],
                (string) $r['u_science_technology_2'],
                (string) $r['u_total'],
                (string) $r['u_position'],
            ];
        }
    }
    return ['headers' => $headers, 'rows' => $rows];
}

/** Per-class aggregates for selected year/term. */
function rfjs_report_data_class_statistics(mysqli $con, array $f): array
{
    $where = rfjs_report_user_mark_where_sql($con, $f);
    $sql = "SELECT `u_class`, COUNT(*) AS `n`, AVG(`u_total`) AS `avg_t`, MIN(`u_total`) AS `min_t`, MAX(`u_total`) AS `max_t` FROM `user_mark` WHERE $where GROUP BY `u_class` ORDER BY `u_class` ASC";
    $q = mysqli_query($con, $sql);
    $headers = ['Class', 'Students', 'Average total', 'Min total', 'Max total'];
    $rows = [];
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $rows[] = [
                (string) $r['u_class'],
                (string) $r['n'],
                number_format((float) $r['avg_t'], 2, '.', ''),
                (string) $r['min_t'],
                (string) $r['max_t'],
            ];
        }
    }
    return ['headers' => $headers, 'rows' => $rows];
}

function rfjs_report_data_top_students(mysqli $con, array $f): array
{
    $where = rfjs_report_user_mark_where_sql($con, $f);
    $limit = (int) $f['top'];
    $sql = "SELECT `u_name`,`u_rollno`,`u_class`,`term`,`year`,`u_total`,`u_position` FROM `user_mark` WHERE $where ORDER BY `u_total` DESC, `u_name` ASC LIMIT $limit";
    $q = mysqli_query($con, $sql);
    $headers = ['Student name', 'Admission no.', 'Class', 'Term', 'Year', 'Total marks', 'Position'];
    $rows = [];
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $rows[] = [
                (string) $r['u_name'],
                (string) $r['u_rollno'],
                (string) $r['u_class'],
                (string) $r['term'],
                (string) $r['year'],
                (string) $r['u_total'],
                (string) $r['u_position'],
            ];
        }
    }
    return ['headers' => $headers, 'rows' => $rows];
}

/** Subject averages per class (year/term filters). */
function rfjs_report_data_subject_averages(mysqli $con, array $f): array
{
    $where = rfjs_report_user_mark_where_sql($con, $f);
    $sql = "SELECT `u_class`,
      AVG(`u_mathematics_1`+`u_mathematics_2`) AS m,
      AVG(`u_english_1`+`u_english_2`) AS e,
      AVG(`u_shona_1`+`u_shona_2`) AS s,
      AVG(`u_social_science_1`+`u_social_science_2`) AS ss,
      AVG(`u_physical_education_arts_1`+`u_physical_education_arts_2`) AS pe,
      AVG(`u_science_technology_1`+`u_science_technology_2`) AS st
      FROM `user_mark` WHERE $where GROUP BY `u_class` ORDER BY `u_class`";
    $q = mysqli_query($con, $sql);
    $headers = ['Class', 'Avg Math', 'Avg English', 'Avg Shona', 'Avg Soc Sci', 'Avg PE/Arts', 'Avg Sci/Tech'];
    $rows = [];
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $rows[] = [
                (string) $r['u_class'],
                number_format((float) $r['m'], 2, '.', ''),
                number_format((float) $r['e'], 2, '.', ''),
                number_format((float) $r['s'], 2, '.', ''),
                number_format((float) $r['ss'], 2, '.', ''),
                number_format((float) $r['pe'], 2, '.', ''),
                number_format((float) $r['st'], 2, '.', ''),
            ];
        }
    }
    return ['headers' => $headers, 'rows' => $rows];
}

/** @return array{headers: string[], rows: array<int, array<int, string>>}|null */
function rfjs_report_data_staff_directory(mysqli $con, array $f): ?array
{
    if (!rfjs_report_staff_table_exists($con)) {
        return null;
    }
    $headers = ['Name', 'Category', 'Job title', 'Role', 'Level', 'Email', 'Phone', 'Status'];
    $statusSql = (isset($_REQUEST['staff_status']) && $_REQUEST['staff_status'] === 'all') ? '' : " AND `status` = 'active'";
    $sql = "SELECT `full_name`,`staff_category`,`job_title`,`role_at_school`,`work_level`,`email`,`phone`,`status` FROM `school_staff` WHERE 1=1 $statusSql ORDER BY `full_name` ASC";
    $q = mysqli_query($con, $sql);
    if (!$q) {
        return ['headers' => $headers, 'rows' => []];
    }
    $rows = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = [
            (string) $r['full_name'],
            $r['staff_category'] === 'teacher' ? 'Teacher' : 'Ancillary',
            (string) $r['job_title'],
            (string) $r['role_at_school'],
            (string) $r['work_level'],
            (string) $r['email'],
            (string) $r['phone'],
            (string) $r['status'],
        ];
    }
    return ['headers' => $headers, 'rows' => $rows];
}

function rfjs_report_type_labels(): array
{
    return [
        'marks_summary' => 'Student marks summary',
        'marks_full' => 'Full marks (all subjects)',
        'class_statistics' => 'Class performance summary',
        'subject_averages' => 'Subject averages by class',
        'top_students' => 'Top students by total marks',
        'staff_directory' => 'Staff directory',
    ];
}

function rfjs_report_title_for_type(string $type, array $f): string
{
    if ($type === 'staff_directory') {
        $scope = (isset($_REQUEST['staff_status']) && $_REQUEST['staff_status'] === 'all') ? 'All statuses' : 'Active staff only';

        return 'Staff directory (' . $scope . ')';
    }
    $y = (string) $f['year'];
    $t = $f['term'] >= 1 && $f['term'] <= 3 ? 'Term ' . $f['term'] : 'All terms';
    $c = $f['class'] !== '' ? (' — Class ' . $f['class']) : '';
    $base = rfjs_report_type_labels()[$type] ?? 'Report';
    if ($type === 'top_students') {
        return $base . ' (top ' . (int) $f['top'] . ') — ' . $y . ', ' . $t . $c;
    }

    return $base . ' — ' . $y . ', ' . $t . $c;
}
