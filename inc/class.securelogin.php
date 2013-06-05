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

require_once 'class.passwordhash.php';

/**
 * Secure login class
 * Manage access to Little Software Stats
 * 
 * @package Little Software Stats
 * @author Little Apps
 */
class SecureLogin {
    /**
     * @var resource Connection to database 
     */
    private $db;

    /**
     * @var resource PHP crypt() wrapper 
     */
    private $password_hash;

    /**
     * @var resource Single instance of class
     */
    private static $m_pInstance; 
    
    /**
     * Constructor for SecureLogin class
     */
    function __construct( ) {
        global $db;
        
        $this->db = $db;
        $this->password_hash = new PasswordHash(8, false);

        if( !isset( $_SESSION['ValidUser'] ) )
            $_SESSION['ValidUser'] = 0;
    }
    
    /**
     * Gets single instance of class
     * @access public
     * @static
     * @return resource Single instance of class 
     */
    public static function getInstance()
    {
        if (!self::$m_pInstance)
            self::$m_pInstance = new SecureLogin();

        return self::$m_pInstance;
    }

    /**
     * Checks if user is logged in
     * @access public
     * @return bool Returns true if user is logged in 
     */
    public function check_user( ) {
        if ( isset( $_SESSION['UserName'] ) && 
            $_SESSION['ValidUser'] == 1 && 
            $_SESSION['REMOTE_ADDR'] == $this->get_ip_address() )
            return true;

        return false;
    }
	
    /**
     * Tries to login user using username and password
     * @access public
     * @param string $user Username
     * @param string $pass Password (plain text)
     * @return string Returns error if username/password is invalid
     */
    public function login_user( $user, $pass ) {
        if ( trim( $user ) == "" || trim( $pass ) == "" )
            return __( "Username and/or password cannot be empty" );

        if ( !$this->db->select( "users", array( "UserName" => trim( $user ) ), "", "0,1" ) )
            return __( "Unable to query database: " ) . $this->db->last_error;

        if ( $this->db->records == 1 ) {
            if ( $this->password_hash->check_password( $pass, $this->db->arrayed_result['UserPass'] ) ) {
                // Clear activation key if its been set
                if ( $this->db->arrayed_result['ActivateKey'] != "" )
                    $this->db->update( "users", array( "ActivateKey" => "" ), array( "UserName" => trim( $user ) ) );

                // Prevent session hijacking 
                session_regenerate_id( );
                $_SESSION['REMOTE_ADDR'] = $this->get_ip_address();

                $_SESSION['ValidUser'] = 1;
                $_SESSION['UserName'] = $user;

                return "";
            }
        } 

        $_SESSION['ValidUser'] = 0;

        return __( "Username and/or password is invalid" );
    }
	
