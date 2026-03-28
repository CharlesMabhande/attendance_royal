<?php
/**
 * Shared DB schema checks (include after dbcon.php).
 */
if (!function_exists('rfjs_user_mark_has_published_column')) {
    function rfjs_user_mark_has_published_column(mysqli $con): bool
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $r = @mysqli_query($con, "SHOW COLUMNS FROM `user_mark` LIKE 'published'");
        $cache = ($r && mysqli_num_rows($r) > 0);
        if ($r) {
            mysqli_free_result($r);
        }
        return $cache;
    }
}

/**
 * mysqli_stmt::bind_param with a dynamic parameter list (uses references).
 */
function rfjs_mysqli_stmt_bind_params(mysqli_stmt $stmt, string $types, array $params): bool
{
    $bindParams = array_merge([$types], $params);
    $refs = [];
    foreach ($bindParams as $key => &$val) {
        $refs[$key] = &$val;
    }
    unset($val);
    return call_user_func_array([$stmt, 'bind_param'], $refs);
}
