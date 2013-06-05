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

require_once 'geoip.php';

/**
 * API Class
 * Use to call API functions
 *
 * @package Little Software Stats
 * @author Little Apps
 */
class API {
    /**
     * @var string Application ID
     */
    private $app_id;
    /**
     * @var string Application version
     */
    private $app_ver;
    /**
     * @var MySQL MySQL Class
     */
    private $db;
    /**
     * @var resource File pointer for GeoIP database 
     */
    private $fp_geo_ip;
    
    /**
     * @var resource Single instance of class
     */
    private static $m_pInstance; 
    
    /**
     * Constructor for API class
     */
    function __construct() {
        global $db;
        
        $this->db = $db;
        
        if ( get_option( 'geoips_service' ) == 'database' )
            $this->fp_geo_ip = geoip_open( get_option( 'geoips_database' ), GEOIP_STANDARD );
    }
    
    /**
     * Destructor for API class 
     */
    function __destruct() {
        if ( isset( $this->fp_geo_ip ) )
            geoip_close( $this->fp_geo_ip );
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
            self::$m_pInstance = new API();

        return self::$m_pInstance;
    }
    
    /**
     * Adds information for started app
     * @access public
     * @param string $unique_id Unique User ID
     * @param string $session_id Session ID
     * @param int $timestamp Timestamp (GMT 0)
     * @param string $os_ver OS Version
     * @param int $os_service_pack OS Service Pack
     * @param int $os_arch OS Architecture (32 or 64 bit)
     * @param string $os_java_ver Java Version
     * @param string $os_net Mono/.NET Version
     * @param int $os_net_sp Mono/.NET Service Pack
     * @param int $lang_id Language ID
     * @param string $screen_res Screen Resolution (ie: 1024x768)
     * @param string $cpu_name CPU Name (ie: Core i7, Pentium IV)
     * @param string $cpu_brand CPU Brand (ie: AMD, Intel)
     * @param int $cpu_freq CPU Frequency (hertz)
     * @param int $cpu_cores CPU Cores
     * @param int $cpu_arch CPU Architecture
     * @param int $mem_total Total Memory (bytes)
     * @param int $mem_free Free Memory (bytes)
     * @param int $disk_total Total Disk Space (bytes)
     * @param int $disk_free Free Disk Space (bytes)
     * @return int Returns error code
     */
    public function start_app( $app_id, $app_ver, $unique_id, $session_id, $timestamp, 
            $os_ver, $os_service_pack, $os_arch, $os_java_ver, $os_net, $os_net_sp, 
            $lang_id, $screen_res, $cpu_name, $cpu_brand, $cpu_freq, $cpu_cores,
            $cpu_arch, $mem_total, $mem_free, $disk_total, $disk_free ) {
        if ( !$this->is_app_id_valid( $app_id ) )
            return -11;
        
        if ( !$this->is_app_ver_valid( $app_ver ) )
            return -14;
        
        if ( substr_count( $app_ver, '.' ) > 1 ) {
            // Remove trailing '.0' so '1.0.0' turns into '1.0'
            if ( substr( $app_ver, -2 ) == '.0' ) 
                $app_ver = substr( $app_ver, 0, -2 );
        }
        
        if ( !$this->is_user_id_valid( $unique_id ) || !$this->is_user_id_valid( $session_id ) )
            return -12;
        
        if ( !is_string( $app_id ) || !is_string( $app_ver ) || !is_string( $os_ver )
                || !is_string( $os_java_ver ) || !is_string( $os_net ) || !is_string( $screen_res )
                || !is_string( $cpu_name ) || !is_string( $cpu_brand ) )
            return -15;
        
        if ( !$this->is_timestamp_valid( $timestamp ) || !is_numeric( $os_service_pack ) || !is_numeric( $os_net_sp )
                || !is_numeric( $lang_id ) || !is_numeric( $cpu_freq ) || !is_numeric( $cpu_cores )|| !is_numeric($cpu_arch)
                || !is_numeric( $mem_total ) || !is_numeric( $disk_total ) || !is_numeric( $disk_free ) )
            return -15;
        
        $screen_res = str_replace( " ", "", $screen_res );
        $os_arch = intval( $os_arch );
        $cpu_arch = intval( $cpu_arch );
        
        if ( !preg_match( '/\d+x\d+/', $screen_res ) )
            return -15;
            
        if ( $os_arch != 32 && $os_arch != 64 )
            return -15;
        
        if ( $cpu_arch != 32 && $cpu_arch != 64 )
            return -15;
        
        switch ( strtolower( $cpu_brand ) ) {
            case "authenticamd":
            case "amd":
                $cpu_brand = "AMD";
                break;
            
            case "genuineintel":
            case "intel":
                $cpu_brand = "Intel";
                break;
            case "centaurhauls":
            case "centaur":
                $cpu_brand = "Centaur";
                break;
            
            case "cyrixinstead":
            case "cyrix":
                $cpu_brand = "Cyrix";
                break;
            
            case "transmetacpu":
            case "genuinetmx86":
            case "transmeta":
                $cpu_brand = "Transmeta";
                break;
            
            case "geode by nsc":
            case "nsc":
            case "national semiconductor":
                $cpu_brand = "National Semiconductor";
                break;
            
            case "nexgendriven":
            case "nexgen":
                $cpu_brand = "NexGen";
                break;
            
            case "riseriserise":
            case "rise":
                $cpu_brand = "Rise";
                break;
            
            case "sis sis sis ":
            case "sis":
                $cpu_brand = "SiS";
                break;
            
            case "umc umc umc ":
            case "umc":
                $cpu_brand = "UMC";
                break;
            
            case "via via via ":
            case "via":
                $cpu_brand = "VIA";
                break;
            
            case "vortex86 soc":
            case "vortex":
                $cpu_brand = "Vortex";
                break;
            default:
                // Unknown CPU
                return -15;
                break;
        }
        
        $ip_address = $this->get_ip_address();

        $country_code = '';
        $country = "Unknown";
        
        $location = $this->get_ip_location( $ip_address );
        if ( $location ) {
            $country_code = $location['code'];
            $country = $location['country'];
        }
        
        $now = time();
        
        $data = array( "UniqueUserId" => $unique_id, "Created" => $now, 
                "LastRecieved" => $now, "IPAddress" => $ip_address,  "CountryCode" => $country_code, 
                "Country" => $country, "OSVersion" => $os_ver, "OSServicePack" => $os_service_pack, 
                "OSArchitecture" => $os_arch, "JavaVer" => $os_java_ver, "NetVer" => $os_net, 
                "NetSP" => $os_net_sp, "LangID" => $lang_id, "ScreenRes" => $screen_res, "CPUName" => $cpu_name, 
                "CPUBrand" => $cpu_brand, "CPUFreq" => $cpu_freq, "CPUCores" => $cpu_cores,
                "CPUArch" => $cpu_arch, "MemTotal" => $mem_total, "MemFree" => $mem_free,
                "DiskTotal" => $disk_total, "DiskFree" => $disk_free );
        
        $this->db->insert_or_update( $data, $data, 'uniqueusers' );
        
        $this->db->insert( array( "SessionId" => $session_id, "UniqueUserId" => $unique_id, "StartApp" => $timestamp,
            "ApplicationId" => $app_id, "ApplicationVersion" => $app_ver), "sessions" );
        
        return 1;
    }
    
