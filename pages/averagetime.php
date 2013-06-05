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

// Create date range
$date_range_day = create_date_range_array( $start_time, $end_time );

$start_point = $date_range_day[0];

$area_chart_data = array();

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    $time_span_total = 0;
	
    $query = "SELECT (StopApp - StartApp) AS 'duration' FROM `".$db->prefix."sessions` ";
    $query .= "WHERE `StartApp` >= '".$start."' AND `StopApp` <= '".$end."' AND `StopApp` > '0'";
    $query .= "AND `ApplicationId` = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND `ApplicationVersion` = '".$app_ver."' " ) : ( "" ) );
    
    $db->execute_sql( $query );

    $sessions = array();

    if ( $db->records == 1 )
        $sessions[] = $db->array_result();
    else if ( $db->records > 1 )
        $sessions = $db->array_results();
    
    if ( count ( $sessions ) == 0 ) {
        $area_chart_data[] = 0;
        continue;
    }
    
    foreach ( $sessions as $session_row ) {
        if ( $session_row['duration'] > 0 )
            $time_span_total += $session_row['duration'];
    }
    
    $area_chart_data[] = round ( $time_span_total / count ( $sessions ) );
}

// Average time chart data
$time_chart_data = array();
$total_average_time = 0;

$date_range = create_date_range_array( $start_time, $end_time, $graph_by );

for ( $i = 0; $i < count( $date_range ) - 1 ;$i++ ) {
    $start = $date_range[$i];
    $end = $date_range[$i + 1];
    
    $time_span_total = 0;
    $average_time = 0;
	
    $query = "SELECT (StopApp - StartApp) AS 'duration' FROM `".$db->prefix."sessions` ";
    $query .= "WHERE `StartApp` >= '".$start."' AND `StopApp` <= '".$end."' AND `StopApp` > '0'";
    $query .= "AND `ApplicationId` = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND `ApplicationVersion` = '".$app_ver."' " ) : ( "" ) );
    
    $db->execute_sql( $query );

    $sessions = array();

    if ( $db->records == 1 )
        $sessions[] = $db->array_result();
    else if ( $db->records > 1 )
        $sessions = $db->array_results();

    if ( count ( $sessions ) != 0 ) {
        foreach ( $sessions as $session_row ) {
            if ( $session_row['duration'] > 0 )
                $time_span_total += $session_row['duration'];
        }

        $average_time = round ( $time_span_total / count ( $sessions ) );

        // Used to calculate percent
        $total_average_time += $average_time;
    }
    
    $time_chart_data[] = array(
        'start' => $start,
        'end' => $end,
        'averagetime' => $average_time
    );
}
?>
<script type="text/javascript">
var chart;
$(document).ready(function() {
    TimeSpan = function(time) {
        var hours = 0;
        var minutes = 0;
        var seconds = 0;

        while(time >= 3600) {
            hours++;
            time -= 3600;
        }

        while(time >= 60) {
            minutes++;
            time -= 60;
        }

        seconds = Math.round(time);
        
        return ( ( hours > 0 ) ? ( hours + 'h ' ) : ( '' ) ) + ( ( minutes > 0 ) ? ( minutes + 'm ' ) : ( '' ) ) + seconds + 's';
    }
    
    chart = new Highcharts.Chart({
            chart: {
                    renderTo: 'chart_div',
                    defaultSeriesType: 'line',
                    height: 200
            },
            title: {
                    text: '<?php echo __( 'Statistics for ' ) . date( "F j, Y", $start_time ) . ' to ' . date( "F j, Y", $end_time ); ?>',
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
                    title: '',
                    labels: {
                        formatter: function() {
                            return TimeSpan(this.value);
                        }
                    }
            },
            tooltip: {
                formatter: function() {
                    return '<b>'+ this.series.name +'</b><br/>'+
                        new Date(this.x).toDateString() +': '+ TimeSpan(this.y);
                }
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
            series: <?php echo json_encode( array( array( 'name' => __( 'Average Time' ),'data' => $area_chart_data ) ) ); ?>
    });

});
</script>
<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Average Time' ); ?></h1>
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

<div style="clear: both"></div>

<div class="contentcontainers">
    <!-- Executions Chart Data Start -->
    <div class="contentcontainer">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Average Time Chart Data' ); ?></h2>
        </div>
        <div class="contentbox">
            <table>
                <?php foreach ( $time_chart_data as $chart_data ) : ?>
                    <tr>
                        <td>
                            <?php
                                if ( $graph_by == 'day' )
                                    echo date( 'l, F j, o', $chart_data['start'] );
                                else 
                                    echo date( 'l, F j, o', $chart_data['start'] ) . ' to ' . date( 'l, F j, o', $chart_data['end'] );
                            ?>
                        </td>
                        <td width="725">
                            <?php $percent = calculate_percent( $chart_data['averagetime'], $total_average_time ); ?>
                            <div class="usagebox">
                                <div class="lowbar" style="width: <?php echo $percent; ?>%;"></div>
                            </div>
                        </td>
                        <td><strong><?php echo $percent; ?>% (<?php echo get_time_duration( $chart_data['averagetime'] ); ?>)<strong></td>
                    </tr>
               <?php endforeach; ?>
            </table>
        </div>
    </div>
    <!-- Executions Chart Data End -->
</div>