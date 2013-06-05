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

$chart_data = array( 'Exceptions' => array( ) );

// Create date range
$date_range_day = create_date_range_array( $start_time, $end_time );

$start_point = $date_range_day[0];

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    $exceptions = $db->select_events( 'exC', $app_id, $app_ver, $start, $end, true );
    
    $chart_data['Exceptions'][] = $exceptions;
}

$query = "SELECT e.ExceptionStackTrace, e.ExceptionMsg, e.ExceptionSource, e.ExceptionTargetSite, e.UtcTimestamp, s.UniqueUserId, s.ApplicationVersion, u.OSVersion, u.OSServicePack ";
$query .= "FROM `".$db->prefix."events` AS e, `".$db->prefix."sessions` AS s, `".$db->prefix."uniqueusers` AS u ";
$query .= "WHERE e.SessionId = s.SessionId AND s.UniqueUserId = u.UniqueUserId AND e.EventCode = 'exC' ";
$query .= "AND e.ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND e.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
$query .= "AND e.UtcTimestamp BETWEEN ".$start_time." AND ".$end_time." ";
//$query .= "GROUP BY s.UniqueUserId";

$db->execute_sql( $query );

$event_rows = array();

if ( $db->records == 1 )
    $event_rows[] = $db->array_result();
else if ( $db->records > 1 )
    $event_rows = $db->array_results();

$exception_data = array();

foreach ( $event_rows as $event_row ) {
    $stack_trace = $event_row['ExceptionStackTrace'];
    $exception_id = md5( preg_replace( '/\s+/', ' ', $stack_trace ) );

    $message = $event_row['ExceptionMsg'];
    $source = $event_row['ExceptionSource'];
    $target_site = $event_row['ExceptionTargetSite'];

    // Get user environment
    $unique_id = $event_row['UniqueUserId'];
    $version = $event_row['ApplicationVersion'];
    $date_ts = intval( $event_row['UtcTimestamp'] );

    $os_version = $event_row['OSVersion'];
    $os_sp = $event_row['OSServicePack'];

    if ( !array_key_exists( $exception_id, $exception_data ) ) {        
        $exception_data[$exception_id] = array(
            'date' => $date_ts,
            'message' => $message,
            'stacktrace' => $stack_trace,
            'occurrences' => array(
                array(
                    'version' => $version,
                    'source' => $source,
                    'targetsite' => $target_site,
                    'os' => $os_version,
                    'sp' => $os_sp,
                    'date' => $date_ts
                )
            )
        );
    } else {
        if ( $exception_data[$exception_id]['date'] > $date_ts )
            $exception_data[$exception_id]['date'] = $date_ts;

        $exception_data[$exception_id]['occurrences'][] = array(
            'version' => $version,
            'source' => $source,
            'targetsite' => $target_site,
            'os' => $os_version,
            'sp' => $os_sp,
            'date' => $date_ts
        );
    }

}
?>
<script type="text/javascript">
    $(document).ready(function () { 
        
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
                    pointStart: <?php printf( '%d000', $start_point ); ?>,
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
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'top',
                x: -10,
                y: 10,
                borderWidth: 0
            },
            series: <?php echo convert_area_chart_data_to_json( $chart_data ); ?>
        });
    });
</script>
<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Exceptions' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowleft.jpg'); ?>" /></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowright.jpg'); ?>" /></th>
        </tr>
        <tr>
            <td id="tbl-border-left"></td>
            <td>
                <div id="content-table-inner">
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

<div class="contentcontainers">
    <div class="contentcontainer">
        <div class="headings alt">
            <h2><?php _e( 'Exceptions' ); ?></h2>
        </div>
        <div class="contentbox">
            <table style="width: 100%" class="datatable">
                <thead>
                    <tr>
                        <th style="width: 145px"><?php _e( 'Date' ); ?></th>
                        <th><?php _e( 'Exception' ); ?></th>
                        <th><?php _e( 'Traceback' ); ?></th>
                        <th><?php _e( 'Count' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $exception_data as $exception_id => $data ) : ?>
                    <tr exceptionid="<?php echo $exception_id; ?>">
                        <td><?php echo date( 'M j, Y, g:i a', $data['date'] ); ?></td>
                        <td><a id="exceptiondetails"><?php echo $data['message']; ?></a></td>
                        <td><a id="exceptiondetails"><?php echo ( ( strlen( $data['stacktrace'] ) > 60 ) ? ( substr( $data['stacktrace'], 0, 60 ) . '...' ) : ( $data['stacktrace'] ) ); ?></a></td>
                        <td><?php echo count( $data['occurrences'] ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="contentcontainer exceptiondetailscontainer">
        <div class="headings altheading">
            <h2><?php _e( 'Exception Details' ); ?></h2>
        </div>
        <div class="contentbox">
            <?php foreach ( $exception_data as $exception_id => $data ) : ?>
            <div style="width: 100%; display: none;" class="exceptiondetails" exceptionid="<?php echo $exception_id; ?>">
                <pre class="exception"><?php echo $data['stacktrace']; ?></pre>
                <table style="width: 100%; clear: none" id="exception" class="datatable">
                    <caption style="padding-top: 7px; padding-bottom: 13px"><?php _e( 'Occurrences' ); ?></caption>
                    <thead>
                        <tr>
                            <th><?php _e( 'Version' ); ?></th>
                            <th><?php _e( 'Source' ); ?></th>
                            <th><?php _e( 'Target Site' ); ?></th>
                            <th><?php _e( 'OS' ); ?></th>
                            <th><?php _e( 'SP' ); ?></th>
                            <th><?php _e( 'Date' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $data['occurrences'] as $occurrence ) : ?>
                        <tr>
                            <td><?php echo $occurrence['version']; ?></td>
                            <td><?php echo $occurrence['source']; ?></td>
                            <td><?php echo $occurrence['targetsite']; ?></td>
                            <td><?php echo $occurrence['os']; ?></td>
                            <td><?php echo $occurrence['sp']; ?></td>
                            <td><?php echo date( 'Y-m-d H:i:s', $occurrence['date'] ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>