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

/**
 * Callback function to sort array keys
 * @param string $a Key A
 * @param string $b Key B
 * @return int Returns -1 if key should move back or 1 if key should move forward
 */
function compare_keys($a, $b) {
    switch ($a) {
        case __( 'Under 1GB' ):
            $a_n = 1;
            break;
        
        case __( '1GB' ):
            $a_n = 2;
            break;
        case __( '2GB - 3GB' ):
            $a_n = 3;
            break;
        case __( '4GB - 7GB' ):
            $a_n = 4;
            break;
        case __( '8GB - 15GB' ):
            $a_n = 5;
            break;
        case __( '16GB - 31GB' ):
            $a_n = 6;
            break;
        case __( 'Over 32GB' ):
            $a_n = 7;
            break;

        default:
            break;
    }
        
    switch ($b) {
        case __( 'Under 1GB' ):
            $b_n = 1;
            break;
        
        case __( '1GB' ):
            $b_n = 2;
            break;
        case __( '2GB - 3GB' ):
            $b_n = 3;
            break;
        case __( '4GB - 7GB' ):
            $b_n = 4;
            break;
        case __( '8GB - 15GB' ):
            $b_n = 5;
            break;
        case __( '16GB - 31GB' ):
            $b_n = 6;
            break;
        case __( 'Over 32GB' ):
            $b_n = 7;
            break;

        default:
            break;
    }

    return ( ( $a_n < $b_n ) ? -1 : 1 );
}

$type = ( ( isset( $_POST['type'] ) ) ? ( $_POST['type'] ) : ( 'total' ) );

$memory_chart_data = array();
$memory_pie_data = array();

// Create date range
$date_range_day = create_date_range_array( $start_time, $end_time );

$start_point = $date_range_day[0];

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    $query = "SELECT u.MemTotal, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS 'total' ";
    $query .= "FROM `".$db->prefix."sessions` AS s, `".$db->prefix."uniqueusers` AS u ";
    $query .= "WHERE s.UniqueUserId = u.UniqueUserId ";
    $query .= "AND s.ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND s.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
    $query .= "AND s.StartApp BETWEEN '".$start."' AND '".$end."' ";
    $query .= "GROUP BY u.MemTotal";

    $db->execute_sql( $query );
    
    $rows = array();

    if ( $db->records == 1 )
        $rows[] = $db->array_result();
    else if ( $db->records > 1 )
        $rows = $db->array_results();
    
    foreach ( $rows as $row ) {
        $mem_total_mb = intval( $row['MemTotal'] );
        
        if ( $mem_total_mb < 1024 )
            $category = __( 'Under 1GB' );
        else if ( $mem_total_mb >= 1024 && $mem_total_mb < 2048 )
            $category = __( '1GB' );
        else if ( $mem_total_mb >= 2048 && $mem_total_mb < 4096 )
            $category = __( '2GB - 3GB' );
        else if ( $mem_total_mb >= 4096 && $mem_total_mb < 8192 )
            $category = __( '4GB - 7GB' );
        else if ( $mem_total_mb >= 8192 && $mem_total_mb < 16384 )
            $category = __( '8GB - 15GB' );
        else if ( $mem_total_mb >= 16384 && $mem_total_mb < 32768 )
            $category = __( '16GB - 31GB' );
        else if ( $mem_total_mb >= 32768 )
            $category = __( 'Over 32GB' );
        
        $count = intval( $row['total'] );

        if ( !array_key_exists( $category, $memory_chart_data ) ) {
            $memory_chart_data[$category] = array_fill( 0, count( $date_range_day ) - 1, 0 );
            $memory_pie_data[$category] = 0;
        }

        $memory_chart_data[$category][$i] = $count;
        $memory_pie_data[$category] += $count;
    }
}

// Sort chart data keys
uksort($memory_chart_data, 'compare_keys');
?>
<script type="text/javascript">
    var size_chart, size_pie;
    $(document).ready(function() {
        <?php if ( count ( $memory_chart_data ) > 0 ) : ?>
        size_chart = new Highcharts.Chart({
            chart: {
                renderTo: 'size_chart',
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
            series: <?php echo convert_area_chart_data_to_json( $memory_chart_data ); ?>
        });
        
        <?php endif; ?>
        <?php if ( count( $memory_pie_data ) > 0 ) : ?>
        size_pie = new Highcharts.Chart({
            chart: {
                renderTo: 'size_pie',
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
                name: '<?php _e( 'Memory percentage' ); ?>',
                data: <?php echo convert_pie_chart_data_to_json( $memory_pie_data ); ?>
            }]
        });
        <?php endif; ?>
    });
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Size' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox nobottom" id="graphs-1">
            <?php if ( count( $memory_chart_data ) > 0 ) : ?>
            <div id="size_chart"></div>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>

        <div class="contentbox nobottom" id="graphs-2">
            <?php if ( count( $memory_pie_data ) > 0 ) : ?>
            <div id="size_pie"></div>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>

        <div class="contentbox bottom">
            <form action="#" method="post" class="right">
                <strong><?php _e( 'Type: ' ); ?></strong>
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