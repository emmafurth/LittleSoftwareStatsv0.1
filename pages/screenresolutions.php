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

$version_query = ( ( $app_ver != "all" ) ? ( "AND s.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );

$query = "SELECT u.ScreenRes, COUNT(*) AS 'total', COUNT(DISTINCT u.UniqueUserId) AS 'unique', ";
$query .= "((COUNT(*) / (SELECT COUNT(*) FROM `".$db->prefix."sessions` AS s WHERE s.ApplicationId = '".$app_id."' " . $version_query . " AND s.StartApp BETWEEN '".$start_time."' AND '".$end_time."')) * 100) AS 'percent' ";
$query .= "FROM `".$db->prefix."sessions` AS s, `".$db->prefix."uniqueusers` AS u ";
$query .= "WHERE s.UniqueUserId = u.UniqueUserId ";
$query .= "AND s.ApplicationId = '".$app_id."' " . $version_query;
$query .= "AND s.StartApp BETWEEN '".$start_time."' AND '".$end_time."' ";
$query .= "GROUP BY u.ScreenRes";

$db->execute_sql( $query );

$resolutions_chart_data = array();

if ( $db->records == 1 )
    $resolutions_chart_data[] = $db->array_result();
else if ( $db->records > 1 )
    $resolutions_chart_data = $db->array_results();
?>
<div id="contentcontainers">
    <!-- Overview Start -->
    <div class="contentcontainer">
        <div class="headings altheading">
            <h2><?php _e( 'Screen Resolutions' ); ?></h2>
        </div>
        <div class="contentbox" style="padding-top: 0;">
            <table style="width: 100%" class="datatable">
                <thead>
                    <tr>
                        <th><?php _e( 'Screen Resolution' ); ?></th>
                        <th><?php _e( 'Executions' ); ?></th>
                        <th><?php _e( 'Unique' ); ?></th>
                        <th><?php _e( 'Percentage Of Executions' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $resolutions_chart_data as $row ) : $percent = round( $row['percent'], 2 ) . "%"; ?>
                    <tr>
                        <td><?php echo $row['ScreenRes'] ?></td>
                        <td><?php echo $row['total'] ?></td>
                        <td><?php echo $row['unique'] ?></td>
                        <td>
                            <div style="width: 85%" class="usagebox left">
                                <div style="width:<?php echo $percent ?>" class="lowbar"></div>
                            </div>
                            <span style="padding: 8px" class="right"><?php echo $percent ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Overview End -->
</div>