    /**
     * Called when app is finished
     * @access public
     * @param int $timestamp Timestamp (GMT 0)
     * @param string $session_id Session ID
     * @return int Returns error code
     */
    public function stop_app( $timestamp, $session_id ) {
        if ( !$this->is_user_id_valid( $session_id ) )
            return -12;
        
        if ( !$this->is_timestamp_valid( $timestamp ) )
            return -15;
        
        $this->update_last_recieved( $session_id );
        
        $this->db->update( "sessions", array( "StopApp" => $timestamp ), array( "SessionId" => $session_id ) );
        
        return 1;
    }
    
    /**
     * Used to track events
     * @access public
     * @param int $timestamp Timestamp (GMT 0)
     * @param string $session_id Session ID
     * @param string $category Category
     * @param string $name Name
     * @return int Returns error code
     */
    public function event( $timestamp, $session_id, $category, $name ) {
        if ( !$this->is_user_id_valid( $session_id ) )
            return -12;
        
        if ( !$this->is_timestamp_valid( $timestamp ) || !is_string( $category ) || !is_string( $name ) )
            return -15;
        
        $this->update_last_recieved( $session_id );
        
        $app_id = $this->get_session_app_id( $session_id );
        $app_ver = $this->get_session_app_ver( $session_id );
        
        $this->db->insert( array( "EventCode" => "ev", "EventCategory" => $category, 
            "EventName" => $name, "SessionId" => $session_id, "UtcTimestamp" => $timestamp,
            "ApplicationId" => $app_id, "ApplicationVersion" => $app_ver ), "events" );
        
        return 1;
    }
    
