<?php
/**
 * Little Software Stats
 *
 * An open source program that allows developers to keep track of how their software is being used
 *
 * @package		Little Software Stats
 * @author		Little Apps
 * @copyright           Copyright (c) 2011, Little Apps
 * @license		http://www.gnu.org/licenses/gpl.html GNU General Public License v3
 * @link		http://little-apps.org
 * @since		Version 0.1
 * @filesource
 */

if ( !defined( 'LSS_LOADED' ) ) die( 'This page cannot be loaded directly' );

session_start();

if ( !session_id() ) {
    die( 'PHP Session could not be started.' );
}

if ( @file_exists( dirname( __FILE__ ) . '/config.php' ) && @filesize( dirname( __FILE__ ) . '/config.php' ) > 0 )
    require_once( dirname( __FILE__ ) . '/config.php' );
else {
    die( 'You must <a href="install/">install and configure</a> Little Software Stats first' );
}

require_once( dirname( __FILE__ ) . '/class.mysql.php' );
require_once( dirname( __FILE__ ) . '/class.securelogin.php' );
require_once( dirname( __FILE__ ) . '/class.geekmail.php' );
require_once( dirname( __FILE__ ) . '/class.api.php' );
require_once( dirname( __FILE__ ) . '/version.php' );
require_once( dirname( __FILE__ ) . '/functions.php' );
require_once( dirname( __FILE__ ) . '/../min/utils.php' );

if ( SITE_DEBUG ) {
    ini_set( 'display_errors', 1 );
    error_reporting( E_ALL );
}

$db = MySQL::getInstance();
$login = SecureLogin::getInstance();
$geek_mail = GeekMail::getInstance();
$api = API::getInstance();

if ( version_compare( PHP_VERSION, MIN_PHP_VERSION, "<" ) )
    die( __( "It appears that the web server is not running PHP 5. Please contact your administrator to have it upgraded." ) );

if ( version_compare( $db->get_db_version(), MIN_MYSQL_VERSION, "<" ) )
    die( __( "It appears that the web server is not running PHP 5. Please contact your administrator to have it upgraded." ) );

if ( !defined( 'SITE_NAME' ) ) {
    $site_name = strtolower( $_SERVER['SERVER_NAME'] );
    if ( substr( $site_name, 0, 4 ) == 'www.' )
        define( 'SITE_NAME', substr( $site_name, 4 ) );
    else
        define( 'SITE_NAME', $site_name );
    unset( $site_name );
}

if ( !defined( 'SITE_NOREPLYEMAIL' ) ) {
    define( 'SITE_NOREPLYEMAIL', 'noreply@'. SITE_NAME );
}

// Make sure is already logged in
if ( $login->check_user() ) {
    $needs_refresh = false;

    // Set request variable to default if not set already
    $apps = get_applications();

    if ( !isset( $_GET['page'] ) ) {
        $_GET['page'] = 'dashboard';
        $needs_refresh = true;
    }
    
    // Prevents LFI (Local File Inclusion)
    if ( !ctype_alpha( $_GET['page'] ) ) {
        $_GET['page'] = 'dashboard';
        $needs_refresh = true;
    }
    
    if ( !file_exists( 'pages/' . $_GET['page'] . '.php' ) ) {
        $_GET['page'] = 'dashboard';
        $needs_refresh = true;
    }

    if ( !isset( $_GET['ver'] ) ) {
        $_GET['ver'] = 'all';
        $needs_refresh = true;
    }

    if ( !isset( $_GET['graphBy'] ) ) {
        $_GET['graphBy'] = 'day';
        $needs_refresh = true;
    }

    if ( !isset( $_GET['start'] ) || !strtotime( $_GET['start'] ) ) {
        $_GET['start'] = date("Y-m-d", time() - ( 30 * 24 * 3600 ) );
        $needs_refresh = true;
    }

    if ( !isset( $_GET['end'] ) || !strtotime( $_GET['end'] ) ) {
        $_GET['end'] = date("Y-m-d");
        $needs_refresh = true;
    }

    // Make sure time range is valid for graphs
    if ( $_GET['graphBy'] == 'day' )
        $tick_interval = strtotime( '+1 day', 0 );
    elseif ( $_GET['graphBy'] == 'week' )
        $tick_interval = strtotime( '+1 week', 0 );
    elseif ( $_GET['graphBy'] == 'month' )
        $tick_interval = strtotime( '+1 month', 0 );

    $time_range = strtotime( $_GET['end'] ) - strtotime( $_GET['start'] );
    
    $end = strtotime( $_GET['end'] );
    
    if ( $time_range < $tick_interval ) {
        $_GET['end'] = date( "Y-m-d", $end + ( $tick_interval - $time_range ) );

        $needs_refresh = true;	

        // Enable notification of time change	
        $_SESSION['time_changed'] = true;	
    }

}