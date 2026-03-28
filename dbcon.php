<?php
/**
 * Local XAMPP: default MySQL user is root with no password.
 * On production/cPanel, replace with your host’s DB user and password (e.g. royalfam_root).
 */
$con = mysqli_connect('localhost', 'root', '', 'royalfam_sql');