    /**
     * Used to track event values
     * @access public
     * @param int $timestamp Timestamp (GMT 0)
     * @param string $session_id Session ID
     * @param string $category Category
     * @param string $name Name
     * @param string $value Value
     * @return int Returns error code
     */
    public function event_value( $timestamp, $session_id, $category, $name, $value ) {
        if ( !$this->is_user_id_valid( $session_id ) )
            return -12;
        
        if ( !$this->is_timestamp_valid( $timestamp ) || !is_string( $category ) || !is_string( $name ) 
                || !is_string( $value ) )
            return -15;
        
        $this->update_last_recieved( $session_id );
        
        $app_id = $this->get_session_app_id( $session_id );
        $app_ver = $this->get_session_app_ver( $session_id );
        
        $this->db->insert( array( "EventCode" => "evV", "EventCategory" => $category, 
            "EventName" => $name, "EventValue" => $value, "SessionId" => $session_id, 
            "UtcTimestamp" => $timestamp, "ApplicationId" => $app_id, 
            "ApplicationVersion" => $app_ver ), "events" );
        
        return 1;
    }
    
    /**
     * Used to track event durations
     * @access public
     * @param int $timestamp Timestamp (GMT 0)
     * @param string $session_id Session ID
     * @param string $category Category
     * @param string $name Name
     * @param int $duration Duration (seconds)
     * @param bool $completed Completed?
     * @return int Returns error code
     */
    public function event_period( $timestamp, $session_id, $category, $name, $duration, $completed ) {
        if ( !$this->is_user_id_valid( $session_id ) )
            return -12;
        
        $completed = (bool)$completed;
        
        if ( !$this->is_timestamp_valid( $timestamp ) || !is_string( $category ) || !is_string( $name )
                || !is_numeric( $duration ) || !is_bool( $completed ) )
            return -15;
        
        $completed = ( ( $completed ) ? ( 1 ) : ( 0 ) );
        
        $this->update_last_recieved( $session_id );
        
        $app_id = $this->get_session_app_id( $session_id );
        $app_ver = $this->get_session_app_ver( $session_id );
        
        $this->db->insert( array( "EventCode" => "evP", "EventCategory" => $category, 
            "EventName" => $name, "EventDuration" => $duration, "EventCompleted" => $completed,
            "SessionId" => $session_id, "UtcTimestamp" => $timestamp,
            "ApplicationId" => $app_id, "ApplicationVersion" => $app_ver ), "events" );
        
        return 1;
    }
    
    /**
     * Simple logging utility
     * @access public
     * @param int $timestamp Timestamp (GMT 0)
     * @param string $session_id Session ID
     * @param string $message Message
     * @return int Returns error code
     */
    public function log( $timestamp, $session_id, $message ) {
        if ( !$this->is_user_id_valid( $session_id ) )
            return -12;
        
        if ( !$this->is_timestamp_valid( $timestamp ) || !is_string( $message ) )
            return -15;
        
        $this->update_last_recieved( $session_id );
        
        $app_id = $this->get_session_app_id( $session_id );
        $app_ver = $this->get_session_app_ver( $session_id );
        
        $this->db->insert( array( "EventCode" => "lg", "LogMessage" => $message,
            "UtcTimestamp" => $timestamp, "SessionId" => $session_id,
            "ApplicationId" => $app_id, "ApplicationVersion" => $app_ver ), "events" );
        
        return 1;
    }
    
    /**
     * Used for tracking specfic information
     * @access public
     * @param int $timestamp Timestamp (GMT 0)
     * @param string $session_id Session ID
     * @param string $name Name
     * @param string $value Value
     * @return int Returns error code
     */
    public function custom_data( $timestamp, $session_id, $name, $value ) {
        if ( !$this->is_user_id_valid( $session_id ) )
            return -12;
        
        if ( !$this->is_timestamp_valid( $timestamp ) || !is_string( $name ) || !is_string( $value ) )
            return -15;
        
        $this->update_last_recieved( $session_id );
        
        $app_id = $this->get_session_app_id( $session_id );
        $app_ver = $this->get_session_app_ver( $session_id );
        
        $this->db->insert( array( "EventCode" => "ctD", "EventCustomName" => $name, 
            "EventCustomValue" => $value, "SessionId" => $session_id, "UtcTimestamp" => $timestamp,
            "ApplicationId" => $app_id, "ApplicationVersion" => $app_ver ), "events" );
        
        return 1;
    }
    
