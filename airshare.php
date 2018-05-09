<?php
/*
Plugin Name: Airshare - Airplane Sharing Management
Plugin URI: http://example.com
Description: Write something good.
Version: 0.1
Author: Judd Meinders
License: MIT
Text Domain: airshare
*/

global $airshare_db_version;
$airshare_db_version = '0.1';

class Airshare{

// Constructor
function __construct() {

  register_activation_hook( __FILE__, array( $this, 'airshare_install' ) );
  register_deactivation_hook( __FILE__, array( $this, 'airshare_uninstall' ) );
  add_action( 'wp_enqueue_scripts', array( $this, 'airshare_styles') );
  add_action( 'wp_enqueue_scripts', array( $this, 'airshare_includes') );
  add_action( 'plugins_loaded', array( $this, 'airshare_update_db_check') );
}

/*
 * Actions perform on activation of plugin
 */
function airshare_install() {
  
  global $wpdb;
  global $airshare_db_version;

  $aircraft_table = $wpdb->prefix . "airshare_aircraft";
  $uselog_table = $wpdb->prefix . "airshare_uselog";

  $charset_collate = $wpdb->get_charset_collate();

  $aircraft_table_sql = "CREATE TABLE `$aircraft_table` (
    `aircraft_id` int(11) NOT NULL AUTO_INCREMENT,
    `tailnum` varchar(8) NOT NULL,
    `fk_maintenance_officer` bigint(20) unsigned DEFAULT NULL,
    `last_oil_change` decimal(8,2) DEFAULT NULL,
    `last_annual` date DEFAULT NULL,
    `last_altimeter_check` date DEFAULT NULL,
    `last_elt_check` date DEFAULT NULL,
    `available` tinyint(4) NOT NULL DEFAULT '1',
    `last_elt_battery` date DEFAULT NULL,
    PRIMARY KEY (`aircraft_id`),
    UNIQUE KEY `tailnum_UNIQUE` (`tailnum`),
    UNIQUE KEY `id_UNIQUE` (`aircraft_id`),
    KEY `ID_idx` (`fk_maintenance_officer`),
    CONSTRAINT `ID` FOREIGN KEY (`fk_maintenance_officer`) REFERENCES `rfc_wp_users` (`ID`) ON DELETE SET NULL ON UPDATE CASCADE
    ) $charset_collate;";

  $uselog_table_sql = "CREATE TABLE `rfc_wp_airshare_uselog` (
    `uselogentry_id` int(11) NOT NULL AUTO_INCREMENT,
    `date` date NOT NULL,
    `fk_ID` bigint(20) unsigned NOT NULL,
    `start` decimal(8,2) unsigned NOT NULL,
    `stop` decimal(8,2) unsigned NOT NULL,
    `fuel_billed` decimal(8,2) unsigned NOT NULL DEFAULT '0.00',
    `fuel_paid` decimal(8,2) unsigned NOT NULL DEFAULT '0.00',
    `oil_start` decimal(2,1) unsigned DEFAULT NULL,
    `oil_added` decimal(2,1) unsigned DEFAULT '0.0',
    `fk_aircraft_id` int(11) unsigned NOT NULL,
    `maintenance_flight` tinyint(4) NOT NULL DEFAULT '0',
    PRIMARY KEY (`uselogentry_id`),
    UNIQUE KEY `uselogentry_id_UNIQUE` (`uselogentry_id`),
    KEY `ID_idx` (`fk_ID`),
    KEY `fk_aircraft_id_idx` (`fk_aircraft_id`),
    CONSTRAINT `fk_ID` FOREIGN KEY (`fk_ID`) REFERENCES `rfc_wp_users` (`ID`) ON DELETE NO ACTION ON UPDATE CASCADE
    ) $charset_collate;";

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $aircraft_table_sql );
  dbDelta( $uselog_table_sql );

  add_option( 'airshare_db_version', $airshare_db_version );
}

/*
 * Update the database schema if required
 */
function airshare_update_db_check( $page ){

  global $airshare_db_version;
  if ( get_site_option( 'airshare_db_version' ) != $airshare_db_version ) {
    airshare_install();
  }
}

/*
 * Actions perform on de-activation of plugin
 */
function airshare_uninstall() {

  //uninstall airshare-administration template
}

/*
 * Styling: loading stylesheets for the plugin.
 */
function airshare_styles( $page ) {

  wp_enqueue_style( 'airshare-style', plugins_url('css/airshare-style.css', __FILE__));
}

/*
 * Include: load helper functions for the plugin.
 */
function airshare_includes( $page ) {
  
  include_once( plugin_dir_path( __FILE__ ) . 'include/airshare-helpers.php'); 
}

