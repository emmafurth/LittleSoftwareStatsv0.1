<?php
if ( basename( $_SERVER['PHP_SELF'] ) == 'config.php' )
    die( 'This page cannot be loaded directly' );

define('SITE_URL', 'http://stats.yourwebsite.com/');
define('SITE_PATH', '/home/username/public_html/');
define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'user');
define('MYSQL_PASS', 'pass');
define('MYSQL_DB', 'database');
define('MYSQL_PREFIX', 'lss_');
// Only enable for developing!
define('SITE_DEBUG', true);
// Set to false to disable cross site request forgery protection (not recommended)
define('SITE_CSRF', true);
