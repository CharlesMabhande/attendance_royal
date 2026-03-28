<?php
/**
 * Royal Family Junior School — logo paths (image/logo-rfjs.png).
 */
if (defined('RFJS_BRANDING')) {
    return;
}
define('RFJS_BRANDING', true);

function rfjs_logo_fs_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'logo-rfjs.png';
}

/** URL path from site root (index.php, result.php). */
function rfjs_logo_src_root(): string
{
    return 'image/logo-rfjs.png';
}

/** URL path from admin/*.php */
function rfjs_logo_src_admin(): string
{
    return '../image/logo-rfjs.png';
}
