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

$type = ( ( isset( $_POST['type'] ) ) ? ( $_POST['type'] ) : ( 'total' ) );

// Array containing custom data info
$custom_data = array();

$grouped_events = $db->select_events( 'ctD', $app_id, $app_ver, $start_time, $end_time, false, '', 'EventCustomName, EventCustomValue');
if ( count( $grouped_events ) > 0 ) {
    foreach ( $grouped_events as $event ) {
        $event_name = $event['EventCustomName'];
        $event_value = $event['EventCustomValue'];
        
        // Ignore 'License'
        if ( $event_name == 'License' )
            continue;

        if ( !array_key_exists( $event_name, $custom_data ) )
            $custom_data[$event_name] = array();

        if ( !in_array( $event_value, $custom_data[$event_name] ) )
            $custom_data[$event_name][] = $event_value;
    }
} else {
    // Just in case..
    $custom_data[__( '(None)' )] = array( __( '(None)' ) );
}

// If name isnt set -> set to first array key
$name_selected = ( ( isset( $_POST['name'] ) ) ? ( $_POST['name'] ) : ( key( $custom_data ) ) );

$chart_data = array();
$pie_chart_data = array();
$total = 0;

// Create date range
$date_range_day = create_date_range_array( $start_time, $end_time );

$start_point = $date_range_day[0];

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    foreach ( array_keys( $custom_data ) as $name ) {
        if ( $i == 0 ) {
            $chart_data[$name] = array();
            $pie_chart_data[$name] = 0;
        }
        
        $query = "SELECT COUNT(*) AS 'count' FROM `".$db->prefix."events` AS e, `".$db->prefix."sessions` AS s ";
        $query .= "WHERE e.SessionId = s.SessionId AND e.EventCode = 'ctD' ";
        $query .= "AND e.ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND e.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
        $query .= "AND e.UtcTimestamp BETWEEN ".$start." AND ".$end." ";
        $query .= "AND e.EventCustomName = '".$name."' ";
        if ( $type == 'unique' )
            $query .= "GROUP BY s.UniqueUserId";

        if ( $db->execute_sql( $query ) ) {
            $db->array_result();
            $events = intval( $db->arrayed_result['count'] );
        } else
            $events = 0;

        $chart_data[$name][] = $events;
        $pie_chart_data[$name] += $events;
        $total += $events;
    }
}

// Data Table array
$data_table = array();

$query = "SELECT e.EventCustomValue, COUNT(". ( ( $type == 'unique' ) ? ( 'DISTINCT s.UniqueUserId' ) : ( '*' ) ).") AS 'total' ";
$query .= "FROM `".$db->prefix."sessions` AS s, `".$db->prefix."events` AS e ";
$query .= "WHERE s.SessionId = e.SessionId ";
$query .= "AND e.ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND e.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
$query .= "AND e.UtcTimestamp BETWEEN '".$start_time."' AND '".$end_time."' ";
$query .= "AND e.EventCustomName = '".$name_selected."' ";
$query .= "GROUP BY e.EventCustomValue ";

$db->execute_sql( $query );

$custom_data_chart_data = array();

if ( $db->records == 1 )
    $custom_data_chart_data[] = $db->array_result();
else if ( $db->records > 1 )
    $custom_data_chart_data = $db->array_results();
?>
<script type="text/javascript">
    $(document).ready(function () { 
        <?php if ( count( $chart_data ) > 0 ) : ?>
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
        <?php endif; ?>
        <?php if ( count( $pie_chart_data ) > 0 ) : ?>
        pie_chart = new Highcharts.Chart({
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
                name: 'Custom data percentage',
                data: <?php echo convert_pie_chart_data_to_json( $pie_chart_data ); ?>
            }]
        });
        <?php endif; ?>
    });
</script>
<div id="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Custom Data' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox nobottom" id="graphs-1" style="overflow: hidden !important">
            <?php if ( count( $chart_data ) > 0 ) : ?>
            <div id="chart_div"></div>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>

        <div class="contentbox nobottom" id="graphs-2">
            <?php if ( count( $pie_chart_data ) > 0 ) : ?>
            <div id="pie_div"></div>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>

        <div class="contentbox bottom">
            <form class="right" action="#" method="post">
                <strong><?php _e( 'Name: ' ); ?></strong>
                <select name="name">
                    <?php foreach ( array_keys($custom_data) as $event_name ) : ?>
                    <option<?php echo ( ( $name_selected == $event_name ) ? ( ' selected' ) : ( '' ) ) ?>><?php echo $event_name ?></option>
                    <?php endforeach; ?>
                </select>
                <strong>&nbsp; <?php _e( 'Type: ' ); ?></strong>
                <select name="type">
                    <option value="total"<?php echo ( ( $type == 'total' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Total' ); ?></option>
                    <option value="unique"<?php echo ( ( $type == 'unique' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Unique' ); ?></option>
                </select>
                <input name="apply" class="form-submit" style="float: none; display: inline;" type="submit" value="<?php _e( 'Apply' ); ?>" />
            </form>
        </div>
        <!-- Graphs Box End -->
    </div>

    <!-- Overview Start -->
    <div class="contentcontainer">
        <div class="headings altheading">
            <h2><?php echo __( 'Total of data available: ' ) . $total ?></h2>
        </div>
        <div class="contentbox" style="padding-top: 0;">
            <table style="width: 100%" class="datatable">
                <thead>
                    <tr>
                        <th><?php _e( 'Name' ); ?></th>
                        <th><?php _e( 'Value' ); ?></th>
                        <th><?php _e( 'Count' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $custom_data_chart_data as $row ) : ?>
                    <tr>
                        <td><?php echo $name_selected ?></td>
                        <td><?php echo $row['EventCustomValue'] ?></td>
                        <td><?php echo $row['total'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Overview End -->
</div>