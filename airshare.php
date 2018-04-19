<?php
/*
Plugin Name: Airshare - Airplane sharing management
Plugin URI: http://rfc.meinersovermatter.com
Description: Write something good.
Version: 0.1
Author: Judd Meinders
License: GPLv2+
Text Domain: airshare
*/

class Airshare{

// Constructor
function __construct() {

  register_activation_hook( __FILE__, array( $this, 'wpa_install' ) );
  register_deactivation_hook( __FILE__, array( $this, 'wpa_uninstall' ) );
  add_action( 'wp_enqueue_scripts', array( $this, 'wpa_styles') );
  add_action( 'wp_enqueue_scripts', array( $this, 'wpa_include') );
}

/*
 * Actions perform on activation of plugin
 */
function wpa_install() {



}

/*
 * Actions perform on de-activation of plugin
 */
function wpa_uninstall() {



}

/*
 * Styling: loading stylesheets for the plugin.
 */
function wpa_styles( $page ) {

  wp_enqueue_style( 'airshare-style', plugins_url('css/airshare-style.css', __FILE__));
}

function wpa_include( $page ) {
  
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

      $v_error = validate_logrow( $logrow );
      if ( $error == 0 ) {
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

//      $content .= "<div id=\"submitrow\">";

      $content .= "<p style=\"float: right;\">";
      $content .= "<input type=\"submit\" value=\"Submit\">";
      
      $content .= "<p style=\"float: right; margin-right: 60px;\">";
      $content .= "Maintenance Flight: ";
      $content .= "<label class=\"switch\" style=\"margin-left: 10px;\">";
      $content .= "<input type=\"checkbox\" name=\"nMaint\" value=\"1\">";
      $content .= "<span class=\"slider round\"></span>";
      $content .= "</label>";

  //    $content .= "</div>";

      $content .= "</form>";

    }
    return $content;
  }
}

new Airshare();
add_shortcode( 'AS_logentry', array( 'Airshare', 'AS_logentry_shortcode' ) );
add_shortcode( 'AS_acstatus', array( 'Airshare', 'AS_acstatus_shortcode' ) );

?>
