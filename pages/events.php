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

$category_selected = ( ( isset( $_POST['category'] ) ) ? ( $_POST['category'] ) : ( 'all' ) );
$type = ( ( isset( $_POST['type'] ) ) ? ( $_POST['type'] ) : ( 'total' ) );

// Create date range
$date_range_day = create_date_range_array( $start_time, $end_time );

$start_point = $date_range_day[0];

$categories = array();
$auniqueusers = array();

$events_chart_data = array();
$pie_chart_data = array();

$grouped_events = $db->select_events( 'ev', $app_id, $app_ver, $start_time, $end_time, false, '', 'EventCategory, EventName' );
foreach ( $grouped_events as $event ) {
    $event_category = $event['EventCategory'];
    $event_name = $event['EventName'];
    
    if ( !in_array( $event_category, $categories ) )
        $categories[] = $event_category;
    
    if ( $category_selected == 'all' || $category_selected == $event_category) {
        if ( !array_key_exists( $event_name, $events_chart_data ) ) {
            $events_chart_data[$event_name] = array_fill( 0, count( $date_range_day ) - 1, 0 );
            $pie_chart_data[$event_name] = 0;
        }
    }
}

// Make sure category specified exists
if ( !in_array( $category_selected, $categories ) && $category_selected != 'all' )
    $category_selected = 'all';

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    $query = "SELECT e.EventName, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS 'total' ";
    $query .= "FROM `".$db->prefix."sessions` AS s, `".$db->prefix."events` AS e ";
    $query .= "WHERE s.SessionId = e.SessionId AND e.EventCode = 'ev' ";
    if ( $category_selected != 'all' )
        $query .= "AND e.EventCategory = '".$category_selected."' ";
    $query .= "AND s.ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND s.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
    $query .= "AND e.UtcTimestamp BETWEEN '".$start."' AND '".$end."' ";
    $query .= "GROUP BY e.EventName";

    $db->execute_sql( $query );

    $rows = array();

    if ( $db->records == 1 )
        $rows[] = $db->array_result();
    else if ( $db->records > 1 )
        $rows = $db->array_results();
    
    foreach ( $rows as $row ) {
        $event_name = $row['EventName'];
        $total = intval( $row['total'] );
        
        $events_chart_data[$event_name][$i] = $total;
        $pie_chart_data[$event_name] += $total;
    }
}

$chart_data = array();

// Get day today
$day_today_start = strtotime('today');
$day_today_end = $day_today_start + ( 24 * 3600 );

$day_today = date( 'l', $day_today_start );

// Get day yesterday
$day_yesterday_start = strtotime('yesterday');
$day_yesterday_end = $day_yesterday_start + ( 24 * 3600 );

$day_yesterday = date( 'l', $day_yesterday_start );

// Get day week from yesterday
$day_week_ago_start = strtotime('last '.$day_yesterday, $day_yesterday_start);
$day_week_ago_end = $day_week_ago_start + ( 24 * 3600 );

$day_week_ago = 'Last '.date('l', $day_week_ago_start );

// Get day 2 weeks from yesterday
$day_2_weeks_ago_start = strtotime('last '.$day_yesterday, $day_week_ago_start);
$day_2_weeks_ago_end = $day_2_weeks_ago_start + ( 24 * 3600 );

// Get events
for ( $i = 0; $i < 3; $i++ ) {
    if ( $i == 0 ) {
        // Today
        $period_start = $day_today_start;
        $period_end = $day_today_end;
    } elseif ( $i == 1 ) {
        // Yesterday
        $period_start = $day_yesterday_start;
        $period_end = $day_yesterday_end;
    } elseif ( $i == 2 ) {
        // Week Ago
        $period_start = $day_week_ago_start;
        $period_end = $day_week_ago_end;
    } elseif ( $i == 3 ) {
        // 2 Weeks Ago
        $period_start = $day_2_weeks_ago_start;
        $period_end = $day_2_weeks_ago_end;
    }

    $query = "SELECT e.EventName, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS total ";
    $query .= "FROM `".$db->prefix."sessions` AS s, `".$db->prefix."events` AS e ";
    $query .= "WHERE s.SessionId = e.SessionId AND e.EventCode = 'ev' ";
    if ( $category_selected != 'all' )
        $query .= "AND e.EventCategory = '".$category_selected."' ";
    $query .= "AND s.ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND s.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
    $query .= "AND e.UtcTimestamp BETWEEN ".$period_start." AND ".$period_end." ";
    $query .= "GROUP BY e.EventName";

    $db->execute_sql( $query );

    $rows = array();

    if ( $db->records == 1 )
        $rows[] = $db->array_result();
    else if ( $db->records > 1 )
        $rows = $db->array_results();
    
    foreach ( $rows as $row ) {
        $event_name = $row['EventName'];
        $total = intval( $row['total'] );
        
        if ( !array_key_exists( $event_name, $chart_data ) )
            $chart_data[$event_name] = array( 0, 0, 0, 0 );
        
        $chart_data[$event_name][$i] = $total;
    }
}
    

