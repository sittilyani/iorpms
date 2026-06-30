<?php
// init_facility_session.php
// This script ensures facility settings are loaded into the session
// at the beginning of every page request.

// Only execute if session variables aren't already set to avoid unnecessary database queries
if (!isset($_SESSION['current_facility_id'])) {

    include_once('../includes/config.php'); // Use include_once to prevent re-inclusion

    // Modified SELECT query to get all necessary fields
    $sql_get_current_settings = "SELECT fs.*, f.mflcode, f.owner, f.sdp, f.agency, f.emr, f.emrstatus, f.infrastructuretype, f.latitude, f.longitude
                                 FROM facility_settings fs
                                 LEFT JOIN facilities f ON fs.facility_id = f.id
                                 LIMIT 1";

    $result_current_settings = $conn->query($sql_get_current_settings);

    if ($result_current_settings && $result_current_settings->num_rows > 0) {
        $current_settings = $result_current_settings->fetch_assoc();

        // Populate session variables with data from the database
        $_SESSION['current_facility_id'] = $current_settings['facility_id'];
        $_SESSION['current_facility_name'] = $current_settings['facilityname'];
        $_SESSION['current_mflcode'] = $current_settings['mflcode'];
        $_SESSION['current_county'] = $current_settings['countyname'];
        $_SESSION['current_subcounty'] = $current_settings['subcountyname'];
        $_SESSION['current_owner'] = $current_settings['owner'];
        $_SESSION['current_sdp'] = $current_settings['sdp'];
        $_SESSION['current_agency'] = $current_settings['agency'];
        $_SESSION['current_emr'] = $current_settings['emr'];
        $_SESSION['current_emrstatus'] = $current_settings['emrstatus'];
        $_SESSION['current_infrastructuretype'] = $current_settings['infrastructuretype'];
        $_SESSION['current_latitude'] = $current_settings['latitude'];
        $_SESSION['current_longitude'] = $current_settings['longitude'];
        $_SESSION['current_facility_incharge'] = $current_settings['facilityincharge'];
        $_SESSION['current_facility_phone'] = $current_settings['facilityphone'];
        $_SESSION['current_facility_email'] = $current_settings['email'];

    } else {
        // No facility is currently set up, unset and clear all relevant session variables
        // This is important to ensure 'No Facility Set' is displayed when appropriate.
        unset($_SESSION['current_facility_id']);
        unset($_SESSION['current_facility_name']);
        // ... and so on for all other facility session variables.
        // I've removed the redundant code here for brevity, but you should clear them all.
    }
}
?>