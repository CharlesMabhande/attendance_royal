<?php
/**
 * Admin role permissions — include after dbcon.php and session check.
 * Run sql/alter_admin_role.sql once if the `role` column is missing.
 */

if (defined('RFJS_ROLE_HELPERS')) {
    return;
}
define('RFJS_ROLE_HELPERS', true);

function rfjs_admin_role_defs(): array
{
    return [
        'full' => 'Super administrator — all tools and user accounts',
        'coordinator' => 'Coordinator — marks, records, messages, reports, bulk tools (no user accounts)',
        'teacher' => 'Teacher — add, update, delete marks and reports only',
        'records_officer' => 'Records officer — student database only',
        'communications' => 'Communications — student messages only',
    ];
}

function rfjs_admin_role_perms(): array
{
    return [
        'full' => ['*'],
        'coordinator' => ['marks', 'delete_marks', 'reports', 'records', 'messages', 'bulk_term', 'bulk_grade', 'bulk_ecd', 'staff_directory'],
        'teacher' => ['marks', 'delete_marks', 'reports'],
        'records_officer' => ['records'],
        'communications' => ['messages'],
    ];
}

function admin_current_role(): string
{
    $r = $_SESSION['admin_role'] ?? 'full';
    return is_string($r) && $r !== '' ? $r : 'full';
}

function admin_role_label(): string
{
    $defs = rfjs_admin_role_defs();
    $r = admin_current_role();
    return $defs[$r] ?? $r;
}

function admin_can(string $permission): bool
{
    $perms = rfjs_admin_role_perms()[admin_current_role()] ?? [];
    if (in_array('*', $perms, true)) {
        return true;
    }
    return in_array($permission, $perms, true);
}

/**
 * Map login username to a single u_class value, or null = see all grades.
 * Patterns: grade1–grade7 → "1".."7", ecda → ECD A, ecdb → ECD B (case-insensitive).
 */
function rfjs_class_scope_from_username(string $username): ?string
{
    $u = strtolower(trim($username));
    if ($u === '') {
        return null;
    }
    if (preg_match('/^grade([1-7])$/i', $u, $m)) {
        return (string) (int) $m[1];
    }
    if ($u === 'ecda') {
        return 'ECD A';
    }
    if ($u === 'ecdb') {
        return 'ECD B';
    }
    return null;
}

function admin_class_scope(): ?string
{
    $s = $_SESSION['admin_class_scope'] ?? null;
    if ($s === null || $s === '') {
        return null;
    }
    return is_string($s) ? $s : null;
}

function admin_is_class_scoped(): bool
{
    return admin_class_scope() !== null;
}

/** Display label e.g. "Grade 3" or "ECD A". */
function admin_class_scope_label(): string
{
    $s = admin_class_scope();
    if ($s === null) {
        return '';
    }
    if ($s === 'ECD A' || $s === 'ECD B') {
        return $s;
    }
    return 'Grade ' . $s;
}

/** Whether a stored u_class belongs to the current scoped grade (if any). */
function admin_u_class_matches_scope(string $dbClass): bool
{
    $scope = admin_class_scope();
    if ($scope === null) {
        return true;
    }
    $db = trim((string) $dbClass);
    if (ctype_digit((string) $scope)) {
        return (string) (int) $db === (string) (int) $scope;
    }
    return strcasecmp($db, $scope) === 0;
}

/** For captions like "Grade 2" vs "ECD A" in filter summaries. */
function rfjs_filter_grade_caption(string $uClass): string
{
    $c = trim($uClass);
    if ($c === 'ECD A' || $c === 'ECD B') {
        return $c;
    }
    return 'Grade ' . $c;
}

