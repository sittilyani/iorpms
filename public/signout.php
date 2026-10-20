<?php
// Start the session to access session variables
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Back to the landing page ("?timeout=1" shows the inactivity notice there)
header("Location: ../index.php" . (isset($_GET['timeout']) ? '?timeout=1' : ''));
exit();
?>
