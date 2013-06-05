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

$chart_data = array(
    __( 'Installs and executes' ) => array_fill( 0, count( $date_range_day ) - 1, 0 ), // Install and execute
    __( 'Installs and doesnt execute' ) => array_fill( 0, count( $date_range_day ) - 1, 0 ) // Install and dont execute
);

$total = $execute_total = $no_execute_total = 0;

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    $execute = $no_execute = 0;
    
    $version_query = ( ( $app_ver != "all" ) ? ( "AND s.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
    
    $query = "SELECT (";
    $query .= "SELECT COUNT(*) FROM `".$db->prefix."sessions` ";
    $query .= "WHERE UniqueUserId = u.UniqueUserId AND ApplicationId = '".$app_id."' ".$version_query."AND StartApp BETWEEN e.UtcTimestamp AND " .$end;
    $query .= ") AS bounces ";
    $query .= "FROM `".$db->prefix."events` AS e, `".$db->prefix."sessions` AS s, `".$db->prefix."uniqueusers` AS u ";
    $query .= "WHERE e.SessionId = s.SessionId AND s.UniqueUserId = u.UniqueUserId AND s.ApplicationId = '".$app_id."' " . $version_query;
    $query .= "AND e.EventCode = 'ist' ";
    $query .= "AND e.UtcTimestamp BETWEEN ".$start." AND ".$end;
    
    $db->execute_sql( $query );
    
    $rows = array();

    if ( $db->records == 1 )
        $rows[] = $db->array_result();
    else if ( $db->records > 1 )
        $rows = $db->array_results();
    
    foreach ( $rows as $row ) {
        $bounces = intval( $row['bounces'] );
        
        if ( $bounces == 1 )
            $no_execute++;
        elseif ( $bounces >= 2 )
            $execute++;
    }
    
    $total += $execute + $no_execute;
    $execute_total += $execute;
    $no_execute_total += $no_execute;
    
    $chart_data[__( 'Installs and executes' )][$i] = $execute;
    $chart_data[__( 'Installs and doesnt execute' )][$i] = $no_execute;
}

$no_execute_last_month_total = 0;

$query = "SELECT COUNT(*) AS total ";
$query .= "FROM `".$db->prefix."events` AS e, `".$db->prefix."sessions` AS s, `".$db->prefix."uniqueusers` AS u ";
$query .= "WHERE e.SessionId = s.SessionId AND s.UniqueUserId = u.UniqueUserId AND s.ApplicationId = '".$app_id."' " . $version_query;
$query .= "AND e.EventCode = 'ist' ";
$query .= "AND e.UtcTimestamp BETWEEN ". ( $start_time - ( 30 * 24 * 3600 ) ) ." AND ".$start_time ." AND (";
$query .= "SELECT COUNT(*) FROM `".$db->prefix."sessions` ";
$query .= "WHERE UniqueUserId = u.UniqueUserId AND ApplicationId = '".$app_id."' ".$version_query."AND StartApp BETWEEN e.UtcTimestamp AND ".$start_time.") = 1";

$db->execute_sql( $query );

$row = $db->array_result();

$no_execute_last_month_total = intval( $row['total'] );

$percentage_increase = calculate_percentage_increase( $no_execute_last_month_total, $no_execute_total );

$percentage_increase_str = $percentage_increase . '%';
if ( $percentage_increase > 0 )
    $percentage_increase_str = '+' . $percentage_increase_str;

$no_execute_percent = calculate_percent( $no_execute_total, $total, 1 );
?>
<script type="text/javascript">
var chart_line, chart_pie;
$(document).ready(function() {
    chart_line = new Highcharts.Chart({
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
    
    chart_pie = new Highcharts.Chart({
        chart: {
            renderTo: 'pie_div',
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false
        },
        title: {
            text: '<?php echo __( 'Statistics for ' ) . date( "F j, Y", $start_time ) . ' ' . __( 'to' ) . ' ' . date( "F j, Y", $end_time ); ?>'
        },
        tooltip: {
            formatter: function() {
                return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %';
            }
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                   enabled: true,
                   formatter: function() {
                      return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %';
                   }
                }
             }
          },
          series: [{
            type: 'pie',
            name: 'Bounce Rate',
            data: <?php echo json_encode( array(
                array( __( 'Installs and executes' ), $execute_total ),
                array( __( 'Installs and doesnt execute' ), $no_execute_total )
            ) ); ?>
          }]
    });
});
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Bounce Rate' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox" id="graphs-1">
            <div id="chart_div"></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <div id="pie_div"></div>
        </div>
        <!-- Graphs Box End -->
    </div>
</div>

<div class="contentcontainers">
    <!-- Overview Start -->
    <div class="contentcontainer med left">
        <div class="headings alt">
            <h2><?php _e( 'Overview' ); ?></h2>
        </div>
        <div class="contentbox">
            <div>
                <p><span class="total"><?php echo $total; ?></span> <?php _e( 'installations' ); ?></p>
                <p><span class="total"><?php echo $no_execute_total; ?></span> <?php _e( 'bounced' ); ?> (<?php echo $no_execute_percent; ?>%)</p>
            </div>
        </div>
    </div>
    <!-- Overview End -->

    <!-- Last Month Period Start -->
    <div class="contentcontainer sml right">
        <div class="headings alt">
            <h2><?php _e( 'Last Month Period' ); ?></h2>
        </div>
        <div class="contentbox" style="text-align: center; padding-top: 30px;">
            <span class="<?php echo ( ( $percentage_increase > 0 ) ? ( 'green' ) : ( 'red' ) ); ?>" style="font-weight: bold; font-size: 52px !important;"><?php echo $percentage_increase_str; ?></span>
            <br />
            <strong><?php _e( 'bounces last month period' ); ?></strong>
        </div>
    </div>
    <!-- Last Month Period End -->
</div>