    /**
     * Registers user into database
     * @param string $user Username
     * @param string $pass1 Password (plain text)
     * @param string $pass2 Repeat password
     * @param string $email E-mail address
     * @return string Returns error if username, password, or email is invalid
     */
    public function register_user( $user, $pass1, $pass2, $email ) {
        // Check valid username
        if ( !preg_match( "/^[a-z\d_]{5,20}$/i", $user ) ) {
            if ( strlen( $user ) < 5 ) return __( "Username must be at least 5 characters" );
            else if ( strlen( $user ) > 20 ) return __( "Username cannot be more then 20 characters" );
            else return __( "Username can only contain alpha-numeric characters (a-z, A-Z, 0-9) and underscores" );
        }

        // Check valid email address
        if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) )
            return __( "E-mail address is invalid" );

        // Check passwords
        if ( trim( $pass1 ) != trim( $pass2 ) ) return __( "Passwords must be identical" );
        else if ( strlen( trim( $pass1 ) ) < 6 ) return __( "Password must be longer than 6 characters" );

        // Check if username already exists
        if ( !$this->db->select( "users", array( "UserName" => $user ), "", "0,1" ) )
            return __( "Unable to query database: " ) . $this->db->last_error;
        if ( $this->db->records == 1 )
            return __( "Another user has already registered that username" );

        // Check if email already exists
        if ( !$this->db->select( "users", array( "UserEmail" => $email ), "", "0,1" ) )
            return __( "Unable to query database: " ) . $this->db->last_error;
        if ( $this->db->records == 1 )
            return __( "Another user has already registered with that e-mail address" );

        $pass_hash = $this->password_hash->hash_password( trim ( $pass1 ) );

        // Add username to table
        if ( !$this->db->insert( array( "UserName" => $user, "UserPass" => $pass_hash, "UserEmail" => $email ), "users" ) )
            return __( "Unable to query database: " ) . $this->db->last_error;

        return "";
    }
	
    /**
     * Sends e-mail to user with link to reset password
     * @access public
     * @param string $email E-mail address
     * @return string Returns error if e-mail address is not found or unable to send e-mail 
     */
    public function forgot_password( $email ) {
        if (!$this->db->select( "users", array( "UserEmail" => $email ), "", "0,1" ) )
            return __( "Unable to query database: " ) . $this->db->last_error;

        if ( $this->db->records == 0 )
            return __( "E-mail address does not exist" ) . "\n";

        $rand_key = $this->make_random_password( 20 );

        if ( !$this->db->update( "users", array( "ActivateKey" => $rand_key ), array( "UserEmail" => $email ) ) )
            return __( "Unable to query database: " ) . $this->db->last_error;

        $subject = __( "Your password at " ) . SITE_NAME; 
        $message = __( "Someone requested that the password be reset for the following account:"  ) . "\r\n\r\n";
        $message .= __( "Username: "  ) . $this->db->arrayed_result['UserName'] . "\r\n\r\n";
        $message .= SITE_URL . "\r\n\r\n";
        $message .= __( "If this was a mistake, just ignore this email and nothing will happen."  ) . "\r\n\r\n";
        $message .= __( "To reset your password, visit the following address:" ) . "\r\n\r\n";
        $message .= "<". SITE_URL . "/login.php?action=resetPwd&key=$rand_key&login=".rawurlencode($this->db->arrayed_result['UserName']).">\r\n\r\n";
        $message .= __( "This is an automated response, please do not reply!" ) . "\n"; 

        if ( !send_mail( $email, $subject, $message ) ) 
            return __( "Unable to send password reset e-mail" );

        return "";
    }
	
    /**
     * Changes password using key sent to e-mail address
     * @access public
     * @param string $user Username
     * @param string $pass New password (plain text)
     * @param string $pass2 New password (again)
     * @param string $key Key sent to e-mail address
     * @return string Returns error if unable to change password 
     */
    public function change_password( $user, $pass, $pass2, $key ) {
        if ( !$this->db->select( "users", array( "UserName" => $user ), "", "0,1" ) )
            return __( "Username does not exist" );

        if ( !$this->db->select( "users", array( "UserName" => $user, "ActivateKey" => $key ), "", "0,1" ) )
            return __( "Activation key does not exist" );

        // Check passwords
        if ( trim( $pass ) != trim( $pass2 ) ) 
            return __( "Passwords must be identical" );
        else if ( strlen( trim( $pass ) ) < 6 ) 
            return __( "Password must be longer then 6 characters" );

        $pass_hash = $this->password_hash->hash_password( trim ( $pass ) );

        if ( !$this->db->update( "users", array( "ActivateKey" => "", "UserPass" => $pass_hash ), array( "UserName" => $user ) ) )
            return __( "Unable to query database: " ) . $this->db->last_error;

        // Notify user of password change
        $subject = __( "Your account at " ) . SITE_NAME;
        $message = __( "Password has been changed for user: " ) . " $user \r\n";
        $message .= __( "This is an automated response, please do not reply!" );

        if ( !send_mail( $this->db->arrayed_result['UserEmail'], $subject, $message ) )
            return __( "Unable to send password notification e-mail" );

        $this->logout_user( );

        return "";
    }
	
    /**
     * Generates a random password
     * @access private
     * @param int $length Length of password
     * @return string Generated password
     */
    private function make_random_password( $length ) { 
        $pass = '';

        $salt = "abcdefghijklmnopqrstuvwxyz0123456789";
        $salt_len = strlen( $salt );

        mt_srand(); 

        for ( $i = 0; $i <= $length; $i++ ) { 
            $chr = $salt[ mt_rand( 0, $salt_len - 1 ) ];
            $pass = $pass . $chr;
        } 

        return $pass;
    } 
        
    /**
     * Checks if IP address is valid and not a private IP
     * @access private
     * @param string $ip IP Address
     * @return boolean Returns true if IP is valid, otherwise, false
     */
    private function check_ip_address( $ip ) {
        $ip_long = ip2long( $ip );
        
        if ( is_numeric( $ip_long ) && $ip_long > 1 && $ip_long != false ) {
            $private_ips = array(
                array( "0.0.0.0", "2.255.255.255" ),
                array( "10.0.0.0", "10.255.255.255" ),
                array( "127.0.0.0", "127.255.255.255" ),
                array( "169.254.0.0", "169.254.255.255" ),
                array( "172.16.0.0", "172.31.255.255" ),
                array( "192.0.2.0", "192.0.2.255" ),
                array( "192.168.0.0", "192.168.255.255" ),
                array( "255.255.255.0", "255.255.255.255" )
            );

            foreach ( $private_ips as $range ) {
                $min = ip2long( $range[0] );
                $max = ip2long( $range[1] );
                if ( $ip_long >= $min && $ip_long <= $max )
                    return false;
            }

            return true;
        }
        
        return false;
    }


    /**
     * Gets ip address of client
     * @access private
     * @return string IP Address 
     */
    private function get_ip_address() {
        // Get ip address
        if ( ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) && $this->check_ip_address( $_SERVER['HTTP_CLIENT_IP'] ) )
            return $_SERVER['HTTP_CLIENT_IP'];
        
        if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip_array = explode( ",", $_SERVER['HTTP_X_FORWARDED_FOR'] );
            if ( $this->check_ip_address( trim( $ip_array[count( $ip_array ) - 1] ) ) )
                return trim( $ip_array[count( $ip_array ) - 1] );
        }
        
        if ( ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) && $this->check_ip_address( $_SERVER['HTTP_X_FORWARDED'] ) )
            return $_SERVER['HTTP_X_FORWARDED'];
        
        if ( ( isset( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ) ) && $this->check_ip_address( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ) )
            return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        
        if ( ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) && $this->check_ip_address( $_SERVER['HTTP_FORWARDED_FOR'] ) )
            return $_SERVER['HTTP_FORWARDED_FOR'];
        
        if ( ( isset( $_SERVER['HTTP_FORWARDED'] ) ) && $this->check_ip_address( $_SERVER['HTTP_FORWARDED'] ) )
            return $_SERVER['HTTP_FORWARDED'];
        
        // If all other IP addresses are invalid -> Go with REMOTE_ADDR
        return $_SERVER['REMOTE_ADDR'];
    }
	
    /**
     * Logout user
     * @access public
     */
    public function logout_user( ){
        // Unset all variables
        unset( $_SESSION['ValidUser'] );
        unset( $_SESSION['UserName'] );
        unset( $_SESSION['REMOTE_ADDR'] );

        // Destroy the session
        session_destroy( );
    }
}
