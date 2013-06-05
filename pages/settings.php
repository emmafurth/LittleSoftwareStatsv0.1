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
 */

if ( !defined( 'LSS_LOADED' ) ) die( 'This page cannot be loaded directly' );

require_once '../inc/main.php';

// Make sure user is logged in
verify_user( );

$admin_email = get_option( 'site_adminemail' );
$rewrite = get_option( 'site_rewrite' );

$recaptcha_enabled = get_option( 'recaptcha_enabled' );
$recaptcha_public_key = get_option( 'recaptcha_public_key' );
$recaptcha_private_key = get_option( 'recaptcha_private_key' );

$mail_protocol = get_option( 'mail_protocol' );
$mail_smtp_server = get_option( 'mail_smtp_server' );
$mail_smtp_port = get_option( 'mail_smtp_port' );
$mail_smtp_user = get_option( 'mail_smtp_username' );
$mail_smtp_pass = get_option( 'mail_smtp_password' );
$mail_sendmail_path = get_option( 'mail_sendmail_path' );

$geoip_service = get_option( 'geoips_service' );
$geoip_api_key = get_option( 'geoips_api_key' );
$geoip_database_file = get_option( 'geoips_database' );

function update_settings() {
    global $db;
    global $admin_email, $rewrite;
    global $recaptcha_enabled, $recaptcha_public_key, $recaptcha_private_key;
    global $mail_protocol, $mail_smtp_server, $mail_smtp_port, $mail_smtp_user, $mail_smtp_pass, $mail_sendmail_path;
    global $geoip_service, $geoip_api_key, $geoip_database_file;
    
    // Verify CSRF token
    verify_csrf_token( );

    require_once('../inc/class.passwordhash.php');
    $password_hash = new PasswordHash(8, false);

    if ( !$db->select( "users", array( "UserName" => $_SESSION['UserName'] ), "", "0,1" ) ) {
        show_msg_box( __( "Unable to query database: " ) . $db->last_error, "red" );
        return;
    }

    $current_pass = $db->arrayed_result['UserPass'];

    if ( !$password_hash->check_password( trim( $_POST['password'] ), $current_pass ) ) {
        show_msg_box( __( "The password does not match your current password" ), "red" );
        return;
    }

    if ( $_POST['email'] != $admin_email ) {
        if ( !filter_var( $_POST['email'], FILTER_VALIDATE_EMAIL ) ) {
            show_msg_box( __( "The e-mail address is invalid" ), "red" );
            return;
        }

        set_option( 'site_adminemail' , $_POST['email'] );
        $admin_email = $_POST['email'];
    }

    if ( $_POST['rewrite'] != $rewrite ) {
        if ( $_POST['rewrite'] != 'true' && $_POST['rewrite'] != 'false' ) {
            show_msg_box( __( "Invalid value for 'rewrite' specified" ), "red" );
            return;
        }

        set_option( 'site_rewrite', $_POST['rewrite'] );
        $rewrite = $_POST['rewrite'];
    }

    if ( $_POST['recaptcha'] != $recaptcha_enabled ) {
        if ( $_POST['recaptcha'] != 'true' && $_POST['recaptcha'] != 'false' ) {
            show_msg_box( __( "Invalid value for 'recaptcha' specified" ), "red" );
            return;
        }

        set_option( 'recaptcha_enabled', $_POST['recaptcha'] );
        $recaptcha_enabled = $_POST['recaptcha'];
    }

    if ( $_POST['recaptcha-public'] != $recaptcha_public_key || $_POST['recaptcha-private'] != $recaptcha_private_key ) {
        set_option( 'recaptcha_public_key', $_POST['recaptcha-public'] );
        set_option( 'recaptcha_private_key', $_POST['recaptcha-private'] );
        
        $recaptcha_public_key = $_POST['recaptcha-public'];
        $recaptcha_private_key = $_POST['recaptcha-private'];
    }

    if ( $_POST['protocol'] != $mail_protocol ) {
        if ( $_POST['protocol'] != 'mail' && $_POST['protocol'] != 'sendmail' && $_POST['protocol'] != 'smtp' ) {
            show_msg_box( __( "Invalid value for 'protocol' specified" ), "red" );
            return;
        }

        set_option( 'mail_protocol', $_POST['protocol'] );
        $mail_protocol = $_POST['protocol'];
    }

    if ( $_POST['smtp-server'] != $mail_smtp_server ) {
        if ( !$_POST['smtp-server'] ) {
            show_msg_box( __( "SMTP server must be specified" ), "red" );
            return;
        }

        set_option( 'smtp-server', $_POST['mail_smtp_server'] );
        $mail_smtp_server = $_POST['mail_smtp_server'];
    }

    if ( $_POST['smtp-port'] != $mail_smtp_port ) {
        if ( $_POST['smtp-port'] < 1 || $_POST['smtp-port'] > 65535 ) {
            show_msg_box( __( "SMTP port is invalid (must be between 1 and 65535)" ), "red" );
            return;
        }

        set_option( 'mail_smtp_port', $_POST['smtp-port'] );
        $mail_smtp_port = $_POST['smtp-port'];
    }

    if ( $_POST['smtp-user'] != $mail_smtp_user ) {
        if ( !$_POST['smtp-user'] ) {
            show_msg_box( __( "SMTP username must be specified" ), "red" );
            return;
        }

        set_option( 'mail_smtp_username', $_POST['smtp-user'] );
        $mail_smtp_user = $_POST['smtp-user'];
    }

    if ( $_POST['smtp-pass'] != $mail_smtp_pass ) {
        if ( !$_POST['smtp-pass'] ) {
            show_msg_box( __( "SMTP password must be specified" ), "red" );
            return;
        }

        set_option( 'mail_smtp_password', $_POST['smtp-pass'] );
        $mail_smtp_pass = $_POST['smtp-pass'];
    }

    if ( $_POST['sendmail-path'] != $mail_sendmail_path ) {
        if ( !$_POST['sendmail-path'] ) {
            show_msg_box( __( "Sendmail path must be specified" ), "red" );
            return;
        }

        set_option( 'mail_sendmail_path', $_POST['sendmail-path'] );
        $mail_sendmail_path = $_POST['sendmail-path'];
    }

    if ( $_POST['geoips-service'] != $geoip_service ) {
        if ( $_POST['geoips-service'] != 'api' && $_POST['geoips-service'] != 'database' ) {
            show_msg_box( __( "GeoIP service must be specified" ), "red" );
            return;
        }

        set_option( 'geoips_service', $_POST['geoips-service'] );
        $geoip_service = $_POST['geoips-service'];
    }

    if ( $_POST['geoips-apikey'] != $geoip_api_key ) {
        if ( $_POST['geoips-service'] == 'api' && !$_POST['geoips-apikey'] ) {
            show_msg_box( __( "GeoIP API key must be specified" ), "red" );
            return;
        }

        set_option( 'geoips_api_key', $_POST['geoips-apikey'] );
        $geoip_api_key = $_POST['geoips-apikey'];
    }

    if ( $_POST['geoips-database'] != $geoip_database_file ) {
        if ( $_POST['geoips-service'] == 'database' && !$_POST['geoips-database'] ) {
            show_msg_box( __( "GeoIP database location must be specified" ), "red" );
            return;
        }

        set_option( 'geoips_database', $_POST['geoips-database'] );
        $geoip_database_file = $_POST['geoips-database'];
    }

    show_msg_box( __( "Settings have been sucessfully updated" ), "green" );
}

