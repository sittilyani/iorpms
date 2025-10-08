<?php
// Initialize the session
session_start();
 
// Unset all of the session variables
$_SESSION = array();
 
// Destroy the session.
session_destroy();
 
// Redirect to login page
echo '<meta content="1;index.php" http-equiv="refresh" />';
//header("location: login.php");
exit;
?>