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

$once = 0;
$twice = 0;
$three_to_five = 0;
$six_to_ten = 0;
$eleven_to_twenty = 0;
$twenty_one = 0;

$unique_users_total = $db->select_sessions( $app_id, $app_ver, $start_time, $end_time, 'UniqueUserId', true, true );

for ( $i = 0; $i < 7; $i++ ) {
    if ( $i == 0 ) {
        $having_query = "(COUNT(*)) = 1";
        $var = &$once;
    } elseif ( $i == 1 ) {
        $having_query = "(COUNT(*)) = 2";
        $var = &$twice;
    } elseif ( $i == 3 ) {
        $having_query = "(COUNT(*)) >= 3 AND (COUNT(*)) <= 5";
        $var = &$three_to_five;
    } elseif ( $i == 4 ) {
        $having_query = "(COUNT(*)) >= 6 AND (COUNT(*)) <= 10";
        $var = &$six_to_ten;
    } elseif ( $i == 5 ) {
        $having_query = "(COUNT(*)) >= 11 AND (COUNT(*)) <= 20";
        $var = &$eleven_to_twenty;
    } elseif ( $i == 6 ) {
        $having_query = "(COUNT(*)) > 21";
        $var = &$twenty_one;
    }
    
    $query = "SELECT COUNT(*) AS 'count' FROM (";
    $query .= "SELECT COUNT(*) AS total ";
    $query .= "FROM `".$db->prefix."sessions` ";
    $query .= "WHERE ApplicationId = '".$app_id."' " . ( ( $app_ver != "all" ) ? ( "AND s.ApplicationVersion = '".$app_ver."' " ) : ( "" ) );
    $query .= "AND StartApp BETWEEN ".$start_time." AND ".$end_time." ";
    $query .= "GROUP BY UniqueUserId ";
    $query .= "HAVING " . $having_query;
    $query .= ") AS t";

    $db->execute_sql( $query );

    $row = $db->array_result();
    $var = intval( $row['count'] );
}

// Calculate percents
$once_percent = calculate_percent( $once, $unique_users_total ) . '%';
$twice_percent = calculate_percent( $twice, $unique_users_total ) . '%';
$three_to_five_percent = calculate_percent( $three_to_five, $unique_users_total ) . '%';
$six_to_ten_percent = calculate_percent( $six_to_ten, $unique_users_total ) . '%';
$eleven_to_twenty_percent = calculate_percent( $eleven_to_twenty, $unique_users_total ) . '%';
$twenty_one_percent = calculate_percent( $twenty_one, $unique_users_total ) . '%';
?>
<script type="text/javascript">
var chart;
$(document).ready(function() {
   chart = new Highcharts.Chart({
      chart: {
         renderTo: 'chart_div',
         defaultSeriesType: 'column',
         height: 200
      },
      title: {
         text: '<?php echo __( 'Statistics for ' ) . date( "F j, Y", $start_time ) . ' ' . __( 'to' ) . ' ' . date( "F j, Y", $end_time ); ?>',
         x: -20 //center
      },
      xAxis: {
        categories: [
            '<?php _e ('1 time' ); ?>',
            '<?php _e ('2 times' ); ?>',
            '<?php _e ('3-5 times' ); ?>',
            '<?php _e ('6-10 times' ); ?>',
            '<?php _e ('11-20 times' ); ?>',
            '<?php _e ('21+ times' ); ?>',
         ]
      },
      yAxis: {
         title: ''
      },
      legend: {
         enabled: false
      },
      tooltip: {
         formatter: function() {
            return ''+
               this.x +': '+ this.y +' users';
         }
      },
      series: [{
         name: '<?php _e( 'Users' ); ?>',
         data: <?php echo json_encode( array( $once,$twice,$three_to_five,$six_to_ten,$eleven_to_twenty,$twenty_one ) ); ?>
      }]
   });
   
   
});
</script>

<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Loyalty Time' ); ?></h1>
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

<div class="clear">&nbsp;</div>
<div class="clear">&nbsp;</div>

<div class="contentcontainers">
    <div class="contentcontainer">
        <div class="headings altheading">
                <h2 class="left"><?php _e( 'Loyalty Time Chart Data' ); ?></h2>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox">
            <table width="100%">
                <thead>
                    <tr>
                        <th><?php _e( 'Executions' ); ?></th>
                        <th><?php _e( 'Number of Users' ); ?></th>
                        <th><?php _e( 'Percentage of Users' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e( '1 Time' ); ?></td>
                        <td><?php echo $once . ' ' . __( 'users' ); ?></td>
                        <td>
                            <div class="usagebox left" style="width: 85%">
                                <div class="lowbar" style="width:<?php echo $once_percent; ?>"></div>
                            </div>
                            <span class="right" style="padding: 8px"><?php echo $once_percent; ?></span>
                        </td>
                    </tr>
                    <tr class="alt">
                        <td><?php _e( '2 Times' ); ?></td>
                        <td><?php echo $twice . ' ' . __( 'users' ); ?></td>
                        <td>
                            <div class="usagebox left" style="width: 85%">
                                <div class="lowbar" style="width:<?php echo $twice_percent; ?>"></div>
                            </div>
                            <span class="right" style="padding: 8px"><?php echo $twice_percent; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e( '3 - 5 Times' ); ?></td>
                        <td><?php echo $three_to_five . ' ' . __( 'users' ); ?></td>
                        <td>
                            <div class="usagebox left" style="width: 85%">
                                <div class="lowbar" style="width:<?php echo $three_to_five_percent; ?>"></div>
                            </div>
                            <span class="right" style="padding: 8px"><?php echo $three_to_five_percent; ?></span>
                        </td>
                    </tr>
                    <tr class="alt">
                        <td><?php _e( '6 - 10 Times' ); ?></td>
                        <td><?php echo $six_to_ten . ' ' . __( 'users' ); ?></td>
                        <td>
                            <div class="usagebox left" style="width: 85%">
                                <div class="lowbar" style="width:<?php echo $six_to_ten_percent; ?>"></div>
                            </div>
                            <span class="right" style="padding: 8px"><?php echo $six_to_ten_percent; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e( '11 - 20 Times' ); ?></td>
                        <td><?php echo $eleven_to_twenty . ' ' . __( 'users' ); ?></td>
                        <td>
                            <div class="usagebox left" style="width: 85%">
                                <div class="lowbar" style="width:<?php echo $eleven_to_twenty_percent; ?>"></div>
                            </div>
                            <span class="right" style="padding: 8px"><?php echo $eleven_to_twenty_percent; ?></span>
                        </td>
                    </tr>
                    <tr class="alt">
                        <td><?php _e( '21+ Times' ); ?></td>
                        <td><?php echo $twenty_one . ' ' . __( 'users' ); ?></td>
                        <td>
                            <div class="usagebox left" style="width: 85%">
                                <div class="lowbar" style="width:<?php echo $twenty_one_percent; ?>"></div>
                            </div>
                            <span class="right" style="padding: 8px"><?php echo $twenty_one_percent; ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
	