    /**
     * Sent when an exception occurs
     * @access public
     * @param int $timestamp Timestamp (GMT 0)
     * @param string $session_id Session ID
     * @param string $message Message
     * @param string $stack_trace Stack Trace
     * @param string $source Source
     * @param string $target_site Target Site
     * @return int Returns error code
     */
    public function exception( $timestamp, $session_id, $message, $stack_trace, $source, $target_site ) {
        if ( !$this->is_user_id_valid( $session_id ) )
            return -12;
        
        if ( !$this->is_timestamp_valid( $timestamp ) || !is_string( $message ) || !is_string( $stack_trace )
                || !is_string( $source ) || !is_string( $target_site ) )
            return -15;
        
        $this->update_last_recieved( $session_id );
        
        $app_id = $this->get_session_app_id( $session_id );
        $app_ver = $this->get_session_app_ver( $session_id );
        
        $this->db->insert( array( "EventCode" => "exC", "ExceptionMsg" => $message,
                "ExceptionStackTrace" => $stack_trace, "ExceptionSource" => $source,
                "ExceptionTargetSite" => $target_site, "UtcTimestamp" => $timestamp, 
                "SessionId" => $session_id, "ApplicationId" => $app_id, 
                "ApplicationVersion" => $app_ver ), "events" );
        
        return 1;
    }
    
    /**
     * Sent when application is installed
     * @access public
     * @param int $timestamp Timestamp (GMT 0)
     * @param string $session_id Session ID
     * @param string $app_id Application ID
     * @param string $app_ver Application Version
     * @return int Returns error code
     */
    public function install( $timestamp, $session_id, $app_id, $app_ver ) {
        if ( !$this->is_app_id_valid( $app_id ) )
            return -11;
        
        if ( !$this->is_app_ver_valid( $app_ver ) )
            return -14;
        
        if ( !$this->is_user_id_valid( $session_id ) )
            return -12;
        
        if ( !$this->is_timestamp_valid( $timestamp ) )
            return -15;

        $this->db->insert( array( "EventCode" => "ist", "UtcTimestamp" => $timestamp, "SessionId" => $session_id, 
            "ApplicationId" => $app_id, "ApplicationVersion" => $app_ver ), "events" );
        
        return 1;
    }
    
    /**
     * Sent when application is uninstalled
     * @access public
     * @param int $timestamp Timestamp (GMT 0)
     * @param string $session_id Session ID
     * @param string $app_id Application ID
     * @param string $app_ver Application Version
     * @return int Returns error code
     */
    public function uninstall( $timestamp, $session_id, $app_id, $app_ver ) {
        if ( !$this->is_app_id_valid( $app_id ) )
            return -11;
        
        if ( !$this->is_app_ver_valid( $app_ver ) )
            return -14;
        
        if ( !$this->is_user_id_valid( $session_id ) )
            return -12;
        
        if ( !$this->is_timestamp_valid( $timestamp ) )
            return -15;
        
        $this->db->insert( array( "EventCode" => "ust", "UtcTimestamp" => $timestamp, "SessionId" => $session_id, 
            "ApplicationId" => $app_id, "ApplicationVersion" => $app_ver ), "events" );
        
        return 1;
    }
    
    /**
     * Checks if App ID is valid
     * @access private
     * @param string $app_id Application Id
     * @return bool True if App ID is valid
     */
    private function is_app_id_valid( $app_id ) {
        // Make sure app id is valid format
        if ( $app_id == '' || !preg_match( "/^([a-f0-9]{32})/", $app_id ) )
            return false;
        
        // Check if app id is in database
        $this->db->select( "applications", array( "ApplicationId" => $app_id ), '', '0,1' );
        if ( $this->db->records == 0 )
            return false;
        
        // Make sure application is recieving
        if ( $this->db->arrayed_result['ApplicationRecieving'] == 0 )
            return false;
        
        return true;
    }
    
    /**
     * Checks if App Version is valid
     * @access private
     * @param string $app_ver Application Version
     * @return bool True if App Version is valid
     */
    private function is_app_ver_valid( $app_ver ) {
        if ( $app_ver == '' || !preg_match( "/^(?:(\d+)\.)?(?:(\d+)\.)?(\*|\d+)$/", $app_ver ) )
            return false;
        
        return true;
    }
    