?>
<script type="text/javascript">
var chart_line, chart_pie;
$(document).ready(function() {
    <?php if ( count ( $events_chart_data ) > 0 ) : ?>
    chart_line = new Highcharts.Chart({
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
            series: <?php echo convert_area_chart_data_to_json( $events_chart_data ); ?>
    });
    <?php endif; ?>
    <?php if ( count( $pie_chart_data ) > 0 ) : ?>
    chart_pie = new Highcharts.Chart({
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
            name: 'Event percentage',
            data: <?php echo convert_pie_chart_data_to_json( $pie_chart_data ); ?>
          }]
    });
    <?php endif; ?>
});
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Events' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox nobottom" id="graphs-1">
            <?php if ( count( $events_chart_data ) > 0 ) : ?>
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
            <form class="right" method="post" action="#">
                <strong>Category: </strong>
                <select name="category">
                    <option value="all"<?php echo ( ( $category_selected == 'all' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'All' ); ?></option>
                    <?php foreach ( $categories as $category ) : ?>
                        <option<?php echo ( ( $category_selected == $category ) ? ( ' selected' ) : ( '' ) ) ?>><?php echo $category; ?></option>
                    <?php endforeach; ?>
                </select>
                <strong>&nbsp;Type: </strong>
                <select name="type">
                    <option value="total"<?php echo ( ( $type == 'total' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Total' ); ?></option>
                    <option value="unique"<?php echo ( ( $type == 'unique' ) ? ( ' selected' ) : ( '' ) ) ?><?php _e( '>Unique' ); ?></option>
                </select>
                &nbsp;&nbsp;
                <input name="apply" class="form-submit right" type="submit" value="<?php _e( 'Apply' ); ?>" />
            </form>
        </div>
        <!-- Graphs Box End -->
    </div>

    <!-- Overview Start -->
    <div class="contentcontainer">
        <div class="headings altheading">
            <h2><?php _e( 'Overview' ); ?></h2>
        </div>
        <div class="contentbox">
            <?php if ( count( $chart_data ) > 0 ) : ?>
            <table style="width: 100%">
                <thead>
                    <tr>
                        <th><?php _e( 'Event' ); ?></th>
                        <th><?php _e( 'Today' ); ?></th>
                        <th><?php echo $day_yesterday ?></th>
                        <th><?php echo $day_week_ago ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ( $chart_data as $event => $period ) : 
                        $today_percent = calculate_percentage_increase( $period[1], $period[0] );
                        $today_percent_str = $today_percent . '%';
                        if ( $today_percent > 0 )
                            $today_percent_str = '+' . $today_percent_str;
                    
                        $yesterday_percent = calculate_percentage_increase( $period[2], $period[1] );
                        $yesterday_percent_str = $yesterday_percent . '%';
                        if ( $yesterday_percent > 0 )
                            $yesterday_percent_str = '+' . $yesterday_percent_str;
                        
                        $week_ago_percent = calculate_percentage_increase( $period[3], $period[2] );
                        $week_ago_percent_str = $week_ago_percent . '%';
                        if ( $yesterday_percent > 0 )
                            $week_ago_percent_str = '+' . $week_ago_percent_str;
                    ?>
                    <tr>
                        <td><?php echo $event ?></td>
                        <td><?php echo $period[0] ?><br /><span class="<?php echo ( $today_percent > 0 ? 'green' : 'red' ) ?>"><?php echo $today_percent_str ?></span></td>
                        <td><?php echo $period[1] ?><br /><span class="<?php echo ( $yesterday_percent > 0 ? 'green' : 'red' ) ?>"><?php echo $yesterday_percent_str ?></span></td>
                        <td><?php echo $period[2] ?><br /><span class="<?php echo ( $week_ago_percent > 0 ? 'green' : 'red' ) ?>"><?php echo $week_ago_percent_str ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Overview End -->