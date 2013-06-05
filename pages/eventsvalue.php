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

$events = array();
$events_chart_data = array();

$grouped_events = $db->select_events( 'evV', $app_id, $app_ver, $start_time, $end_time, false, '', 'EventCategory, EventName, EventValue' );
if ( count( $grouped_events ) > 0 ) {
    foreach ( $grouped_events as $event ) {
        $event_category = $event['EventCategory'];
        $event_name = $event['EventName'];
        $event_value = $event['EventValue'];

        if ( !array_key_exists( $event_category, $events ) )
            $events[$event_category] = array();
        
        if ( !in_array( $event_name, $events[$event_category] ) ) {
            $events[$event_category][] = $event_name;
        }
    }
} else {
    // Just in case..
    $events[__( '(None)' )] = array( __( '(None)' ) );
}

// Get first array element
$first_category = key( $events );
$first_event = current( $events[$first_category] );

$category_selected = ( ( isset( $_POST['category'] ) ) ? ( $_POST['category'] ) : ( $first_category ) );
$event_selected = ( ( isset( $_POST['event'] ) ) ? ( $_POST['event'] ) : ( $first_event ) );

$chart_data = array();
$pie_chart_data = array();

// Create date range
$date_range_day = create_date_range_array( $start_time, $end_time );

$start_point = $date_range_day[0];

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    $query = "SELECT e.EventValue, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS 'total' ";
    $query .= "FROM `".$db->prefix."sessions` AS s, `".$db->prefix."events` AS e ";
    $query .= "WHERE s.SessionId = e.SessionId AND e.EventCode = 'evV' ";
    $query .= "AND e.EventCategory = '".$category_selected."' AND e.EventName = '".$event_selected."' ";
    $query .= "AND s.ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND s.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
    $query .= "AND e.UtcTimestamp BETWEEN '".$start."' AND '".$end."' ";
    $query .= "GROUP BY e.EventValue";

    $db->execute_sql( $query );

    $rows = array();

    if ( $db->records == 1 )
        $rows[] = $db->array_result();
    else if ( $db->records > 1 )
        $rows = $db->array_results();
    
    foreach ( $rows as $row ) {
        $event_value = $row['EventValue'];
        $total = intval( $row['total'] );
        
        if ( !array_key_exists( $event_value, $chart_data ) ) {
            $chart_data[$event_value] = array_fill( 0, count( $date_range_day ) - 1, 0 );
            $pie_chart_data[$event_value] = 0;
        }
        
        $chart_data[$event_value][$i] = $total;
        $pie_chart_data[$event_value] += $total;
    }
}
?>
<script type="text/javascript">
    $(document).ready(function () { 
        $("select#categories").change(function() {
           category = $("#categories option:selected").text();
           
           // Hide all event lists
           $("select#event").each(function () {
               $(this).attr("disabled", "disabled");
               $(this).hide();
           });
           
           // Only show events for category
           $('select[category="'+category+'"]').removeAttr('disabled');
           $('select[category="'+category+'"]').show();
        }); 
        
        <?php if ( count ( $chart_data ) > 0 ) : ?>
        events_line = new Highcharts.Chart({
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
        events_pie = new Highcharts.Chart({
            chart: {
                renderTo: 'pie_div',
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false
            },
            title: {
                text: '<?php echo __( 'Statistics for ' ) . date( "F j, Y", $start_time ) . ' to ' . date( "F j, Y", $end_time ); ?>'
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
                name: 'Events values percentage',
                data: <?php echo convert_pie_chart_data_to_json( $pie_chart_data ); ?>
            }]
        });
        <?php endif; ?>
    });
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Events Value' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox nobottom" id="graphs-1">
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
                <strong><?php _e( 'Category: ' ); ?></strong>
                <select name="category" id="categories">
                <?php foreach ( array_keys( $events ) as $category ) : ?>
                    <option<?php echo ( ( $category_selected == $category ) ? ( ' selected' ) : ( '' ) ) ?>><?php echo $category; ?></option>
                <?php endforeach; ?>
                </select>
                <strong>&nbsp;<?php _e( 'Events: ' ); ?></strong>
                
                <?php foreach ( array_keys($events) as $category ) : ?>
                <select name="event" id="event" category="<?php echo $category; ?>" <?php echo ( ( $category_selected != $category ) ? ( 'style="display:none" disabled' ) : ( '' ) ); ?>>
                    <?php foreach ( $events[$category] as $event ) :  ?>
                    <option<?php echo ( ( $event_selected == $event && $category_selected == $category ) ? ( ' selected' ) : ( '' ) ) ?>><?php echo $event; ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endforeach; ?>
                
                <strong>&nbsp;<?php _e( 'Type: ' ); ?></strong>
                <select name="type">
                    <option value="total"<?php echo ( ( $type == 'total' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Total' ); ?></option>
                    <option value="unique"<?php echo ( ( $type == 'unique' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Unique' ); ?></option>
                </select>
                &nbsp;&nbsp;
                <input name="apply" class="form-submit right" type="submit" value="<?php _e( 'Apply' ); ?>" />
            </form>
        </div>
        <!-- Graphs Box End -->
    </div>
</div>