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

// Create date range
$date_range_day = create_date_range_array( $start_time, $end_time );

$start_point = $date_range_day[0];

$versions_chart_data = array();
$versions_pie_data = array();

$arch_chart_data = array( '32' => array_fill( 0, count( $date_range_day ) - 1, 0 ), '64' => array_fill( 0, count( $date_range_day ) - 1, 0 ) );
$arch_pie_data = array( '32' => 0, '64' => 0 );

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    $query = "SELECT u.OSVersion, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS 'total'";
    $query .= "FROM `".$db->prefix."sessions` AS s, `".$db->prefix."uniqueusers` AS u ";
    $query .= "WHERE s.UniqueUserId = u.UniqueUserId ";
    $query .= "AND s.ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND s.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
    $query .= "AND s.StartApp BETWEEN '".$start."' AND '".$end."' ";
    $query .= "GROUP BY u.OSVersion";

    $db->execute_sql( $query );
    
    $rows = array();

    if ( $db->records == 1 )
        $rows[] = $db->array_result();
    else if ( $db->records > 1 )
        $rows = $db->array_results();
    
    foreach ( $rows as $row ) {
        $os_version = $row['OSVersion'];
        $count = intval( $row['total'] );
        
        if ( !array_key_exists( $os_version , $versions_chart_data ) ) {
            $versions_chart_data[$os_version] = array_fill( 0, count( $date_range_day ) - 1, 0 );
            $versions_pie_data[$os_version] = 0;
        }
        
        $versions_chart_data[$os_version][$i] = $count;
        $versions_pie_data[$os_version] += $count;
    }
    
    $query = "SELECT u.OSArchitecture, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS 'total'";
    $query .= "FROM `".$db->prefix."sessions` AS s, `".$db->prefix."uniqueusers` AS u ";
    $query .= "WHERE s.UniqueUserId = u.UniqueUserId ";
    $query .= "AND s.ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND s.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
    $query .= "AND s.StartApp BETWEEN '".$start."' AND '".$end."' ";
    $query .= "GROUP BY u.OSArchitecture";

    $db->execute_sql( $query );
    
    $rows = array();

    if ( $db->records == 1 )
        $rows[] = $db->array_result();
    else if ( $db->records > 1 )
        $rows = $db->array_results();
    
    foreach ( $rows as $row ) {
        $os_arch = $row['OSArchitecture'];
        $count = intval( $row['total'] );
        
        $arch_chart_data[$os_arch][$i] = $count;
        $arch_pie_data[$os_arch] += $count;
    }
}
?>
<script type="text/javascript">
    var chart_versions, pie_versions, chart_arch, pie_arch;
    $(document).ready(function() {
        <?php if ( count ( $versions_chart_data ) > 0 ) : ?>
        chart_versions = new Highcharts.Chart({
            chart: {
                renderTo: 'chart_versions',
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
            series: <?php echo convert_area_chart_data_to_json( $versions_chart_data ); ?>
        });
        
        <?php endif; ?>
        <?php if ( count( $versions_pie_data ) > 0 ) : ?>
        pie_versions = new Highcharts.Chart({
            chart: {
                renderTo: 'pie_versions',
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
                name: '<?php _e( 'Operating systems versions percentage' ); ?>',
                data: <?php echo convert_pie_chart_data_to_json( $versions_pie_data ); ?>
            }]
        });
        <?php endif; ?>

        <?php if ( count ( $versions_pie_data ) > 0 ) : ?>
        chart_arch = new Highcharts.Chart({
            chart: {
                renderTo: 'chart_arch',
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
                layout: 'horizontal',
                align: 'right',
                verticalAlign: 'top',
                floating: true,
                x: -10,
                y: -10,
                borderWidth: 0
            },
            series: <?php echo convert_area_chart_data_to_json( $arch_chart_data ); ?>
        });
        
        <?php endif; ?>
        <?php if ( count( $arch_pie_data ) > 0 ) : ?>
        pie_arch = new Highcharts.Chart({
            chart: {
                renderTo: 'pie_arch',
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
                name: '<?php _e( 'Operating system architecture percentage' ); ?>',
                data: <?php echo convert_pie_chart_data_to_json( $arch_pie_data ); ?>
            }]
        });
        <?php endif; ?>
    });
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Version' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox" id="graphs-1">
            <?php if ( count( $versions_chart_data ) > 0 ) : ?>
            <div id="chart_versions"></div>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>

        <div class="contentbox" id="graphs-2">
            <?php if ( count( $versions_pie_data ) > 0 ) : ?>
            <div id="pie_versions"></div>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
        <!-- Graphs Box End -->
    </div>

    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Architecture' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox" id="graphs-1">
            <?php if ( count( $arch_chart_data ) > 0 ) : ?>
            <div id="chart_arch"></div>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>

        <div class="contentbox" id="graphs-2">
            <?php if ( count( $arch_pie_data ) > 0 ) : ?>
            <div id="pie_arch"></div>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
        <!-- Graphs Box End -->
    </div>
    
    <form action="#" class="right" style="padding-top: 15px">
        <strong><?php _e( 'Type:' ); ?> </strong>
        <select name="type">
            <option value="total"<?php echo ( ( $type == 'total' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Total' ); ?></option>
            <option value="unique"<?php echo ( ( $type == 'unique' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Unique' ); ?></option>
        </select>
        &nbsp;&nbsp;
        <input name="apply" class="form-submit right" type="submit" value="<?php _e( 'Apply' ); ?>" />
    </form>
    
</div>