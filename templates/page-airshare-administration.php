<?php get_header(); ?>

<?php get_template_part( 'content', 'page' ); ?>

<?php
global $wpdb;
$p = $wpdb->prefix;

$user = wp_get_current_user();
$roles = $user->roles;
$page = $_POST['page'];

switch ($page) {
  // First page of form
  case NULL:
    // Guard against unauthorized use
    if (!isASMO($user)) {noAccess();break;}
?>
<center>
<h3>Select an aircraft to modify</h3>
<form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
<input type="hidden" name="page" value="1">
<select name="aircraft">

<?php
      //Only show option to add aircraft to site admins
    if (in_array('administrator', $roles)) {
?>
  <option value="newac">--New Aircraft--</option>
<?php
    }
?>
<?php
    $aircraft_list = getAllAC();
    print_r($aircraft_list);
    //Loop through all the aircraft in the database
    foreach ( $aircraft_list as $aircraft ) {
      //Only show aircraft of which the user is a maintenance officer
      if ( canModAC($user, $aircraft[aircraft_id]) ) {
?>
  <option value="<?php echo "{$aircraft[aircraft_id]}"; ?>"><?php echo "{$aircraft[tailnum]}"; ?></option>
<?php
      }
    }
?>

</select>
<br>
<br>
<input type="submit" value="Next">
</form>
</center>

<?php
    break;

  case 1:
    $acid = $_POST['aircraft'];
    // Guard against unauthorized use
    if (!canModAC($user, $acid)) {noAccess();break;}
    $acdata = getACData( $acid );
?>
<form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
<?php
    if ($acid == "newac") {
?>
<h3>Add New Aircraft</h3>
<input type="hidden" name="page" value="2">
<?php
    }else{
?>
<h3>Modify Aircraft</h3>
<input type="hidden" name="page" value="3">
<?php
    }

    $members = getMembers();
?>

Tail Number:
<input type="text" name="tail" value="<?php echo $acdata['tailnum']?>"><br>
Available:
<label class="switch" style="margin-left: 10px;">
  <input type="checkbox" name="isavailable" value="1" <?php if ($acdata['available'] == 1) {echo "checked";}?>>
    <span class="slider round"></span>
</label><br>
<?php
    if ($acid == "newac") {
?>
Starting TAC:
<input type="number" name="currenttac" min="0" step="0.1" value="<?php echo $acdata['tailnum']?>"><br>
<?php } ?>
TAC of last Oil Change:
<input type="number" name="last_oilchange" min="0" step="0.1" value="<?php echo $acdata['last_oil_change']?>"><br>
Date of last Annual:
<input type="date" name="last_annual" value="<?php echo $acdata['last_annual']?>"><br>
Date of last Altimeter Check:
<input type="date" name="last_altimeter" value="<?php echo $acdata['last_altimeter_check']?>"><br>
Date of last ELT Check:
<input type="date" name="last_eltcheck" value="<?php echo $acdata['last_elt_check']?>"><br>
Date of last ELT Battery Replacement:
<input type="date" name="last_eltbattery" value="<?php echo $acdata['last_elt_battery']?>"><br>
Maintenance Officer:
<select name="maintenance_officer">

<?php
    foreach ($members as $member):
?>

  <option value="<?php echo $member->ID;?>"<?php if ($acdata['fk_maintenance_officer'] == $member->ID) { echo " selected"; }?>><?php echo $member->display_name;?></option>

<?php
    endforeach;
?>
</select>
<br>
<input type="submit" value="Submit">
</form>
<?php
    break;

  case 2:
    // Guard against unauthorized use
    if (!in_array('administrator', $roles)) {noAccess();break;}
    // Validate input
    $aircraftrow = array(
      "tailnum" => $_POST['tail'],
      "fk_maintenance_officer" => (int)$_POST['maintenance_officer'],
      "last_oil_change" => (float)$_POST['last_oilchange'],
      "last_annual" => $_POST['last_annual'],
      "last_altimeter_check" => $_POST['last_altimeter'],
      "last_elt_check" => $_POST['last_eltcheck'],
      "available" => (int)$_POST['isavailable'],
      "last_elt_battery" => $_POST['last_eltbattery'] );

    $v_error = validate_acdata( $aircraftrow );
    if ( $v_error == 0 ) {
      if ( insertAircraft( $aircraftrow ) == 1 ) {
?>
<center><?php echo $_POST['tail'] . " ";?>has been successfully added.</center>
<?php      
      }else{
?>
<center>Unknown database error occured</center>
<?php
      }
    }else{
?>
<center><?php echo $v_error;?></center>
<?php
    }

    $initiallogrow = array(
      "fk_ID" => $user->ID,
      "date" => date("Y-m-d"),
      "start" => (float)$_POST['currenttac'],
      "stop" => (float)$_POST['currenttac'],
      "fuel_billed" => 0,
      "fuel_paid" => 0,
      "oil_start" => 0,
      "oil_added" => 0,
      "fk_aircraft_id" => getAircraft_ID($_POST['tail']),
      "maintenance_flight" => 1);

    insertUseLog($initiallogrow);
    break;

  case 3:
    // Guard against unauthorized use
    if (!canModAC($user, $acid)) {noAccess();break;}
    
    $aircraftrow = array(
      "aircraft_id" => getAircraft_ID($_POST['tail']),
      "tailnum" => $_POST['tail'],
      "fk_maintenance_officer" => (int)$_POST['maintenance_officer'],
      "last_oil_change" => (float)$_POST['last_oilchange'],
      "last_annual" => $_POST['last_annual'],
      "last_altimeter_check" => $_POST['last_altimeter'],
      "last_elt_check" => $_POST['last_eltcheck'],
      "available" => (int)$_POST['isavailable'],
      "last_elt_battery" => $_POST['last_eltbattery'] );

    $v_error = validate_acdata( $aircraftrow );
    if ( $v_error == 0 ) {
      if ( updateAircraft( $aircraftrow, $aircraftrow['aircraft_id'] ) == 1 ) {
?>
<center><?php echo $_POST['tail'];?> updated successfully.</center>
<?php
      }else{
?>
<center>An error occured while updating the aircraft</center>
<?php
      }
    }else{
?>
<center><?php echo $v_error ?></center>
<?php
    }
    break;

  default:
?>
<center>Something happened that should never happen.</center>
<?php
    break;
}

?>


<?php get_footer(); ?>

<?php
function noAccess() {
  echo "<center>You are not authorized to view this page</center>";
  return -1;
}
?>