public static function AS_acstatus_shortcode( $atts ) {

  $tail = $atts['tail'];
  $today = date("Y-m-d");
  $maxTac = number_format((float)getMaxTac( $tail ), 2, ".", "");
  $annualDue = getAnnualDue( $tail );
  $oilChangeDue = getOilChangeDue( $tail );
  $altimeterDue = getAltimeterDue( $tail );
  $eltCheckDue = getEltCheckDue( $tail );
  $eltBatteryDue = getEltBatteryDue( $tail );
  $maintOfficer = getMaintOfficer( $tail );
  
  if ( getACAvail($tail) ) {
    $availability = "<font color=\"GREEN\">AVAILABLE</font>";
  } else {
    $availability = "<font color=\"RED\">UNAVAILABLE</font>";
  } 

  $content = "<h3 style=\"text-align: center;\">{$tail}: ${availability}</h3>";
  $content .= "<div style=\"text-align: center;\">Maintenance Officer: " . $maintOfficer[display_name] . " (" . $maintOfficer[user_email] . ")</div>";
  $content .= "<div class=\"as_stat\">";
  $content .= "<div class=\"as_stat_row\">";
  $content .= "<div class=\"as_stat_cell\">";
  $content .= "Current TAC<br><b><span style=\"font-size: larger;\">" . $maxTac . "</span></b>";
  $content .= "</div>";  
  $content .= "<div class=\"as_stat_cell\">";
  $content .= "Oil Change Due<br><b><span style=\"font-size: larger;\">" . $oilChangeDue . "</span></b>";
  $content .= "</div>"; 
  $content .= "<div class=\"as_stat_cell\">";
  $content .= "Annual Due<br><b><span style=\"font-size: larger;\">" . $annualDue ."</span></b>";
  $content .= "</div>"; 
  $content .= "</div>"; 
  $content .= "<div class=\"as_stat_row\">";
  $content .= "<div class=\"as_stat_cell\">";
  $content .= "Altimeter Check Due<br><b><span style=\"font-size: larger;\">" . $altimeterDue . "</span></b>";
  $content .= "</div>"; 
  $content .= "<div class=\"as_stat_cell\">";
  $content .= "ELT Check Due<br><b><span style=\"font-size: larger;\">" . $eltCheckDue . "</span></b>";
  $content .= "</div>"; 
  $content .= "<div class=\"as_stat_cell\">";
  $content .= "ELT Battery Due<br><b><span style=\"font-size: larger;\">" . $eltBatteryDue . "</span></b>";
  $content .= "</div>";  
  $content .= "</div>";  
  $content .= "</div>";
  $content .= "<br>"; 
  return $content;
}

public static function AS_logentry_shortcode( $atts ) {

  $tail = $atts['tail'];
  $uid = get_current_user_id();
  $user_info = get_userdata( $uid );
  $uDisplayName = $user_info->display_name;
  $today = date("Y-m-d");
  $maxTac = number_format((float)getMaxTac( $tail ), 2, ".", "");
  $content = "<h3 style=\"text-align: center;\">{$tail} Use Log</h3>";

  if ( $_SERVER["REQUEST_METHOD"] == "POST" ) {

    $aircraft_id = getAircraft_ID( $tail );

    $logrow = array(
        "fk_ID" => $uid,
        "date" => $_POST["nDate"],
        "start" => (float)$_POST["nStart"],
        "stop" => (float)$_POST["nStop"],
        "fuel_billed" => (float)$_POST["nGalCharged"],
        "fuel_paid" => (float)$_POST["nGalPaid"],
        "oil_start" => (float)$_POST["nOilStart"],
        "oil_added" => (float)$_POST["nOilAdded"],
        "fk_aircraft_id" => $aircraft_id,
        "maintenance_flight" => (int)$_POST["nMaint"] );

    print_r($logrow);

    $v_error = validate_logrow( $logrow );
    if ( $v_error == 0 ) {
      if ( insertUselog( $logrow ) == 1 ) {
        $content .= "<div class=\"as_table\">";
        $content .= printLogHeader();
        $content .= printLogRows( $tail, 5 );
        $content .= "</div>";
      } else {
        return "ERROR: Database: Unable to insert.";
      }
    } else {
      return $v_error;
    }
  } else {

    $content .= "<form method=\"POST\" action=\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\">";
    $content .= "<div class=\"as_table\">";
    $content .= printLogHeader();
    $content .= printLogRows( $tail, 5 );

    $content .= "<div class=\"as_trow\">";
    $content .= "<div class=\"as_tcell\">{$uDisplayName}</div>";
    $content .= "<div class=\"as_tcell\"><input type=\"date\" name=\"nDate\" value=\"{$today}\" size=\"10\"required></div>";
    $content .= "<div class=\"as_tcell\"><input type=\"number\" name=\"nStart\" value=\"{$maxTac}\" min=\"0\" max=\"9999\" step=\"0.1\" size=\"6\"required></div>";
    $content .= "<div class=\"as_tcell\"><input type=\"number\" name=\"nStop\" min=\"0\" max=\"9999\" step=\"0.1\" size=\"6\"required></div>";
    $content .= "<div class=\"as_tcell\">N/A</div>";
    $content .= "<div class=\"as_tcell\"><input type=\"number\" name=\"nGalCharged\" value=\"0\" min=\"0\" max=\"99\" size=\"5\" required></div>";
    $content .= "<div class=\"as_tcell\"><input type=\"number\" name=\"nGalPaid\" value=\"0\" min=\"0\" max=\"99\" size=\"5\" required></div>";
    $content .= "<div class=\"as_tcell\"><input type=\"number\" name=\"nOilStart\" min=\"0\" max=\"8\" step=\"0.5\" size=\"3\"required></div>";
    $content .= "<div class=\"as_tcell\"><input type=\"number\" name=\"nOilAdded\" value=\"0\" min=\"0\" max=\"5\" step=\"0.5\" size=\"3\"required></div>";
    $content .= "</div>";

    $content .= "</div>";

    $content .= "<br>";

    $content .= "<p style=\"float: right;\">";
    $content .= "<input type=\"submit\" value=\"Submit\">";
      
    $content .= "<p style=\"float: right; margin-right: 60px;\">";
    $content .= "Maintenance Flight: ";
    $content .= "<label class=\"switch\" style=\"margin-left: 10px;\">";
    $content .= "<input type=\"checkbox\" name=\"nMaint\" value=\"1\">";
    $content .= "<span class=\"slider round\"></span>";
    $content .= "</label>";

    $content .= "</form>";
  }

  return $content;
} // AS_logentry_shortcode
} // Class Airshare

new Airshare();
add_shortcode( 'AS_logentry', array( 'Airshare', 'AS_logentry_shortcode' ) );
add_shortcode( 'AS_acstatus', array( 'Airshare', 'AS_acstatus_shortcode' ) );

?>
