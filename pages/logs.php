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

$rows = array();

$query = "SELECT e.LogMessage, MAX(e.UtcTimestamp) AS 'date', COUNT(*) AS 'total', COUNT(DISTINCT s.UniqueUserId) AS 'unique' ";
$query .= "FROM `".$db->prefix."events` AS e, `".$db->prefix."sessions` AS s ";
$query .= "WHERE e.SessionId = s.SessionId AND e.EventCode = 'lg' ";
$query .= "AND e.ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND e.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
$query .= "AND e.UtcTimestamp BETWEEN ".$start_time." AND ".$end_time." ";
$query .= "GROUP BY e.LogMessage";

$db->execute_sql( $query );

if ( $db->records == 1 )
    $rows[] = $db->array_result();
else if ( $db->records > 1 )
    $rows = $db->array_results();
?>
<div id="contentcontainers">	
    <!-- Overview Start -->
    <div class="contentcontainer">
        <div class="headings altheading">
            <h2><?php _e( 'Logs' ); ?></h2>
        </div>
        <div class="contentbox" style="padding-top: 0;">
            <table style="width: 100%" class="datatable">
                <thead>
                    <tr>
                        <th><?php _e( 'Message' ); ?></th>
                        <th><?php _e( 'Date' ); ?></th>
                        <th><?php _e( 'Execution' ); ?></th>
                        <th><?php _e( 'Unique' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $row ) : ?>
                    <tr>
                        <td><?php echo $row['LogMessage'] ?></td>
                        <td><?php echo date( 'Y-m-d h:i:s A', intval( $row['date'] ) ) ?></td>
                        <td><?php echo $row['total'] ?></td>
                        <td><?php echo $row['unique'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Overview End -->
</div>