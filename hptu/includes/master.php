<?php
// master_include.php - Single point of inclusion for all dependencies

// 1. First include configuration and access control files
require_once('config.php');
require_once('restrict_access.php');
require_once('auth_check.php');
require_once('header.php');
require_once('footer.php');

// 2. Include database and function files
require_once('sql_functions.php');

// 3. Output JavaScript functions (if needed in the <head>)
function include_js_functions() {
    echo '<script>';
    include('functions.js');
    echo '</script>';
}

// 4. Add any common initialization code here
// (e.g., session start, global variables)
?>