    /**
     * Checks if timestamp is valid and not newer then 3 days
     * @access private
     * @param type $timestamp
     * @return bool True if timestamp is valid
     */
    private function is_timestamp_valid( $timestamp ) {
        if ( !is_numeric( $timestamp ) )
            return false;
        
        $timestamp = intval( $timestamp );
        
        if ( $timestamp > strtotime( '+3 days' ) )
            return false;
        
        return true;
    }
    
    /**
     * Checks if user ID is valid
     * @access private
     * @param string $user_id User ID
     * @return bool True if user ID is valid
     */
    private function is_user_id_valid( $user_id ) {
        if ( $user_id == '' || !preg_match( "/^([A-F0-9]{32})/", $user_id ) )
            return false;
        
        return true;
    }
    
    /**
     * Updates last recieved field for unique user id
     * @access private
     * @param string $session_id Session ID
     */
    private function update_last_recieved( $session_id ) {
        $this->db->select( "sessions", array( "SessionId" => $session_id ) );
        
        if ( isset( $this->db->arrayed_result['UniqueUserId'] ) && $this->db->records > 0 ) {
            $unique_id = $this->db->arrayed_result['UniqueUserId'];
            
            $this->db->update( "uniqueusers", array( "LastRecieved" => time() ), array( "UniqueUserId" => $unique_id ) );
        }
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
     * Gets country for ip address
     * @access private
     * @param string $ip IP address
     * @return array|bool Returns location array if successful, otherwise false
     */
    public function get_ip_location( $ip ) {
        if ( !filter_var( $ip, FILTER_VALIDATE_IP ) )
            return false;
        
        // Use database if API is disabled
        if ( isset( $this->fp_geo_ip ) )
            return $this->get_ip_location_by_db( $ip );
        else
            return $this->get_ip_location_by_api( $ip );
        
        // Return false if nothing was found
        return false;
    }

    /**
     * Tries to lookup country for IP address using MaxMinds database
     * @access private
     * @param string $sIP IP address
     * @return array|bool Returns location array if successful, otherwise false
     */
    private function get_ip_location_by_db( $ip ) {
        if ( !isset( $this->fp_geo_ip ) )
            return false;
        
        $code = geoip_country_code_by_addr( $this->fp_geo_ip, $ip );
        $country = geoip_country_name_by_addr( $this->fp_geo_ip, $ip );
        
        if ( $country == '' )
            $country = 'Unknown';
        
        return array( 
            'code' => $code, 
            'country' => ucwords( strtolower( $country ) )
        );
    }
    
    /**
     * Looks up ip address location using GeoIPs API
     * @access private
     * @param string $sIP IP address
     * @return array|bool Returns location array if successful, otherwise false
     */
    private function get_ip_location_by_api( $ip ) {
        if ( isset( $this->fp_geo_ip ) )
            return false;

        $api_key = get_option( 'geoips_api_key' );
        $url = 'http://api.geoips.com/ip/'.$ip.'/key/'.$api_key.'/output/xml';
	
        if ( !( $data = get_page_contents( $url ) ) )
            return false;
	
        libxml_use_internal_errors( true );
        
        $xml = simplexml_load_string( $data );
        
        if ( count( libxml_get_errors() ) > 0 || !is_object( $xml ) ) {
            libxml_clear_errors();
            return false;
        }
        
        if ( $xml->status != 'Success' || isset( $xml->message ) )
            return false;
        
        if ( empty( $xml->country_name ) )
            return false;
        
        return array(
            'code' => $xml->country_code,
            'country' => ucwords( strtolower( $xml->country_name ) )
        );
    }
    
    /**
     * Gets the application ID from session ID
     * @access private
     * @param string $session_id Session ID
     * @return bool|string Returns application ID, otherwise false if it wasnt found 
     */
    private function get_session_app_id( $session_id ) {
        $this->db->select( 'sessions', array( 'SessionId' => $session_id ), '', '0,1' );
        
        if ( $this->db->records == 0 )
            return false;
        
        return $this->db->arrayed_result['ApplicationId'];
    }
    
    /**
     * Gets the application version from session ID
     * @access private
     * @param string $session_id Session ID
     * @return bool|string Returns application version, otherwise false if it wasnt found 
     */
    private function get_session_app_ver( $session_id ) {
        $this->db->select( 'sessions', array('SessionId' => $session_id ), '', '0,1' );
        
        if ( $this->db->records == 0 )
            return false;
        
        return $this->db->arrayed_result['ApplicationVersion'];
    }
}
