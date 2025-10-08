<?php
// Start the session to access session variables
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page or any other page after sign-out
header("Location: ../index.php");
exit();
?>
