<?php

// returns an array of all aircraft with id, tailnum, maintenance officer
function getAllAC() {

  global $wpdb;
  $p = $wpdb->prefix;

  $aircraft_list = $wpdb->get_results(
    "SELECT aircraft_id, tailnum, fk_maintenance_officer
      FROM {$p}airshare_aircraft
      ORDER BY tailnum ASC", ARRAY_A );

  return $aircraft_list;
}

// Returns an array of wp_user objects indexed by ID
function getFormerMembers() {

	global $wpdb;
  $p = $wpdb->prefix;
	$formermembers = [];

	$all_users_id = $wpdb->get_col(
		"SELECT ID
			FROM $wpdb->users
			ORDER BY user_registered ASC");

	foreach ( $all_users_id as $i_users_id ) :
		$user = get_userdata( $i_users_id );
		if ( empty($user->roles) ) {
			$formermembers[$i_users_id] = $user;
		}
	endforeach;

  return $formermembers;
}

// Returns an array of wp_user objects indexed by ID
function getMembers() {

	global $wpdb;
	$members = array();

	$all_users_id = $wpdb->get_col(
		"SELECT ID
			FROM $wpdb->users
			ORDER BY user_registered ASC");

	foreach ( $all_users_id as $i_users_id ) :
		$user = get_userdata( $i_users_id );
		if ( ! empty($user->roles) ) {
			$members[$i_users_id] = $user;
		}
	endforeach;

  return $members;
}

function getTailnum( $aircraft_id ) {

	global $wpdb;
  $p = $wpdb->prefix;

  $result = $wpdb->get_var( $wpdb->prepare(
    "SELECT tailnum
      FROM {$p}airshare_aircraft
      WHERE aircraft_id = %s", $aircraft_id));

  return $result;
}

// Check if a user has maintenance privs for an aircraft
// returns a boolean
function canModAC( $user, $acid ) {

  global $wpdb;
  $p = $wpdb->prefix;
  $retval = false;

  if (in_array('administrator', $user->roles)) {
    $retval = true;
  }else{
    $maintenance_officer = $wpdb->get_var( $wpdb-> prepare(
      "SELECT fk_maintenance_officer
        FROM {$p}airshare_aircraft
        WHERE aircraft_id = %s", $acid));
    if ($maintenance_officer == $user->ID) {
      $retval = true;
    }
  }

  return $retval;
}

// Get the current data for an aircraft by aircraft_id
// returns an array indexed by column name
function getACData( $acid ) {

  global $wpdb;
  $p = $wpdb->prefix;

  $result = $wpdb->get_row( $wpdb->prepare(
    "SELECT *
      FROM {$p}airshare_aircraft
      WHERE aircraft_id = %s", $acid), ARRAY_A );

  return $result;
}

function getMaintOfficer( $tailnum ) {

	global $wpdb;
  $p = $wpdb->prefix;

  $result = $wpdb->get_row( $wpdb->prepare(
    "SELECT ID, display_name, user_email
      FROM {$p}users
        INNER JOIN {$p}airshare_aircraft
          ON ID = fk_maintenance_officer
      WHERE tailnum = %s", $tailnum ), ARRAY_A );
  
	$officer = array( display_name => $result[display_name],
										user_email => $result[user_email] );

  return $officer;
}

function getACAvail( $tailnum ) {

	global $wpdb;
  $p = $wpdb->prefix;

	$result = $wpdb->get_var( $wpdb->prepare(
		"SELECT available
			FROM {$p}airshare_aircraft
			WHERE tailnum = %s", $tailnum ));

	return boolval( $result );
}

function getOilChangeDue( $tailnum ) {

	global $wpdb;
  $p = $wpdb->prefix;

  $result = $wpdb->get_var( $wpdb->prepare(
    "SELECT last_oil_change
      FROM {$p}airshare_aircraft
      WHERE tailnum = %s", $tailnum ));

  $dueTac = (float)$result + 50.00;

  return number_format($dueTac, 2, ".", "");
}

function getAnnualDue( $tailnum ) {

	global $wpdb;
  $p = $wpdb->prefix;

  $result = $wpdb->get_var( $wpdb->prepare(
    "SELECT last_annual
      FROM {$p}airshare_aircraft
      WHERE tailnum = %s", $tailnum ));

  $dueDate = addCalMonths($result, 12);

  return $dueDate;
}

function getAltimeterDue( $tailnum ) {

	global $wpdb;
  $p = $wpdb->prefix;

  $result = $wpdb->get_var( $wpdb->prepare(
    "SELECT last_altimeter_check
      FROM {$p}airshare_aircraft
      WHERE tailnum = %s", $tailnum ));

  $dueDate = addCalMonths($result, 24);

  return $dueDate;
}

function getEltCheckDue( $tailnum ) {

	global $wpdb;
  $p = $wpdb->prefix;

  $result = $wpdb->get_var( $wpdb->prepare(
    "SELECT last_elt_check
      FROM {$p}airshare_aircraft
      WHERE tailnum = %s", $tailnum ));

  $dueDate = addCalMonths($result, 12);

  return $dueDate;
}

function getEltBatteryDue( $tailnum ) {

	global $wpdb;
  $p = $wpdb->prefix;

  $result = $wpdb->get_var( $wpdb->prepare(
    "SELECT last_elt_battery
      FROM {$p}airshare_aircraft
      WHERE tailnum = %s", $tailnum ));

  $dueDate = addCalMonths($result, 60);

  return $dueDate;
}

function addCalMonths( $startDate, $months ) {

  $t = strtotime($startDate);
  $t2 = strtotime("+{$months} months", $t);
  $newDate = date("Y-m-t", $t2);

  return $newDate;
}

function getLogRows( $tailnum, $num ) {

	global $wpdb;
  $p = $wpdb->prefix;

  $result = $wpdb->get_var( $wpdb->prepare(
    "SELECT count(*)
      FROM {$p}airshare_uselog
        INNER JOIN {$p}airshare_aircraft
          ON aircraft_id = fk_aircraft_id
      WHERE tailnum = %s", $tailnum ));

  $calculated_offset = (int)$result - $num;
  $offset = max(0,$calculated_offset);

	$result = $wpdb->get_results( $wpdb->prepare(
    "SELECT uselogentry_id,
            display_name,
            date,
            start,
            stop,
            sum(stop)-sum(start) AS total,
            fuel_billed,
            fuel_paid,
            oil_start,
            oil_added,
            maintenance_flight
      FROM {$p}airshare_uselog
        INNER JOIN {$p}users
          ON ID = fk_ID
        INNER JOIN {$p}airshare_aircraft
          ON aircraft_id = fk_aircraft_id
      WHERE tailnum = %s
      GROUP BY  uselogentry_id,
                display_name,
                date,
                start,
                stop,
                fuel_billed,
                fuel_paid,
                oil_start,
                oil_added,
                maintenance_flight
      ORDER BY stop
      LIMIT %d offset %d", $tailnum, $num, $offset ), OBJECT );

	return $result;
}

function printLogRows( $tailnum, $num ) {

  $result = getLogRows( $tailnum, $num );

  foreach ( $result as $row ) {
    $bg = "";
    if ( (int)$row->maintenance_flight != 0 ) {
      $bg = " style=\"border-color: #4169E1; border-width: thick;\"";
    }
    $content .= "<div class=\"as_trow\"{$bg}>";
    $content .= " <div class=\"as_tcell\"{$bg}>" . $row->display_name . "</div>";
    $content .= " <div class=\"as_tcell\"{$bg}>" . $row->date . "</div>";
    $content .= " <div class=\"as_tcell\"{$bg}>" . $row->start . "</div>";
    $content .= " <div class=\"as_tcell\"{$bg}>" . $row->stop . "</div>";
    $content .= " <div class=\"as_tcell\"{$bg}>" . $row->total . "</div>";
    $content .= " <div class=\"as_tcell\"{$bg}>" . $row->fuel_billed . "</div>";
    $content .= " <div class=\"as_tcell\"{$bg}>" . $row->fuel_paid . "</div>";
    $content .= " <div class=\"as_tcell\"{$bg}>" . $row->oil_start . "</div>";
    $content .= " <div class=\"as_tcell\"{$bg}>" . $row->oil_added . "</div>";
    $content .= "</div>";
  }

    return $content;
}

function printLogHeader() {

  $content .= "<div class=\"as_theader\">";
  $content .= " <div class=\"as_tcell\">Pilot</div>";
  $content .= " <div class=\"as_tcell\">Date</div>";
  $content .= " <div class=\"as_tcell\">Tac<br>Start</div>";
  $content .= " <div class=\"as_tcell\">Tac<br>Stop</div>";
  $content .= " <div class=\"as_tcell\">Total</div>";
  $content .= " <div class=\"as_tcell\">Fuel Gal<br>Charged</div>";
  $content .= " <div class=\"as_tcell\">Fuel Gal<br>Paid</div>";
  $content .= " <div class=\"as_tcell\">Oil<br>Start</div>";
  $content .= " <div class=\"as_tcell\">Oil<br>Added</div>";
  $content .= "</div>";

  return $content;
}

function insertUselog( $logrow ) {

  global $wpdb;
  $p = $wpdb->prefix;

  $result = $wpdb->insert( "{$p}airshare_uselog", $logrow, array('%d','%s','%f','%f','%f','%f','%f','%f','%f', '%d') );

  return $result;
}

// Check if a user has maintenanve officer privsi
// returns a boolean
function isASMO( $user ) {

  global $wpdb;
  $p = $wpdb->prefix;
  $retval = false;

  if (in_array('administrator', $user->roles)) {
    $retval = true;
  }else{
    $maintenance_officers = $wpdb->get_col(
      "SELECT fk_maintenance_officer
        FROM {$p}airshare_aircraft");
    if (in_array($user->ID, $maintenance_officers)) {
      $retval = true;
    }
  }

  return $retval;
}

function getAircraft_ID ( $tailnum ) {

  global $wpdb;
  $p = $wpdb->prefix;

  $result = $wpdb->get_var( $wpdb->prepare(
    "SELECT aircraft_id
      FROM {$p}airshare_aircraft
      WHERE tailnum = %s", $tailnum ));

  return $result;
}

function getMaxTac( $tailnum ) {

  global $wpdb;
  $p = $wpdb->prefix;

  $result = $wpdb->get_var( $wpdb->prepare(
    "SELECT MAX(stop)
      FROM {$p}airshare_uselog
        INNER JOIN {$p}airshare_aircraft
          ON aircraft_id = fk_aircraft_id
      WHERE {$p}airshare_aircraft.tailnum = %s", $tailnum ));

  return number_format($result, 2, ".", "");
}

function is_tac( $val ) {
  return ( is_float( $val ) && $val >= 0.0 && $val <= 9999.0 );
}

function is_gal( $val ) {
  return ( is_float( $val ) && $val >= 0.0 && $val <= 99.0 );
}

function is_oil( $val ) {
  return ( is_float( $val ) && $val >= 0.0 && $val <= 9.0 );
}

function is_vdate( $val ) {
  $date = explode( "-", $val, 3 );
  return checkdate( $date[1], $date[2], $date[0] );
}

function is_tailnum( $val ) {
  return preg_match('/^[A-Z0-9-]{4,9}$/', $val);
}

function validate_acdata ( $acdata ) {
  if ( ! is_tailnum( $acdata['tailnum'] ) ) { return "ERROR: Invalid tailnum"; } 
  if ( ! is_int( $acdata['fk_maintenance_officer'] ) ) { return "ERROR: Invalid maintenance officer"; } 
  if ( ! is_vdate( $acdata['last_oil_change'] ) ) { return "ERROR: Invalid Last Oil Change"; } 
  if ( ! is_vdate( $acdata['last_annual'] ) ) { return "ERROR: Invalid Last Annual"; } 
  if ( ! is_vdate( $acdata['last_altimeter_check'] ) ) { return "ERROR: Invalid Last Altimeter Check"; } 
  if ( ! is_vdate( $acdata['last_elt_check'] ) ) { return "ERROR: Invalid Last ELT Check"; } 
  if ( ! is_int( $acdata['available'] ) ) { return "ERROR: Invalid Aircraft Availability"; } 
  if ( ! is_vdate( $acdata['last_elt_battery'] ) ) { return "ERROR: Invalid Last ELT Battery"; }
  return 0;
}

function validate_logrow( $logrow ) {
  if ( ! is_vdate( $logrow["date"] ) ) { return "ERROR: Invalid date"; }
  if ( ! is_tac( $logrow["start"] ) ) { return "ERROR: Invalid TAC start"; }
  if ( $logrow["start"] < $maxTac ) { return "ERROR: TAC start cannot be less than the last recorded stop"; }
  if ( ! is_tac( $logrow["stop"] ) ) { return "ERROR: Invalid TAC stop"; }
  if ( $logrow["stop"] <= $logrow["start"] ) { return "ERROR: TAC stop must be greater than TAC start"; }
  if ( ! is_gal( $logrow["fuel_billed"] ) ) { return "ERROR: Invalid Gal Charged"; }
  if ( ! is_gal( $logrow["fuel_paid"] ) ) { return "ERROR: Invalid Gal Paid"; }
  if ( ! is_oil( $logrow["oil_start"] ) ) { return "ERROR: Invalid Oil start"; }
  if ( ! is_oil( $logrow["oil_added"] ) ) { return "ERROR: Invalid Oil added"; }
  return 0;
}
?>
