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

$brands_area_chart_data = array();
$brands_pie_chart_data = array();

$arch_chart_data = array( '32' => array_fill( 0, count( $date_range_day ) - 1, 0 ), '64' => array_fill( 0, count( $date_range_day ) - 1, 0 ) );
$arch_pie_chart_data = array( '32' => 0, '64' => 0 );

$cores_chart_data = array();
$cores_pie_chart_data = array();

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    $query = "SELECT u.CPUBrand, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS count FROM `".$db->prefix."sessions` AS s, `".$db->prefix."uniqueusers` AS u ";
    $query .= "WHERE s.UniqueUserId = u.UniqueUserId ";
    $query .= "AND s.ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND s.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
    $query .= "AND s.StartApp BETWEEN '".$start."' AND '".$end."' ";
    $query .= "GROUP BY u.CPUBrand";

    $db->execute_sql( $query );

    $rows = array();

    if ( $db->records == 1 )
        $rows[] = $db->array_result();
    else if ( $db->records > 1 )
        $rows = $db->array_results();

    foreach ( $rows as $row ) {
        $brand = $row['CPUBrand'];
        $count = intval( $row['count'] );
        
        if ( !array_key_exists( $brand, $brands_area_chart_data ) ) {
            $brands_area_chart_data[$brand] = array_fill( 0, count( $date_range_day ) - 1, 0 );
            $brands_pie_chart_data[$brand] = 0;
        }

        $brands_area_chart_data[$brand][$i] = $count;
        $brands_pie_chart_data[$brand] += $count;
    }
    
    $query = "SELECT u.CPUArch, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS count FROM `".$db->prefix."sessions` AS s, `".$db->prefix."uniqueusers` AS u ";
    $query .= "WHERE s.UniqueUserId = u.UniqueUserId ";
    $query .= "AND s.ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND s.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
    $query .= "AND s.StartApp BETWEEN '".$start."' AND '".$end."' ";
    $query .= "GROUP BY u.CPUArch";
    
    $db->execute_sql( $query );
    
    $rows = array();
    
    if ( $db->records == 1 )
        $rows[] = $db->array_result();
    else if ( $db->records > 1 )
        $rows = $db->array_results();
    
    foreach ( $rows as $row ) {
        $arch = $row['CPUArch'];
        $count = intval( $row['count'] );
        
        $arch_chart_data[$arch][$i] = $count;
        $arch_pie_chart_data[$arch] += $count;
    }
    
    $query = "SELECT u.CPUCores, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS count FROM `".$db->prefix."sessions` AS s, `".$db->prefix."uniqueusers` AS u ";
    $query .= "WHERE s.UniqueUserId = u.UniqueUserId ";
    $query .= "AND s.ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND s.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
    $query .= "AND s.StartApp BETWEEN '".$start."' AND '".$end."' ";
    $query .= "GROUP BY u.CPUCores";
    
    $db->execute_sql( $query );
    
    $rows = array();
    
    if ( $db->records == 1 )
        $rows[] = $db->array_result();
    else if ( $db->records > 1 )
        $rows = $db->array_results();
    
    foreach ( $rows as $row ) {
        $cores = $row['CPUCores'];
        $count = intval( $row['count'] );
        
        if ( !array_key_exists( $cores, $cores_chart_data ) ) {
            $cores_chart_data[$cores] = array_fill( 0, count( $date_range_day ) - 1, 0 );
            $cores_pie_chart_data[$cores] = 0;
        }
        
        $cores_chart_data[$cores][$i] = $count;
        $cores_pie_chart_data[$cores] += $count;
    }
}
?>
<script type="text/javascript">
    var chart_brands, pie_brands, chart_arch, pie_arch, chart_cores, pie_cores;
    $(document).ready(function() {
        <?php if ( count( $brands_area_chart_data ) > 0 ) : ?>
        chart_brands = new Highcharts.Chart({
            chart: {
                renderTo: 'chart_brands',
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
            series: <?php echo convert_area_chart_data_to_json( $brands_area_chart_data ); ?>
        });
        
        <?php endif; ?>
        <?php if ( count( $brands_pie_chart_data ) > 0 ) : ?>
        pie_brands = new Highcharts.Chart({
            chart: {
                renderTo: 'pie_brands',
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
                name: 'CPU Brands percentage',
                data: <?php echo convert_pie_chart_data_to_json( $brands_pie_chart_data ); ?>
            }]
        });
        <?php endif; ?>

        <?php if ( count( $arch_chart_data ) > 0 ) : ?>
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
        <?php if ( count( $arch_pie_chart_data ) > 0 ) : ?>
        pie_arch = new Highcharts.Chart({
            chart: {
                renderTo: 'pie_arch',
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
                name: 'CPU Architecture percentage',
                data: <?php echo convert_pie_chart_data_to_json( $arch_pie_chart_data ); ?>
            }]
        });
        <?php endif; ?>

        <?php if ( count( $cores_chart_data ) > 0 ) : ?>
        chart_cores = new Highcharts.Chart({
            chart: {
                renderTo: 'chart_cores',
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
            series: <?php echo convert_area_chart_data_to_json( $cores_chart_data ); ?>
        });
        
        <?php endif; ?>
        <?php if ( count( $cores_pie_chart_data ) > 0 ) : ?>
        pie_cores = new Highcharts.Chart({
            chart: {
                renderTo: 'pie_cores',
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
                name: 'CPU Cores percentage',
                data: <?php echo convert_pie_chart_data_to_json( $cores_pie_chart_data ); ?>
            }]
        });
        <?php endif; ?>
    });
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Brand' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox" id="graphs-1">
            <?php if ( count( $brands_area_chart_data ) > 0 ) : ?>
            <div id="chart_brands"></div>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>

        <div class="contentbox" id="graphs-2">
            <?php if ( count( $brands_pie_chart_data ) > 0 ) : ?>
            <div id="pie_brands"></div>
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
            <?php if ( count( $arch_pie_chart_data ) > 0 ) : ?>
            <div id="pie_arch"></div>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
        <!-- Graphs Box End -->
    </div>

    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Cores' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox" id="graphs-1">
            <?php if ( count( $cores_chart_data ) > 0 ) : ?>
            <div id="chart_cores"></div>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>

        <div class="contentbox" id="graphs-2">
            <?php if ( count( $cores_pie_chart_data ) > 0 ) : ?>
            <div id="pie_cores"></div>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
        <!-- Graphs Box End -->
    </div>
    
    <form action="#" class="right" style="padding-top: 15px">
        <strong><?php _e( 'Type: ' ); ?></strong>
        <select name="type">
            <option value="total"<?php echo ( ( $type == 'total' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Total' ); ?></option>
            <option value="unique"<?php echo ( ( $type == 'unique' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Unique' ); ?></option>
        </select>
        &nbsp;&nbsp;
        <input name="apply" class="form-submit right" type="submit" value="<?php _e( 'Apply' ); ?>" />
    </form>
</div>