function admin_require_rollno_in_scope(mysqli $con, string $rollno): void
{
    if (!admin_is_class_scoped()) {
        return;
    }
    $esc = mysqli_real_escape_string($con, $rollno);
    $q = mysqli_query($con, "SELECT `u_class` FROM `student_data` WHERE `u_rollno` = '$esc' LIMIT 1");
    $row = ($q && mysqli_num_rows($q) > 0) ? mysqli_fetch_assoc($q) : null;
    if (!$row) {
        $q2 = mysqli_query($con, "SELECT `u_class` FROM `user_mark` WHERE `u_rollno` = '$esc' LIMIT 1");
        $row = ($q2 && mysqli_num_rows($q2) > 0) ? mysqli_fetch_assoc($q2) : null;
    }
    if (!$row || !admin_u_class_matches_scope((string) $row['u_class'])) {
        header('Location: admindash.php?denied=1');
        exit;
    }
}

function admin_require_user_mark_row_in_scope(mysqli $con, int $id): void
{
    $scope = admin_class_scope();
    if ($scope === null) {
        return;
    }
    $st = $con->prepare('SELECT `u_class` FROM `user_mark` WHERE `id` = ? LIMIT 1');
    if (!$st) {
        header('Location: admindash.php?denied=1');
        exit;
    }
    $st->bind_param('i', $id);
    if (!$st->execute()) {
        $st->close();
        header('Location: admindash.php?denied=1');
        exit;
    }
    $res = $st->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $st->close();
    if (!$row || !admin_u_class_matches_scope((string) $row['u_class'])) {
        header('Location: admindash.php?denied=1');
        exit;
    }
}

function admin_sync_role_from_db(mysqli $con): void
{
    if (!isset($_SESSION['uid'])) {
        return;
    }
    $id = (int) $_SESSION['uid'];
    $st = $con->prepare('SELECT `role`, `username` FROM `admin` WHERE `id` = ? LIMIT 1');
    if (!$st) {
        $st = $con->prepare('SELECT `role` FROM `admin` WHERE `id` = ? LIMIT 1');
    }
    if (!$st) {
        return;
    }
    $st->bind_param('i', $id);
    if (!$st->execute()) {
        $st->close();
        return;
    }
    $res = $st->get_result();
    if ($row = $res->fetch_assoc()) {
        $r = isset($row['role']) ? trim((string) $row['role']) : 'full';
        if ($r === '' || !isset(rfjs_admin_role_defs()[$r])) {
            $r = 'full';
        }
        $_SESSION['admin_role'] = $r;
        if (isset($row['username'])) {
            $uname = trim((string) $row['username']);
        } else {
            $uname = isset($_SESSION['admin_username']) ? trim((string) $_SESSION['admin_username']) : '';
        }
        $_SESSION['admin_username'] = $uname;
        $_SESSION['admin_class_scope'] = rfjs_class_scope_from_username($uname);
    }
    $st->close();
}

function require_admin_permission(string $permission): void
{
    if (!isset($_SESSION['uid'])) {
        header('Location: ../login.php');
        exit;
    }
    if (!admin_can($permission)) {
        header('Location: admindash.php?denied=1');
        exit;
    }
}

/**
 * Super administrators only (role `full`). Call after `admin_sync_role_from_db()`.
 * Coordinators, teachers, and other roles are redirected to the dashboard.
 */
function require_full_admin_role(): void
{
    if (!isset($_SESSION['uid'])) {
        header('Location: ../login.php');
        exit;
    }
    if (admin_current_role() !== 'full') {
        header('Location: admindash.php?denied=1');
        exit;
    }
}

function require_addmark_permission(): void
{
    $action = isset($_GET['action']) ? strtolower((string) $_GET['action']) : '';
    if ($action === 'delete') {
        require_admin_permission('delete_marks');
    } elseif ($action === 'reports') {
        require_admin_permission('reports');
    } else {
        require_admin_permission('marks');
    }
}

function rfjs_valid_admin_role(string $role): bool
{
    return isset(rfjs_admin_role_defs()[$role]);
}

/** True if the current role sees at least one dashboard card. */
function admin_dashboard_has_any_tool(): bool
{
    foreach (['users', 'marks', 'delete_marks', 'records', 'messages', 'reports', 'bulk_term', 'bulk_grade', 'bulk_ecd', 'staff_directory'] as $p) {
        if (admin_can($p)) {
            return true;
        }
    }
    return false;
}
