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
    __( 'Free' ) => array(),
    __( 'Trial' ) => array(),
    __( 'Demo' ) => array(),
    __( 'Registered' ) => array(),
    __( 'Cracked' ) => array(),
);

$free_total = 0;
$trial_total = 0;
$demo_total = 0;
$registered_total = 0;
$cracked_total = 0;

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    $free = 0;
    $trial = 0;
    $demo = 0;
    $registered = 0;
    $cracked = 0;
    
    $custom_data = $db->select_events( 'ctD', $app_id, $app_ver, $start, $end, false, array( 'EventCustomName' => 'License' ) );
    
    foreach ( $custom_data as $custom_data_row ) {
         if ( $custom_data_row['EventCustomValue'] == 'F' ) {
            $free++;
            $free_total++;
        } elseif ( $custom_data_row['EventCustomValue'] == 'T' ) {
            $trial++;
            $trial_total++;
        } elseif ( $custom_data_row['EventCustomValue'] == 'D' ) {
            $demo++;
            $demo_total++;
        } elseif ( $custom_data_row['EventCustomValue'] == 'R' ) {
            $registered++;
            $registered_total++;
        } elseif ( $custom_data_row['EventCustomValue'] == 'C' ) {
            $cracked++;
            $cracked_total++;
        }
    }
    
    $chart_data[__( 'Free' )][] = $free;
    $chart_data[__( 'Trial' )][] = $trial;
    $chart_data[__( 'Demo' )][] = $demo;
    $chart_data[__( 'Registered' )][] = $registered;
    $chart_data[__( 'Cracked' )][] = $cracked;
}
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
            name: '<?php _e( 'Type of license' ); ?>',
            data: <?php echo json_encode( array(
                array( __( 'Free' ), $free_total ),
                array( __( 'Trial' ), $trial_total ),
                array( __( 'Demo' ), $demo_total ),
                array( __( 'Registered' ), $registered_total ),
                array( __( 'Cracked' ), $cracked_total ),
            ) ); ?>
          }]
    });
});
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Licenses' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

<!-- Graphs Box Start -->
        <div class="contentbox" id="graphs-1" style="height: 200px">
            <div id="chart_div"></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <div id="pie_div"></div>
        </div>
    <!-- Graphs Box End -->
    </div>
</div>