if ( isset( $_POST['password'] ) ) {
    echo '<div id="output">';
    update_settings();
    echo '</div>';
}
?>
<form id="form" action="#" method="post">
    <?php generate_csrf_token(); ?>
    <div class="contentcontainers">
        <div class="contentcontainer left" style="width: 49%">
            <div class="headings alt">
                <h2 class="left"><?php _e( 'Site Options' ); ?></h2>
            </div>

            <!-- Application Info Start -->
            <div class="contentbox">
                <table id="id-form" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <th><?php _e( 'Admin Email:' ); ?></th>
                            <td><input type="text" class="inp-form" name="email" value="<?php echo $admin_email ?>" /></td>
                        </tr>
                        <tr>
                            <th><?php _e( 'URL Rewriting:' ); ?></th>
                            <td>
                                <input type="radio" class="inp-form" name="rewrite" value="true" <?php echo ( ( $rewrite == 'true' ) ? ( 'checked' ) : ( '' ) ) ?> /> Enabled
                                <input type="radio" class="inp-form" name="rewrite" value="false" <?php echo ( ( $rewrite == 'false' ) ? ( 'checked' ) : ( '' ) ) ?> /> Disabled
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Application Info Start -->
        </div>
        
        <div class="contentcontainer right" style="width: 49%">
            <div class="headings alt">
                <h2 class="left"><?php _e( 'reCAPTCHA' ); ?></h2>
            </div>

            <!-- Application Info Start -->
            <div class="contentbox">
                <table id="id-form" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <th><?php _e( 'Use reCAPTCHA:' ); ?></th>
                            <td>
                                <input type="radio" class="inp-form" name="recaptcha" value="true" <?php echo ( ( $recaptcha_enabled == 'true' ) ? ( 'checked' ) : ( '' ) ) ?> /> Enabled
                                <input type="radio" class="inp-form" name="recaptcha" value="false" <?php echo ( ( $recaptcha_enabled == 'false' ) ? ( 'checked' ) : ( '' ) ) ?> /> Disabled
                            </td>
                        </tr>
                        <tr id="recaptcha-settings">
                            <th><?php _e( 'Public Key:' ); ?></th>
                            <td><input type="text" class="inp-form" name="recaptcha-public" value="<?php echo $recaptcha_public_key ?>" /></td>
                        </tr>
                        <tr id="recaptcha-settings">
                            <th><?php _e( 'Private Key:' ); ?></th>
                            <td><input type="text" class="inp-form" name="recaptcha-private" value="<?php echo $recaptcha_private_key ?>" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Application Info Start -->
        </div>
        
        <div class="clear"></div>
        
        <div class="contentcontainer left" style="width: 49%; min-height: 160px;">
            <div class="headings alt">
                <h2 class="left"><?php _e( 'Mail Options' ); ?></h2>
            </div>

            <!-- Reset Analytics Data Start -->
            <div class="contentbox">
                <table id="id-form" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <th><?php _e( 'Protocol:' ); ?></th>
                            <td>
                                <input type="radio" class="inp-form" name="protocol" value="mail" <?php echo ( ( $mail_protocol == 'mail' ) ? ( 'checked' ) : ( '' ) ) ?> /> Mail<br />
                                <input type="radio" class="inp-form" name="protocol" value="sendmail" <?php echo ( ( $mail_protocol == 'sendmail' ) ? ( 'checked' ) : ( '' ) ) ?> /> Sendmail<br />
                                <input type="radio" class="inp-form" name="protocol" value="smtp" <?php echo ( ( $mail_protocol == 'smtp' ) ? ( 'checked' ) : ( '' ) ) ?> /> SMTP
                            </td>
                        </tr>
                        <tr id="mail-smtp">
                            <th><?php _e( 'SMTP Server:' ); ?></th>
                            <td><input type="text" class="inp-form" name="smtp-server" value="<?php echo $mail_smtp_server ?>" /></td>
                        </tr>
                        <tr id="mail-smtp">
                            <th><?php _e( 'SMTP Port:' ); ?></th>
                            <td><input type="text" class="inp-form" name="smtp-port" value="<?php echo $mail_smtp_port ?>" /></td>
                        </tr>
                        <tr id="mail-smtp">
                            <th><?php _e( 'SMTP Username:' ); ?></th>
                            <td><input type="text" class="inp-form" name="smtp-user" value="<?php echo $mail_smtp_user ?>" /></td>
                        </tr>
                        <tr id="mail-smtp">
                            <th><?php _e( 'SMTP Password:' ); ?></th>
                            <td><input type="text" class="inp-form" name="smtp-pass" value="<?php echo $mail_smtp_pass ?>" /></td>
                        </tr>
                        <tr id="mail-sendmail">
                            <th><?php _e( 'Sendmail Path:' ); ?></th>
                            <td><input type="text" class="inp-form" name="sendmail-path" value="<?php echo $mail_sendmail_path ?>" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Reset Analytics Data End -->
        </div>
        
        <div class="contentcontainer right" style="width: 49%">
            <div class="headings alt">
                <h2 class="left"><?php _e( 'IP Geolocation' ); ?></h2>
            </div>

            <!-- Reset Analytics Data Start -->
            <div class="contentbox">
                <table id="id-form" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <th><?php _e( 'Service:' ); ?></th>
                            <td>
                                <input type="radio" class="inp-form" name="geoips-service" value="api" <?php echo ( ( $geoip_service == 'api' ) ? ( 'checked' ) : ( '' ) ) ?> /> API
                                <input type="radio" class="inp-form" name="geoips-service" value="database" <?php echo ( ( $geoip_service == 'database' ) ? ( 'checked' ) : ( '' ) ) ?> /> Database
                            </td>
                        </tr>
                        <tr id="geoips-api">
                            <th><?php _e( 'API Key:' ); ?></th>
                            <td><input type="text" class="inp-form" name="geoips-apikey" value="<?php echo $geoip_api_key ?>" /></td>
                        </tr>
                        <tr id="geoips-database">
                            <th><?php _e( 'Database Location:' ); ?></th>
                            <td><input type="text" class="inp-form" name="geoips-database" value="<?php echo $geoip_database_file ?>" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Reset Analytics Data End -->
        </div>

        <div class="contentcontainer right" style="width: 49%">
            <div class="headings alt">
                <h2 class="left"><?php _e( 'Update Settings' ); ?></h2>
            </div>

            <div class="contentbox">
                <p><?php _e( 'You must verify your password in order to update the settings' ); ?></p>
                <table id="id-form" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <th valign="top"><?php _e( 'Current Password:' ); ?></th>
                            <td><input name="password" id="validate-text" type="password" class="inp-form" style="width: 155px;" /></td>
                            <td id="error"></td>
                        </tr>
                        <tr>
                            <th>&nbsp;</th>
                            <td><input name="apply" type="submit" value="<?php _e( 'Apply' ); ?>" class="form-submit" /></td>
                            <td>&nbsp;</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>