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

// Arrays for MySQL queries
$countries = array();
$operating_systems = array();
$languages = array();

$version_query = ( ( $app_ver != "all" ) ? ( "AND s.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );

$query = "SELECT u.LangID, l.DisplayName, ";
$query .= "((COUNT(*) / (SELECT COUNT(*) FROM `".$db->prefix."sessions` AS s WHERE s.ApplicationId = '".$app_id."' " . $version_query . " AND s.StartApp BETWEEN '".$start_time."' AND '".$end_time."')) * 100) AS 'percent' ";
$query .= "FROM `".$db->prefix."sessions` AS s, `".$db->prefix."uniqueusers` AS u, `".$db->prefix."locales` AS l ";
$query .= "WHERE s.UniqueUserId = u.UniqueUserId AND u.LangID = l.LCID ";
$query .= "AND s.ApplicationId = '".$app_id."' " . $version_query;
$query .= "AND s.StartApp BETWEEN '".$start_time."' AND '".$end_time."' ";
$query .= "GROUP BY u.LangID ";
$query .= "ORDER BY percent DESC ";
$query .= "LIMIT 0,5";

$db->execute_sql( $query );

$languages = array();

if ( $db->records == 1 )
    $languages[] = $db->array_result();
else if ( $db->records > 1 )
    $languages = $db->array_results();

$query = "SELECT u.OSVersion, ";
$query .= "((COUNT(*) / (SELECT COUNT(*) FROM `".$db->prefix."sessions` AS s WHERE s.ApplicationId = '".$app_id."' " . $version_query . " AND s.StartApp BETWEEN '".$start_time."' AND '".$end_time."')) * 100) AS 'percent' ";
$query .= "FROM `".$db->prefix."sessions` AS s, `".$db->prefix."uniqueusers` AS u ";
$query .= "WHERE s.UniqueUserId = u.UniqueUserId ";
$query .= "AND s.ApplicationId = '".$app_id."' " . $version_query;
$query .= "AND s.StartApp BETWEEN '".$start_time."' AND '".$end_time."' ";
$query .= "GROUP BY u.OSVersion ";
$query .= "ORDER BY percent DESC ";
$query .= "LIMIT 0,5";

$db->execute_sql( $query );

$operating_systems = array();

if ( $db->records == 1 )
    $operating_systems[] = $db->array_result();
else if ( $db->records > 1 )
    $operating_systems = $db->array_results();

$query = "SELECT u.Country, ";
$query .= "((COUNT(*) / (SELECT COUNT(*) FROM `".$db->prefix."sessions` AS s WHERE s.ApplicationId = '".$app_id."' " . $version_query . " AND s.StartApp BETWEEN '".$start_time."' AND '".$end_time."')) * 100) AS 'percent' ";
$query .= "FROM `".$db->prefix."sessions` AS s, `".$db->prefix."uniqueusers` AS u ";
$query .= "WHERE s.UniqueUserId = u.UniqueUserId ";
$query .= "AND s.ApplicationId = '".$app_id."' " . $version_query;
$query .= "AND s.StartApp BETWEEN '".$start_time."' AND '".$end_time."' ";
$query .= "GROUP BY u.Country ";
$query .= "ORDER BY percent DESC ";
$query .= "LIMIT 0,5";

$db->execute_sql( $query );

$countries = array();

if ( $db->records == 1 )
    $countries[] = $db->array_result();
else if ( $db->records > 1 )
    $countries = $db->array_results();

// Get events
$version_query = ( ( $app_ver != "all" ) ? ( "AND ApplicationVersion = '".$app_ver."' " ) : ( "" ) );

$query = "SELECT EventName, ";
$query .= "((COUNT(*) / (SELECT COUNT(*) FROM `".$db->prefix."events` WHERE EventCode = 'ev' AND ApplicationId = '".$app_id."' " . $version_query . " AND UtcTimestamp BETWEEN '".$start_time."' AND '".$end_time."')) * 100) AS 'percent' ";
$query .= "FROM `".$db->prefix."events` ";
$query .= "WHERE EventCode = 'ev' ";
$query .= "AND ApplicationId = '".$app_id."' " . $version_query;
$query .= "AND UtcTimestamp BETWEEN '".$start_time."' AND '".$end_time."' ";
$query .= "GROUP BY EventName ";
$query .= "ORDER BY percent DESC ";
$query .= "LIMIT 0,5";

$db->execute_sql( $query );

$events = array();

if ( $db->records == 1 )
    $events[] = $db->array_result();
else if ( $db->records > 1 )
    $events = $db->array_results();

// Create date range
$date_range = create_date_range_array( $start_time, $end_time );

$start_point = $date_range[0];

$area_chart_data = array(
    __( 'Executions' ) => array(),
    __( 'Installs' ) => array(),
    __( 'Uninstalls' ) => array()
);

for ( $i = 0; $i < count($date_range) - 1 ;$i++ ) {
    $start = $date_range[$i];
    $end = $date_range[$i + 1];
    
    $execs = $db->select_sessions( $app_id, $app_ver, $start, $end, '*', false, true );
    $installs = $db->select_events( 'ist', $app_id, $app_ver, $start, $end, true );
    $uninstalls = $db->select_events( 'ust', $app_id, $app_ver, $start, $end, true );
    
    $area_chart_data[__( 'Executions' )][] = $execs;
    $area_chart_data[__( 'Installs' )][] = $installs;
    $area_chart_data[__( 'Uninstalls' )][] = $uninstalls;
}
?>

<script type="text/javascript">
var chart;
$(document).ready(function() {
        chart = new Highcharts.Chart({
                chart: {
                        renderTo: 'chart_div',
                        defaultSeriesType: 'line',
                        height: 200
                },
                title: {
                        text: '<?php echo __( 'Statistics for ' ) . date( "F j, Y", $start_time ) . ' ' . __( 'to' ) . ' ' . date( "F j, Y", $end_time ); ?>',
                        x: -20 //center
                },
                plotOptions: {
                    series: {
                        pointStart: <?php printf('%d000', $start_point); ?>,
                        pointInterval: <?php echo $tick_interval * 1000; ?> 
                    }
                },
                xAxis: {
                        type: 'datetime',
                        allowDecimals: false
                },
                yAxis: {
                        title: ''
                },
                legend: {
                        layout: 'horizontal',
                        align: 'right',
                        verticalAlign: 'top',
                        floating: true,
                        x: -10,
                        y: -10,
                        borderWidth: 0
                },
                series: <?php echo convert_area_chart_data_to_json( $area_chart_data ); ?>
        });


});
</script>

<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Overview' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowleft.jpg'); ?>"></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowright.jpg'); ?>"></th>
        </tr>
        <tr>
            <td id="tbl-border-left"></td>
            <td>
                <div id="content-table-inner" style="height: 200px">
                    <div id="chart_div"></div>
                </div>
            </td>
            <td id="tbl-border-right"></td>
        </tr>
        <tr>
            <th class="sized bottomleft"></th>
            <td id="tbl-border-bottom">&nbsp;</td>
            <th class="sized bottomright"></th>
        </tr>
    </tbody>
</table>
<!-- end stats graph -->

<div class="clear">&nbsp;</div>
<div class="clear">&nbsp;</div>

<div class="contentcontainers">
    <!-- Events Start -->
    <div class="contentcontainer left" style="width: 49%">
        <div class="headings alt">
            <h2><?php _e( 'Events' ); ?></h2>
        </div>
        <div class="contentbox">
            <?php if ( count( $events ) > 0 ) : ?>
            <table>
                <?php foreach ( $events as $row ) : ?>
                <tr>
                    <td width="150"><strong><?php echo $row['EventName']; ?></strong></td>
                    <td width="500">
                        <div class="usagebox">
                            <div class="lowbar" style="width: <?php echo $row['percent'] . '%;' ?>"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Events End -->

    <!-- OSes Start -->
    <div class="contentcontainer right" style="width: 49%">
        <div class="headings alt">
            <h2><?php _e( 'Operating Systems' ); ?></h2>
        </div>
        <div class="contentbox">
            <?php if ( count( $operating_systems ) > 0 ) : ?>
            <table>
                <?php foreach ( $operating_systems as $row ) : ?>
                <tr>
                    <td width="150"><strong><?php echo $row['OSVersion']; ?></strong></td>
                    <td width="500">
                        <div class="usagebox">
                            <div class="lowbar" style="width: <?php echo $row['percent'] . '%;' ?>"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <!-- OSes End -->

    <!-- Countries Start -->
    <div class="contentcontainer left" style="width: 49%; clear: both">
        <div class="headings alt">
            <h2><?php _e( 'Countries' ); ?></h2>
        </div>
        <div class="contentbox">
            <?php if ( count( $countries ) > 0 ) : ?>
            <table>
                <?php foreach ( $countries as $row ) : ?>
                <tr>
                    <td width="150"><strong><?php echo $row['Country']; ?></strong></td>
                    <td width="500">
                        <div class="usagebox">
                            <div class="lowbar" style="width: <?php echo $row['percent'] . '%;' ?>"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Countries End -->

    <!-- Languages Start -->
    <div class="contentcontainer right" style="width: 49%">
        <div class="headings alt">
            <h2><?php _e( 'Languages' ); ?></h2>
        </div>
        <div class="contentbox">
            <?php if ( count( $languages ) > 0 ) : ?>
            <table>
                <?php foreach ( $languages as $row ) : ?>
                <tr>
                    <td width="150"><strong><?php echo $row['DisplayName']; ?></strong></td>
                    <td width="500">
                        <div class="usagebox">
                            <div class="lowbar" style="width: <?php echo $row['percent'] . '%;' ?>"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Languages End -->
</div>
	