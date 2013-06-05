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

$version_execs = array();
$version_installs = array();
$version_uninstalls = array();

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    // Executions
    $query = "SELECT ApplicationVersion, COUNT(*) AS 'total' ";
    $query .= "FROM `".$db->prefix."sessions` ";
    $query .= "WHERE ApplicationId = '".$app_id."' ";
    $query .= "AND StartApp BETWEEN '".$start."' AND '".$end."' ";
    $query .= "GROUP BY ApplicationVersion";
    
    $db->execute_sql( $query );

    $rows = array();

    if ( $db->records == 1 )
        $rows[] = $db->array_result();
    else if ( $db->records > 1 )
        $rows = $db->array_results();

    foreach ( $rows as $row ) {
        $version = 'v' . rtrim( $row['ApplicationVersion'], ".0" );
        $total = intval( $row['total'] );
        
        if ( $version == "v" )
            $version = __( "Unknown" );
        
        if ( !array_key_exists( $version, $version_execs ) )
            $version_execs[$version] = array_fill( 0, count( $date_range_day ) - 1, 0 );
        
        $version_execs[$version][$i] = $total;
    }
    
    ksort( $version_execs );
    
    // Installations
    $query = "SELECT ApplicationVersion, COUNT(*) AS 'total' ";
    $query .= "FROM `".$db->prefix."events` ";
    $query .= "WHERE ApplicationId = '".$app_id."' AND EventCode = 'ist' ";
    $query .= "AND UtcTimestamp BETWEEN '".$start."' AND '".$end."' ";
    $query .= "GROUP BY ApplicationVersion";
    
    $db->execute_sql( $query );

    $rows = array();

    if ( $db->records == 1 )
        $rows[] = $db->array_result();
    else if ( $db->records > 1 )
        $rows = $db->array_results();

    foreach ( $rows as $row ) {
        $version = 'v' . rtrim( $row['ApplicationVersion'], ".0" );
        $total = intval( $row['total'] );
        
        if ( $version == "v" )
            $version = __( "Unknown" );
        
        if ( !array_key_exists( $version, $version_installs ) )
            $version_installs[$version] = array_fill( 0, count( $date_range_day ) - 1, 0 );
        
        $version_installs[$version][$i] = $total;
    }
    
    ksort( $version_installs );
    
    // Uninstallations
    $query = "SELECT ApplicationVersion, COUNT(*) AS 'total' ";
    $query .= "FROM `".$db->prefix."events` ";
    $query .= "WHERE ApplicationId = '".$app_id."' AND EventCode = 'ust' ";
    $query .= "AND UtcTimestamp BETWEEN '".$start."' AND '".$end."' ";
    $query .= "GROUP BY ApplicationVersion";
    
    $db->execute_sql( $query );

    $rows = array();

    if ( $db->records == 1 )
        $rows[] = $db->array_result();
    else if ( $db->records > 1 )
        $rows = $db->array_results();

    foreach ( $rows as $row ) {
        $version = 'v' . rtrim( $row['ApplicationVersion'], ".0" );
        $total = intval( $row['total'] );
        
        if ( $version == "v" )
            $version = __( "Unknown" );
        
        if ( !array_key_exists( $version, $version_uninstalls ) )
            $version_uninstalls[$version] = array_fill( 0, count( $date_range_day ) - 1, 0 );
        
        $version_uninstalls[$version][$i] = $total;
    }
    
    ksort( $version_uninstalls );
}
?>
<script type="text/javascript">
var chart_execs, chart_installs, chart_uninstalls;
$(document).ready(function() {
    <?php if ( count ( $version_execs ) > 0 ) : ?>
        chart_execs = new Highcharts.Chart({
                chart: {
                        renderTo: 'execs_div',
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
                series: <?php echo convert_area_chart_data_to_json( $version_execs ); ?>
        });
        
        <?php endif; ?>
        <?php if ( count( $version_installs ) > 0 ) : ?>
        chart_installs = new Highcharts.Chart({
                chart: {
                        renderTo: 'installs_div',
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
                series: <?php echo convert_area_chart_data_to_json( $version_installs ); ?>
        });
        
        <?php endif; ?>
        <?php if ( count( $version_uninstalls ) > 0 ) : ?>
        chart_uninstalls = new Highcharts.Chart({
                chart: {
                        renderTo: 'uninstalls_div',
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
                series: <?php echo convert_area_chart_data_to_json( $version_uninstalls ); ?>
        });
        <?php endif; ?>
});
</script>
<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Executions' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowleft.jpg'); ?>"></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowright.jpg'); ?>"></th>
        </tr>
        <tr>
            <td id="tbl-border-left"></td>
            <td>
                <div id="content-table-inner">
                    <?php if ( count( $version_execs ) > 0 ) : ?>
                    <div id="execs_div"></div>
                    <?php else : ?>
                    <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
                    <?php endif; ?>
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

<br />

<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Installations' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowleft.jpg'); ?>"></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowright.jpg'); ?>"></th>
        </tr>
        <tr>
            <td id="tbl-border-left"></td>
            <td>
                <div id="content-table-inner">
                    <?php if ( count( $version_installs ) > 0 ) : ?>
                    <div id="installs_div"></div>
                    <?php else : ?>
                    <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
                    <?php endif; ?>
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

<br />

<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Uninstallations' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowleft.jpg'); ?>"></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowright.jpg'); ?>"></th>
        </tr>
        <tr>
            <td id="tbl-border-left"></td>
            <td>
                <div id="content-table-inner">
                    <?php if ( count( $version_uninstalls ) > 0 ) : ?>
                    <div id="uninstalls_div"></div>
                    <?php else : ?>
                    <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
                    <?php endif; ?>
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